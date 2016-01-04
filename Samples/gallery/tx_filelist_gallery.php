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

class tx_filelist_gallery {

	public function filesDirectoriesProcessor(array $items, tx_filelist_pi1 $pObj) {

	}

	public function extraItemMarkerProcessor(array $markers, array $data, tx_filelist_pi1 $pObj) {
		$thumbnail = $pObj->settings['thumbnail.'];

			// Update configuration with current filename
		$thumbnail['file.']['10.']['file'] = $data['path'];
		$thumbnail['imageLinkWrap.']['typolink.']['parameter.']['cObject.']['file'] = $data['path'];
		$thumbnail['altText'] = $data['name'];

		$markers['###THUMBNAIL###'] = $pObj->cObj->IMAGE($thumbnail);

		return $markers;
	}

}
