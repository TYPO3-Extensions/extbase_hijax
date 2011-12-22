<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][] = 'EXT:extbase_hijax/Classes/Tslib/FE/Hook.php:&Tx_ExtbaseHijax_Tslib_FE_Hook->contentPostProc';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = 'EXT:extbase_hijax/Classes/Tslib/FE/Hook.php:&Tx_ExtbaseHijax_Tslib_FE_Hook->contentPostProc';

/**
 * Configure the Plugin to call the
 * right combination of Controller and Action according to
 * the user input (default settings, FlexForm, URL etc.)
 */
Tx_Extbase_Utility_Extension::configurePlugin(
	$_EXTKEY,																	// The extension name (in UpperCamelCase) or the extension key (in lower_underscore)
	'Pi1',																		// A unique name of the plugin in UpperCamelCase
	array (		// An array holding the controller-action-combinations that are accessible
///		'Comments' => 'list',
	),
	array (
	)
);

$TYPO3_CONF_VARS['FE']['eID_include']['extbase_hijax_dispatcher'] = t3lib_extMgm::extPath($_EXTKEY).'Resources/Private/Eid/dispatcher.php';

?>