<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][] = 'EXT:extbase_hijax/Classes/Tslib/FE/Hook.php:&Tx_ExtbaseHijax_Tslib_FE_Hook->contentPostProcAll';
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = 'EXT:extbase_hijax/Classes/Tslib/FE/Hook.php:&Tx_ExtbaseHijax_Tslib_FE_Hook->contentPostProcOutput';
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser'][] = 'EXT:extbase_hijax/Classes/Tslib/FE/Hook.php:&Tx_ExtbaseHijax_Tslib_FE_Hook->initFEuser';

Tx_Extbase_Utility_Extension::configurePlugin(
	$_EXTKEY,
	'Pi1',
	array(
		'ContentElement' => 'user,userInt',
	),
	// non-cacheable actions
	array(
		'ContentElement' => 'userInt',
	)
);

$TYPO3_CONF_VARS['FE']['eID_include']['extbase_hijax_dispatcher'] = t3lib_extMgm::extPath($_EXTKEY).'Resources/Private/Eid/dispatcher.php';
$TYPO3_CONF_VARS['FE']['eID_include']['extbase_hijax_thumb'] = t3lib_extMgm::extPath($_EXTKEY).'Resources/Private/Eid/thumb.php';

if (!$TYPO3_CONF_VARS['SYS']['extbase_hijax']['lockingMode']) {
	$TYPO3_CONF_VARS['SYS']['extbase_hijax']['lockingMode'] = 'flock';
}

	// Register cache for extbase_hijax
	// Tracking
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax_tracking'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax_tracking'] = array();
}

if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax_tracking']['backend'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax_tracking']['backend'] = 't3lib_cache_backend_FileBackend';
}

// Settings/serialized storage
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax_storage'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax_storage'] = array();
}

if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax_storage']['backend'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax_storage']['backend'] = 't3lib_cache_backend_FileBackend';
}

// Settings/serialized storage
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax_img_storage'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax_img_storage'] = array();
}

if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax_img_storage']['backend'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_hijax_img_storage']['backend'] = 't3lib_cache_backend_FileBackend';
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] =
	'EXT:extbase_hijax/Classes/TCEmain/Hooks.php:&Tx_ExtbaseHijax_TCEmain_Hooks->clearCachePostProc';
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 
	'EXT:extbase_hijax/Classes/TCEmain/Hooks.php:Tx_ExtbaseHijax_TCEmain_Hooks';
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] =
	'EXT:extbase_hijax/Classes/TCEmain/Hooks.php:Tx_ExtbaseHijax_TCEmain_Hooks';

if (version_compare(TYPO3_version,'6.0.0','<')) {
	$extbaseObjectContainer = t3lib_div::makeInstance('Tx_Extbase_Object_Container_Container'); // Singleton
	$extbaseObjectContainer->registerImplementation('Tx_Extbase_MVC_Dispatcher', 'Tx_ExtbaseHijax_MVC_Dispatcher');
	$extbaseObjectContainer->registerImplementation('Tx_Extbase_Persistence_Storage_BackendInterface', 'Tx_ExtbaseHijax_Persistence_Storage_Typo3DbBackend');
	$extbaseObjectContainer->registerImplementation('Tx_Extbase_Persistence_BackendInterface', 'Tx_ExtbaseHijax_Persistence_Backend');
	$extbaseObjectContainer->registerImplementation('Tx_Extbase_Persistence_QueryInterface', 'Tx_ExtbaseHijax_Persistence_Query');
	unset($extbaseObjectContainer);
} else {
	/** @var $extbaseObjectContainer \TYPO3\CMS\Extbase\Object\Container\Container */
	$extbaseObjectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\Container\\Container');
	$extbaseObjectContainer->registerImplementation('Tx_Extbase_MVC_Dispatcher', 'Tx_ExtbaseHijax_MVC_Dispatcher');
	$extbaseObjectContainer->registerImplementation('TYPO3\\CMS\\Extbase\\Mvc\\Dispatcher', 'Tx_ExtbaseHijax_MVC_Dispatcher');
	$extbaseObjectContainer->registerImplementation('Tx_Extbase_Persistence_Storage_BackendInterface', 'Tx_ExtbaseHijax_Persistence_Storage_Typo3DbBackend');
	$extbaseObjectContainer->registerImplementation('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\BackendInterface', 'Tx_ExtbaseHijax_Persistence_Storage_Typo3DbBackend');
	$extbaseObjectContainer->registerImplementation('Tx_Extbase_Persistence_BackendInterface', 'Tx_ExtbaseHijax_Persistence_Backend');
	$extbaseObjectContainer->registerImplementation('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\BackendInterface', 'Tx_ExtbaseHijax_Persistence_Backend');
	$extbaseObjectContainer->registerImplementation('Tx_Extbase_Persistence_QueryInterface', 'Tx_ExtbaseHijax_Persistence_Query');
	$extbaseObjectContainer->registerImplementation('TYPO3\\CMS\\Extbase\\Persistence\\QueryInterface', 'Tx_ExtbaseHijax_Persistence_Query');
	unset($extbaseObjectContainer);
}

?>