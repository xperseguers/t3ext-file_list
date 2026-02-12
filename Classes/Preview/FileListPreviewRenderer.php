<?php
declare(strict_types = 1);

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

namespace Causal\FileList\Preview;

use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileListPreviewRenderer extends StandardContentPreviewRenderer
{
    protected $typo3Version;

    protected $flexFormData;

    protected $labelPrefix;

    public function renderPageModulePreviewContent(GridColumnItem $item): string
    {
        $this->typo3Version = (new Typo3Version())->getMajorVersion();

        $out = [];
        $languageService = $this->getLanguageService();
        $this->labelPrefix = 'LLL:EXT:file_list/Resources/Private/Language/locallang_flexform.xlf:';

        $pluginTitle = $languageService->sL($this->labelPrefix . 'filelist_title');
        $out[] = '<strong>' . htmlspecialchars($pluginTitle) . '</strong>';

        if ($this->typo3Version >= 14) {
            $record = $item->getRecord()->toArray();
            $this->flexFormData = $record['pi_flexform'];
        } else {
            $record = $item->getRecord();
            $this->flexFormData = GeneralUtility::xml2array($record['pi_flexform']);
        }

        if (is_array($this->flexFormData) || $this->flexFormData instanceof \ArrayAccess) {
            if ($this->typo3Version >= 14) {
                $cssClasses = 'table table-sm table-vertical-top mt-3 mb-0';
            } else {
                $cssClasses = 'table table-sm mt-3 mb-0';
            }
            $out[] = '<table class="' . $cssClasses . '">';
            $this->renderFlexFormPreviewContent($record, $out);
            $out[] = '</table>';
        }

        return implode(LF, $out);
    }

    protected function showError(string $text): string
    {
        $errorPattern = '<span class="badge badge-danger">%s</span>';
        return sprintf($errorPattern, $text);
    }

    protected function getFieldFromFlexForm(string $key, string $sheet = 'sDEF'): ?string
    {
        if ($this->typo3Version >= 14) {
            $sheets = $this->flexFormData->getSheets();
            $keyParts = explode('.', $key);
            $value = null;
            if (isset($sheets[$sheet])) {
                $current = $sheets[$sheet];
                foreach ($keyParts as $part) {
                    if (isset($current[$part])) {
                        $current = $current[$part];
                    } else {
                        return null;
                    }
                }
                return is_array($current) ? implode(',', $current) : (string)$current;
            }
        } else {
            $flexForm = $this->flexFormData;
            if (isset($flexForm['data'])) {
                $flexForm = $flexForm['data'];
                return $flexForm[$sheet]['lDEF'][$key]['vDEF'] ?? null;
            }
        }

        return null;
    }

    protected function addTableRow(string $label, string $content): string
    {
        $cssClasses = $this->typo3Version >= 14 ? '' : 'align-top';

        $out[] = '<tr>';
        $out[] = '<td class="' . $cssClasses . '">' . htmlspecialchars($label) . '</td>';
        $out[] = '<td class="' . $cssClasses . '" style="font-weight: bold">' . $content . '</td>';
        $out[] = '</tr>';

        return implode(LF, $out);
    }

    protected function describeBoolean(bool $value = true): string
    {
        $key = 'LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:';
        $key .= $value ? 'yes' : 'no';

        return htmlspecialchars($this->getLanguageService()->sL($key));
    }

    protected function renderFlexFormPreviewContent(array $record, array &$out): void
    {
        $languageService = $this->getLanguageService();

        $label = $languageService->sL($this->labelPrefix . 'filelist.mode');
        $mode = $this->getFieldFromFlexForm('settings.mode') ?? 'folder';
        $description = htmlspecialchars($languageService->sL($this->labelPrefix . 'filelist.mode.' . strtolower($mode)));
        $out[] = $this->addTableRow($label, $description);

        switch ($mode) {
            case 'FOLDER':
                $label = $languageService->sL($this->labelPrefix . 'filelist.path.summary');
                $path = $this->getFieldFromFlexForm('settings.path');
                if (empty($path)) {
                    $error = $languageService->sL($this->labelPrefix . 'filelist.path.summary.errorEmpty');
                    $description = $this->showError(htmlspecialchars($error));
                } else {
                    $description = htmlspecialchars($path);
                }
                $out[] = $this->addTableRow($label, $description);

                $label = $languageService->sL($this->labelPrefix . 'filelist.includeSubfolders');
                $includeSubfolders = (bool)$this->getFieldFromFlexForm('settings.includeSubfolders');
                $out[] = $this->addTableRow($label, $this->describeBoolean($includeSubfolders));
                break;
        }

        $label = $languageService->sL($this->labelPrefix . 'filelist.templateLayout');
        $templateLayout = $this->getTemplateLayout();
        $description = htmlspecialchars($templateLayout);
        $out[] = $this->addTableRow($label, $description);
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
                    $templateLayout = $this->getLanguageService()->sL($item[0]);
                }
            }
        }

        return $templateLayout ?? '';
    }
}
