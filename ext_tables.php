<?php
defined('TYPO3_MODE') || die();

$boot = function ($_EXTKEY) {
    /* ===========================================================================
        Extbase-based plugin
    =========================================================================== */
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'Causal.' . $_EXTKEY,
        'Filelist',
        'LLL:EXT:file_list/Resources/Private/Language/locallang_flexform.xlf:filelist_title'
    );

    $extensionName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($_EXTKEY);
    $pluginSignature = strtolower($extensionName) . '_filelist';

    // Disable the display of layout, select_key and page fields
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key,pages,recursive';

    // Activate the display of the plugin FlexForm field and set FlexForm definition
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
    if (version_compare(TYPO3_version, '7.6', '>=')) {
        $flexform = 'flexform_filelist.xml';
    } else {
        $flexform = 'flexform_filelist_62.xml';
    }
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/' . $flexform);

    // Register the wizard for new content element
    $GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses'][\Causal\FileList\Controller\FileControllerWizard::class] =
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/Controller/FileControllerWizard.php';

    /* ===========================================================================
        Register default TypoScript
    =========================================================================== */
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'File List');

};

$boot($_EXTKEY);
unset($boot);
