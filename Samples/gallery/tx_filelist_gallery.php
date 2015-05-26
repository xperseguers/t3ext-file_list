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
