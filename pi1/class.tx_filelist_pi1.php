<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2009 Moreno Feltscher <moreno@feltscher.ch>
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

require_once(PATH_tslib . 'class.tslib_pibase.php');
require_once('t3lib/class.t3lib_befunc.php');
if (t3lib_extMgm::isLoaded('indexed_search')) {		// Is indexed search engine loaded?
	require_once(t3lib_extMgm::extPath('indexed_search') . 'class.indexer.php');
}

/**
 * Plugin 'File List' for the 'file_list' extension.
 *
 * @package     TYPO3
 * @subpackage  tx_filelist
 * @author      Moreno Feltscher <moreno@feltscher.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class tx_filelist_pi1 extends tslib_pibase {
	public $prefixId = 'tx_filelist_pi1';		// Same as class name
	public $scriptRelPath = 'pi1/class.tx_filelist_pi1.php';
	public $extKey = 'file_list';

	/**
	 * Main-function, returns output
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The	content that is displayed on the website
	 */
	public function main($content, $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->error = 0;		// Disable Filelist if an error occurred
		$this->pi_loadLL();
		$this->pi_USER_INT_obj = 1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
		$path_to_dir = $this->cObj->data['tx_filelist_path'];		// Specified path to directory from frontent-plugin
		$days_show_new = $this->cObj->data['tx_filelist_show_new'];		// Read out the count of days for having 'new'
		settype($days_show_new, 'integer');		// Set variable-type to'integer

		$fe_show_sort = ($this->cObj->data['tx_filelist_fe_user_sort'] == '1');
		$iconpath = t3lib_extMgm::siteRelPath('file_list') . 'pi1/icons/';	// Path where all the icons are

			// Prepare the variable $files_order_by and $folders_order_by (for sorting by)
		if (!isset($this->cObj->data['tx_filelist_order_by']) || $this->cObj->data['tx_filelist_order_by'] == '') {
			$files_order_by = 'files_name';
		}
		else {
			switch ($this->cObj->data['tx_filelist_order_by']) {
				case 0:
					$files_order_by = 'files_name';
					break;
				case 1:
					$files_order_by = 'files_date';
					break;
				case 2:
					$files_order_by = 'files_size';
					break;
			}
		}
		$folders_order_by = $files_order_by;

			// Prepare the variable $files_sort_sequence and $folders_sort_sequence (for sort-sequence)
		if (!isset($this->cObj->data['tx_filelist_order_sort']) || $this->cObj->data['tx_filelist_order_sort'] == '') {
			$files_sort_sequence = 'SORT_ASC';
		}
		else {
			switch ($this->cObj->data['tx_filelist_order_sort']) {
				case 0:
					$files_sort_sequence = SORT_ASC;
					break;
				case 1:
					$files_sort_sequence = SORT_DESC;
					break;
			}
		}
		$folders_sort_sequence = $files_sort_sequence;

			// Preparing some arrays
		$tx_folders = array();
		$tx_files = array();

			// Gets the pid
		$config['pid_list'] = trim($this->cObj->stdWrap($this->conf['pid_list'], $this->conf['pid_list.']));
		$config['pid_list'] = $config['pid_list'] ? implode(t3lib_div::intExplode(',', $config['pid_list']), ',') : $GLOBALS['TSFE']->id;
		list($pid) = explode(',', $config['pid_list']);

		// Gets the uid
		$config['uid_list'] = trim($this->cObj->stdWrap($this->conf['uid_list'], $this->conf['uid_list.']));
		$config['uid_list'] = $config['uid_list'] ? implode(t3lib_div::intExplode(',', $config['uid_list']), ',') : $GLOBALS['TSFE']->uid;
		list($uid) = explode(',', $config['uid_list']);

		$rl = $this->getUidRootLineForClosestTemplate($pid);

			// Preparing the path to the directory
		if (substr($path_to_dir, -1, 1) != '/') {
			$path_to_dir = $path_to_dir . '/';
		}
			// Is the directory readable?
		if (!@is_readable($path_to_dir)) {
			$content = 'Could not open ' . $path_to_dir;
		}
			// Checking get-parameters
		if (!t3lib_div::_GET('tx_file_list-path')) {
			$temp_path = $path_to_dir;
		}
		else {
			if ((substr(t3lib_div::_GET('tx_file_list-path'), 0, 2) != '..') && (!preg_match('/\./', t3lib_div::_GET('tx_file_list-path')))) {
				$temp_path = $path_to_dir . t3lib_div::_GET('tx_file_list-path');
				if (substr(t3lib_div::_GET('tx_file_list-path'), -1, 1) != '/') {
					$temp_path = $temp_path. '/';
				}
				if (substr($temp_path, -3, 3) == '%2F' || substr($temp_path, -4, 3) == '%2F') {
					$temp_path = preg_replace('/%2F/', '', $temp_path);
				}
			}
			else {
				$temp_path = $path_to_dir;
			}
		}

		if (t3lib_div::_GET('tx_file_list-order_by') && t3lib_div::_GET('tx_file_list-order_sequence')) {
			$files_order_by = 'files_' . t3lib_div::_GET('tx_file_list-order_by');
			if (t3lib_div::_GET('tx_file_list-order_by') == 'name') {
				$folders_order_by = 'files_' . t3lib_div::_GET('tx_file_list-order_by');
			}
			if (t3lib_div::_GET('tx_file_list-order_sequence') == 'asc') {
				$files_sort_sequence = SORT_ASC;
				if (t3lib_div::_GET('tx_file_list-order_by') == 'name') {
					$folders_sort_sequence = SORT_ASC;
				}
			}
			elseif (t3lib_div::_GET('tx_file_list-order_sequence') == 'desc') {
				$files_sort_sequence = SORT_DESC;
				if (t3lib_div::_GET('tx_file_list-order_by') == 'name') {
					$folders_sort_sequence = SORT_DESC;
				}
			}
		}

			// Open the directory and read out all folders and files (/write them to an array)
		$open = @opendir($temp_path);
		while ($dir_content = @readdir($open)) {
			if ($dir_content != '.' && $dir_content != 'thumb' && $dir_content != '..') {
				if (is_dir($temp_path . '/' . $dir_content)) {
					$tx_folders[] = array(
						'files_name' => $dir_content,
						'files_path' => $temp_path.$dir_content
					);
				}
				elseif (is_file($temp_path . '/' .$dir_content)) {
					$tx_files[] = array(
						'files_name' => $dir_content,
						'files_date' => filemtime($temp_path.$dir_content),
						'files_size' => filesize($temp_path.$dir_content),
						'files_path' => $temp_path.$dir_content
					);
				}
			}
		}
			// Close the directory
		@closedir($open);

			// Are there any files in the directory?
		if ((count($tx_files) == 0) && (count($tx_folders) < 0)) {
			$content = $this->pi_getClassName('no_files');
		}
		else {
				/* Sort Start */
			if (count($tx_folders) != 0 && $folders_order_by == 'files_name') {
				foreach ($tx_folders as $tx_key => $tx_row) {
					$files_name[$tx_key] = $tx_row['files_name'];
					$files_path[$tx_key] = $tx_row['files_name'];
				}
				$ok_sort = array_multisort($$folders_order_by, $folders_sort_sequence, $tx_folders);
			}
			if (count($tx_files) != 0) {
				foreach ($tx_files as $tx_key => $tx_row) {
					$files_name[$tx_key] = $tx_row['files_name'];
			        $files_date[$tx_key] = $tx_row['files_date'];
					$files_size[$tx_key] = $tx_row['files_size'];
			        $files_path[$tx_key] = $tx_row['files_path'];
				}
				$ok_sort = array_multisort($$files_order_by, $files_sort_sequence, $tx_files);
			}
				/* Sort End */

				// Preparing the table
			$content = '<table border="0" cellspacing="0" cellpadding="0" class="' . $this->pi_getClassName('table') . '">';
			$content .= '<tr class="' . $this->pi_getClassName('header-tr') . '">';
			$content .= '<td width="30" class="' . $this->pi_getClassName('header-icon') . '"></td>'; //Icon
			$content .= '<td align="left" valign="middle" class="' . $this->pi_getClassName('header-filename') . '">' . htmlspecialchars($this->pi_getLL('filename'));  // Filename
			if ($fe_show_sort) {
				$content .= $this->fe_sort('name', 'desc', $pid, $iconpath);
				$content .= $this->fe_sort('name', 'asc', $pid, $iconpath);
			}
			$content .= '</td>';
			$content .= '<td align="left" valign="middle" class="' . $this->pi_getClassName('header-info') . '">' . htmlspecialchars($this->pi_getLL('info')); //Info
			if ($fe_show_sort) {
				$content .= $this->fe_sort('size', 'desc', $pid, $iconpath);
				$content .= $this->fe_sort('size', 'asc', $pid, $iconpath);
			}
			$content .= '</td>';
			$content .= '<td align="left" valign="middle" class="' . $this->pi_getClassName('header-last_modification') . '">' . htmlspecialchars($this->pi_getLL('last_modification')); //Last modification
			if ($fe_show_sort) {
				$content .= $this->fe_sort('date', 'desc', $pid, $iconpath);
				$content .= $this->fe_sort('date', 'asc', $pid, $iconpath);
			}
			$content .= '</td>';
			$content .= '</tr>';


			if (count($tx_folders) >= 0) {

					// Put '..' on the start of the array
				$temp_tx_folders = array_reverse($tx_folders);
				$temp_tx_folders[] = array(
					'files_name' => '..',
					'files_path' => $temp_path. '..'
				);
				$tx_folders = array_reverse($temp_tx_folders);

					// Displays the folders in a table
				for ($d = 0; $d < count($tx_folders); $d++) {
					if (!(!t3lib_div::_GET('tx_file_list-path') && $tx_folders[$d]['files_name'] == '..')) {
						$content .= '<tr class="' .$this->pi_getClassName('tr') . '">';
						$content .= '<td class="' .$this->pi_getClassName('icon') . '">';
						if ($tx_folders[$d]['files_name'] == '..') {
							$content .= '<img src="' . $iconpath . 'move_up.png" alt="' . $tx_folders[$d]['files_name'] . '"';
						}
						else {
							$content .= '<img src="' . $iconpath . 'folder.png" alt="' . $tx_folders[$d]['files_name'] . '"';
						}
						$content .= '</td>';
						$content .= '<td class"' . $this->pi_getClassName('filename') . '">';
						$content .= '<a href="index.php?id=' . $pid;
						if (!t3lib_div::_GET('tx_file_list-path')) {
							$content .= '&tx_file_list-path=' . $tx_folders[$d]['files_name'];
						}
						else {
							if ($tx_folders[$d]['files_name'] == '..' && similar_text(preg_replace('/\//', '%2F', t3lib_div::_GET('tx_file_list-path')) ,'%2F') >= 3) {
								$temp = explode('%2F', preg_replace('/\//', '%2F', t3lib_div::_GET('tx_file_list-path')));
								$temp1 = count($temp)-1;
								$content = $content . '&tx_file_list-path=' . preg_replace('/%2F/' . $temp[$temp1], '', preg_replace('/\//', '%2F', t3lib_div::_GET('tx_file_list-path')));
							}
							else {
								if ($tx_folders[$d]['files_name'] != '..') {
									$content .= '&tx_file_list-path=' . preg_replace('/\//', '%2F', t3lib_div::_GET('tx_file_list-path')) . '%2F' . $tx_folders[$d]['files_name'];
								}
							}
						}
						$content .= '">' . $tx_folders[$d]['files_name'] . '</a></td>';
						$content .= '<td class="' . $this->pi_getClassName('info') . '"><font size="1">';
						$file_counte = $this->filecounter($temp_path.$tx_folders[$d]['files_name']);
						$content .= $file_counte. ' ' .htmlspecialchars($this->pi_getLL('files_in_directory')). '</font></td>';
						$content .= '<td class="' .$this->pi_getClassName('last_modification'). '"><font size="1">';
						$content .= t3lib_BEfunc::datetime(@filemtime($temp_path . $tx_folders[$d]['files_name']));
						$content .= '</font></td>';
						$content .= '</tr>';
					}
				}
			}

				// Displays the files in a table
			if (count($tx_files) != 0) {
				for ($f = 0; $f < count($tx_files); $f++) {
					$content .= '<tr class="' . $this->pi_getClassName('tr') . '">';
					$content .= '<td class="' . $this->pi_getClassName('icon') . '">';
					$content .= '<img src="' . $iconpath . $this->fileicon($tx_files[$f]['files_name']) . '" alt="' . $tx_files[$f]['files_name'] . '">';
					$content .= '</td><td valign="bottom" class="' . $this->pi_getClassName('filename') . '">';
					$content .= '<a href="' . $tx_files[$f]['files_path'] . '" target="_blank">' . $tx_files[$f]['files_name'] . '</a> ';
					$content .= $this->show_new($tx_files[$f]['files_path'], $days_show_new, $iconpath) . '</td>';
					$content .= '<td><font size="1">' . $this->getHRFileSize($tx_files[$f]['files_path']) . '</font></td>';
					$content .= '<td class="' . $this->pi_getClassName('last_modification') . '"><font size="1">';
					$content .= t3lib_BEfunc::datetime(@filemtime($temp_path.$tx_files[$f]['files_name'])) . '</font></td>';

					if (t3lib_extMgm::isLoaded('indexed_search')) {		// Is indexed search engine on? When yes select some data from a indexed search table
						$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'*',
							'index_phash',
							'date_filename = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tx_files[$f]['files_path'], 'index_phash')
						);
						$out = array();
						while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
							array_push($out, $row['item_crdate']);
						}
					}
					
					if (t3lib_extMgm::isLoaded('indexed_search')) {		// Is indexed search engine on?

							// If there are more then one entries in the index-database delete them all
						if ((count($out) == 1 && @filemtime($tx_files[$f]['files_path']) != $out[0]) || (count($out) == 0)) {
								// OK, let's index the files with indexed search engine
							$indexerObj = &t3lib_div::makeInstance('tx_indexedsearch_indexer');
							$setId = t3lib_div::md5int(microtime());
							$indexerObj->backend_initIndexer($pid, 0, 0, '', $rl);
							$indexerObj->backend_setFreeIndexUid($uid, $setId);
							$indexerObj->indexRegularDocument($tx_files[$f]['files_path'], TRUE);
						}
					}
					if (isset($out)) {
						unset($out);
					}
				}
			}
			$content .= '</table>';
		}
		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * How many files are in the directory?
	 *
	 * @param	string		Path to the specified directory
	 * @return	integer		Number of files in the directory
	 */
	protected function filecounter($counter_dir) {
		$counter = 0;
		$counter_open = @opendir($counter_dir);
		while ($counter_content = readdir($counter_open)) {
			if (is_file($counter_dir . '/' . $counter_content) && $counter_content != 'thumb') {
				$counter++;
			}
		}
		return $counter;
	}

	/**
	 * Returns the icon which represents a file-type
	 *
	 * @param	string		Path to the specified file
	 * @return	string		Filename of the icon
	 */
	protected function fileicon($fn) {
		$allfileends = array(
			'doc', 'pdf', 'pps', 'tar', 'txt', 'xls', 'swf', 'htm', 'html', 'phtml', 'gif', 'jpg', 'jpeg', 'png', 'bpm', 'mp3', 'wav', 'wmv', 'tar', 'gz', 'txt', 'mp4', 'mpg', 'mpeg', 'tif'
		);
		$normfileend = array('doc', 'pdf', 'pps', 'tar', 'txt', 'xls');
		$fileends = array(
			'draw' => array('draw'),
			'flash' => array('flash', 'swf'),
			'html' => array('html', 'htm', 'phtml'),
			'image' => array('image', 'gif', 'jpg', 'jpeg', 'png', 'bpm', 'tif'),
			'sound' => array('sound','mp3', 'wav', 'wmv'),
			'source' => array('source'),
			'tar' => array('tar', 'gz'),
			'txt' => array('txt'),
			'video' => array('video', 'mp4', 'mpg', 'mpeg')
		);
		$fileend = explode('.', $fn);
		$f_count = count($fileend) - 1;
		$fileend = strtolower($fileend[$f_count]);
		if (in_array($fileend, $allfileends)) {
			if (in_array($fileend, $normfileend)) {
				return $fileend . '.png';
			}
			else {
				foreach($fileends as $temp_fileend) {
					if (in_array($fileend, $temp_fileend)) {
						return $temp_fileend[0] . '.png';
					}
				}
			}
		}
		else {
			return 'mime.png';
		}
	}

	/**
	 * Returns a human-readable size of a file.
	 *
	 * @param	string		Path to the specified file
	 * @return	string		Size of the file
	 */
	protected function getHRFileSize($filename) {
		$units = array(
			'0' => $this->pi_getLL('units.bytes'),
			'1' => $this->pi_getLL('units.KB'),
			'2' => $this->pi_getLL('units.MB'),
			'3' => $this->pi_getLL('units.GB'),
			'4' => $this->pi_getLL('units.TB'),
		);
		$filesize = @filesize($filename);
		for ($offset = 0; $filesize >= 1024; $offset++) {
			$filesize /= 1024;
		}
		$decimalPlaces = ($offset < 2) ? 0 : $offset - 1;
		$format = '%.' . $decimalPlaces . 'f %s';
		return sprintf($format, $filesize, $units[$offset]);
	}

	/**
	 * Returns the date of the last modification
	 *
	 * @param	string		Path to the specified file
	 * @return	string		Last modification of file
	 */
    protected function file_create_date($fn) {
		$filedate = filemtime($fn);
		return date('d-m-y H:i', $filedate);
	}

	/**
	 * Returns the uid
	 *
	 * @param	integer		pid
	 * @return	integer		uid
	 */
	protected function getUidRootLineForClosestTemplate($id)	{
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

	/**
	 * Returns the new-icon, when the file is selected as new
	 *
	 * @param	string		Path to the specified file
	 * @param	integer		With how mutch of days a file is new?
	 * @param	string		Path to the directory, where the icons are
	 * @return	string		Returns the 'new-icon'
	 */
	protected function show_new($fn, $days_show_new, $iconpath) {
		if ($days_show_new != 0 && $days_show_new != '' && isset($days_show_new) && $days_show_new != NULL)  {
			if (filemtime($fn) > mktime(0, 0, 0, date('m'), date('d') - $days_show_new, date('Y'))) {
				return '<img src="' . $iconpath.$this->pi_getLL('new_icon') . '.png" alt="' . $this->pi_getLL('new_text') . '">';
			}
			else {
				return '';
			}
		}
		else {
			return '';
		}
	}

	/**
	 * Returns the icons, with witch the user on the frontend can sort the files
	 *
	 * @param	string		Order by (name, date, size)
	 * @param	string		Order sequence (ASC, DESC)
	 * @param	integer		pid
	 * @param	string		Path to the directory, where the icons are
	 * @return	string		Return of images for sorting
	 */
	protected function fe_sort($order_by, $order_seq, $pid, $iconpath) {
		$temp_content = ' <a href="index.php?id=' . $pid;
		if (t3lib_div::_GET('tx_file_list-path')) {
			$temp_content = $temp_content . '&tx_file_list-path=' . preg_replace('/\//', '%2F', t3lib_div::_GET('tx_file_list-path'));
		}
		$temp_content = $temp_content . '&tx_file_list-order_by=' . $order_by . '&tx_file_list-order_sequence=' . $order_seq . '"><img src="' . $iconpath;
		if ($order_seq == 'asc') {
			$temp_content = $temp_content . 'up.gif" alt="' . htmlspecialchars($this->pi_getLL('asc')) . '" border="0"></a>';
		}
		if ($order_seq == 'desc') {
			$temp_content = $temp_content . 'down.gif" alt="' . htmlspecialchars($this->pi_getLL('desc')) . '" border="0"></a>';
		}
		return $temp_content;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/file_list/pi1/class.tx_filelist_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/file_list/pi1/class.tx_filelist_pi1.php']);
}

?>