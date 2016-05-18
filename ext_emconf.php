<?php

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Newrelic Tracking',
	'description' => 'Allows to configure appname and sets specific transaction names for Newrelic by using the PHP API: https://docs.newrelic.com/docs/php/the-php-api',
	'category' => '',
	'author' => 'Markus Klein',
	'author_email' => 'markus.klein@reelworx.at',
	'state' => 'stable',
	'uploadfolder' => false,
	'createDirs' => '',
	'clearCacheOnLoad' => true,
	'author_company' => 'Reelworx GmbH',
	'version' => '1.0.2',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.2.0-7.99.99',
			'php' => '5.5.0-7.99.99'
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);
