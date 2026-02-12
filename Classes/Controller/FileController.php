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

use Causal\FalProtect\Utility\AccessSecurity;
use Causal\FileList\Domain\Repository\FileRepository;
use Causal\FileList\Utility\Helper;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\FileCollectionRepository;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * File controller.
 *
 * @category    Controller
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   Causal Sàrl
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class FileController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    const SORT_BY_NAME = 'NAME';
    const SORT_BY_TITLE = 'TITLE';
    const SORT_BY_DESCRIPTION = 'DESCRIPTION';
    const SORT_BY_DATE = 'DATE';
    const SORT_BY_CRDATE = 'CRDATE';
    const SORT_BY_SIZE = 'SIZE';
    const SORT_NONE = 'NONE';

    const SORT_DIRECTION_ASC = 'ASC';
    const SORT_DIRECTION_DESC = 'DESC';

    /**
     * @var FileRepository
     */
    protected $fileRepository;

    /**
     * @var \TYPO3\CMS\Core\TypoScript\TypoScriptService
     */
    protected $typoScriptService;

    /**
     * @param \Causal\FileList\Domain\Repository\FileRepository $fileRepository
     */
    public function injectFileRepository(FileRepository $fileRepository): void
    {
        $this->fileRepository = $fileRepository;
    }

    /**
     * @param \TYPO3\CMS\Core\TypoScript\TypoScriptService $typoScriptService
     */
    public function inject(\TYPO3\CMS\Core\TypoScript\TypoScriptService $typoScriptService): void
    {
        $this->typoScriptService = $typoScriptService;
    }

    /**
     * Handles stdWrap on various settings.
     */
    public function initializeAction(): void
    {
        $settings = $this->convertPlainArrayToTypoScriptArray($this->settings);
        $contentObject = $this->getContentObject();
        $keys = ['path', 'dateFormat', 'fileIconRootPath', 'newDurationMaxSubfolders'];

        foreach ($keys as $key) {
            if (isset($settings[$key . '.'])) {
                $this->settings[$key] = $contentObject->stdWrap($settings[$key] ?? '', $settings[$key . '.']);
            }
        }

        // Special handling for "root" which may be either a string/stdWrap or an array
        if (isset($settings['root.'])) {
            if (!empty($settings['root'])) {
                // This is a stdWrap
                $this->settings['root'] = $contentObject->stdWrap($settings['root'], $settings['root.']);
            } else {
                // Apply stdWrap on the subarray
                $this->settings['root'] = [];

                foreach ($settings['root.'] as $key => $root) {
                    if (substr($key, -1) !== '.') {
                        if (!isset($settings['root.'][$key . '.'])) {
                            $this->settings['root'][] = $root;
                        }
                        continue;
                    }
                    $value = $settings['root.'][substr($key, 0, -1)] ?? '';
                    $value = $contentObject->stdWrap($value, $settings['root.'][$key]);
                    $this->settings['root'][] = $value;
                }
            }
        }
        if (!is_array($this->settings['root'] ?? null)) {
            $root = $this->settings['root'] ?? null;
            $this->settings['root'] = [];
            if (!empty($root)) {
                $this->settings['root'][] = $root;
            }
        }
        foreach ($this->settings['root'] as $key => $value) {
            $this->settings['root'][$key] = $this->canonicalizeAndCheckFolderIdentifier($value);
        }
    }

    /**
     * Listing of files.
     *
     * @param string $path Optional path of the subdirectory to be listed
     */
    public function listAction(string $path = '')
    {
        $files = [];
        $subfolders = [];
        $parentFolder = null;
        $breadcrumb = [];

        try {
            $this->checkConfiguration();

            // Sanitize requested path
            if ($path === '/') {
                $path = '';
            }
            if (!empty($path)) {
                if ($path[0] !== '/') {
                    $rootFolder = $this->fileRepository->getFolderByIdentifier($this->settings['path'] ?? '');
                    if ($rootFolder !== null) {
                        $path = $this->getRootPrefix($rootFolder) . '/' . $path;
                    }
                }
                $path = $this->canonicalizeAndCheckFolderIdentifier($path);
            }

            // Collect files and folders to be shown
            switch ($this->settings['mode'] ?? null) {
                case 'FOLDER':
                    $this->populateFromFolder($path, $files, $subfolders, $parentFolder, $breadcrumb);
                    break;

                case 'FILE_COLLECTIONS':
                    $this->populateFromFileCollections($files);
                    break;
            }
        } catch (\Throwable $e) {
            $errorMessage = $this->error('The configuration of the file_list plugin is incorrect: ' . $e->getMessage());
            return new HtmlResponse($errorMessage);
        }

        // Filter files
        $files = Helper::filterInaccessibleFiles($files);

        // Sort files and folders
        $files = $this->sortFiles($files);
        $subfolders = $this->sortFolders($subfolders);

        // Mark folders as "new" if needed
        // BEWARE: This needs to be done at the end since it is using an internal method which
        //         may break other operations such as sorting
        /** @var Context $context */
        $context = GeneralUtility::makeInstance(Context::class);
        $currentTimestamp = $context->getPropertyFromAspect('date', 'timestamp');
        $newDuration = (int)($this->settings['newDuration'] ?? 0);
        $newTimestamp = $currentTimestamp - 86400 * $newDuration;
        if ($newDuration) {
            $newDurationMaxSubfolders = max(0, (int)($this->settings['newDurationMaxSubfolders'] ?? 0));
            foreach ($subfolders as &$folder) {
                /** @var \Causal\FileList\Domain\Model\Folder $folder */
                if ($folder->hasFileNewerThan($newTimestamp, $newDurationMaxSubfolders)) {
                    $properties = $folder->getProperties();
                    $properties['tx_filelist']['isNew'] = true;
                    $folder->updateProperties($properties);
                }
            }
        }

        if (class_exists(Context::class)) {
            $context = GeneralUtility::makeInstance(Context::class);
            $feUserUid = $context->getPropertyFromAspect('frontend.user', 'id');
        } else {
            $feUserUid = $GLOBALS['TSFE']->fe_user->user['uid'] ?? 0;
        }

        $this->view->assignMultiple([
            'isAuthenticated' => $feUserUid > 0,
            'isEmpty' => $parentFolder === null && empty($subfolders) && empty($files),
            'breadcrumb' => $breadcrumb,
            'parent' => $parentFolder,
            'folders' => $subfolders,
            'files' => $files,
            'newTimestamp' => $newTimestamp,
            'data' => $this->getContentObject()->data,
        ]);

        return $this->htmlResponse();
    }

    /**
     * Canonicalizes and (basically) checks a FAL folder identifier.
     *
     * @param string $identifier
     * @return string
     */
    protected function canonicalizeAndCheckFolderIdentifier(string $identifier): string
    {
        $prefix = '';

        // New format since TYPO3 v8
        if (preg_match('#^t3://#', $identifier)) {
            /** @var LinkService $linkService */
            $linkService = GeneralUtility::makeInstance(LinkService::class);
            $data = $linkService->resolveByStringRepresentation($identifier);
            if ($data['type'] === 'folder' && $data['folder'] !== null) {
                /** @var \TYPO3\CMS\Core\Resource\Folder $folder */
                $folder = $data['folder'];
                $identifier = 'file:' . $folder->getCombinedIdentifier();
            }
        }

        // New format in FlexForm since TYPO3 v12
        if (preg_match('/^(\d+):(.*)$/', $identifier, $matches)) {
            $identifier = 'file:' . $identifier;
        }

        if (preg_match('/^file:(\d+):(.*)$/', $identifier, $matches)) {
            $prefix = 'file:' . $matches[1] . ':';
            $identifier = $matches[2];
        }

        $identifier = PathUtility::getCanonicalPath($identifier);
        $identifier = $prefix . rtrim($identifier, '/') . '/';

        return $identifier;
    }

    /**
     * Checks plugin configuration and security settings.
     *
     * @throws \RuntimeException
     */
    protected function checkConfiguration(): void
    {
        // Sanitize configuration
        if (!empty($this->settings['path'])) {
            $this->settings['path'] = $this->canonicalizeAndCheckFolderIdentifier($this->settings['path']);
        }

        // Security check
        if (!empty($this->settings['path']) && !$this->isWithinRoot($this->settings['path'])) {
            throw new \RuntimeException(sprintf('Could not open directory "%s"', $this->settings['path']), 1452143787);
        }
    }

    /**
     * Returns true if $path is within the allowed root.
     *
     * @param string $path
     * @return bool
     */
    protected function isWithinRoot(string $path): bool
    {
        // No root defined: true by definition, if not, we have to check each allowed root
        $success = empty($this->settings['root']);

        foreach ($this->settings['root'] as $root) {
            if (PHP_VERSION_ID >= 80000) {
                $success |= str_starts_with($path, $root);
            } else {
                $success |= GeneralUtility::isFirstPartOfStr($path, $root);
            }

            if ($success) {
                break;
            }
        }

        return $success;
    }

    /**
     * Populates files and folders from a configured root path.
     *
     * @param string $path Optional subpath of $this->settings['path']
     * @param \TYPO3\CMS\Core\Resource\File[] &$files
     * @param \Causal\FileList\Domain\Model\Folder[] &$subfolders
     * @param \Causal\FileList\Domain\Model\Folder|null &$parentFolder
     * @param array &$breadcrumb
     * @throws \InvalidArgumentException|\TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     */
    protected function populateFromFolder(
        string $path,
        array  &$files,
        array  &$subfolders,
        ?\Causal\FileList\Domain\Model\Folder &$parentFolder = null,
        array  &$breadcrumb = []
    ): void
    {
        $includeSubfolder = (bool)($this->settings['includeSubfolders'] ?? false);
        if (!$includeSubfolder) {
            // No way!
            $path = '';
        }

        $rootFolder = $this->fileRepository->getFolderByIdentifier($this->settings['path'] ?? '');
        if ($rootFolder === null) {
            return;
        }
        $rootPrefix = $this->getRootPrefix($rootFolder);

        $folder = null;
        if (!empty($path) && preg_match('/^file:(\d+):(.*)$/', $this->settings['path'] ?? '', $matches)) {
            $storageUid = (int)$matches[1];
            $rootIdentifier = $matches[2];
            // Security check before blindly accepting the requested folder's content
            if ($path === $rootIdentifier) {
                $path = '';
            }
            if (PHP_VERSION_ID >= 80000) {
                $isFirstPartOfStr = str_starts_with($path, $rootIdentifier);
            } else {
                $isFirstPartOfStr = GeneralUtility::isFirstPartOfStr($path, $rootIdentifier);
            }
            if (!empty($path) && $isFirstPartOfStr) {
                $identifier = 'file:' . $storageUid . ':' . $path;
                $folder = $this->fileRepository->getFolderByIdentifier($identifier);
            } else {
                $path = '';
            }
        }
        if ($folder === null) {
            $folder = $rootFolder;
        }

        // Retrieve the list of files
        $files = $folder->getFiles();

        // In a subfolder, so retrieve parent folder
        if (!empty($path)) {
            $parentFolder = Helper::cast($folder->getParentFolder(), \Causal\FileList\Domain\Model\Folder::class);
            $properties = $parentFolder->getProperties();
            $relativeIdentifier = $rootPrefix === null
                ? $parentFolder->getIdentifier()
                : substr($parentFolder->getIdentifier(), strlen($rootPrefix) + 1);
            $properties['identifier'] = $relativeIdentifier ?: '/';    // Relative to root folder and without leading slash
            $parentFolder->updateProperties($properties);
        }

        if ($includeSubfolder) {
            $hasFalProtect = ExtensionManagementUtility::isLoaded('fal_protect');
            $tempSubfolders = $folder->getSubfolders();
            foreach ($tempSubfolders as $subfolder) {
                switch ($subfolder->getRole()) {
                    case FolderInterface::ROLE_RECYCLER:
                    case FolderInterface::ROLE_PROCESSING:
                    case FolderInterface::ROLE_TEMPORARY:
                        // Special folder, should not be shown in Frontend
                        break;
                    default:
                        if (!$hasFalProtect || AccessSecurity::isFolderAccessible($subfolder)) {
                            $cFolder = Helper::cast($subfolder, \Causal\FileList\Domain\Model\Folder::class);
                            $properties = $cFolder->getProperties();
                            $relativeIdentifier = $rootPrefix === null
                                ? $subfolder->getIdentifier()
                                : substr($subfolder->getIdentifier(), strlen($rootPrefix) + 1);
                            $properties['identifier'] = $relativeIdentifier;    // Relative to root folder and without leading slash
                            $cFolder->updateProperties($properties);
                            $subfolders[] = $cFolder;
                        }
                }
            }

            // Prepare the breadcrumb data
            if (!empty($subfolders) || !empty($path)) {
                $f = $folder;
                while ($this->settings['path'] !== 'file:' . $f->getCombinedIdentifier()) {
                    array_unshift($breadcrumb, ['folder' => $f]);
                    $f = $f->getParentFolder();
                }
                array_unshift($breadcrumb, ['folder' => $rootFolder, 'isRoot' => true]);
                $breadcrumb[count($breadcrumb) - 1]['state'] = 'active';
            }
        }

        // Tag the page cache so that FAL signal operations may be listened to in
        // order to flush corresponding page cache
        $cacheTags = [
            'tx_filelist_folder_' . $folder->getHashedIdentifier(),
        ];
        $typo3Version = (new Typo3Version())->getMajorVersion();
        if ($typo3Version >= 12) {
            $cacheDataCollector = $this->request->getAttribute('frontend.cache.collector');
            $cacheTags = array_map(fn(string $cacheTag) => new CacheTag($cacheTag), $cacheTags);
            $cacheDataCollector->addCacheTags(...$cacheTags);
        } else {
            $GLOBALS['TSFE']->addCacheTags($cacheTags);
        }
    }

    /**
     * Populates files from a list of file collections.
     *
     * @param \TYPO3\CMS\Core\Resource\File[] $files
     * @throws \InvalidArgumentException|\TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     */
    protected function populateFromFileCollections(array &$files): void
    {
        $folders = [];
        $fileCollectionRepository = GeneralUtility::makeInstance(FileCollectionRepository::class);

        if (!empty($this->settings['path'])) {
            // Returned files needs to be within a given root path
            $folders[] = $this->fileRepository->getFolderByIdentifier($this->settings['path']);
        } else {
            foreach ($this->settings['root'] as $root) {
                $folders[] = $this->fileRepository->getFolderByIdentifier($root);
            }
        }

        $collectionUids = GeneralUtility::intExplode(',', $this->settings['file_collections'] ?? '', true);
        foreach ($collectionUids as $uid) {
            $collection = $fileCollectionRepository->findByUid($uid);
            if ($collection !== null) {
                $collection->loadContents();
                /** @var \TYPO3\CMS\Core\Resource\File[] $collectionFiles */
                $collectionFiles = $collection->getItems();
                if (empty($folders)) {
                    $files = array_merge($files, $collectionFiles);
                } else {
                    foreach ($collectionFiles as $file) {
                        $success = false;
                        foreach ($folders as $folder) {
                            if ($file->getStorage() === $folder->getStorage()) {
                                // TODO: Check if this is the correct way to filter out files with non-local storages
                                if (PHP_VERSION_ID >= 80000) {
                                    $isFirstPartOfStr = str_starts_with($file->getIdentifier(), $folder->getIdentifier());
                                } else {
                                    $isFirstPartOfStr = GeneralUtility::isFirstPartOfStr($file->getIdentifier(), $folder->getIdentifier());
                                }
                                if ($isFirstPartOfStr) {
                                    $success = true;
                                    break;
                                }
                            }
                        }
                        if ($success) {
                            $files[] = $file;
                        }
                    }
                }
            }
        }
    }

    /**
     * Sorts files.
     *
     * @param \TYPO3\CMS\Core\Resource\File[] $files
     * @return \TYPO3\CMS\Core\Resource\File[]
     */
    protected function sortFiles(array $files): array
    {
        $orderedFiles = [];
        $isNumericSorting = false;

        foreach ($files as $file) {
            switch ($this->settings['orderBy']) {
                case static::SORT_BY_DATE:
                    $date = 0;
                    if ($file->hasProperty('content_modification_date')) {
                        $date = $file->getProperty('content_modification_date');
                    }
                    if ($date === 0) {
                        // It's fair enough to consider content_modification_date is unlikely to be 1970-01-01 at midnight
                        $date = $file->getProperty('modification_date');
                    }
                    $key = sprintf('%010d', $date);
                    break;
                case static::SORT_BY_CRDATE:
                    $date = 0;
                    if ($file->hasProperty('content_creation_date')) {
                        $date = $file->getProperty('content_creation_date');
                    }
                    if ($date === 0) {
                        // It's fair enough to consider content_creation_date is unlikely to be 1970-01-01 at midnight
                        $date = $file->getProperty('creation_date');
                    }
                    $key = sprintf('%010d', $date);
                    break;
                case static::SORT_BY_TITLE:
                    $key = $file->getProperty('title');
                    if (empty($key)) {
                        // Fall-back onto file name
                        $key = $file->getName();
                    }
                    break;
                case static::SORT_BY_DESCRIPTION:
                    $key = $file->getProperty('description');
                    if (empty($key)) {
                        // Fall-back onto file name
                        $key = $file->getName();
                    }
                    break;
                case static::SORT_BY_SIZE:
                    $key = $file->getSize();
                    $isNumericSorting = true;
                    break;
                case static::SORT_NONE:
                    $key = '';
                    break;
                case static::SORT_BY_NAME:
                default:
                    $key = $file->getName();
                    break;
            }
            if (!$key) {
                $orderedFiles[] = $file;
            } else {
                $key .= "\t" . $file->getUid();
                $orderedFiles[strtolower($key)] = $file;
            }
        }

        if (($this->settings['sortDirection'] ?? '') === static::SORT_DIRECTION_ASC) {
            if ($isNumericSorting) {
                $this->natksort($orderedFiles);
            } else {
                ksort($orderedFiles);
            }
        } else {
            if ($isNumericSorting) {
                $this->natksort($orderedFiles, true);
            } else {
                krsort($orderedFiles);
            }
        }

        return $orderedFiles;
    }

    /**
     * Sorts an array by key using a "natural order" algorithm
     * @link http://php.net/manual/en/function.natsort.php
     *
     * @param array $array
     * @param bool $reverse If true then the keys will be sorted in reverse order
     * @return bool true on success or false on failure.
     */
    protected function natksort(array &$array, bool $reverse = false): bool
    {
        // Like ksort but uses natural sort instead
        $keys = array_keys($array);
        natsort($keys);

        if ($reverse) {
            $keys = array_reverse($keys);
        }

        foreach ($keys as $k) {
            $new_array[$k] = $array[$k];
        }

        $array = $new_array;
        return true;
    }

    /**
     * Sorts folders.
     *
     * @param Folder[] $folders Array of folders, keys are the names of the corresponding folders
     * @return Folder[]
     */
    protected function sortFolders(array $folders): array
    {
        if (($this->settings['sortDirection'] ?? '') === static::SORT_DIRECTION_DESC
            && in_array($this->settings['orderBy'] ?? '', [static::SORT_BY_NAME, static::SORT_BY_TITLE], true)
        ) {
            krsort($folders);
        } else {
            ksort($folders);
        }

        return $folders;
    }

    /**
     * Returns an error message for frontend output.
     *
     * @param string $string Error message input
     * @return string
     */
    protected function error(string $string): string
    {
        return '
			<!-- ' . __CLASS__ . ' ERROR message: -->
			<div style="
					border: 2px red solid;
					background-color: yellow;
					color: black;
					text-align: center;
					padding: 20px 20px 20px 20px;
					margin: 20px 20px 20px 20px;
					">' .
            '<strong>' . __CLASS__ . ' ERROR:</strong><br /><br />' . nl2br(htmlspecialchars(trim($string))) .
            '</div>';
    }

    /**
     * Returns an array with Typoscript the old way (with dot).
     *
     * Extbase converts the "classical" TypoScript (with trailing dot) to a format without trailing dot,
     * to be more future-proof and not to have any conflicts with Fluid object accessor syntax.
     * However, if you want to call legacy TypoScript objects, you somehow need the "old" syntax (because this is what TYPO3 is used to).
     * With this method, you can convert the extbase TypoScript to classical TYPO3 TypoScript which is understood by the rest of TYPO3.
     *
     * @param array $plainArray An TypoScript Array with Extbase Syntax (without dot but with _typoScriptNodeValue)
     * @return array array with TypoScript as usual (with dot)
     * @see \TYPO3\CMS\Core\TypoScript\TypoScriptService::convertPlainArrayToTypoScriptArray() in TYPO3 v8
     */
    protected function convertPlainArrayToTypoScriptArray(array $plainArray): array
    {
        $typoScriptArray = [];
        foreach ($plainArray as $key => $value) {
            if (is_array($value)) {
                if (isset($value['_typoScriptNodeValue'])) {
                    $typoScriptArray[$key] = $value['_typoScriptNodeValue'];
                    unset($value['_typoScriptNodeValue']);
                }
                $typoScriptArray[$key . '.'] = $this->convertPlainArrayToTypoScriptArray($value);
            } else {
                $typoScriptArray[$key] = $value ?? '';
            }
        }
        return $typoScriptArray;
    }

    protected function getContentObject(): ContentObjectRenderer
    {
        $typo3Version = (new Typo3Version())->getMajorVersion();
        if ($typo3Version >= 12) {
            return $this->request->getAttribute('currentContentObject');
        } else {
            return $this->configurationManager->getContentObject();
        }
    }

    protected function getRootPrefix(Folder $rootFolder): ?string
    {
        return $rootFolder->getStorage()->getDriverType() === 'Local'
            ? rtrim($rootFolder->getIdentifier(), '/')
            : null;
    }
}
