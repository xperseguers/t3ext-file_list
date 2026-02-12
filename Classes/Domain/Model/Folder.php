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

use Causal\FileList\Utility\Helper;

/**
 * Folder.
 *
 * @category    Domain\Model
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
     */
    public function updateProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param int $newTimestamp
     * @param int $maxDepth
     * @param int $depth (internal)
     * @return bool
     */
    public function hasFileNewerThan(int $newTimestamp, int $maxDepth = 3, int $depth = 0): bool
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
                $hasNew = Helper::cast($subfolder, __CLASS__)->hasFileNewerThan($newTimestamp, $maxDepth, $depth + 1);
                if ($hasNew) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns a list of files in this folder, optionally filtered. There are several filter modes available, see the
     * FILTER_MODE_* constants for more information.
     *
     * For performance reasons the returned items can also be limited to a given range
     *
     * @param int $start The item to start at
     * @param int $numberOfItems The number of items to return
     * @param int $filterMode The filter mode to use for the filelist.
     * @param bool $recursive
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return \TYPO3\CMS\Core\Resource\File[]
     */
    public function getFiles(int $start = 0, int $numberOfItems = 0, int $filterMode = self::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, bool $recursive = false, string $sort = '', bool $sortRev = false): array
    {
        // We want to search for files recursively
        $forceRecursive = true;
        $files = parent::getFiles($start, $numberOfItems, $filterMode, $forceRecursive, $sort, $sortRev);
        $files = Helper::filterInaccessibleFiles($files);
        return $files;
    }
}
