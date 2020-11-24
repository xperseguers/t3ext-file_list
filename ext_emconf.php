<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "file_list".
 *
 * Auto generated 25-05-2015 10:32
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'File List',
    'description' => 'This extension provides a frontend plugin which shows a list of files and folders in a specified directory on the file system (comparable to Apache directory listing) or using more advanced FAL selectors (categories, collection of files, ...). This extension may also be used for creating image galleries. Default templates are Bootstrap-ready.',
    'category' => 'plugin',
    'shy' => 0,
    'version' => '2.5.0-dev',
    'priority' => '',
    'loadOrder' => '',
    'module' => '',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => '',
    'clearcacheonload' => 0,
    'lockType' => '',
    'author' => 'Xavier Perseguers',
    'author_email' => 'xavier@causal.ch',
    'author_company' => 'Causal Sàrl',
    'CGLcompliance' => '',
    'CGLcompliance_note' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-10.4.99',
            'php' => '7.2.0-7.4.99',
        ],
        'conflicts' => [],
        'suggests' => [
            'fal_protect' => ''
        ],
    ],
    '_md5_values_when_last_written' => '',
];
