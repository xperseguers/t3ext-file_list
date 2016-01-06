<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Causal\FileList\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * File controller wizard for new content element.
 *
 * @category    Controller
 * @package     TYPO3
 * @subpackage  tx_filelist
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class FileControllerWizard
{

    /**
     * Adds the indexed_search pi1 wizard icon
     *
     * @param array $wizardItems Input array with wizard items for plugins
     * @return array Modified input array, having the item for indexed_search pi1 added.
     */
    public function proc($wizardItems)
    {
        $wizardItem = [
            'title' => $this->getLanguageService()->sL('LLL:EXT:file_list/Resources/Private/Language/locallang_flexform.xlf:filelist_title'),
            'description' => $this->getLanguageService()->sL('LLL:EXT:file_list/Resources/Private/Language/locallang_flexform.xlf:filelist_wizard_description'),
            'params' => '&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=filelist_filelist'
        ];

        if (version_compare(TYPO3_version, '7.5', '>=')) {
            /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
            $iconRegistry = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
            $iconRegistry->registerIcon('extensions-filelist-wizard',
                'TYPO3\\CMS\\Core\\Imaging\\IconProvider\\BitmapIconProvider',
                [
                    'source' => 'EXT:file_list/Resources/Public/Icons/ce_wizard.png',
                ]
            );
            $wizardItem['iconIdentifier'] = 'extensions-filelist-wizard';
        } else {
            $wizardItem['icon'] = ExtensionManagementUtility::extRelPath('file_list') . 'Resources/Public/Icons/ce_wizard_62.png';
        }

        $wizardItems['plugins_tx_indexed_search'] = $wizardItem;

        return $wizardItems;
    }

    /**
     * Returns the language service.
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

}
