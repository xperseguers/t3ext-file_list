<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "file_list".
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'File List',
    'description' => 'This extension provides a frontend plugin which shows a list of files and folders in a specified directory on the file system (comparable to Apache directory listing) or using more advanced FAL selectors (categories, collection of files, ...). This extension may also be used for creating image galleries. Default templates are Bootstrap-ready.',
    'category' => 'plugin',
    'version' => '3.0.1',
    'state' => 'stable',
    'author' => 'Xavier Perseguers',
    'author_email' => 'xavier@causal.ch',
    'author_company' => 'Causal Sàrl',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-12.4.99',
            'php' => '7.4.0-8.3.99',
        ],
        'conflicts' => [],
        'suggests' => [
            'fal_protect' => '1.5.1-'
        ],
    ],
];
