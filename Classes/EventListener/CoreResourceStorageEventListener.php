<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

declare(strict_types=1);

namespace Causal\FileList\EventListener;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Resource\Event\AfterFileAddedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileContentsSetEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileCopiedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileCreatedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileDeletedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileMovedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileRenamedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileReplacedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFolderAddedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFolderCopiedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFolderDeletedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFolderMovedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFolderRenamedEvent;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CoreResourceStorageEventListener
{

    /**
     * @var CacheManager
     */
    protected $pageCache;

    /**
     * CoreResourceStorageEventListener constructor.
     *
     * @param CacheManager $cacheManager
     */
    public function __construct(CacheManager $cacheManager = null)
    {
        if ($cacheManager === null) {
            // We are before TYPO3 v10 where DI is taken care of
            $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        }
        $this->pageCache = $cacheManager;
    }

    /**
     * A file has been added.
     *
     * @param AfterFileAddedEvent $event
     */
    public function afterFileAdded(AfterFileAddedEvent $event): void
    {
        $this->flushCachesByFolder($event->getFolder());
    }

    /**
     * A file has been copied.
     *
     * @param AfterFileCopiedEvent $event
     */
    public function afterFileCopied(AfterFileCopiedEvent $event): void
    {
        $this->flushCachesByFolder($event->getFolder());
    }

    /**
     * A file has been moved.
     *
     * @param AfterFileMovedEvent $event
     */
    public function afterFileMoved(AfterFileMovedEvent $event): void
    {
        $this->flushCachesByFolder($event->getFolder());
        $this->flushCachesByFolder($event->getOriginalFolder());
    }

    /**
     * A file has been renamed.
     *
     * @param AfterFileRenamedEvent $event
     */
    public function afterFileRenamed(AfterFileRenamedEvent $event): void
    {
        $this->flushCachesByFolder($event->getFile()->getParentFolder());
    }

    /**
     * A file has been added as a *replacement* of an existing one.
     *
     * @param AfterFileReplacedEvent $event
     */
    public function afterFileReplaced(AfterFileReplacedEvent $event): void
    {
        $this->flushCachesByFolder($event->getFile()->getParentFolder());
    }

    /**
     * A file has been created.
     *
     * @param AfterFileCreatedEvent $event
     */
    public function afterFileCreated(AfterFileCreatedEvent $event): void
    {
        $this->flushCachesByFolder($event->getFolder());
    }

    /**
     * A file has been deleted.
     *
     * @param AfterFileDeletedEvent $event
     */
    public function afterFileDeleted(AfterFileDeletedEvent $event): void
    {
        try {
            $this->flushCachesByFolder($event->getFile()->getParentFolder());
        } catch (\Exception $e) {
            // Exception may happen when a file is moved to /_recycler_/ but the user has no access to it
        }
    }

    /**
     * Contents of a file has been set.
     *
     * @param AfterFileContentsSetEvent $event
     */
    public function afterFileContentsSet(AfterFileContentsSetEvent $event): void
    {
        $this->flushCachesByFolder($event->getFile()->getParentFolder());
    }

    /**
     * A folder has been added.
     *
     * @param AfterFolderAddedEvent $event
     */
    public function afterFolderAdded(AfterFolderAddedEvent $event): void
    {
        $this->flushCachesByFolder($event->getFolder()->getParentFolder());
    }

    /**
     * A folder has been copied.
     *
     * @param AfterFolderCopiedEvent $event
     */
    public function afterFolderCopied(AfterFolderCopiedEvent $event): void
    {
        $this->flushCachesByFolder($event->getFolder());
        $this->flushCachesByFolder($event->getTargetFolder()->getParentFolder());
    }

    /**
     * A folder has been moved.
     *
     * @param AfterFolderMovedEvent $event
     */
    public function afterFolderMoved(AfterFolderMovedEvent $event): void
    {
        $this->flushCachesByFolder($event->getFolder());
        $this->flushCachesByFolder($event->getTargetFolder());
    }

    /**
     * A folder has been renamed.
     *
     * @param AfterFolderRenamedEvent $event
     */
    public function afterFolderRenamed(AfterFolderRenamedEvent $event): void
    {
        $this->flushCachesByFolder($event->getFolder());
        $this->flushCachesByFolder($event->getFolder()->getParentFolder());
    }

    /**
     * A folder has been deleted.
     *
     * @param AfterFolderDeletedEvent $event
     */
    public function afterFolderDeleted(AfterFolderDeletedEvent $event): void
    {
        $this->flushCachesByFolder($event->getFolder());
        $this->flushCachesByFolder($event->getFolder()->getParentFolder());
    }

    /**
     * Flushes caches by folder, using tags set by \Causal\FileList\Controller\FileController.
     *
     * @param FolderInterface $folder
     * @internal
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
