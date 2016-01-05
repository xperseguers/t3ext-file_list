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

namespace Causal\FileList\Utility;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Helper class for the 'file_list' extension.
 *
 * @category    Utility
 * @package     TYPO3
 * @subpackage  tx_filelist
 * @author      Moreno Feltscher <moreno@luagsh.ch>
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class Helper
{

    /**
     * Sorts an array according to a key and a sort direction.
     *
     * @param array $arr
     * @param string $sortKey
     * @param string $direction (either 'asc' or 'desc')
     * @return array The sorted array
     */
    static public function arraySort(array $arr, $sortKey, $direction = 'asc')
    {
        if (count($arr) > 0) {
            foreach ($arr as $key => $row) {
                $sortArr[$key] = $row[$sortKey];
            }
            array_multisort($sortArr, strtolower($direction) === 'asc' ? SORT_ASC : SORT_DESC, $arr);
        }

        return $arr;
    }

    /**
     * Gets a list of all files inside a given directory.
     *
     * @param string $path Path to the specified directory
     * @param bool $recursive Defines whether files should be searched recursively
     * @return array List of all files inside the directory
     */
    static public function getListOfFiles($path, $recursive = false, $invalidFileNamePattern = '', $invalidFolderNamePattern = '')
    {
        $result = [];
        $handle = opendir($path);
        while ($tempName = readdir($handle)) {
            if (($tempName !== '.') && ($tempName !== '..')) {
                $tempPath = $path . '/' . $tempName;
                if (is_dir($tempPath) && static::isValidName($tempName, $invalidFolderNamePattern) && $recursive) {
                    $result = array_merge($result, static::getListOfFiles($tempPath, true, $invalidFileNamePattern, $invalidFolderNamePattern));
                } elseif (is_file($tempPath) && static::isValidName($tempName, $invalidFileNamePattern)) {
                    $result[] = $tempPath;
                }
            }
        }
        closedir($handle);

        return $result;
    }

    /**
     * Counts the amount of files inside a given directory.
     *
     * @param string $path Path to the specified directory
     * @param bool $recursive Defines whether files should be counted recursively
     * @param string $invalidFileNamePattern Invalid filename pattern
     * @param string $invalidFolderNamePattern Invalid directory name pattern
     * @return int Number of files in the directory
     */
    static public function getNumberOfFiles($path, $recursive = false, $invalidFileNamePattern = '', $invalidFolderNamePattern = '')
    {
        return count(static::getListOfFiles($path, $recursive, $invalidFileNamePattern, $invalidFolderNamePattern));
    }

    /**
     * Returns the highest timestamp of all files inside a given directory.
     *
     * @param string $path Path to the specified directory
     * @param bool $recursive Defines whether files should be searched recursively
     * @param string $invalidFileNamePattern Invalid filename pattern
     * @param string $invalidFolderNamePattern Invalid directory name pattern
     * @return int Highest timestamp of all files in the directory
     */
    static public function getHighestFileTimestamp($path, $recursive = true, $invalidFileNamePattern = '', $invalidFolderNamePattern = '')
    {
        $allFiles = static::getListOfFiles($path, $recursive, $invalidFileNamePattern, $invalidFolderNamePattern);
        $highestKnown = 0;
        foreach ($allFiles as $val) {
            $currentValue = filemtime($val);
            if ($currentValue > $highestKnown) {
                $highestKnown = $currentValue;
            }
        }

        return $highestKnown;
    }

    /**
     * Gets content of a directory.
     *
     * @param string $path Path to the specified directory
     * @param string $invalidFileNamePattern Invalid filename pattern
     * @param string $invalidFolderNamePattern Invalid directory name pattern
     * @return array list(array $directories, array $files)
     */
    static public function getDirectoryContent($path, $invalidFileNamePattern = '', $invalidFolderNamePattern = '')
    {
        $dirs = [];
        $files = [];

        // Open the directory and read out all folders and files
        $dh = opendir($path);
        while ($dir_content = readdir($dh)) {
            if ($dir_content !== '.' && $dir_content !== '..') {
                if (is_dir($path . '/' . $dir_content) && static::isValidName($dir_content, $invalidFolderNamePattern)) {
                    $dirs[] = array(
                        'type' => 'DIRECTORY',
                        'name' => $dir_content,
                        'date' => static::getHighestFileTimestamp($path . '/' . $dir_content, true, $invalidFileNamePattern, $invalidFolderNamePattern),
                        'size' => static::getNumberOfFiles($path . '/' . $dir_content, false, $invalidFileNamePattern, $invalidFolderNamePattern),
                        'path' => $path . $dir_content,
                        'fullpath' => PATH_site . $path . $dir_content,
                    );
                } elseif (is_file($path . '/' . $dir_content) && static::isValidName($dir_content, $invalidFileNamePattern)) {
                    $files[] = array(
                        'type' => 'FILE',
                        'name' => $dir_content,
                        'date' => filemtime($path . $dir_content),
                        'size' => filesize($path . $dir_content),
                        'path' => $path . $dir_content,
                        'fullpath' => PATH_site . $path . $dir_content,
                    );
                }
            }
        }
        // Close the directory
        closedir($dh);

        return array($dirs, $files);
    }

    /**
     * Sanitizes a path by making sure a trailing slash is present and
     * all directories are resolved (no more '../' within string).
     *
     * @param string $path Either an absolute path or a path relative to website root
     * @return string
     */
    static public function sanitizePath($path)
    {
        $path = PathUtility::getCanonicalPath($path);

        // Ensure a trailing slash is present
        $path = rtrim($path, '/') . '/';

        return $path;
    }

    /**
     * Resolves a site-relative path and or filename.
     *
     * @param string $path
     * @return string
     */
    static public function resolveSiteRelPath($path)
    {
        if (strcmp(substr($path, 0, 4), 'EXT:')) {
            return $path;
        }
        $path = substr($path, 4);    // Remove 'EXT:' at the beginning
        $extension = substr($path, 0, strpos($path, '/'));
        $references = explode(':', substr($path, strlen($extension) + 1));
        $pathOrFilename = ExtensionManagementUtility::siteRelPath($extension) . $references[0];

        if (is_dir(PATH_site . $pathOrFilename)) {
            $pathOrFilename = static::sanitizePath($pathOrFilename);
        }

        return $pathOrFilename;
    }

    /**
     * Generates a proper URL for file links by encoding special characters and spaces.
     *
     * @param string $path Path to the file
     * @return string
     */
    static public function generateProperURL($path)
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    /**
     * Returns true if $invalidPattern does not match $name.
     *
     * @param string $name
     * @param string $invalidPattern
     * @return bool
     */
    static protected function isValidName($name, $invalidPattern)
    {
        return empty($invalidPattern) || !preg_match($invalidPattern, $name);
    }
}
