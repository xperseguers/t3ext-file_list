<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2011 Moreno Feltscher  <moreno@luagsh.ch>
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


/**
 * Class that adds the wizard icon.
 *
 * @package     TYPO3
 * @subpackage  tx_filelist
 * @author      Moreno Feltscher  <moreno@luagsh.ch>
 * @author      Xavier Perseguers  <typo3@perseguers.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class tx_filelist_pi1_wizicon {

	/**
	 * Processing the wizard items array
	 *
	 * @param	array		$wizardItems: The wizard items
	 * @return	Modified array with wizard items
	 */
	function proc($wizardItems) {
		$LL = $this->includeLocalLang();

		$wizardItems['plugins_tx_filelist_pi1'] = array(
			'icon'        => t3lib_extMgm::extRelPath('file_list') . 'pi1/ce_wiz.gif',
			'title'       => $GLOBALS['LANG']->getLLL('pi1_title', $LL),
			'description' => $GLOBALS['LANG']->getLLL('pi1_plus_wiz_description', $LL),
			'params'      => '&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=file_list_pi1'
		);

		return $wizardItems;
	}

	/**
	 * Reads the extension locallang.xml and returns the $LOCAL_LANG array found in that file.
	 *
	 * @return	The array with language labels
	 */
	function includeLocalLang()	{
		$llFile = t3lib_extMgm::extPath('file_list') . 'Resources/Private/Language/locallang.xml';
		$LOCAL_LANG = t3lib_div::readLLXMLfile($llFile, $GLOBALS['LANG']->lang);

		return $LOCAL_LANG;
	}
}



if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/file_list/pi1/class.tx_filelist_pi1_wizicon.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/file_list/pi1/class.tx_filelist_pi1_wizicon.php']);
}
