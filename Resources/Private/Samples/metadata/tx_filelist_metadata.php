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

class tx_filelist_metadata
{

    public function extraItemMarkerProcessor(array $markers, array $data, tx_filelist_pi1 $pObj)
    {
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
     * @param    array $ts
     * @param    string $filename
     * @return    array
     */
    function replaceFilename(array $ts, $filename)
    {
        $ret = [];
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
