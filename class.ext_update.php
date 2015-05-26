<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2015 Xavier Perseguers <xavier@causal.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Update class for the 'file_list' extension.
 *
 * @package     TYPO3
 * @subpackage  tx_filelist
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class ext_update extends \TYPO3\CMS\Backend\Module\BaseScriptClass {

	/**
	 * Checks whether the "UPDATE!" menu item should be shown. If no old configuration (in
	 * dedicated fields of tt_content) is found, should return FALSE; otherwise TRUE.
	 *
	 * @return bool
	 */
	public function access() {
		return count($this->getPluginsWithOldConfiguration()) > 0;
	}

	/**
	 * Main method that is called whenever UPDATE! menu was clicked. This method outputs a
	 * HTML form to update the configuration of all plugins of the website.
	 *
	 * @return string HTML to display
	 */
	public function main() {
		$this->content = '';

		$this->doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\StandardDocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->form = '<form action="" method="post">';

		$title = 'File List Plugin Configuration';
		$this->content .= $this->doc->startPage($title);
		$this->content .= $this->doc->header($title);
		$this->content .= $this->doc->spacer(5);

		$this->content .= $this->doc->section('',
			'This extension does not need any dedicated columns in table tt_content anymore.  ' .
			'These columns were used to store the plugin configuration.  This update wizard ' .
			'detected that you still have plugins whose configuration has not yet been upgrade.  ' .
			'This form allows you to upgrade your existing file_list plugins to use the new ' .
			'FlexForm configuration.'
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
	protected function prepareUpgrade() {
		$oldPlugins = $this->getPluginsWithOldConfiguration();
		return $this->doc->section('Configuration Upgrade',
			count($oldPlugins) . ' plugins with old configuration were found.  ' .
			'<a href="' . GeneralUtility::linkThisScript(array('upgrade' => 1)) . '">Click here</a> ' .
			'to start the upgrade process.'
		);
	}

	/**
	 * Performs the configuration upgrade.
	 *
	 * @return string
	 */
	protected function upgrade() {
		$oldPlugins = $this->getPluginsWithOldConfiguration();
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

		return $this->doc->section('Configuration Upgrade',
			count($oldPlugins) . ' plugins were upgraded.  You should now consider ' .
			'opening the Install Tool and removing deprecated file_list columns from ' .
			'table tt_content by running the Compare tool.'
		);
	}

	/**
	 * Returns a FlexForm configuration template.
	 *
	 * @param array $config
	 * @return string
	 */
	protected function getFlexFormConfiguration(array $config) {
		$flexform = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="path">
                    <value index="vDEF">%s</value>
                </field>
                <field index="order_by">
                    <value index="vDEF">%s</value>
                </field>
                <field index="sort_direction">
                    <value index="vDEF">%s</value>
                </field>
                <field index="new_duration">
                    <value index="vDEF">%s</value>
                </field>
                <field index="fe_sort">
                    <value index="vDEF">%s</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';

		$path = $config['tx_filelist_path'];
		switch ($config['tx_filelist_order_by']) {
			case '1':
				$order_by = 'date';
				break;
			case '2':
				$order_by = 'size';
				break;
			default:
				$order_by = 'name';
		}
		$direction = $config['tx_filelist_order_sort'] == '1' ? 'desc' : 'asc';
		$duration = (int)$config['tx_filelist_show_new'];
		$fe_sort = (int)$config['tx_filelist_fe_user_sort'];

		return sprintf($flexform, $path, $order_by, $direction, $duration, $fe_sort);
	}

	/**
	 * Returns a list of plugins that have not yet been upgraded to use the FlexForm configuration.
	 *
	 * @return array Array of "light" tt_content rows
	 */
	protected function getPluginsWithOldConfiguration() {
		// Do not take deleted flag into account as we wish to upgrade ALL plugins
		$records = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField('tt_content', 'list_type', 'file_list_pi1');
		$fields = GeneralUtility::trimExplode(',', 'uid,pid,tx_filelist_path,tx_filelist_order_by,tx_filelist_order_sort,tx_filelist_show_new,tx_filelist_fe_user_sort');
		$plugins = array();
		foreach ($records as $record) {
			if (isset($record['tx_filelist_path']) && $record['tx_filelist_path'] !== '') {
				$plugin = array();
				foreach ($fields as $field) {
					$plugin[$field] = $record[$field];
				}
				$plugins[] = $plugin;
			}
		}
		return $plugins;
	}

	/**
	 * Returns the database connection.
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
