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

class tx_filelist_multilingual {

	public function filesDirectoriesProcessor(array $items, tx_filelist_pi1 $pObj) {
		$ret = array();

		// Filter out translations
		foreach ($items as $item) {
			if ($item['type'] === 'DIRECTORY' || !preg_match('/_..\.[^.]+$/', $item['path'])) {
				$ret[] = $item;
			}
		}
		return $ret;
	}

	public function extraItemMarkerProcessor(array $markers, array $data, tx_filelist_pi1 $pObj) {
		$markers['###FRENCH###'] = '';
		$markers['###GERMAN###'] = '';
		$markers['###ITALIAN###'] = '';

		if ($data['type'] === 'FILE') {
			// Search a translation
			foreach (array('fr' => '###FRENCH###', 'de' => '###GERMAN###', 'it' => '###ITALIAN###') as $translation => $tMarker) {
				$translationFullPath = preg_replace('/(\.[^.]+)$/', '_' . $translation . '\1', $data['fullpath']);
				$translationPath = preg_replace('/(\.[^.]+)$/', '_' . $translation . '\1', $data['path']);
				if (is_file($translationFullPath)) {
					$flag = '<img src="/typo3/gfx/flags/' . $translation . '.gif" alt="' . $translation . '" />';
					$markers[$tMarker] = $pObj->cObj->typolink($flag, array('parameter' => $translationPath));
				}
			}
		}

		return $markers;
	}

}
