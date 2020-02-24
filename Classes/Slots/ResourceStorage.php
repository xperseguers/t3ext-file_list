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

namespace Causal\FileList\Slots;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hooks into \TYPO3\CMS\Core\Resource\ResourceStorage.
 *
 * @category    Slots
 * @package     TYPO3
 * @subpackage  tx_filelist
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class ResourceStorage
{

    /**
     * @var CacheManager
     */
    protected $pageCache;

    /**
     * ResourceStorage constructor.
     */
    public function __construct()
    {
        $this->pageCache = GeneralUtility::makeInstance(CacheManager::class);
    }

    /**
     * @param FileInterface $file
     * @param Folder $targetFolder
     */
    public function postFileAdd(FileInterface $file, Folder $targetFolder)
    {
        $this->flushCachesByFolder($targetFolder);
    }

    /**
     * @param string $fileIdentifier
     * @param Folder $targetFolder
     */
    public function postFileCreate($fileIdentifier, Folder $targetFolder)
    {
        $this->flushCachesByFolder($targetFolder);
    }

    /**
     * @param FileInterface $file
     * @param Folder $targetFolder
     */
    public function postFileCopy(FileInterface $file, Folder $targetFolder)
    {
        $this->flushCachesByFolder($targetFolder);
    }

    /**
     * @param FileInterface $file
     * @param Folder $targetFolder
     * @param Folder $originalFolder
     */
    public function postFileMove(FileInterface $file, Folder $targetFolder, Folder $originalFolder)
    {
        $this->flushCachesByFolder($targetFolder);
        $this->flushCachesByFolder($originalFolder);
    }

    /**
     * @param FileInterface $file
     */
    public function postFileDelete(FileInterface $file)
    {
        try {
            $this->flushCachesByFolder($file->getParentFolder());
        } catch (\Exception $e) {
            // Exception may happen when a file is moved to /_recycler_/ but the user has no access to it
        }
    }

    /**
     * @param FileInterface $file
     * @param string $sanitizedTargetFileName
     */
    public function postFileRename(FileInterface $file, $sanitizedTargetFileName)
    {
        $this->flushCachesByFolder($file->getParentFolder());
    }

    /**
     * @param FileInterface $file
     * @param string $localFilePath
     */
    public function postFileReplace(FileInterface $file, $localFilePath)
    {
        $this->flushCachesByFolder($file->getParentFolder());
    }

    /**
     * @param FileInterface $file
     * @param mixed $content
     */
    public function postFileSetContents(FileInterface $file, $content)
    {
        $this->flushCachesByFolder($file->getParentFolder());
    }

    /**
     * @param Folder $folder
     */
    public function postFolderAdd(Folder $folder)
    {
        $this->flushCachesByFolder($folder->getParentFolder());
    }

    /**
     * @param Folder $folder
     * @param Folder $targetFolder
     * @param string $newName
     * @param Folder $originalFolder
     */
    public function postFolderMove(Folder $folder, Folder $targetFolder, $newName, Folder $originalFolder)
    {
        $this->flushCachesByFolder($folder);
        $this->flushCachesByFolder($targetFolder);
        $this->flushCachesByFolder($originalFolder);
    }

     /**
     * @param Folder $folder
     * @param Folder $targetFolder
     * @param $newName
     */
    public function postFolderCopy(Folder $folder, Folder $targetFolder, $newName) {
        $this->flushCachesByFolder($folder);
        $this->flushCachesByFolder($targetFolder);
    }

    /**
     * @param Folder $folder
     */
    public function postFolderDelete(Folder $folder)
    {
        $this->flushCachesByFolder($folder);
        $this->flushCachesByFolder($folder->getParentFolder());
    }

    /**
     * @param Folder $folder
     * @param string $newName
     */
    public function postFolderRename(Folder $folder, $newName)
    {
        $this->flushCachesByFolder($folder);
        $this->flushCachesByFolder($folder->getParentFolder());
    }

    /**
     * Flushes caches by folder, using tags set by \Causal\FileList\Controller\FileController.
     *
     * @param FolderInterface $folder
     * @return void
     */
    public function flushCachesByFolder(FolderInterface $folder)
    {
        switch ($folder->getRole()) {
            case FolderInterface::ROLE_RECYCLER:
            case FolderInterface::ROLE_PROCESSING:
            case FolderInterface::ROLE_TEMPORARY:
                // Special folder, is not taken into account by the controller anyway
                break;
            default:
                $this->pageCache->flushCachesByTag('tx_filelist_folder_' . $folder->getHashedIdentifier());
        }
    }

}
