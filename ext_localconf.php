<?php
defined('TYPO3_MODE') || die();

$settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY]);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Controller/Pi1/Pi1Controller.php', '_pi1', 'list_type', $settings['noCache'] ? 0 : 1);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Causal.' . $_EXTKEY,
    'Filelist',
    array(
        'File' => 'list',
    ),
    // non-cacheable actions
    []
);

// Register default template layouts
$GLOBALS['TYPO3_CONF_VARS']['EXT']['file_list']['templateLayouts'][] = [
    'LLL:EXT:file_list/Resources/Private/Language/locallang_flexform.xlf:filelist.templateLayout.simple',
    'Simple'
];
$GLOBALS['TYPO3_CONF_VARS']['EXT']['file_list']['templateLayouts'][] = [
    'LLL:EXT:file_list/Resources/Private/Language/locallang_flexform.xlf:filelist.templateLayout.thumbnailDescription',
    'ThumbnailDescription'
];