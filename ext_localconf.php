<?php
defined('TYPO3_MODE') || die('access denied');

/** @var \AOE\Newrelic\Service $service */
$service = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\AOE\Newrelic\Service::class);
$service->setConfiguredAppName();
$service->setTransactionNameDefault('Base');
$service->addCommonRequestParameters();

if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] === 'CLI Mode') {
    $service->addTransactionNamePostfix('DirectRequest');
}

if (isset($_SERVER['HTTP_X_T3CRAWLER'])) {
    $service->addTransactionNamePostfix('Crawler');
}

if (defined('TYPO3_cliMode') && TYPO3_cliMode) {
    $service->setTransactionName('CliMode');
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $service->addTransactionNamePostfix('FORMSUBMIT');
}

if (TYPO3_MODE === 'BE') {
    /** @var \AOE\Newrelic\Service $service */
    $service = t3lib_div::makeInstance('\AOE\Newrelic\Service');
    $service->setConfiguredAppName();
    $service->setTransactionName('Backend');
 }

if (TYPO3_MODE === 'FE') {
    $TYPO3_CONF_VARS['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest']['newrelic'] = \AOE\Newrelic\Hooks::class . '->frontendPreprocessRequest';
    $TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']['newrelic'] = \AOE\Newrelic\Hooks::class . '->frontendEndOfFrontend';
}
