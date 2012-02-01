<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][] = 'EXT:extbase_hijax/Classes/Tslib/FE/Hook.php:&Tx_ExtbaseHijax_Tslib_FE_Hook->contentPostProcAll';
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = 'EXT:extbase_hijax/Classes/Tslib/FE/Hook.php:&Tx_ExtbaseHijax_Tslib_FE_Hook->contentPostProcOutput';

$TYPO3_CONF_VARS['FE']['eID_include']['extbase_hijax_dispatcher'] = t3lib_extMgm::extPath($_EXTKEY).'Resources/Private/Eid/dispatcher.php';

if (!$TYPO3_CONF_VARS['SYS']['extbase_hijax']['lockingMode']) {
	$TYPO3_CONF_VARS['SYS']['extbase_hijax']['lockingMode'] = 'flock';
}

// Register cache for ed_extbase
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax'] = array();
}

if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax']['backend'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax']['backend'] = 't3lib_cache_backend_FileBackend';
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] =
	'EXT:extbase_hijax/Classes/TCEmain/Hooks.php:&Tx_ExtbaseHijax_TCEmain_Hooks->clearCachePostProc';
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 
	'EXT:extbase_hijax/Classes/TCEmain/Hooks.php:Tx_ExtbaseHijax_TCEmain_Hooks';
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] =
	'EXT:extbase_hijax/Classes/TCEmain/Hooks.php:Tx_ExtbaseHijax_TCEmain_Hooks';

$objectContainer = t3lib_div::makeInstance('Tx_Extbase_Object_Container_Container');
$objectContainer->registerImplementation('Tx_Extbase_MVC_Dispatcher', 'Tx_ExtbaseHijax_MVC_Dispatcher');

?>