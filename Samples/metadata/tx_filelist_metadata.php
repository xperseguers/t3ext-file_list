<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2015 Xavier Perseguers <xavier@causal.ch>
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

class tx_filelist_metadata {

	public function extraItemMarkerProcessor(array $markers, array $data, tx_filelist_pi1 $pObj) {
		$markers['###APERTURE###'] = '';
		$markers['###AUTHOR###'] = '';
		$markers['###DIMENSIONS###'] = '';

		if (!($data['type'] === 'FILE' && strtolower(substr($data['path'], -4)) === '.jpg')) {
			return $markers;
		}

		$fields = $pObj->settings['fields.'];

		// Update configuration with current filename
		$fields = $this->replaceFilename($fields, $data['fullpath']);

		// Populate additional markers
		$markers['###APERTURE###'] = $pObj->cObj->cObjGetSingle($fields['aperture'], $fields['aperture.']);
		$markers['###AUTHOR###'] = $pObj->cObj->cObjGetSingle($fields['author'], $fields['author.']);
		$markers['###DIMENSIONS###'] = $pObj->cObj->cObjGetSingle($fields['dimensions'], $fields['dimensions.']);

		return $markers;
	}

	/**
	 * Replaces the 'dynamicFilename' TS key with actual filename.
	 *
	 * @param	array	$ts
	 * @param	string	$filename
	 * @return	array
	 */
	function replaceFilename(array $ts, $filename) {
		$ret = array();
		foreach ($ts as $key => $value) {
			if (is_array($value)) {
				$value = $this->replaceFilename($value, $filename);
			} else if (!strcmp($key, 'dynamicFilename')) {
				$key = 'file';
				$value = $filename;
			}
			$ret[$key] = $value;
		}
		return $ret;
	}

}
