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

namespace Causal\FileList\Hooks;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hooks into \TYPO3\CMS\Backend\View\PageLayoutView.
 *
 * @category    Hooks
 * @package     TYPO3
 * @subpackage  tx_filelist
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class PageLayoutView
{

    const LL_PATH = 'LLL:EXT:file_list/Resources/Private/Language/locallang_flexform.xlf:';

    /**
     * @var \TYPO3\CMS\Lang\LanguageService
     */
    protected $languageService;

    /**
     * @var array
     */
    protected $flexFormData;

    /**
     * PageLayoutView constructor.
     */
    public function __construct()
    {
        $this->languageService = $GLOBALS['LANG'];
    }

    /**
     * Prepares the summary for this plugin.
     *
     * @param array $params
     * @return string
     */
    public function getExtensionSummary(array $params)
    {
        $content = '<strong>' . htmlspecialchars($this->sL('filelist_title')) . '</strong><br /><br />';

        if ($params['row']['list_type'] === 'filelist_filelist') {
            $this->flexFormData = GeneralUtility::xml2array($params['row']['pi_flexform']);

            if (is_array($this->flexFormData)) {
                $mode = $this->getFieldFromFlexForm('settings.mode');
                $modeDescription = htmlspecialchars($this->sL('filelist.mode.' . strtolower($mode)));
                $content .= htmlspecialchars($this->sL('filelist.mode')) . ': <strong>' . $modeDescription . '</strong>';

                switch ($mode) {
                    case 'FOLDER':
                        $content .= '<br />';
                        $content .= htmlspecialchars($this->sL('filelist.path.summary')) . ': <strong>' . htmlspecialchars($this->getFieldFromFlexForm('settings.path')) . '</strong>';
                        break;
                }

                $templateLayout = $this->getTemplateLayout();
                $content .= '<br />';
                $content .= htmlspecialchars($this->sL('filelist.templateLayout')) . ': <strong>' . htmlspecialchars($templateLayout) . '</strong>';
            }
        }

        return $content;
    }

    /**
     * Returns the title of the template layout.
     *
     * @return string
     */
    protected function getTemplateLayout()
    {
        $templateLayout = $this->getFieldFromFlexForm('settings.templateLayout', 'display');

        if (!empty($templateLayout)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXT']['file_list']['templateLayouts'] as $item) {
                if ($item[1] === $templateLayout) {
                    $templateLayout = $this->languageService->sL($item[0]);
                }
            }
        }

        return $templateLayout;
    }

    /**
     * Translates a label.
     *
     * @param string $key
     * @return string
     */
    protected function sL(string $key) : string
    {
        $label = $this->languageService->sL(static::LL_PATH . $key);
        return $label;
    }

    /**
     * Returns a field value from the FlexForm configuration.
     *
     * @param string $key The name of the key
     * @param string $sheet The name of the sheet
     * @return string|null The value if found, otherwise null
     */
    protected function getFieldFromFlexForm($key, $sheet = 'sDEF')
    {
        $flexForm = $this->flexFormData;
        if (isset($flexForm['data'])) {
            $flexForm = $flexForm['data'];
            if (is_array($flexForm) && is_array($flexForm[$sheet]) && is_array($flexForm[$sheet]['lDEF'])
                && is_array($flexForm[$sheet]['lDEF'][$key]) && isset($flexForm[$sheet]['lDEF'][$key]['vDEF'])
            ) {
                return $flexForm[$sheet]['lDEF'][$key]['vDEF'];
            }
        }

        return null;
    }

}
