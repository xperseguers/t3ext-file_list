<?php
declare(strict_types=1);

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

namespace Causal\FileList\ViewHelpers\Folder;

use Causal\FileList\Domain\Model\Folder;
use Causal\FileList\Utility\Helper;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class LastModificationDateViewHelper extends AbstractViewHelper
{
    /**
     * Initialize arguments.
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('folder', Folder::class, 'Folder', true);
    }

    /**
     * Renders the last modification date of the supplied folder.
     *
     * @return int
     */
    public function render(): int
    {
        /** @var Folder $folder */
        $folder = $this->arguments['folder'];

        $lastModification = PHP_INT_MIN;

        $allFiles = $folder->getFiles(0, 0, Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, true);
        $allFiles = Helper::filterInaccessibleFiles($allFiles);

        foreach ($allFiles as $file) {
            $modificationDate = 0;
            if ($file->hasProperty('content_modification_date')) {
                $modificationDate = $file->getProperty('content_modification_date');
            }
            if ($modificationDate === 0) {
                // It's fair enough to consider content_modification_date is unlikely to be 1970-01-01 at midnight
                $modificationDate = $file->getProperty('modification_date');
            }
            if ($modificationDate > $lastModification) {
                $lastModification = $modificationDate;
            }
        }

        // TODO: We may possibly need to consider subfolders and add them as
        //       cache tags for the page, as we do and the end of
        //       \Causal\FileList\Controller\FileController::populateFromFolder()

        return $lastModification !== PHP_INT_MIN ? $lastModification : 0;
    }
}