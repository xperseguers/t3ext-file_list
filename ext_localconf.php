<?php

defined('TYPO3') || die();

(static function (string $_EXTKEY) {
    $typo3Branch = class_exists(\TYPO3\CMS\Core\Information\Typo3Version::class)
        ? (new \TYPO3\CMS\Core\Information\Typo3Version())->getBranch()
        : TYPO3_branch;

    if (version_compare($typo3Branch, '10.0', '>=')) {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            $_EXTKEY,
            'Filelist',
            // cacheable actions
            [
                \Causal\FileList\Controller\FileController::class => 'list',
            ],
            // non-cacheable actions
            []
        );
    } else {
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
    }

    /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon(
        'extensions-filelist-wizard',
        \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        [
            'source' => 'EXT:file_list/Resources/Public/Icons/ce_wizard.png',
        ]
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:file_list/Configuration/TsConfig/Page/Mod/Wizards/NewContentElement.tsconfig">'
    );

    /* ===========================================================================
        Web > Page hook
    =========================================================================== */
    $extensionName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($_EXTKEY);
    $pluginSignature = strtolower($extensionName) . '_filelist';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$pluginSignature][$_EXTKEY] =
        \Causal\FileList\Hooks\PageLayoutView::class . '->getExtensionSummary';

    if (version_compare($typo3Branch, '10.2', '<')) {
        /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
        $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);

        $listenSignals = [
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileAdd,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileCopy,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileMove,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileRename,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileReplace,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileCreate,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileDelete,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileSetContents,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderAdd,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderCopy,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderMove,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderRename,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderDelete,
        ];
        foreach ($listenSignals as $signal) {
            $signalSlotDispatcher->connect(
                'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
                $signal,
                \Causal\FileList\Slots\ResourceStorage::class,
                $signal
            );
        }
    }

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
