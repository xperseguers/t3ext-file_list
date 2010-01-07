<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_filelist_pi1.php', '_pi1', 'list_type', 0);

// Hook to allow indexed search to work
if (t3lib_extMgm::isLoaded('indexed_search')) {
	$indexedSearchConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search']);
	if (!$indexedSearchConfig['disableFrontendIndexing']) {
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['file_list']['extraItemMarkerHook'][] = 'EXT:file_list/Classes/Hooks/class.user_tx_filelist_indexedsearch.php:user_tx_filelist_indexedsearch';
	}
}
?>