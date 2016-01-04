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
        switch ($this->settings['mode']) {
            case 'FOLDER':
                $folder = null;
                if (!empty($path) && preg_match('/^file:(\d+):(.*)$/', $this->settings['path'], $matches)) {
                    $storageUid = (int)$matches[1];
                    $rootIdentifier = $matches[2];
                    // Security check before blindly accepting the requested folder's content
                    if (GeneralUtility::isFirstPartOfStr($path, $rootIdentifier)) {
                        $identifier = 'file:' . $storageUid . ':' . $path;
                        $folder = $this->fileRepository->getFolderByIdentifier($identifier);
                    }
                }
                if ($folder === null) {
                    $folder = $this->fileRepository->getFolderByIdentifier($this->settings['path']);
                }

                $parentFolder = !empty($path) ? $folder->getParentFolder() : null;
                $subfolders = $folder->getSubfolders();
                ksort($subfolders);
                $files = $folder->getFiles();
                break;

            case 'FILE_COLLECTIONS':
                throw new \RuntimeException('Mode "FILE_COLLECTIONS" is not yet implemented', 1451922432);

            case 'CATEGORIES':
                throw new \RuntimeException('Mode "CATEGORIES" is not yet implemented', 1451922447);
        }

        $this->view->assignMultiple([
            'isEmpty' => empty($parentFolder) && empty($subfolders) && empty($files),
            'parent' => $parentFolder,
            'folders' => $subfolders,
            'files' => $files,
        ]);
    }

}
