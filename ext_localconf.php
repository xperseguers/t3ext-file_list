<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY]);
t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_filelist_pi1.php', '_pi1', 'list_type', $settings['noCache'] ? 0 : 1);
