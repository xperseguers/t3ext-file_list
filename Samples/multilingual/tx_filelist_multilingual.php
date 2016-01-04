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
