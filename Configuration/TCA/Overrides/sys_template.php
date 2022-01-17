<?php

defined('TYPO3') || die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'file_list',
    'Configuration/TypoScript/',
    'File List'
);
