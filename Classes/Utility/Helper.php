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

use Causal\FalProtect\Utility\AccessSecurity;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper class for the 'file_list' extension.
 *
 * @category    Utility
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class Helper
{

    /**
     * Casts an object.
     *
     * @param object $object
     * @param string $toClass
     * @return bool|object
     */
    public static function cast($object, string $toClass)
    {
        if ($toClass === \Causal\FileList\Domain\Model\Folder::class) {
            return GeneralUtility::makeInstance($toClass, $object->getStorage(), $object->getIdentifier(), $object->getName());
        }
        if (class_exists($toClass)) {
            $objIn = serialize($object);
            $classIn = get_class($object);
            $prefixChars = strlen((string)strlen($classIn)) + 6 + strlen($classIn);
            $objOut = 'O:' . strlen($toClass) . ':"' . $toClass . '":' . substr($objIn, $prefixChars);
            return unserialize($objOut);
        }
        return false;
    }

    /**
     * Filters out inaccessible files for the current Frontend user.
     *
     * @param \TYPO3\CMS\Core\Resource\File[] $files
     * @return \TYPO3\CMS\Core\Resource\File[]
     */
    public static function filterInaccessibleFiles(array $files): array
    {

        if (class_exists(ApplicationType::class)) {
            if (ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend() === false) {
                return $files;
            }
        } else {
            if (TYPO3_MODE !== 'FE') {
                return $files;
            }
        }

        $filteredFiles = [];

        if (ExtensionManagementUtility::isLoaded('fal_protect')) {
            // Enhanced support of file protection when using EXT:fal_protect
            // See: https://extensions.typo3.org/extension/fal_protect/
            foreach ($files as $file) {
                if (AccessSecurity::isFileAccessible($file)) {
                    $filteredFiles[] = $file;
                }
            }
        } else {
            if (class_exists(Context::class)) {
                $context = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class);
                $groupIds = $context->getPropertyFromAspect('frontend.user', 'groupIds');
                $userGroups = GeneralUtility::intExplode(',', $groupIds, true);
            } else {
                $userGroups = GeneralUtility::intExplode(',', $GLOBALS['TSFE']->gr_list, true);
            }

            foreach ($files as $file) {
                $isVisible = $file->hasProperty('visible') ? (bool)$file->getProperty('visible') : true;
                if (!$isVisible) {
                    continue;
                }

                $accessGroups = $file->getProperty('fe_groups');
                if (!empty($accessGroups)) {
                    $accessGroups = GeneralUtility::intExplode(',', $accessGroups, true);
                    if (empty(array_intersect($accessGroups, $userGroups))) {
                        continue;
                    }
                }

                $filteredFiles[] = $file;
            }
        }

        return $filteredFiles;
    }
}
