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
        /** @var Service $service */
        $service = GeneralUtility::makeInstance(Service::class);
        $service->setConfiguredAppName();
        $service->setTransactionNameDefault('Frontend-Pre');
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
        $service->setTransactionName('Frontend');
        $service->addMemoryUsageCustomMetric();
        $service->addTslibFeCustomParameters();
    }

}
