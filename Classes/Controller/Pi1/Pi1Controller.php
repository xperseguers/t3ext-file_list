<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2015 Xavier Perseguers <xavier@causal.ch>
 *  (c) 2006-2011 Moreno Feltscher <moreno@luagsh.ch>
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

use Causal\FileList\Utility\Helper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Plugin 'File List' for the 'file_list' extension.
 *
 * @package     TYPO3
 * @subpackage  tx_filelist
 * @author      Moreno Feltscher <moreno@luagsh.ch>
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class tx_filelist_pi1 extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {

	// Members coming from tslib_pibase
	public $prefixId = 'tx_filelist_pi1';
	public $scriptRelPath = 'Classes/Controller/Pi1/Pi1Controller.php';
	public $extKey = 'file_list';

	/**
	 * Settings of the extension. Public to be available in hooks.
	 * @var array
	 */
	public $settings = array();

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
	 * @param string $content The Plugin content
	 * @param array $settings The Plugin configuration
	 * @return string Content which appears on the website
	 */
	public function main($content, array $settings) {
		$this->init($settings);
		$this->pi_setPiVarDefaults();
		$this->initTemplate();

		$listingPath = $this->settings['path'];
		if ($this->args['path']) {
			$listingPath = Helper::sanitizePath($listingPath . $this->args['path']);
		}

		// Check that $listingPath is a valid directory
		if (!(is_dir($listingPath) && is_readable($listingPath) && $this->isValidDirectory($listingPath))) {
			return $this->error(sprintf('Could not open directory "%s"', $this->settings['path']));
		}

		if ($this->settings['fe_sort'] && $this->args['direction']) {
			$this->settings['sort_direction'] = $this->args['direction'];
		}
		if ($this->settings['fe_sort'] && $this->args['order_by']) {
			$this->settings['order_by'] = $this->args['order_by'];
			if (!GeneralUtility::inList('name,date,size', $this->settings['order_by'])) {
				$this->settings['order_by'] = 'name';
			}
		}

		list($subdirs, $files) = Helper::getDirectoryContent($listingPath, $this->settings['ignoreFileNamePattern'], $this->settings['ignoreFolderNamePattern']);

		// Hook for post-processing the list of files
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['file_list']['filesDirectoriesHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['file_list']['filesDirectoriesHook'] as $_classRef) {
				$_procObj =& GeneralUtility::getUserObj($_classRef);
				$files = $_procObj->filesDirectoriesProcessor($files, $this);
				$subdirs = $_procObj->filesDirectoriesProcessor($subdirs, $this);
			}
		}

		// Are there any files in the directory?
		if ((count($files) === 0) && (count($subdirs) === 0) && !$this->args['path']) {
			$content = $this->pi_getLL('no_files');
			return $this->pi_wrapInBaseClass($content);
		}

		// Sort directories and files according to user settings
		$subdirs = Helper::arraySort($subdirs, $this->settings['order_by'], $this->settings['sort_direction']);
		$files = Helper::arraySort($files, $this->settings['order_by'], $this->settings['sort_direction']);

		// Generate table rows
		$odd = TRUE;
		$directoryRows = $this->generateDirectoryRows($subdirs, $listingPath, $odd);
		$fileRows = $this->generateFileRows($files, $listingPath, $odd);
		$content = $this->generateTable(array_merge($directoryRows, $fileRows));

		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * Returns templated rows for a given array of directories.
	 *
	 * @param array $directories
	 * @param string $listingPath Current path
	 * @param bool $odd Whether first row is an odd row.
	 * @return array
	 */
	protected function generateDirectoryRows(array $directories, $listingPath, &$odd) {
		$rows = array();

		// Put '..' at the beginning of the array
		array_unshift($directories, array(
			'name' => '..',
			'path' => Helper::sanitizePath($listingPath . '../')
		));

		for ($i = 0; $i < count($directories); $i++) {
			if (!$this->args['path'] && $directories[$i]['name'] === '..') {
				continue;
			}
			$markers = array();
			$markers['###ICON###'] = '<a href="' . $this->getLink(array('path' => substr($directories[$i]['path'], strlen($this->settings['path'])))) . '">';
			if ($directories[$i]['name'] === '..') {
				$markers['###ICON###'] .= '<img src="' . $this->settings['iconsPathFolders'] . 'move_up.png" alt="' . $directories[$i]['name'] . '" border="0" />';
			} else {
				$markers['###ICON###'] .= '<img src="' . $this->settings['iconsPathFolders'] . 'folder.png" alt="' . $directories[$i]['name'] . '" border ="0" />';
			}
			$markers['###ICON###'] .= '</a>';
			$markers['###FILENAME###'] = $directories[$i]['name'];
			$markers['###PATH###'] = substr($directories[$i]['path'], strlen($this->settings['path']));
			$markers['###NEWFILE###'] = '';
			$markers['###INFO###'] = '';
			if (isset($directories[$i]['size'])) {
				if ($directories[$i]['size'] > 0) {
					$markers['###INFO###'] = $directories[$i]['size'] . ' ';
					if ($directories[$i]['size'] > 1) {
						$markers['###INFO###'] .= $this->pi_getLL('directory_size.multiple_files');
					} else {
						$markers['###INFO###'] .= $this->pi_getLL('directory_size.one_file');
					}
				} else {
					$markers['###INFO###'] = $this->pi_getLL('directory_size.no_files');
				}
			}
			$markers['###DATE###'] = $directories[$i]['date'] > 0 ? BackendUtility::datetime($directories[$i]['date']) : '';

			// Hook for processing of extra item markers
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['file_list']['extraItemMarkerHook'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['file_list']['extraItemMarkerHook'] as $_classRef) {
					$_procObj =& GeneralUtility::getUserObj($_classRef);
					$markers = $_procObj->extraItemMarkerProcessor($markers, $directories[$i], $this);
				}
			}

			$wrappedSubpartArray['###LINK_FILE###'] = array(
				'<a href="' . $this->getLink(array('path' => substr($directories[$i]['path'], strlen($this->settings['path'])))) . '">',
				'</a>'
			);

			$row = $this->cObj->substituteMarkerArrayCached(
				$odd ? $this->templates['odd'] : $this->templates['even'],
				$markers,
				array(),
				$wrappedSubpartArray
			);
			$rows[] = $row;
			$odd = !$odd;
		}

		return $rows;
	}

	/**
	 * Returns rows with applied template for a given array of files.
	 *
	 * @param array $files
	 * @param string $listingPath Current path
	 * @param bool $odd Whether first row is an odd row.
	 * @return array
	 */
	protected function generateFileRows(array $files, $listingPath, &$odd) {
		$rows = array();
		for ($i = 0; $i < count($files); $i++) {
			$markers = array();
			$markers['###ICON###'] = $this->cObj->typolink('<img src="' . $this->settings['iconsPathFiles'] . $this->getFileTypeIcon($files[$i]['name']) . '" alt="' . $files[$i]['name'] . '">', array('parameter' => Helper::generateProperURL($files[$i]['path'])));
			$markers['###FILENAME###'] = $files[$i]['name'];
			$markers['###PATH###'] = $files[$i]['path'];
			$markers['###NEWFILE###'] = ($this->settings['new_duration'] > 0) ? $this->getNewFileText($files[$i]['path'], $this->settings['new_duration']) : '';
			$markers['###INFO###'] = $this->getHRFileSize($files[$i]['path']);
			$markers['###DATE###'] = BackendUtility::datetime(filemtime($listingPath . $files[$i]['name']));

			// Hook for processing of extra item markers
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['file_list']['extraItemMarkerHook'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['file_list']['extraItemMarkerHook'] as $_classRef) {
					$_procObj =& GeneralUtility::getUserObj($_classRef);
					$markers = $_procObj->extraItemMarkerProcessor($markers, $files[$i], $this);
				}
			}
			$wrappedSubpartArray['###LINK_FILE###'] = $this->cObj->typolinkWrap(array('parameter' => Helper::generateProperURL($files[$i]['path'])));

			$row = $this->cObj->substituteMarkerArrayCached(
				$odd ? $this->templates['odd'] : $this->templates['even'],
				$markers,
				array(),
				$wrappedSubpartArray
			);
			$rows[] = $row;
			$odd = !$odd;
		}

		return $rows;
	}

	/**
	 * Returns a table with applied template containing all given rows as body.
	 *
	 * @param array $rows Rows with applied template
	 * @return string
	 */
	protected function generateTable(array $rows) {
		// Replace header markers and create the listing table
		$markers = array(
			'###HEADER_FILENAME###' => $this->pi_getLL('filename'),
			'###HEADER_INFO###' => $this->pi_getLL('info'),
			'###HEADER_DATE###' => $this->pi_getLL('date'),
			'###BODY###' => implode("\n", $rows),
		);
		if ($this->settings['fe_sort']) {
			$markers['###HEADER_FILENAME###'] .= $this->getFeSortIcons('name');
			$markers['###HEADER_INFO###'] .= $this->getFeSortIcons('size');
			$markers['###HEADER_DATE###'] .= $this->getFeSortIcons('date');
		}
		return $this->cObj->substituteMarkerArray($this->templates['table'], $markers);
	}

	/**
	 * Reads the template file, fills in global wraps and markers and writes the result
	 * parts to $this->templates array.
	 *
	 * @return void
	 */
	protected function initTemplate() {
		$content = $this->cObj->fileResource($this->settings['templateFile']);
		$templateCode = $this->cObj->getSubpart($content, '###TEMPLATE_DEFAULT###');

		$globalMarkerArray = array(
			'###TABLE_CLASS###'     => $this->pi_getClassName('table'),
			'###ODD_CLASS###'       => $this->pi_getClassName('odd'),
			'###EVEN_CLASS###'      => $this->pi_getClassName('even'),
			'###ICON_CLASS###'      => $this->pi_getClassName('icon'),
			'###FILENAME_CLASS###'  => $this->pi_getClassName('filename'),
			'###NEWFILE_CLASS###'	=> $this->pi_getClassName('newFile'),
			'###INFO_CLASS###'      => $this->pi_getClassName('info'),
			'###DATE_CLASS###'      => $this->pi_getClassName('date'),
		);
		$templateCode = $this->cObj->substituteMarkerArray($templateCode, $globalMarkerArray);

		$this->templates['odd'] = $this->cObj->getSubpart($templateCode, '###ODD_TEMPLATE###');
		$this->templates['even'] = $this->cObj->getSubpart($templateCode, '###EVEN_TEMPLATE###');

		$defaultTemplate = $this->cObj->getSubpart($templateCode, '###TEMPLATE###');
		if (empty($this->templates['odd'])) {
			$this->templates['odd'] = $defaultTemplate;
		}
		if (empty($this->templates['even'])) {
			$this->templates['even'] = $defaultTemplate;
		}

		$this->templates['table'] = $this->cObj->substituteSubpart($templateCode, '###BODY###', '###BODY###');
	}

	/**
	 * Returns a link to the same page with additional parameters.
	 *
	 * @param array $params
	 * @return string
	 */
	protected function getLink(array $params) {
		// Merge existing parameters with $params
		foreach ($this->args as $key => $value) {
			if (!isset($params[$key])) {
				$params[$key] = $value;
			}
		}

		$conf = array();
		$conf['useCacheHash'] = $this->pi_USER_INT_obj ? 0 : 1;
		$conf['no_cache'] = FALSE;
		$conf['parameter'] = $GLOBALS['TSFE']->id;
		$conf['additionalParams'] = GeneralUtility::implodeArrayForUrl($this->getPrefix, $params, '', TRUE);

		$this->cObj->typoLink('|', $conf);
		return $this->cObj->lastTypoLinkUrl;
	}

	/**
	 * Returns the icon which represents a file type
	 *
	 * @param string $filename Path to the specified file
	 * @return string Filename of the icon
	 */
	protected function getFileTypeIcon($filename) {
		$categories = array();
		foreach ($this->settings['extension.']['category.'] as $category => $extensions) {
			$categories[$category] = GeneralUtility::trimExplode(',', $extensions);
		}
		$remapExtensions = $this->settings['extension.']['remap.'];

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
			if (is_file($this->settings['iconsPathFiles'] . $ext . '.png')) {
				return $ext . '.png';
			} elseif (is_file($this->settings['iconsPathFiles'] . $ext . '.gif')) {
				return $ext . '.gif';
			}
		}

		// Try to find a file type category icon
		foreach ($categories as $cat => $extensions) {
			if (GeneralUtility::inArray($extensions, $ext)) {
				return 'category_' . $cat . '.png';
			}
		}

			// Fallback icon
		return 'blank_document.png';
	}

	/**
	 * Checks that the given path is within the allowed root directory and
	 * within the plugin's root directory.
	 *
	 * @param string $path Path relative to the website root
	 * @return bool
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
	 * @param string $filename Path to the specified file
	 * @return string Size of the file
	 */
	protected function getHRFileSize($filename) {
		$units = array(
			'0' => $this->pi_getLL('units.bytes'),
			'1' => $this->pi_getLL('units.KB'),
			'2' => $this->pi_getLL('units.MB'),
			'3' => $this->pi_getLL('units.GB'),
			'4' => $this->pi_getLL('units.TB'),
		);
		$filesize = filesize($filename);
		for ($offset = 0; $filesize >= 1024; $offset++) {
			$filesize /= 1024;
		}
		$decimalPlaces = ($offset < 2) ? 0 : $offset - 1;
		$format = '%.' . $decimalPlaces . 'f %s';
		return sprintf($format, $filesize, $units[$offset]);
	}

	/**
	 * Returns a text stating "new" if a file is considered to be marked as new
	 *
	 * @param string $fn Path to the specified file
	 * @param int $duration User specific amount of days within a file is considered to be new
	 * @return string
	 */
	protected function getNewFileText($fn, $duration) {
		return ($duration > 0 && filemtime($fn) > mktime(0, 0, 0, date('m'), date('d') - $duration, date('Y'))) ? $this->pi_getLL('newFile') : '';
	}

	/**
	 * Reads out the icons in order to sort FE output of files
	 *
	 * @param string $order_by Order by (name, date, size)
	 * @return string HTML of ordering icons
	 */
	protected function getFeSortIcons($order_by) {
		$ret = '&nbsp;';
		$direction = 'desc';
		for ($i = 0; $i < 2; $i++) {
			$link = $this->getLink(array(
				'path'      => $this->args['path'],
				'order_by'  => $order_by,
				'direction' => $direction,
			));
			$ret .= '<a href="' . $link . '" target="_top" title="' . $this->pi_getLL('sort.' . $direction) . '">';
			$ret .= '<img src="' . $this->settings['iconsPathSorting'] . $direction .'.gif" alt="' . $this->pi_getLL('sort.' . $direction) . '" border="0" />';
			$ret .= '</a>';
			$direction = 'asc';
		}
		return $ret;
	}

	/**
	 * This method performs various initializations.
	 *
	 * @param array $settings Plugin configuration, as received by the main() method
	 * @param array $explodeFlexFormFields FlexForm fields to be exploded as an array
	 * @return void
	 */
	protected function init(array $settings, array $explodeFlexFormFields = array()) {
		// Initialize default values based on extension TS
		$this->settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
		if (!is_array($this->settings)) {
			$this->settings = array();
		}

		// Base configuration is equal the the plugin's TS setup
		$this->settings = array_merge($this->settings, $settings);

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

		// Set the icons paths
		foreach (array('Files', 'Folders', 'Sorting') as $subdirectory) {
			if (isset($this->settings['iconsPath' . $subdirectory])) {
				$iconsPath = $this->cObj->stdWrap($this->settings['iconsPath' . $subdirectory], $this->settings['iconsPath' . $subdirectory . '.']);
				$this->settings['iconsPath' . $subdirectory] = Helper::resolveSiteRelPath($iconsPath);
			} else {	// Fallback
				$this->settings['iconsPath' . $subdirectory] = ExtensionManagementUtility::siteRelPath('file_list') . 'Resources/Public/Icons/' . $subdirectory . '/';
			}
		}

		// Prepare open base directory
		$root = $this->settings['root'];
		if (!$root) {
			$root = 'fileadmin/';
		}
		$this->settings['root'] = $root;
		$this->settings['rootabs'] = ($root{0} === '/') ? $root : PATH_site . $root;

		// Prepare the path to the directory
		if (!ExtensionManagementUtility::isLoaded('rgfolderselector')) {
			// When RTE file browser is used, additional components may be present (target/class according
			// to typolink documentation. However those additional arguments cannot contain a slash (/) except for
			// the title attribute but then it should be either be alone in title, or should not contain spaces in
			// in AND should end with a / (otherwise it would be quoted). This is very unlikely to occur and it's
			// just fine to fail under those conditions.
			// As such, we take for granted that a slash at the end of the path marks the end of it and any
			// space-delimited argument before is in fact part of the path itself.
			if (strrpos($this->settings['path'], '/') != strlen($this->settings['path']) - 1) {
				$pathOptions = GeneralUtility::trimExplode(' ', $this->settings['path']);
				$this->settings['path'] = $pathOptions[0];
			}

			// Furthermore, if TYPO3 native folder browser is used with a directory containing spaces,
			// the resulting path will have spaces encoded as %20
			$this->settings['path'] = urldecode($this->settings['path']);
		}

		// Compatibility with FAL-encoded paths in TYPO3 6.x
		if (preg_match('/^file:(\d+):(.*)$/', $this->settings['path'], $matches)) {
			/** @var $storageRepository \TYPO3\CMS\Core\Resource\StorageRepository */
			$storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
			/** @var $storage \TYPO3\CMS\Core\Resource\ResourceStorage */
			$storage = $storageRepository->findByUid((int)$matches[1]);
			$storageRecord = $storage->getStorageRecord();
			if ($storageRecord['driver'] === 'Local') {
				$storageConfiguration = $storage->getConfiguration();
				$basePath = rtrim($storageConfiguration['basePath'], '/') . '/';
				$this->settings['path'] = $basePath . substr($matches[2], 1);
			}
		}

		$this->settings['path'] = Helper::sanitizePath($this->settings['path']);

		// Retrieval of arguments
		$this->getPrefix = $this->pi_getClassName($this->cObj->data['uid']);
		$arguments = GeneralUtility::_GET($this->getPrefix);
		if (isset($arguments['noCache'])) {
			unset($arguments['noCache']);
		}
		$this->args = array(
			'path'      => '',
			'order_by'  => '',
			'direction' => ''
		);
		if (is_array($arguments)) {
			$this->args = array_merge($this->args, $arguments);
		}

		// Disable file_list if an error occurred
		$this->error = 0;
		// Load language data
		$this->pi_loadLL();
		// Configure the plugin either as USER or USER_INT according to plugin configuration
		$this->pi_USER_INT_obj = $this->settings['noCache'] ? 1 : 0;
	}

	/**
	 * Loads local-language values by looking for a "locallang.php" file in the plugin class directory ($this->scriptRelPath) and if found includes it.
	 * Also locallang values set in the TypoScript property "_LOCAL_LANG" are merged onto the values found in the "locallang.php" file.
	 * Overrides the base method to load language file from new directory structure.
	 *
	 * @return void
	 */
	public function pi_loadLL() {
		if (!$this->LOCAL_LANG_loaded) {
			$llFile = ExtensionManagementUtility::extPath($this->extKey) . 'Resources/Private/Language/locallang_pi1.xml';

			// Read the strings in the required charset (since TYPO3 4.2)
			$this->LOCAL_LANG = GeneralUtility::readLLfile($llFile, $this->LLkey, $GLOBALS['TSFE']->renderCharset);
			if ($this->altLLkey) {
				$tempLOCAL_LANG = GeneralUtility::readLLfile($llFile, $this->altLLkey);
				$this->LOCAL_LANG = array_merge(is_array($this->LOCAL_LANG) ? $this->LOCAL_LANG : array(), $tempLOCAL_LANG);
			}

			// Overlaying labels from TypoScript (including fictitious language keys for non-system languages!):
			if (is_array($this->settings['_LOCAL_LANG.'])) {
				foreach ($this->settings['_LOCAL_LANG.'] as $k => $lA) {
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
	 * @param string $string Error message input
	 * @return string
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
