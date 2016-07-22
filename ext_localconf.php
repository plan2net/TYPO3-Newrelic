<?php
defined('TYPO3_MODE') || die('access denied');

/** @var \AOE\Newrelic\Service $service */
$service = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\AOE\Newrelic\Service::class);
if ($service->getConfiguration('track_page_cache_info')) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_pages']['frontend'] = '\AOE\Newrelic\Cache\Frontend\VariableFrontend';
}
$service->setConfiguredAppName();
if ($service->getConfiguration('prepend_context')) {
    $service->setTransactionNameDefault('Base');
}
$service->addCommonRequestParameters();

if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] === 'CLI Mode') {
    $service->addTransactionNamePostfix('DirectRequest');
}

if (isset($_SERVER['HTTP_X_T3CRAWLER'])) {
    $service->addTransactionNamePostfix('Crawler');
}

if (defined('TYPO3_cliMode') && TYPO3_cliMode && $service->getConfiguration('prepend_context')) {
    $service->setTransactionName('CliMode');
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($service->getConfiguration('prepend_context')) {
        $service->addTransactionNamePostfix('FORMSUBMIT');
    } else {
        $service->setTransactionName('FORMSUBMIT');
    }
}
if (TYPO3_MODE === 'BE') {
    /** @var \AOE\Newrelic\Service $service */
    $service = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('AOE\Newrelic\Service');
    $service->setConfiguredAppName();
    if ($service->getConfiguration('prepend_context')) {
        $service->setTransactionName('Backend');
    }
}
if (TYPO3_MODE === 'FE') {
    $TYPO3_CONF_VARS['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest']['newrelic'] = \AOE\Newrelic\Hooks::class . '->frontendPreprocessRequest';
    $TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']['newrelic'] = \AOE\Newrelic\Hooks::class . '->frontendEndOfFrontend';
}
$service->checkAndDisableAutoRum();
