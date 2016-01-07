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

namespace Causal\FileList;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Update class for the 'file_list' extension.
 *
 * @package     TYPO3
 * @subpackage  tx_filelist
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class ext_update extends \TYPO3\CMS\Backend\Module\BaseScriptClass
{

    /**
     * Checks whether the "UPDATE!" menu item should be shown. If no old configuration (in
     * dedicated fields of tt_content) is found, should return false; otherwise true.
     *
     * @return bool
     */
    public function access()
    {
        $showUpgradeWizard = false;

        $settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['file_list']);
        if (!isset($settings['enableLegacyPlugin']) || (bool)$settings['enableLegacyPlugin']) {
            $legacyPlugins = $this->getLegacyPlugins(true);
            $showUpgradeWizard = count($legacyPlugins['canUpgrade']) + count($legacyPlugins['cannotUpgrade']) > 0;
        }

        return $showUpgradeWizard;
    }

    /**
     * Main method that is called whenever UPDATE! menu was clicked. This method outputs a
     * HTML form to update the configuration of all plugins of the website.
     *
     * @return string HTML to display
     */
    public function main()
    {
        $this->content = '';

        $this->doc = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);
        $this->doc->backPath = $GLOBALS['BACK_PATH'];
        $this->doc->form = '<form action="" method="post">';

        $title = 'File List Plugin Configuration';
        $this->content .= $this->doc->startPage($title);
        $this->content .= $this->doc->header($title);

        $this->content .= $this->doc->section('',
            'This extension has been rewritten from scratch using Extbase and Fluid-based templates.  ' .
            'This upgrade wizard detected that you still use the legacy plugin.  Although this is perfectly ' .
            'fine for the time being, you should stop using it and switch to the new plugin.  ' .
            'This form allows you to upgrade your existing file_list plugins to use the new ' .
            'modern plugin.'
        );

        if (GeneralUtility::_GET('upgrade')) {
            $this->content .= $this->upgrade();
        } else {
            $this->content .= $this->prepareUpgrade();
        }

        return $this->content;
    }

    /**
     * Prepares the upgrade process.
     *
     * @return string
     */
    protected function prepareUpgrade()
    {
        $legacyPlugins = $this->getLegacyPlugins();

        $content = [];
        $content[] = '<h4>Automatic upgrade</h4>';

        $upgradeLink = GeneralUtility::linkThisScript(array('upgrade' => 1));
        $content[] = '<p><strong>' . count($legacyPlugins['canUpgrade']) . '</strong> legacy plugins without dedicated ' .
            'template were found.';
        if (count($legacyPlugins['canUpgrade']) > 0) {
            $content[] = '  <a href="' . htmlspecialchars($upgradeLink) . '">Click here</a> to start the upgrade ' .
            'process.';
        }
        $content[] = '</p>';

        if (count($legacyPlugins['cannotUpgrade']) > 0) {
            $content[] = '<h4>Manual upgrade</h4>';
            $pages = [];
            foreach ($legacyPlugins['cannotUpgrade'] as $row) {
                $pages[] = $row['pid'];
            }
            $pages = array_unique($pages);

            $content[] = '<p><strong>' . count($legacyPlugins['cannotUpgrade']) . '</strong> legacy plugins with dedicated ' .
                'template were found.  Unfortunately these plugins cannot be safely upgraded.<br />';
            $content[] = 'Please either remove the dedicated template and then run this upgrade wizard again or upgrade ' .
                'them to the new plugin manually.<br />';
            $content[] = 'These legacy plugins are located on following pages: ' . implode(', ', $pages) . '.</p>';
        }

        return $this->doc->section('Configuration Upgrade', implode(LF, $content));
    }

    /**
     * Performs the configuration upgrade.
     *
     * @return string
     */
    protected function upgrade()
    {
        $legacyPlugins = $this->getLegacyPlugins();
        $databaseConnection = $this->getDatabaseConnection();
        $upgradedPlugins = 0;

        foreach ($legacyPlugins['canUpgrade'] as $row) {
            $newFlexForm = $this->upgradeFlexFormConfiguration($row['pi_flexform']);
            if ($newFlexForm !== null) {
                $databaseConnection->exec_UPDATEquery(
                    'tt_content',
                    'uid=' . $row['uid'],
                    [
                        'tstamp' => time(),
                        'list_type' => 'filelist_filelist',
                        'pi_flexform' => $newFlexForm,
                    ]
                );
                if ($databaseConnection->sql_affected_rows() > 0) {
                    $upgradedPlugins++;
                }
            }
        }

        return $this->doc->section('Configuration Upgrade',
            '<strong>' . $upgradedPlugins . '</strong> plugins were upgraded.'
        );

        $oldPlugins = $this->WithOldConfiguration();
        foreach ($oldPlugins as $plugin) {
            $this->getDatabaseConnection()->exec_UPDATEquery(
                'tt_content',
                'uid=' . $plugin['uid'],
                array(
                    'tstamp' => time(),
                    'pi_flexform' => $this->getFlexFormConfiguration($plugin),
                    'tx_filelist_path' => '',
                )
            );
        }
    }

    /**
     * Upgrade the FlexForm configuration from a legacy plugin to be compatible
     * with the new modern one.
     *
     * @param array $flexForm
     * @return string|null
     */
    protected function upgradeFlexFormConfiguration(array $flexForm)
    {
        static $storage = null;

        $path = $this->getFieldFromFlexForm($flexForm, 'path');
        $orderBy = $this->getFieldFromFlexForm($flexForm, 'order_by');
        $sortDirection = $this->getFieldFromFlexForm($flexForm, 'sort_direction');
        $newDuration = (int)$this->getFieldFromFlexForm($flexForm, 'new_duration');

        // Make path FAL compatible if needed
        if (!preg_match('/^file:\d+:.*$/', $path)) {
            list($topDirectory, $subdirectory) = explode('/', $path, 2);
            if ($topDirectory !== 'fileadmin') {
                // Upgrade is not (yet?) supported
                return null;
            }
            if ($storage === null) {
                /** @var \TYPO3\CMS\Core\Resource\StorageRepository $storageRepository */
                $storageRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\StorageRepository::class);
                $localStorages = $storageRepository->findByStorageType('Local');
                foreach ($localStorages as $localStorage) {
                    if ($localStorage->isOnline()) {
                        $storage = $localStorage;
                        break;
                    }
                }
                if ($storage === null) {
                    // Upgrade is NOT supported
                    return null;
                }
            }

            $path = 'file:' . $storage->getUid() . ':/' . rtrim($subdirectory, '/') . '/';
        }

        $configuration = $this->getFlexFormConfiguration($path, $orderBy, $sortDirection, $newDuration);

        return $configuration;
    }

    /**
     * Returns a FlexForm configuration template.
     *
     * @param string $path
     * @param string $orderBy
     * @param string $sortDirection
     * @param int $newDuration
     * @return string
     */
    protected function getFlexFormConfiguration($path, $orderBy, $sortDirection, $newDuration)
    {
        $templateFlexForm = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="settings.mode">
                    <value index="vDEF">FOLDER</value>
                </field>
                <field index="settings.path">
                    <value index="vDEF">%s</value>
                </field>
                <field index="settings.orderBy">
                    <value index="vDEF">%s</value>
                </field>
                <field index="settings.sortDirection">
                    <value index="vDEF">%s</value>
                </field>
            </language>
        </sheet>
        <sheet index="display">
            <language index="lDEF">
                <field index="settings.newDuration">
                    <value index="vDEF">%s</value>
                </field>
                <field index="settings.templateLayout">
                    <value index="vDEF">Simple</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';

        return sprintf(
            $templateFlexForm,
            $path,
            strtoupper($orderBy),
            strtoupper($sortDirection),
            (int)$newDuration
        );
    }

    /**
     * Returns a list of plugins that have not yet been upgraded to use the FlexForm configuration.
     *
     * @param bool $firstRow
     * @return array Array of tt_content rows
     */
    protected function getLegacyPlugins($firstRow = false)
    {
        $rows = BackendUtility::getRecordsByField('tt_content', 'list_type', 'file_list_pi1', ' AND CType=\'list\'');
        if (!is_array($rows)) {
            $rows = array();
        }

        $plugins = [
            'canUpgrade' => [],
            'cannotUpgrade' => [],
        ];
        if (!empty($rows) && $firstRow) {
            $plugins['canUpgrade'][] = $rows[0];
        } else {
            foreach ($rows as $row) {
                $flexForm = GeneralUtility::xml2array($row['pi_flexform']);
                $templateFile = $this->getFieldFromFlexForm($flexForm, 'templateFile');
                if (empty($templateFile)) {
                    // Do not do the work twice when upgrading, store FlexForm as an array right away
                    $row['pi_flexform'] = $flexForm;
                    $plugins['canUpgrade'][] = $row;
                } else {
                    $plugins['cannotUpgrade'][] = $row;
                }
            }
        }

        return $plugins;
    }

    /**
     * Returns a field value from the FlexForm configuration.
     *
     * @param string $key The name of the key
     * @param string $sheet The name of the sheet
     * @return string|null The value if found, otherwise null
     */
    protected function getFieldFromFlexForm(array $flexForm, $key, $sheet = 'sDEF')
    {
        if (isset($flexForm['data'])) {
            $flexForm = $flexForm['data'];
            if (is_array($flexForm) && is_array($flexForm[$sheet]) && is_array($flexForm[$sheet]['lDEF'])
                && is_array($flexForm[$sheet]['lDEF'][$key]) && isset($flexForm[$sheet]['lDEF'][$key]['vDEF'])
            ) {
                return $flexForm[$sheet]['lDEF'][$key]['vDEF'];
            }
        }

        return null;
    }

    /**
     * Returns the database connection.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

}
