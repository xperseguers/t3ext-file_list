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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (version_compare(TYPO3_branch, '8', '>=')) {
    include(ExtensionManagementUtility::extPath('file_list') . 'Resources/Private/Php/ThumbnailViewHelper.v8.php');
} else {
    include(ExtensionManagementUtility::extPath('file_list') . 'Resources/Private/Php/ThumbnailViewHelper.v7.php');
}
