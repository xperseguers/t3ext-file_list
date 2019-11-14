<?php
defined('TYPO3_MODE') || die();

$boot = function ($_EXTKEY) {
    if (version_compare(TYPO3_branch, '9.5', '<')) {
        // Register the wizard for new content element
        // TODO: For TYPO3 v9, see https://docs.typo3.org/m/typo3/reference-coreapi/9.5/en-us/ApiOverview/Examples/ContentElementWizard/Index.html
        $GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses'][\Causal\FileList\Controller\FileControllerWizard::class] =
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/Controller/FileControllerWizard.php';
    }
};

$boot('file_list');
unset($boot);
