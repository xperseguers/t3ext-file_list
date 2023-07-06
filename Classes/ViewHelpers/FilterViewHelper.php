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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * View helper to filter the list of files based on a list of extensions.
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:for each="{files -> fl:filter(extensions:'jpg, jpeg, png, gif')}" as="file">
 *      // whatever
 * </f:for>
 * </code>
 *
 * @category    ViewHelpers
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class FilterViewHelper extends AbstractViewHelper
{
    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('subject', 'mixed', 'Array of File object or single File', false);
        $this->registerArgument('extensions', 'mixed', 'Array or comma-separated list of file extensions', false);
    }

    /**
     * Filters the list of files.
     *
     * @return File[]
     */
    public function render()
    {
        $subject = $this->arguments['subject'] ?? null;
        $extensions = $this->arguments['extensions'] ?? '';

        /** @var File[] $subject */
        if ($subject === null) {
            $subject = $this->renderChildren();
        }
        if (!is_array($subject)) {
            $subject = [];
        }
        if (!is_array($extensions)) {
            $extensions = GeneralUtility::trimExplode(',', $extensions, true);
        }
        array_walk($extensions, function (&$extension) {
            $extension = strtolower($extension);
        });
        $items = array_filter($subject, function ($file) use ($extensions) {
            return in_array(strtolower($file->getExtension()), $extensions);
        });
        return $items;
    }
}
