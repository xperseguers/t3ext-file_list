<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

// Disable the display of layout, select_key and page fields
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_pi1'] = 'layout,select_key,pages';

// Activate the display of the plug-in flexform field and set FlexForm definition
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_pi1'] = 'pi_flexform';
if (!t3lib_extMgm::isLoaded('rgfolderselector')) {
	t3lib_extMgm::addPiFlexFormValue($_EXTKEY . '_pi1', 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/flexform_pi1.xml');
} else {
	t3lib_extMgm::addPiFlexFormValue($_EXTKEY . '_pi1', 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/flexform_pi1_rgfolderselector.xml');
}

t3lib_extMgm::addPlugin(array('LLL:EXT:file_list/Resources/Private/Language/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY . '_pi1'), 'list_type');

t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'File List');
