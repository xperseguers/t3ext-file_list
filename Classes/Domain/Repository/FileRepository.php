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

namespace Causal\FileList\Domain\Repository;

use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Resource\Folder;

/**
 * File repository.
 *
 * @category    Domain\Repository
 * @package     TYPO3
 * @subpackage  tx_filelist
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class FileRepository
{

    /**
     * @var \TYPO3\CMS\Core\Resource\StorageRepository
     */
    protected $storageRepository;

    /**
     * @param \TYPO3\CMS\Core\Resource\StorageRepository $storageRepository
     * @return void
     */
    public function injectStorageRepository(StorageRepository $storageRepository)
    {
        $this->storageRepository = $storageRepository;
    }

    /**
     * Returns a folder object.
     *
     * @param string $identifier Identifier of the form "file:<uid>:<path>"
     * @return Folder
     * @throws \InvalidArgumentException|\TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     */
    public function getFolderByIdentifier($identifier)
    {
        $folder = null;

        if (preg_match('/^file:(\d+):(.*)$/', $identifier, $matches)) {
            $storageUid = (int)$matches[1];
            $identifier = $matches[2];

            $storage = $this->storageRepository->findByUid($storageUid);
            $folder = $storage->getFolder($identifier);
        } else {
            throw new \InvalidArgumentException(__METHOD__ . '() expects a FAL identifier. Input was: ' . $identifier, 1451923517);
        }

        return $folder;
    }

}
