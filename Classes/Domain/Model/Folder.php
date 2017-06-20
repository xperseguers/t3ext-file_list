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

namespace Causal\FileList\Domain\Model;

/**
 * Folder.
 *
 * @category    Domain\Model
 * @package     TYPO3
 * @subpackage  tx_filelist
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class Folder extends \TYPO3\CMS\Core\Resource\Folder
{

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * Overrides the parent method since it's internal anyway
     * and purpose is to support custom properties solely.
     *
     * @param array $properties
     * @return void
     */
    public function updateProperties(array $properties)
    {
        $this->properties = $properties;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param int $newTimestamp
     * @param int $maxDepth
     * @param int $depth (internal)
     * @return bool
     */
    public function hasFileNewerThan($newTimestamp, $maxDepth = 3, $depth = 0)
    {
        $files = $this->getFiles();
        foreach ($files as $file) {
            $properties = $file->getProperties();
            if ($properties['creation_date'] >= $newTimestamp) {
                return true;
            }
        }

        // Do not go deeper than $maxDepth folders
        if ($depth < $maxDepth) {
            $subfolders = $this->getSubfolders();
            foreach ($subfolders as $subfolder) {
                $hasNew = \Causal\FileList\Utility\Helper::cast($subfolder, __CLASS__)->hasFileNewerThan($newTimestamp, $maxDepth, $depth + 1);
                if ($hasNew) {
                    return true;
                }
            }
        }

        return false;
    }

}
