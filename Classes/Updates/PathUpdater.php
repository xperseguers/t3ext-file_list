<?php
declare(strict_types = 1);

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

namespace Causal\FileList\Updates;

use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('fileList_pathUpdater')]
class PathUpdater implements UpgradeWizardInterface
{
    public function getTitle(): string
    {
        return 'EXT:file_list: Migrate path definition since TYPO3 v12';
    }

    public function getDescription(): string
    {
        return 'Migrates the file_list plugins to the new native path format.';
    }

    public function executeUpdate(): bool
    {
        $queryBuilder = $this->getTtContentQueryBuilder();
        $tableConnection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content');
        $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);

        $rows = $queryBuilder
            ->select('*')
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($rows as $row) {
            $flexFormData = GeneralUtility::xml2array($row['pi_flexform']);
            $path = &$flexFormData['data']['sDEF']['lDEF']['settings.path']['vDEF'] ?? '';
            if (preg_match('#^t3://folder\?storage=(\d+)&identifier=(.*)$#', $path, $matches)) {
                $path = $matches[1] . ':' . urldecode($matches[2]);
                $newFlexFormData = $flexFormTools->flexArray2Xml($flexFormData, true);

                $tableConnection->update(
                    'tt_content',
                    [
                        'pi_flexform' => $newFlexFormData,
                    ],
                    [
                        'uid' => $row['uid'],
                    ]
                );
            }
        }

        return true;
    }

    public function updateNecessary(): bool
    {
        $ttContentQueryBuilder = $this->getTtContentQueryBuilder();

        return $ttContentQueryBuilder
            ->count('*')
            ->executeQuery()
            ->fetchOne() > 0;
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    protected function getTtContentQueryBuilder(): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction());

        $queryBuilder
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->quote('filelist_filelist')),
                $queryBuilder->expr()->like('pi_flexform', $queryBuilder->quote('%<value index="vDEF">t3://folder?storage=%'))
            );

        return $queryBuilder;
    }
}
