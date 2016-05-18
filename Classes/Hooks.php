<?php

namespace AOE\Newrelic;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class Hooks
{

    /**
     * Handles and dispatches the shutdown of the current process.
     *
     * @return void
     */
    public function frontendPreprocessRequest()
    {
        if (extension_loaded('newrelic')
            && isset($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_newrelic.'])
            && isset($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_newrelic.']['disableRum'])
            && $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_newrelic.']['disableRum']
        ) {
            newrelic_disable_autorum();
        }
        /** @var Service $service */
        $service = GeneralUtility::makeInstance(Service::class);
        $service->setConfiguredAppName();
        if ($service->getConfiguration('prepend_context')) {
            $service->setTransactionNameDefault('Frontend-Pre');
        }
    }

    /**
     * Handles and dispatches the shutdown of the current frontend process.
     *
     * @return void
     */
    public function frontendEndOfFrontend()
    {
        /** @var Service $service */
        $service = GeneralUtility::makeInstance(Service::class);

        if ($temp_extId = GeneralUtility::_GP('eID')) {
            $service->setTransactionNameOverride('eId_' . $temp_extId);
        }
        if ($service->getConfiguration('prepend_context')) {
            $service->setTransactionName('Frontend');
        }
        $service->addMemoryUsageCustomMetric();
        $service->addTslibFeCustomParameters();
        if (extension_loaded('newrelic')
            && (!isset($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_newrelic.'])
                || !isset($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_newrelic.']['disableRum'])
                || !$GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_newrelic.']['disableRum']
            )
        ) {
            newrelic_disable_autorum();
            $GLOBALS['TSFE']->additionalHeaderData['tx_newrelic'] = newrelic_get_browser_timing_header();
            $GLOBALS['TSFE']->additionalFooterData['tx_newrelic'] = newrelic_get_browser_timing_footer();
        }
    }

}
