<?php
defined('TYPO3_MODE') or die();

$settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY]);
t3lib_extMgm::addPItoST43($_EXTKEY, 'Classes/Controller/Pi1/class.tx_filelist_pi1.php', '_pi1', 'list_type', $settings['noCache'] ? 0 : 1);

if (version_compare(TYPO3_branch, '6.0', '>=')) {
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
		'Causal.' . $_EXTKEY,
		'Filelist',
		array(
			'File' => 'list',
		),
		// non-cacheable actions
		array(
		)
	);
}
