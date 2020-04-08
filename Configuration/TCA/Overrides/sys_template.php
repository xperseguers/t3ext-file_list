<?php
defined('TYPO3_MODE') || die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'file_list',
    'Configuration/TypoScript/',
    'File List'
);
