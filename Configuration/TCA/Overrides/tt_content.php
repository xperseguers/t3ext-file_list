<?php
defined('TYPO3') || die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin([
    'LLL:EXT:file_list/Resources/Private/Language/locallang_flexform.xlf:filelist_title',
    'filelist_filelist',
    'extensions-filelist-wizard',
    'special'
], 'CType', 'file_list');

$typo3Branch = (new \TYPO3\CMS\Core\Information\Typo3Version())->getBranch();
if (version_compare($typo3Branch, '11.0', '<')) {
    $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['filelist_filelist'] = 'extensions-filelist-wizard';
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:file_list/Configuration/FlexForms/flexform_filelist.xml',
    'filelist_filelist'
);

$GLOBALS['TCA']['tt_content']['types']['filelist_filelist']['showitem'] = '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            --palette--;;general,
            --palette--;;headers,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.plugin,
            pi_flexform,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
            --palette--;;frames,
            --palette--;;appearanceLinks,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
            --palette--;;language,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            --palette--;;hidden,
            --palette--;;access,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
            categories,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
            rowDescription,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
    ';

/**
 * Configure a custom preview renderer for the plugins
 * @see https://docs.typo3.org/m/typo3/reference-coreapi/11.5/en-us/ApiOverview/ContentElements/CustomBackendPreview.html
 */
$GLOBALS['TCA']['tt_content']['types']['filelist_filelist']['previewRenderer']
    = \Causal\FileList\Preview\FileListPreviewRenderer::class;
