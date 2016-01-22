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
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * View helper to filter the list of files based on a list of extensions.
 *
 * = Examples =
 *
 * <code title="Example">
  * <f:for each="{files -> fl.filter(extensions:'jpg, jpeg, png, gif')}" as="file">
 *      // whatever
 * </f:for>
  * </code>
 *
 * @category    ViewHelpers
 * @package     TYPO3
 * @subpackage  tx_filelist
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class FilterViewHelper extends AbstractViewHelper
{

    /**
     * Filters the list of files.
     *
     * @param File[]|null $subject
     * @param string|array $extensions
     * @return File[]
     */
    public function render($subject = null, $extensions = null)
    {
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
        array_walk($extensions, function (&$extension) { $extension = strtolower($extension); });
        $items = array_filter($subject, function ($file) use ($extensions) {
            return in_array(strtolower($file->getExtension()), $extensions);
        });
        return $items;
    }

}
