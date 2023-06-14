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
    'version' => '3.0.0',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearcacheonload' => 0,
    'author' => 'Xavier Perseguers',
    'author_email' => 'xavier@causal.ch',
    'author_company' => 'Causal Sàrl',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
            'php' => '7.2.0-8.2.99',
        ],
        'conflicts' => [],
        'suggests' => [
            'fal_protect' => '1.1.0-'
        ],
    ],
];
