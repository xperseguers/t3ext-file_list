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

namespace Causal\FileList\Controller;

use Causal\FileList\Domain\Repository\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\FileCollectionRepository;
use TYPO3\CMS\Core\Resource\Folder;

/**
 * File controller.
 *
 * @category    Controller
 * @package     TYPO3
 * @subpackage  tx_filelist
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class FileController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    const SORT_BY_NAME = 'NAME';
    const SORT_BY_DATE = 'DATE';
    const SORT_BY_SIZE = 'SIZE';

    const SORT_DIRECTION_ASC = 'ASC';
    const SORT_DIRECTION_DESC = 'DESC';

    /**
     * @var FileRepository
     */
    protected $fileRepository;

    /**
     * @param \Causal\FileList\Domain\Repository\FileRepository $fileRepository
     * @return void
     */
    public function injectFileRepository(FileRepository $fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }

    /**
     * Listing of files.
     *
     * @param string $path
     * @return void
     */
    public function listAction($path = '')
    {
        $files = [];
        $subfolders = [];
        $parentFolder = null;
        $breadcrumb = [];

        // Sanitize configuration
        if (!empty($this->settings['path'])) {
            $this->settings['path'] = rtrim($this->settings['path'], '/') . '/';
        }
        if (!empty($path)) {
            $path = rtrim($path, '/') . '/';
        }

        try {
            switch ($this->settings['mode']) {
                case 'FOLDER':
                    $this->populateFromFolder($path, $files, $subfolders, $parentFolder, $breadcrumb);
                    break;

                case 'FILE_COLLECTIONS':
                    $this->populateFromFileCollections($files);
                    break;
            }
        } catch (\Exception $e) {
            return sprintf('<p class="bg-danger">%s</p>', htmlspecialchars($e->getMessage()));
        }

        // Sort folders and files
        if ($this->settings['orderBy'] === static::SORT_BY_NAME && $this->settings['sortDirection'] === static::SORT_DIRECTION_DESC) {
            krsort($subfolders);
        } else {
            ksort($subfolders);
        }

        $orderedFiles = [];
        foreach ($files as $file) {
            switch ($this->settings['orderBy']) {
                case static::SORT_BY_NAME:
                    $key = $file->getName();
                    break;
                case static::SORT_BY_DATE:
                    $key = $file->getProperty('modification_date');
                    break;
                case static::SORT_BY_SIZE:
                    $key = $file->getSize();
                    break;
            }
            $key .= TAB . $file->getUid();
            $orderedFiles[$key] = $file;
        }

        if ($this->settings['sortDirection'] === static::SORT_DIRECTION_ASC) {
            ksort($orderedFiles);
        } else {
            krsort($orderedFiles);
        }

        // Mark files as "new" if needed
        // BEWARE: This needs to be done at the end since it is using an internal method which
        //         may break other operations such as sorting
        if ((int)$this->settings['newDuration'] > 0) {
            $newTimestamp = $GLOBALS['EXEC_TIME'] - 86400 * (int)$this->settings['newDuration'];
            foreach ($orderedFiles as &$file) {
                $properties = $file->getProperties();
                $properties['tx_filelist']['isNew'] = $properties['creation_date'] >= $newTimestamp;
                $file->updateProperties($properties);
            }
        }

        $this->view->assignMultiple([
            'isEmpty' => $parentFolder === null && empty($subfolders) && empty($files),
            'breadcrumb' => $breadcrumb,
            'parent' => $parentFolder,
            'folders' => $subfolders,
            'files' => $orderedFiles,
        ]);
    }

    /**
     * Populates files and folders from a configured root path.
     *
     * @param string $path Optional subpath of $this->settings['path']
     * @param \TYPO3\CMS\Core\Resource\File[] &$files
     * @param Folder[] &$subfolders
     * @param Folder &$parentFolder
     * @param array &$breadcrumb
     * @return void
     * @throws \InvalidArgumentException|\TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     */
    protected function populateFromFolder($path, array &$files, array &$subfolders, Folder &$parentFolder = null, array &$breadcrumb)
    {
        if (!(bool)$this->settings['includeSubfolders']) {
            // No way!
            $path = '';
        }

        $rootFolder = $this->fileRepository->getFolderByIdentifier($this->settings['path']);

        $folder = null;
        if (!empty($path) && preg_match('/^file:(\d+):(.*)$/', $this->settings['path'], $matches)) {
            $storageUid = (int)$matches[1];
            $rootIdentifier = $matches[2];
            // Security check before blindly accepting the requested folder's content
            if ($path === $rootIdentifier) {
                $path = '';
            }
            if (!empty($path) && GeneralUtility::isFirstPartOfStr($path, $rootIdentifier)) {
                $identifier = 'file:' . $storageUid . ':' . $path;
                $folder = $this->fileRepository->getFolderByIdentifier($identifier);
            }
        }
        if ($folder === null) {
            $folder = $rootFolder;
        }

        // Retrieve the list of files
        $files = $folder->getFiles();

        // In a subfolder, so retrieve parent folder
        if (!empty($path)) {
            $parentFolder = $folder->getParentFolder();
        }

        if ((bool)$this->settings['includeSubfolders']) {
            $subfolders = $folder->getSubfolders();

            // Prepare the breadcrumb data
            $f = $folder;
            while ($this->settings['path'] !== 'file:' . $f->getCombinedIdentifier()) {
                array_unshift($breadcrumb, [ 'folder' => $f ]);
                $f = $f->getParentFolder();
            }
            array_unshift($breadcrumb, [ 'folder' => $rootFolder, 'isRoot' => true ]);
            $breadcrumb[count($breadcrumb) - 1]['state'] = 'active';
        }
    }

    /**
     * Populates files from a list of file collections.
     *
     * @param \TYPO3\CMS\Core\Resource\File[] $files
     * @return void
     * @throws \InvalidArgumentException|\TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     */
    protected function populateFromFileCollections(array &$files)
    {
        /** @var FileCollectionRepository $fileCollectionRepository */
        $fileCollectionRepository = $this->objectManager->get(FileCollectionRepository::class);
        if (!empty($this->settings['rootPath'])) {
            // Returned files needs to be within a given root path
            $folder = $this->fileRepository->getFolderByIdentifier($this->settings['rootPath']);
        }

        $collectionUids = GeneralUtility::intExplode(',', $this->settings['file_collections'], true);
        foreach ($collectionUids as $uid) {
            $collection = $fileCollectionRepository->findByUid($uid);
            if ($collection !== null) {
                $collection->loadContents();
                /** @var \TYPO3\CMS\Core\Resource\File[] $collectionFiles */
                $collectionFiles = $collection->getItems();
                if ($folder === null) {
                    $files += $collectionFiles;
                } else {
                    foreach ($collectionFiles as $file) {
                        if ($file->getStorage() === $folder->getStorage()) {
                            // TODO: Check if this is the correct way to filter out files with non-local storages
                            if (GeneralUtility::isFirstPartOfStr($file->getIdentifier(), $folder->getIdentifier())) {
                                $files[] = $file;
                            }
                        }
                    }
                }
            }
        }
    }

}
