<?php
defined('TYPO3_MODE') || die();

// Register Frontend plugin
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Causal.file_list',
    'Filelist',
    'LLL:EXT:file_list/Resources/Private/Language/locallang_flexform.xlf:filelist_title'
);

$pluginSignature = 'filelist_filelist';

// Disable the display of layout, select_key and page fields
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key,pages,recursive';

// Activate the display of the plugin FlexForm field and set FlexForm definition
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:file_list/Configuration/FlexForms/flexform_filelist.xml');
