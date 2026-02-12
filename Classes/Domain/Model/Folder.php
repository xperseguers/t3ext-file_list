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
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * Folder.
 *
 * @category    Domain\Model
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
abstract class FileListAbstractFolder extends \TYPO3\CMS\Core\Resource\Folder
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
}

if ((new Typo3Version())->getMajorVersion() >= 14) {
    class Folder extends FileListAbstractFolder
    {
        public function getFiles(int $start = 0, int $numberOfItems = 0, int $filterMode = self::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, bool $recursive = false, string $sort = '', bool $sortRev = false): array
        {
            // We want to search for files recursively
            $forceRecursive = true;
            $files = parent::getFiles($start, $numberOfItems, $filterMode, $forceRecursive, $sort, $sortRev);
            $files = Helper::filterInaccessibleFiles($files);
            return $files;
        }
    }
} else {
    class Folder extends FileListAbstractFolder
    {
        public function getFiles($start = 0, $numberOfItems = 0, $filterMode = self::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, $recursive = false, $sort = '', $sortRev = false)
        {
            // We want to search for files recursively
            $forceRecursive = true;
            $files = parent::getFiles($start, $numberOfItems, $filterMode, $forceRecursive, $sort, $sortRev);
            $files = Helper::filterInaccessibleFiles($files);
            return $files;
        }
    }
}
