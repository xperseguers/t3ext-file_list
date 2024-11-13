<?php
defined('TYPO3') || die();

(static function (string $_EXTKEY) {
    $typo3Version = (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion();
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        $_EXTKEY,
        'Filelist',
        // cacheable actions
        [
            \Causal\FileList\Controller\FileController::class => 'list',
        ],
        // non-cacheable actions
        [],
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    if ($typo3Version < 11) {
        /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
        $iconRegistry->registerIcon(
            'extensions-filelist-wizard',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            [
                'source' => 'EXT:file_list/Resources/Public/Icons/ce_wizard.png',
            ]
        );
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:file_list/Configuration/TsConfig/Page/Mod/Wizards/NewContentElement.tsconfig">'
    );

    /* ===========================================================================
        Register default template layouts
    =========================================================================== */
    $GLOBALS['TYPO3_CONF_VARS']['EXT']['file_list']['templateLayouts'][] = [
        'LLL:EXT:file_list/Resources/Private/Language/locallang_flexform.xlf:filelist.templateLayout.simple',
        'Simple',
    ];
    $GLOBALS['TYPO3_CONF_VARS']['EXT']['file_list']['templateLayouts'][] = [
        'LLL:EXT:file_list/Resources/Private/Language/locallang_flexform.xlf:filelist.templateLayout.thumbnailDescription',
        'ThumbnailDescription',
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][$_EXTKEY] = \Causal\FileList\Hooks\DataHandler::class;

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['FileListFolderMapper'] = \Causal\FileList\Routing\Aspect\FileListFolderMapper::class;
})('file_list');
