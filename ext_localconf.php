<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][] = 'EXT:extbase_hijax/Classes/Tslib/FE/Hook.php:&Tx_ExtbaseHijax_Tslib_FE_Hook->contentPostProc';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = 'EXT:extbase_hijax/Classes/Tslib/FE/Hook.php:&Tx_ExtbaseHijax_Tslib_FE_Hook->contentPostProc';

$TYPO3_CONF_VARS['FE']['eID_include']['extbase_hijax_dispatcher'] = t3lib_extMgm::extPath($_EXTKEY).'Resources/Private/Eid/dispatcher.php';

// Register cache for ed_extbase
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax'] = array();
}

if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax']['backend'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax']['backend'] = 't3lib_cache_backend_FileBackend';
}

?>