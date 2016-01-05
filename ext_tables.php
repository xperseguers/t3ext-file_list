<?php
defined('TYPO3_MODE') || die();

$boot = function ($_EXTKEY) {
    $settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY]);

    /* ===========================================================================
        Legacy plugin (pibase-based)
    =========================================================================== */
    if (!isset($settings['enableLegacyPlugin']) || (bool)$settings['enableLegacyPlugin']) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
            [
                'LLL:EXT:file_list/Resources/Private/Language/locallang_db.xml:tt_content.list_type_pi1',
                $_EXTKEY . '_pi1'
            ],
            'list_type'
        );

        $pluginSignature = $_EXTKEY . '_pi1';

        // Disable the display of layout, select_key and page fields
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key,pages,recursive';

        // Activate the display of the plugin FlexForm field and set FlexForm definition
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/flexform_pi1.xml');
    }

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

    /* ===========================================================================
        Register default TypoScript
    =========================================================================== */
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'File List');
};

$boot($_EXTKEY);
unset($boot);
