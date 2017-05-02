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

namespace Causal\FileList\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * View helper for the icon associated to a file.
 *
 * @category    ViewHelpers
 * @package     TYPO3
 * @subpackage  tx_filelist
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class FileIconViewHelper extends AbstractViewHelper implements CompilableInterface
{

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Renders the icon of the supplied file resource.
     *
     * @param File $file The incoming data to convert, or NULL if VH children should be used
     * @return string Image tag
     * @api
     */
    public function render(File $file = null)
    {
        return static::renderStatic(
            array(
                'file' => $file,
            ),
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * Applies htmlspecialchars() on the specified value.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        /** @var File $file */
        $file = $arguments['file'];
        if ($file === null) {
            $file = $renderChildrenClosure();
        }

        $settings = $renderingContext->getTemplateVariableContainer()->get('settings');
        $settings['fileIconRootPath'] = GeneralUtility::getFileAbsFileName($settings['fileIconRootPath']);
        $fileName = $file->getProperty('name');
        $iconFileName = static::getFileTypeIcon($settings, $fileName);

        $iconWebPath = PathUtility::getAbsoluteWebPath($settings['fileIconRootPath'] . $iconFileName);
        $output = '<img src="' . htmlspecialchars($iconWebPath) . '" alt="" />';

        return $output;
    }

    /**
     * Returns the icon which represents a file type
     *
     * @param array $settings
     * @param string $fileName Name of the specified file
     * @return string File name of the icon
     */
    protected static function getFileTypeIcon(array $settings, $fileName)
    {
        $categories = [];
        foreach ($settings['extension']['category'] as $category => $extensions) {
            $categories[$category] = GeneralUtility::trimExplode(',', $extensions, true);
        }
        $remapExtensions = $settings['extension']['remap'];

        // Extract the file extension
        $ext = strtolower(substr($fileName, strrpos($fileName, '.') + 1));

        // Try to find a dedicated icon
        for ($i = 0; $i < 2; $i++) {
            if ($i == 1) {
                // Remap the extension
                if (isset($remapExtensions[$ext])) {
                    $ext = $remapExtensions[$ext];
                } else {
                    break;
                }
            }
            if (is_file($settings['fileIconRootPath'] . $ext . '.png')) {
                return $ext . '.png';
            } elseif (is_file($settings['fileIconRootPath'] . $ext . '.gif')) {
                return $ext . '.gif';
            }
        }

        // Try to find a file type category icon
        foreach ($categories as $cat => $extensions) {
            if (in_array($ext, $extensions)) {
                return 'category_' . $cat . '.png';
            }
        }

        // Fallback icon
        return 'blank_document.png';
    }

}
