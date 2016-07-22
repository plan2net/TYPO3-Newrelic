<?php
namespace AOE\Newrelic;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Class Service
 * Usage:
 *  $service = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('AOE\Newrelic\Service');
 *  $service->setTransactionName('Product Single View');
 * @package AOE\Newrelic
 */
class Service implements SingletonInterface
{
    const CACHE_INFO_UNKNOWN = -1;
    const CACHE_INFO_UNCACHED = 0;
    const CACHE_INFO_CACHED = 1;

    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var string
     */
    protected $transactionNameDefault;

    /**
     * @var string
     */
    protected $transactionName;

    /**
     * @var string
     */
    protected $transactionNameOverride;

    /**
     * @var string
     */
    protected $transactionNamePostfix = '';

    /**
     * @var string
     */
    protected $cacheInfo = self::CACHE_INFO_UNKNOWN;

    /**
     * Cosntructor
     */
    public function __construct()
    {
        $this->configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['newrelic']);
    }

    /**
     * sets the configured app name to newrelic
     */
    public function setConfiguredAppName()
    {
        if (!extension_loaded('newrelic')) {
            return;
        }
        $postfix = ' (Frontend)';
        $configurationName = 'appnameFrontend';
        if (defined('TYPO3_MODE') && TYPO3_MODE == 'BE') {
            $postfix = ' (Backend)';
            $configurationName = 'appnameBackend';
        }
        if (defined('TYPO3_cliMode') && TYPO3_cliMode) {
            $postfix = ' (CLI)';
            $configurationName = 'appnameCli';
        }
        $name = "TYPO3 Portal" . $postfix;
        if (isset($_SERVER["HTTP_HOST"]) && !empty($_SERVER["HTTP_HOST"])) {
            $name = $_SERVER["HTTP_HOST"] . $postfix;
        }
        // Try to use app name setting for current context
        if (isset($this->configuration[$configurationName]) && !empty($this->configuration[$configurationName])) {
            $name = $this->configuration[$configurationName];
            // As last resort, try to get app name setting for frontend context
        } elseif (isset($this->configuration[$configurationName]) && !empty($this->configuration[$configurationName])) {
            $name = $this->configuration['appnameFrontend'];
        }
        newrelic_set_appname($name);
    }

    /**
     * @param string $categoryPostfix
     */
    public function addMemoryUsageCustomMetric($categoryPostfix = '')
    {
        if (!function_exists('memory_get_usage')) {
            return;
        }
        if (!isset($this->configuration['track_memory']) || !$this->configuration['track_memory']) {
            return;
        }
        $memoryUsage = memory_get_usage(true);
        $this->setCustomMetric('MemoryUsage' . $categoryPostfix, 'RealSize', $memoryUsage);
        $this->setCustomMetric('MemoryUsage' . $categoryPostfix . $this->transactionNamePostfix, 'RealSize',
            $memoryUsage);
        $this->setCustomParameter("MemoryUsage-RealSize", $memoryUsage);
        $this->setCustomParameter("MemoryUsage-Size", memory_get_usage());
        if (!function_exists('memory_get_peak_usage')) {
            return;
        }
        $memoryUsage = memory_get_peak_usage(true);
        $this->setCustomMetric('MemoryUsage' . $categoryPostfix, 'RealPeakSize', $memoryUsage);
        $this->setCustomMetric('MemoryUsage' . $categoryPostfix . $this->transactionNamePostfix, 'RealPeakSize',
            $memoryUsage);
        $this->setCustomParameter("MemoryUsage-RealPeakSize", $memoryUsage);
        $this->setCustomParameter("MemoryUsage-PeakSize", memory_get_peak_usage());
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getConfiguration($key)
    {
        if (isset($this->configuration[$key])) {
            return $this->configuration[$key];
        }
        return null;
    }

    /**
     * @return string
     */
    protected function getRootline()
    {
        if (!isset($GLOBALS['TSFE'])) {
            return '';
        }

        $rootlineTitles = array();

        foreach ($GLOBALS['TSFE']->rootLine as $level) {
            $rootlineTitles[] = $level['title'];
        }

        return implode('/', array_reverse($rootlineTitles));
    }

    protected function getTld()
    {
        return array_pop(explode('.', $_SERVER['HTTP_HOST']));
    }

    protected function getRequestPath()
    {
        return current(explode('?', $_SERVER['REQUEST_URI']));
    }


    /**
     * adds some flags based from TSFE object
     */
    public function addTslibFeCustomParameters()
    {
        if (!isset($GLOBALS['TSFE'])) {
            return;
        }

        if (isset($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_newrelic.'])
            && isset($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_newrelic.']['transactionName'])
            && $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_newrelic.']['transactionName']
        ) {
            if ($this->getConfiguration('prepend_context')) {
                $this->addTransactionNamePostfix($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_newrelic.']['transactionName']);
            } else {
                $this->setTransactionName($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_newrelic.']['transactionName']);
            }
        } elseif ($this->getConfiguration('use_request_path')) {
            if ($this->getConfiguration('prepend_context')) {
                $this->addTransactionNamePostfix($this->getRequestPath());
            } else {
                $this->setTransactionName($this->getRequestPath());
            }
        } else {
            if ($this->getConfiguration('prepend_context')) {
                $this->addTransactionNamePostfix('Unnamed');
            } else {
                $this->setTransactionName('Unnamed');
            }
        }

        /** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $tsfe */
        $tsfe = $GLOBALS['TSFE'];
        switch ($this->cacheInfo) {
            case self::CACHE_INFO_CACHED:
                $this->setCustomParameter("TYPO3-CACHED", 1);
                $this->addTransactionNamePostfix('CACHED');
                break;
            case self::CACHE_INFO_UNCACHED:
                $this->setCustomParameter("TYPO3-UNCACHED", 1);
                $this->addTransactionNamePostfix('UNCACHED');
                break;
            default:
                if (!$tsfe->no_cache) {
                    $this->setCustomParameter("TYPO3-CACHEUNKNOWN", 1);
                    $this->addTransactionNamePostfix('CACHEUNKNOWN');
                }
        }
        if ($tsfe->no_cache) {
            $this->setCustomParameter("TYPO3-NOCACHE", 1);
            $this->addTransactionNamePostfix('NOCACHE');
        }
        if ($tsfe->isINTincScript()) {
            $this->setCustomParameter("TYPO3-INTincScript", 1);
            $this->addTransactionNamePostfix('INTincScript');
        }
        if ($tsfe->isClientCachable) {
            $this->setCustomParameter("TYPO3-ClientCacheable", 1);
            $this->addTransactionNamePostfix('ClientCacheable');
        }
        if (isset($tsfe->pageCacheTags) && is_array($tsfe->pageCacheTags)) {
            $this->setCustomParameter('X-CacheTags', implode('|', $tsfe->pageCacheTags) . '|');
        }
        /** @var FrontendUserAuthentication $frontEndUser */
        $frontEndUser = $GLOBALS['TSFE']->fe_user;
        if ($this->isFrontendUserActive($frontEndUser)) {
            $this->setCustomParameter('FrontendUser', 'yes');
            $this->addTransactionNamePostfix('FrontendUser');
        } else {
            $this->setCustomParameter('FrontendUser', 'no');
        }

    }

    /**
     * @param FrontendUserAuthentication $frontendUser
     * @return bool
     */
    protected function isFrontendUserActive(FrontendUserAuthentication $frontendUser = null)
    {
        if (!$frontendUser instanceof FrontendUserAuthentication) {
            return false;
        }
        if (isset($frontendUser->user['uid']) && $frontendUser->user['uid']) {
            return true;
        }

        return false;
    }

    /**
     * adds some env variables
     */
    public function addCommonRequestParameters()
    {
        $this->setCustomParameter("REQUEST_URI", GeneralUtility::getIndpEnv('REQUEST_URI'));
        $this->setCustomParameter("REMOTE_ADDR", GeneralUtility::getIndpEnv('REMOTE_ADDR'));
        $this->setCustomParameter("HTTP_USER_AGENT", GeneralUtility::getIndpEnv('HTTP_USER_AGENT'));
        $this->setCustomParameter("SCRIPT_FILENAME", GeneralUtility::getIndpEnv('SCRIPT_FILENAME'));
        $this->setCustomParameter("TYPO3_SSL", GeneralUtility::getIndpEnv('TYPO3_SSL'));
    }


    /**
     * @param $key
     * @param $value
     */
    public function setCustomParameter($key, $value)
    {
        if (!extension_loaded('newrelic')) {
            return;
        }
        newrelic_add_custom_parameter($key, $value);
    }

    /**
     * @param $category
     * @param $key
     * @param $value
     */
    public function setCustomMetric($category, $key, $value)
    {
        if (!extension_loaded('newrelic')) {
            return;
        }
        newrelic_custom_metric("Custom/" . $category . "/" . $key, $value);
    }

    /**
     * sets the configured transaction name to newrelic
     * @param $name
     */
    public function setTransactionNameDefault($name)
    {
        $this->transactionNameDefault = $name;
        $this->setNewrelicTransactionName();
    }

    /**
     * sets the configured transaction name to newrelic
     *
     * @param $name
     */
    public function setTransactionName($name)
    {
        $this->transactionName = $name;
        $this->setNewrelicTransactionName();
    }

    /**
     * sets the configured transaction name to newrelic
     *
     * @param $name
     */
    public function setTransactionNameOverride($name)
    {
        $this->transactionNameOverride = $name;
        $this->setNewrelicTransactionName();
    }

    public function setCacheInfo($cacheInfo)
    {
        $this->cacheInfo = $cacheInfo;
    }

    public function checkAndDisableAutoRum() {
        if (!extension_loaded('newrelic')) {
            return;
        }
        $disable = false;
        if (defined('TYPO3_MODE') && TYPO3_MODE == 'BE' && $this->getConfiguration('disableRumBackend')) {
            $disable = true;
        } elseif (defined('TYPO3_cliMode') && TYPO3_cliMode) {
            $disable = true;
        } elseif (defined('TYPO3_MODE') && TYPO3_MODE == 'FE' && $this->getConfiguration('disableRumFrontend')) {
            $disable = true;
        }
        if ($disable) {
            newrelic_disable_autorum();
        }
    }

    public function addTransactionNamePostfix($name)
    {
        // Don't add empty or duplicate name
        if (!$name || strstr($this->transactionNamePostfix, $name)) {
            return;
        }
        $this->transactionNamePostfix .= '/' . $name;
        $this->setNewrelicTransactionName();
    }

    /**
     * @return void
     */
    protected function setNewrelicTransactionName()
    {
        if (!extension_loaded('newrelic')) {
            return;
        }
        $name = '';
        if (isset($this->transactionNameDefault)) {
            $name = $this->transactionNameDefault;
        }
        if (isset($this->transactionName)) {
            $name = $this->transactionName;
        }
        if (isset($this->transactionNameOverride)) {
            $name = $this->transactionNameOverride;
        }
        if (isset($this->transactionNamePostfix)) {
            $name .= $this->transactionNamePostfix;
        }
        if ($name) {
            $name .= $this->transactionNamePostfix;
            newrelic_name_transaction($name);
        }
    }
}
