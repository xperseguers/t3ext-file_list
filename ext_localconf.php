<?php
defined('TYPO3_MODE') || die();

$boot = function ($_EXTKEY) {
    $settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY]);

    /* ===========================================================================
        Legacy plugin (pibase-based)
    =========================================================================== */
    if (!isset($settings['enableLegacyPlugin']) || (bool)$settings['enableLegacyPlugin']) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
            $_EXTKEY,
            'Classes/Controller/Pi1/Pi1Controller.php',
            '_pi1',
            'list_type',
            $settings['noCache'] ? 0 : 1
        );
    }

    /* ===========================================================================
        Extbase-based plugin
    =========================================================================== */
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Causal.' . $_EXTKEY,
        'Filelist',
        // cacheable actions
        [
            'File' => 'list',
        ],
        // non-cacheable actions
        []
    );

    /* ===========================================================================
        Page module hook
    =========================================================================== */
    $extensionName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($_EXTKEY);
    $pluginSignature = strtolower($extensionName) . '_filelist';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$pluginSignature][$_EXTKEY] =
        \Causal\FileList\Hooks\PageLayoutView::class . '->getExtensionSummary';

    /* ===========================================================================
        Register default template layouts
    =========================================================================== */
    $GLOBALS['TYPO3_CONF_VARS']['EXT']['file_list']['templateLayouts'][] = [
        'LLL:EXT:file_list/Resources/Private/Language/locallang_flexform.xlf:filelist.templateLayout.simple',
        'Simple'
    ];
    $GLOBALS['TYPO3_CONF_VARS']['EXT']['file_list']['templateLayouts'][] = [
        'LLL:EXT:file_list/Resources/Private/Language/locallang_flexform.xlf:filelist.templateLayout.thumbnailDescription',
        'ThumbnailDescription'
    ];
};

$boot($_EXTKEY);
unset($boot);
