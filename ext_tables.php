<?php
defined('TYPO3_MODE') or die();

// Disable the display of layout, select_key and page fields
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_pi1'] = 'layout,select_key,pages';

// Activate the display of the plug-in flexform field and set FlexForm definition
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_pi1'] = 'pi_flexform';
if (!t3lib_extMgm::isLoaded('rgfolderselector')) {
	t3lib_extMgm::addPiFlexFormValue($_EXTKEY . '_pi1', 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/flexform_pi1.xml');
} else {
	t3lib_extMgm::addPiFlexFormValue($_EXTKEY . '_pi1', 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/flexform_pi1_rgfolderselector.xml');
}

t3lib_extMgm::addPlugin(array('LLL:EXT:file_list/Resources/Private/Language/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY . '_pi1'), 'list_type');

if (version_compare(TYPO3_branch, '6.0', '>=')) {
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
		'Causal.' . $_EXTKEY,
		'Filelist',
		'File List - List of files'
	);

	$extensionName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($_EXTKEY);
	$pluginSignature = strtolower($extensionName) . '_filelist';
	$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/flexform_filelist.xml');
}

t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'File List');
