<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$tempColumns = array(
	'tx_filelist_path' => array(        
		'exclude' => 0,
		'label' => 'LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_path',        
		'config' => array(
			'type' => 'input',
			'size' => '48',
			'eval' => 'required,trim',
		)
	),
	'tx_filelist_order_by' => array(        
		'exclude' => 0,        
		'label' => 'LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_order_by',        
		'config' => array(
			'type' => 'select',
			'items' => array(
				array('LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_order_by.I.0', '0'),
				array('LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_order_by.I.1', '1'),
				array('LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_order_by.I.2', '2'),
			),
			'size' => 1,    
			'maxitems' => 1,
		)
	),
	'tx_filelist_order_sort' => array(        
		'exclude' => 0,        
		'label' => 'LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_order_sort',        
		'config' => array(
			'type' => 'select',
			'items' => array(
				array('LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_order_sort.I.0', '0'),
				array('LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_order_sort.I.1', '1'),
			),
			'size' => 1,    
			'maxitems' => 1,
		)
	),
	'tx_filelist_show_new' => array(        
		'exclude' => 0,        
		'label' => 'LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_show_new',        
		'config' => array(
			'type' => 'input',    
			'size' => '30',    
		)
	),
	'tx_filelist_fe_user_sort' => array(        
		'exclude' => 0,        
		'label' => 'LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_fe_user_sort',        
		'config' => array(
			'type' => 'check',
		)
	),
);

if (t3lib_extMgm::isLoaded('rgfolderselector')) {
		// Add support for EXT:rgfolderselector
	$tempColumns['tx_filelist_path']['config']['wizards'] = array(
		'_PADDING' => 2,
		'link' => array(
			'type' => 'popup',
			'title' => 'LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_path_wizards.link.title',
			'icon' => 'link_popup.gif',
			'script' => 'EXT:rgfolderselector/browse_links.php',
			'JSopenParams' => 'height=400,width=400,status=0,menubar=0,scrollbars=1'
		),
	);
}

t3lib_div::loadTCA('tt_content');
t3lib_extMgm::addTCAcolumns('tt_content', $tempColumns, 1);

	// Disable the display of layout, select_key and page fields
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_pi1'] = 'layout,select_key,pages';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_pi1'] = 'tx_filelist_path;;;;1-1-1, tx_filelist_order_by, tx_filelist_order_sort, tx_filelist_show_new, tx_filelist_fe_user_sort';

t3lib_extMgm::addPlugin(array('LLL:EXT:file_list/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY . '_pi1'), 'list_type');

t3lib_extMgm::addStaticFile($_EXTKEY,'pi1/static/', 'File List');
?>
