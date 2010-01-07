<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Moreno Feltscher <moreno@luagsh.ch>
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

require_once(t3lib_extMgm::extPath('indexed_search') . 'class.indexer.php');

/**
 * Hook for EXT:indexed_search for the 'file_list' extension.
 *
 * @package     TYPO3
 * @subpackage  tx_filelist
 * @author      Moreno Feltscher <moreno@luagsh.ch>
 * @author      Xavier Perseguers  <typo3@perseguers.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class tx_filelist_indexedsearch {

	/**
	 * Pseudo extraItemMarkerProcessor to actually index an external file.
	 *
	 * @param	array		$markers
	 * @param	string		$filename
	 * @param	tx_filelist_pi1		$pObj
	 * @return	array		$markers array, unchanged
	 */
	public function extraItemMarkerProcessor(array $markers, $filename, tx_filelist_pi1 $pObj) {
		if (@is_file($filename)) {
			$this->indexExternalFile($filename, $pObj);
		}

		return $markers;
	}

	/**
	 * Indexes an external file.
	 * 
	 * @param	string		$filename
	 * @param	tx_filelist_pi1		$pObj
	 * @return	void
	 */
	protected function indexExternalFile($filename, tx_filelist_pi1 $pObj) {
		 
			// Get the pid
		$config['pid_list'] = trim($pObj->cObj->stdWrap($this->conf['pid_list'], $this->conf['pid_list.']));
		$config['pid_list'] = $config['pid_list'] ? implode(t3lib_div::intExplode(',', $config['pid_list']), ',') : $GLOBALS['TSFE']->id;
		list($pid) = explode(',', $config['pid_list']);

			// Get the uid
		$config['uid_list'] = trim($pObj->cObj->stdWrap($this->conf['uid_list'], $this->conf['uid_list.']));
		$config['uid_list'] = $config['uid_list'] ? implode(t3lib_div::intExplode(',', $config['uid_list']), ',') : $GLOBALS['TSFE']->uid;
		list($uid) = explode(',', $config['uid_list']);

		$rootline = $this->getUidRootLineForClosestTemplate($pid);

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'index_phash',
			'date_filename = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($filename, 'index_phash')
		);
		$out = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			array_push($out, $row['item_crdate']);
		}
		
			// If there are more then one entries in the index-database delete them all
		if ((count($out) == 1 && @filemtime($filename) != $out[0]) || (count($out) == 0)) {
				// OK, let's index the files with indexed search engine
			$indexerObj = &t3lib_div::makeInstance('tx_indexedsearch_indexer');
			$setId = t3lib_div::md5int(microtime());
			$indexerObj->backend_initIndexer($GLOBALS['TSFE']->id, 0, 0, '', $rootline);
			$indexerObj->backend_setFreeIndexUid($uid, $setId);
			$indexerObj->indexRegularDocument($filename, TRUE);
		}
		
		if (isset($out)) {
			unset($out);
		}
	}

	/**
	 * Returns the uid
	 *
	 * @param	integer		pid
	 * @return	integer		uid
	 */
	protected function getUidRootLineForClosestTemplate($id) {
		global $TYPO3_CONF_VARS;

		require_once(PATH_t3lib . 'class.t3lib_page.php');
		require_once(PATH_t3lib . 'class.t3lib_tstemplate.php');
		require_once(PATH_t3lib . 'class.t3lib_tsparser_ext.php');

		$tmpl = t3lib_div::makeInstance('t3lib_tsparser_ext');
		$tmpl->tt_track = 0;	// Do not log time-performance information
		$tmpl->init();

			// Gets the rootLine
		$sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
		$rootLine = $sys_page->getRootLine($id);
		$tmpl->runThroughTemplates($rootLine, 0);	// This generates the constants/config + hierarchy info for the template.

			// Root line uids
		$rootline_uids = array();
		foreach($tmpl->rootLine as $rlkey => $rldat) {
			$rootline_uids[$rlkey] = $rldat['uid'];
		}
		return $rootline_uids;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/file_list/Classes/Hooks/class.tx_filelist_indexedsearch.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/file_list/Classes/Hooks/class.tx_filelist_indexedsearch.php']);
}

?>