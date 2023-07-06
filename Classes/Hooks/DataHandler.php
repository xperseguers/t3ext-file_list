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

namespace Causal\FileList\Hooks;

use Causal\FileList\EventListener\CoreResourceStorageEventListener;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hooks into \TYPO3\CMS\Core\DataHandling\DataHandler.
 *
 * @category    Hooks
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class DataHandler
{
    /**
     * Hooks into \TYPO3\CMS\Core\DataHandling\DataHandler after records have been saved to the database.
     *
     * @param string $operation
     * @param string $table
     * @param mixed $uid
     * @param array $fields
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObject
     */
    public function processDatamap_afterDatabaseOperations($operation, $table, $id, array $fields, \TYPO3\CMS\Core\DataHandling\DataHandler $parentObject)
    {
        if ($table === 'sys_file_metadata' && !$parentObject->isImporting) {
            if (!is_numeric($id)) {
                $id = $parentObject->substNEWwithIDs[$id];
            }

            $row = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_file_metadata')
                ->select(
                    ['file'],
                    'sys_file_metadata',
                    [
                        'uid' => (int)$id,
                    ]
                )
                ->fetch();

            $file = GeneralUtility::makeInstance(FileRepository::class)->findByUid($row['file']);

            /** @var CoreResourceStorageEventListener $coreResourceStorageEventListener */
            $coreResourceStorageEventListener = GeneralUtility::makeInstance(CoreResourceStorageEventListener::class);
            $coreResourceStorageEventListener->flushCachesByFolder($file->getParentFolder());
        }
    }
}
