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
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * View helper to limit the list of files based on a list of extensions.
 *
 * = Examples =
 *
 * <code title="Example">
  * <f:for each="{files -> fl:limit(offset:0, length:4)}" as="file">
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
class LimitViewHelper extends AbstractViewHelper
{

    /**
     * Limits the list of files.
     *
     * @param mixed $subject
     * @param int $offset
     * @param int $length
     * @return File[]
     */
    public function render($subject = null, $offset = 0, $length = 9999)
    {
        /** @var File[] $subject */
        if ($subject === null) {
            $subject = $this->renderChildren();
        }
        if (!is_array($subject)) {
            $subject = [];
        }
        $items = array_slice($subject, $offset, (int)$length);
        return $items;
    }

}
