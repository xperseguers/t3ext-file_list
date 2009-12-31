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
	
	// Members coming from tslib_pibase
	public $prefixId = 'tx_filelist_pi1';
	public $scriptRelPath = 'pi1/class.tx_filelist_pi1.php';
	public $extKey = 'file_list';

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @var string
	 */
	protected $getPrefix;

	/**
	 * Plugin arguments (read from URL)
	 * @var array
	 */
	protected $args = array();

	/**
	 * @var array
	 */
	protected $templates = array();
	
	/**
	 * Main-function, returns output
	 *
	 * @param	string		$content: The Plugin content
	 * @param	array		$settings: The Plugin configuration
	 * @return	The	content that is displayed on the website
	 */
	public function main($content, array $settings) {
		$this->init($settings);
		$this->pi_setPiVarDefaults();
		$this->initTemplate();
	
		$subdirs = array();
		$files = array();

		$listingPath = $this->settings['path'];
		if ($this->args['path']) {
			$listingPath = $this->sanitizePath($listingPath . $this->args['path']);
		}

			// Checks that $listingPath is a valid directory
		if (!(is_dir($listingPath) && is_readable($listingPath) && $this->isValidDirectory($listingPath))) {
			return $this->error(sprintf('Could not open directory "%s"', $this->settings['path']));
		}

		if ($this->settings['fe_sort'] && $this->args['direction']) {
			$this->settings['sort_direction'] = $this->args['direction']; 
		}
		if ($this->settings['fe_sort'] && $this->args['order_by']) {
			$this->settings['order_by'] = $this->args['order_by'];
			if (!t3lib_div::inList('name,date,size', $this->settings['order_by'])) {
				$this->settings['order_by'] = 'name';
			}
		}

		list($subdirs, $files) = $this->getDirectoryContent($listingPath);

			// Are there any files in the directory?
		if ((count($files) == 0) && (count($subdirs) == 0)) {
			$content = $this->pi_getLL('no_files');
		}
		else {
				/* Sort Start */
			if (count($subdirs) > 0 && $this->settings['order_by'] === 'name') {
				foreach ($subdirs as $tx_key => $tx_row) {
					$sortArr[$tx_key] = $tx_row['name'];
				}
				$direction = $this->settings['sort_direction'] === 'asc' ? SORT_ASC : SORT_DESC;
				$ok_sort = array_multisort($sortArr, $direction, $subdirs);
			}
			if (count($files) > 0) {
				foreach ($files as $tx_key => $tx_row) {
					$sortArr[$tx_key] = $tx_row[$this->settings['order_by']];
				}
				$direction = $this->settings['sort_direction'] === 'asc' ? SORT_ASC : SORT_DESC;
				$ok_sort = array_multisort($sortArr, $direction, $files);
			}
				/* Sort End */

			$rows = array();
			$odd = TRUE;
			if (count($subdirs) >= 0) {

					// Put '..' at the beginning of the array
				array_unshift($subdirs, array(
					'name' => '..',
					'path' => $this->sanitizePath($listingPath . '../')
				));

					// Display the folders in a table
				for ($d = 0; $d < count($subdirs); $d++) {
					if (!$this->args['path'] && $subdirs[$d]['name'] === '..') {
						continue;
					}
					$markers = array();
					if ($subdirs[$d]['name'] === '..') {
						$markers['###ICON###'] = '<a href="' . $this->getLink(array('path' => substr($subdirs[$d]['path'], strlen($this->settings['path'])))) . '">' . '<img src="' . $this->settings['iconsPath'] . 'move_up.png" alt="' . $subdirs[$d]['name'] . '" border="0" /></a>';
					} else {
						$markers['###ICON###'] = '<img src="' . $this->settings['iconsPath'] . 'folder.png" alt="' . $subdirs[$d]['name'] . '" />';
					}
					$markers['###FILENAME###'] = '<a href="' . $this->getLink(array('path' => substr($subdirs[$d]['path'], strlen($this->settings['path'])))) . '">' . $subdirs[$d]['name'] . '</a>';
					$totalFiles = $this->getNumberOfFiles($listingPath . $subdirs[$d]['name']);
					$markers['###INFO###'] = $totalFiles . ' '; 
					if ($totalFiles > 1) {
						$markers['###INFO###'] .= $this->pi_getLL('files_in_directory');
					} else {
						$markers['###INFO###'] .= $this->pi_getLL('file_in_directory');
					}
					$markers['###DATE###'] = t3lib_BEfunc::datetime(@filemtime($listingPath . $subdirs[$d]['name']));

						// Hook for processing of extra item markers
					if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['file_list']['extraItemMarkerHook'])) {
						foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['file_list']['extraItemMarkerHook'] as $_classRef) {
							$_procObj =& t3lib_div::getUserObj($_classRef);
							$markers = $_procObj->extraItemMarkerProcessor($markers, $subdirs[$d]['path'], $this);
						}
					}
					
					$rows[] = $this->cObj->substituteMarkerArray($odd ? $this->templates['odd'] : $this->templates['even'], $markers);
					$odd = !$odd;
				}
			}

				// Display the files in a table
			for ($f = 0; $f < count($files); $f++) {
				$markers = array();
				$markers['###ICON###'] = '<img src="' . $this->settings['iconsPath'] . $this->getFileTypeIcon($files[$f]['name']) . '" alt="' . $files[$f]['name'] . '">';
				$markers['###FILENAME###'] = $this->cObj->typolink($files[$f]['name'], array('parameter' => $files[$f]['path']));
				$markers['###FILENAME###'] .= ' ' . $this->getNewIcon($files[$f]['path'], $this->settings['new_duration']);
				$markers['###INFO###'] = $this->getHRFileSize($files[$f]['path']);
				$markers['###DATE###'] = t3lib_BEfunc::datetime(@filemtime($listingPath . $files[$f]['name']));

					// Hook for processing of extra item markers
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['file_list']['extraItemMarkerHook'])) {
					foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['file_list']['extraItemMarkerHook'] as $_classRef) {
						$_procObj =& t3lib_div::getUserObj($_classRef);
						$markers = $_procObj->extraItemMarkerProcessor($markers, $files[$f]['path'], $this);
					}
				}
				$rows[] = $this->cObj->substituteMarkerArray($odd ? $this->templates['odd'] : $this->templates['even'], $markers);
				$odd = !$odd;
			}
			
			$markers = array(
				'###HEADER_FILENAME###' => $this->pi_getLL('filename'),
				'###HEADER_INFO###' => $this->pi_getLL('info'),
				'###HEADER_DATE###' => $this->pi_getLL('date'),
				'###BODY###' => implode("\n", $rows),
			);
			if ($this->settings['fe_sort']) {
				$markers['###HEADER_FILENAME###'] .= $this->fe_sort('name', 'desc') . $this->fe_sort('name', 'asc');
				$markers['###HEADER_INFO###'] .= $this->fe_sort('size', 'desc') . $this->fe_sort('size', 'asc');
				$markers['###HEADER_DATE###'] .= $this->fe_sort('date', 'desc') . $this->fe_sort('date', 'asc');
			}
			$content = $this->cObj->substituteMarkerArray($this->templates['table'], $markers);
		}
		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * Reads the template file, fill in global wraps and markers and write the result
	 * parts to $this->templates array.
	 *
	 * @return	void
	 */
	function initTemplate() {
		$content = $this->cObj->fileResource($this->settings['templateFile']);
		$templateCode = $this->cObj->getSubpart($content, '###TEMPLATE_DEFAULT###');

		$globalMarkerArray = array(
			'###TABLE_CLASS###'     => $this->pi_getClassName('table'),
			'###ODD_CLASS###'       => $this->pi_getClassName('odd'),
			'###EVEN_CLASS###'      => $this->pi_getClassName('even'),
			'###ICON_CLASS###'      => $this->pi_getClassName('icon'),
			'###FILENAME_CLASS###'  => $this->pi_getClassName('filename'),
			'###INFO_CLASS###'      => $this->pi_getClassName('info'),
			'###DATE_CLASS###'      => $this->pi_getClassName('date'),
		);
		$templateCode = $this->cObj->substituteMarkerArray($templateCode, $globalMarkerArray);

		$this->templates['odd'] = $this->cObj->getSubpart($templateCode, '###ODD_TEMPLATE###');
		$this->templates['even'] = $this->cObj->getSubpart($templateCode, '###EVEN_TEMPLATE###');
		$this->templates['table'] = $this->cObj->substituteSubpart($templateCode, '###BODY###', '###BODY###');
	}

	/**
	 * Returns a link to the same page with additional parameters.
	 * 
	 * @param	array		$params
	 * @return	string
	 */
	protected function getLink(array $params) {
			// Merge existing parameters with $params
		foreach ($this->args as $key => $value) {
			if (!isset($params[$key])) {
				$params[$key] = $value;
			}
		}
		$tmp = array();
		foreach ($params as $key => $value) {
			$tmp[] = sprintf('%s=%s', $this->getPrefix . '[' . $key . ']', urlencode($value));
		}
		$params = $tmp;
		return $this->pi_getPageLink($GLOBALS['TSFE']->id, '', '&' . implode('&', $params));
	}

	/**
	 * How many files are in the directory?
	 *
	 * @param	string		Path to the specified directory
	 * @return	integer		Number of files in the directory
	 */
	protected function getNumberOfFiles($path) {
		$files = 0;
		$dh = @opendir($path);
		while ($c = readdir($dh)) {
			if (is_file($path . '/' . $c) && $c !== 'thumb') {
				$files++;
			}
		}
		@closedir($dh);
		return $files;
	}

	/**
	 * Returns the icon which represents a file-type.
	 *
	 * @param	string		Path to the specified file
	 * @return	string		Filename of the icon
	 */
	protected function getFileTypeIcon($filename) {
		$categories = array(
			'archive'    => array('bz2', 'gz', 'rar', 'tar', 'zip'),
			'document'   => array('doc', 'docx', 'pdf', 'pps', 'ppt', 'pptx', 'xls', 'xlsx'),
			'flash'      => array('fla', 'swf'),
			'image'      => array('bmp', 'draw', 'gif', 'jpg', 'jpeg', 'png', 'tif', 'tiff'),
			'sound'      => array('m4a', 'mid', 'midi', 'mp3', 'mp4', 'wav'),
			'source'     => array('php', 'htm', 'html', 'inc', 'phtml'),
			'video'      => array('mpg', 'mpeg', 'wmv'),
		);
			// Remapping occurs if a dedicated icon cannot be found
		$remapExtensions = array(
			'docx'  => 'doc',
			'htm'   => 'html',
			'midi'  => 'mid',
			'phtml' => 'html',
			'pptx'  => 'ptt',
			'tiff'  => 'tif',
			'xlsx'  => 'xls',
		);

			// Extract the file extension
		$ext = strtolower(substr($filename, strrpos($filename, '.') + 1));

			// Try to find a dedicated icon
		for ($i = 0; $i < 2; $i++) {
			if ($i == 1) {
					// Remap the extension
				if (isset($remapExtensions[$ext])) {
					$ext = $remapExtensions[$ext];
				} else {
					break;
				}
			}
			if (is_file($this->settings['iconsPath'] . $ext . '.png')) {
				return $ext . '.png';
			} elseif (is_file($this->settings['iconsPath'] . $ext . '.gif')) {
				return $ext . '.gif';
			}
		}

			// Try to find a filetype category icon
		$category = '';
		foreach ($categories as $cat => $extensions) {
			if (t3lib_div::inArray($extensions, $ext)) {
				return 'category_' . $cat . '.png';
			}
		}

			// Fallback icon
		return 'blank_document.png';
	}

	/**
	 * Gets content of a directory.
	 * 
	 * @param	string		$path
	 * @return	array		list(array $directories, array $files)
	 */
	protected function getDirectoryContent($path) {
		$dirs = array();
		$files = array();

			// Open the directory and read out all folders and files
		$dh = @opendir($path);
		while ($dir_content = @readdir($dh)) {
			if ($dir_content != '.' && $dir_content !== 'thumb' && $dir_content !== '..') {
				if (is_dir($path . '/' . $dir_content)) {
					$dirs[] = array(
						'name' => $dir_content,
						'path' => $path . $dir_content
					);
				}
				elseif (is_file($path . '/' . $dir_content)) {
					$files[] = array(
						'name' => $dir_content,
						'date' => filemtime($path . $dir_content),
						'size' => filesize($path . $dir_content),
						'path' => $path . $dir_content
					);
				}
			}
		}
			// Close the directory
		@closedir($dh);
		return array($dirs, $files);
	}

	/**
	 * Sanitizes a path by making sure a trailing slash is present and
	 * all directories are resolved (no more '../' within string).
	 *   
	 * @param	string		$path: either an absolute path or a path relative to website root
	 * @return	string
	 */
	protected function sanitizePath($path) {
		if ($path{0} === '/') {
			$prefix = '';
		} else {
			$prefix = PATH_site;
			$path = PATH_site . $path;
		}
			// Make sure there is no more ../ inside
		$path = realpath($path);
			// Make it relative again (if needed)
		$path = substr($path, strlen($prefix));
			// Ensure a trailing slash is present
		if (substr($path, -1, 1) !== '/') {
			$path .= '/';
		}
		return $path;
	}

	/**
	 * Checks that the given path is within the allowed root directory and
	 * within the plugin's root directory.
	 * 
	 * @param	string		$path Path relative to the website root
	 * @return	boolean
	 */
	protected function isValidDirectory($path) {
		return
			// Within the allowed root directory
			!(strcmp(substr(PATH_site . $path, 0, strlen($this->settings['rootabs'])), $this->settings['rootabs']))
			// Within the plugin's root directory
			&& !(strcmp(substr($path, 0, strlen($this->settings['path'])), $this->settings['path']));
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
	 * Returns the new-icon, when the file is selected as new.
	 *
	 * @param	string		Path to the specified file
	 * @param	integer		With how much of days a file is new?
	 * @return	string		Returns the 'new-icon'
	 */
	protected function getNewIcon($fn, $duration) {
		if ($duration > 0 && filemtime($fn) > mktime(0, 0, 0, date('m'), date('d') - $duration, date('Y'))) {
			return '<img src="' . $this->settings['iconsPath'] . $this->pi_getLL('new.icon') . '" alt="' . $this->pi_getLL('new.altText') . '" />';
		}

		return '';
	}

	/**
	 * Returns the icons, with witch the user on the frontend can sort the files
	 *
	 * @param	string		Order by (name, date, size)
	 * @param	string		Order sequence ('asc', 'desc')
	 * @return	string		Return of images for sorting
	 */
	protected function fe_sort($order_by, $direction) {
		$link = $this->getLink(array(
			'path'      => $this->args['path'],
			'order_by'  => $order_by,
			'direction' => $direction,
		));
		$ret = ' <a href="' . $link . '">';
		$ret .= '<img src="' . $this->settings['iconsPath'];
		if ($direction === 'asc') {
			$ret .= 'up.gif" alt="' . $this->pi_getLL('sort.asc');
		} else {
			$ret .= 'down.gif" alt="' . $this->pi_getLL('sort.desc');
		}
		$ret .= '" border="0"></a>';
		return $ret;
	}

	/**
	 * This method performs various initializations.
	 *
	 * @param	array		$settings: Plugin configuration, as received by the main() method
	 * @return	void
	 */
	protected function init(array $settings) {
		$this->settings = $settings;

			// Load the flexform and loop on all its values to override TS setup values
			// Some properties use a different test (more strict than not empty) and yet some others no test at all
			// see http://wiki.typo3.org/index.php/Extension_Development,_using_Flexforms
		$this->pi_initPIflexForm(); // Init and get the flexform data of the plugin

			// Assign the flexform data to a local variable for easier access
		$piFlexForm = $this->cObj->data['pi_flexform'];
		
		if (is_array($piFlexForm['data'])) {
				// Traverse the entire array based on the language
				// and assign each configuration option to $this->settings array...
			foreach ($piFlexForm['data'] as $sheet => $langData) {
				foreach ($langData as $lang => $fields) {
					foreach (array_keys($fields) as $field) {
						$value = $this->pi_getFFvalue($piFlexForm, $field, $sheet);	
							
						if (!empty($value)) {
							if (in_array($field, $explodeFlexFormFields)) {
								$this->settings[$field] = explode(',', $value);
							} else {
								$this->settings[$field] = $value;
							}
						}
					}
				}
			}
		}

			// Set the icons path
		if (isset($this->settings['iconsPath'])) {
			$iconsPath = $this->cObj->stdWrap($this->settings['iconsPath'], $this->settings['iconsPath.']);
			$this->settings['iconsPath'] = $this->resolveSiteRelPath($iconsPath);
		} else {	// Fallback
			$this->settings['iconsPath'] = t3lib_extMgm::siteRelPath('file_list') . 'Resources/Public/Icons/';
		}

			// Prepare open base directory
		$root = $this->settings['root'];
		if (!$root) {
			$root = 'fileadmin/';
		}
		$this->settings['root'] = $root;
		$this->settings['rootabs'] = ($root{0} === '/') ? $root : PATH_site . $root; 

			// Preparing the path to the directory
		$pathOptions = t3lib_div::trimExplode(' ', $this->settings['path']); // When RTE file browser is used, additionnal components may be present
		$this->settings['path'] = $this->sanitizePath($pathOptions[0]);

			// Retrieval of arguments
		$this->getPrefix = $this->pi_getClassName($this->cObj->data['uid']);
		$this->args = array_merge(
			array(
				'path'      => '',
				'order_by'  => '',
				'direction' => '',
			),
			t3lib_div::_GET($this->getPrefix)
		);

			// Disable Filelist if an error occurred
		$this->error = 0;
			// Load language data
		$this->pi_loadLL();
			// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
		$this->pi_USER_INT_obj = 1;
	}

	/**
	 * Resolves a site-relative path and or filename.
	 * 
	 * @param	string		$path
	 * @return	string
	 */
	protected function resolveSiteRelPath($path) {
		if (strcmp(substr($path, 0, 4), 'EXT:')) {
			return $path;
		}
		$path = substr($path, 4);	// Remove 'EXT:' at the beginning
		$extension = substr($path, 0, strpos($path, '/'));
		$references = explode(':', substr($path, strlen($extension) + 1));
		$pathOrFilename = t3lib_extMgm::siteRelPath($extension) . $references[0];

		if (is_dir(PATH_site . $pathOrFilename)) {
			$pathOrFilename = $this->sanitizePath($pathOrFilename);
		}

		return $pathOrFilename;
	}

	/**
	 * Loads local-language values by looking for a "locallang.php" file in the plugin class directory ($this->scriptRelPath) and if found includes it.
	 * Also locallang values set in the TypoScript property "_LOCAL_LANG" are merged onto the values found in the "locallang.php" file.
	 * Overrides the base method to load language file from new directory structure.
	 *
	 * @return	void
	 */
	public function pi_loadLL() {
		if (!$this->LOCAL_LANG_loaded) {
			$llFile = t3lib_extMgm::extPath($this->extKey) . 'Resources/Private/Language/locallang_pi1.xml';

				// Read the strings in the required charset (since TYPO3 4.2)
			$this->LOCAL_LANG = t3lib_div::readLLfile($llFile, $this->LLkey, $GLOBALS['TSFE']->renderCharset);
			if ($this->altLLkey) {
				$tempLOCAL_LANG = t3lib_div::readLLfile($llFile, $this->altLLkey);
				$this->LOCAL_LANG = array_merge(is_array($this->LOCAL_LANG) ? $this->LOCAL_LANG : array(), $tempLOCAL_LANG);
			}

				// Overlaying labels from TypoScript (including fictitious language keys for non-system languages!):
			if (is_array($this->conf['_LOCAL_LANG.'])) {
				foreach ($this->conf['_LOCAL_LANG.'] as $k => $lA) {
					if (is_array($lA)) {
						$k = substr($k, 0, -1);
						foreach ($lA as $llK => $llV) {
							if (!is_array($llV)) {
								$this->LOCAL_LANG[$k][$llK] = $llV;
									// For labels coming from the TypoScript (database) the charset is assumed to be "forceCharset" and if that is not set, assumed to be that of the individual system languages
								$this->LOCAL_LANG_charset[$k][$llK] = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] ? $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] : $GLOBALS['TSFE']->csConvObj->charSetArray[$k];
							}
						}
					}
				}
			}
		}
		$this->LOCAL_LANG_loaded = 1;
	}

	/**
	 * Returns an error message for frontend output.
	 *
	 * @param	string		$string Error message input
	 * @return	void
	 */
	protected function error($string) {
		return '
			<!-- ' . get_class($this) . ' ERROR message: -->
			<div class="' . $this->pi_getClassName('error') . '" style="
					border: 2px red solid;
					background-color: yellow;
					color: black;
					text-align: center;
					padding: 20px 20px 20px 20px;
					margin: 20px 20px 20px 20px;
					">'.
				'<strong>' . get_class($this) . ' ERROR:</strong><br /><br />' . nl2br(trim($string)) .
			'</div>';
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/file_list/pi1/class.tx_filelist_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/file_list/pi1/class.tx_filelist_pi1.php']);
}

?>