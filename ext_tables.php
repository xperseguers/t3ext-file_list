<?php
if (!defined ('TYPO3_MODE'))     die ('Access denied.');
$tempColumns = Array (
    "tx_filelist_path" => Array (        
        "exclude" => 0,        
        "label" => "LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_path",        
        "config" => Array (
            "type" => "input",    
            "size" => "48",    
            "eval" => "required",
        )
    ),
    "tx_filelist_order_by" => Array (        
        "exclude" => 0,        
        "label" => "LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_order_by",        
        "config" => Array (
            "type" => "select",
            "items" => Array (
                Array("LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_order_by.I.0", "0"),
                Array("LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_order_by.I.1", "1"),
                Array("LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_order_by.I.2", "2"),
            ),
            "size" => 1,    
            "maxitems" => 1,
        )
    ),
    "tx_filelist_order_sort" => Array (        
        "exclude" => 0,        
        "label" => "LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_order_sort",        
        "config" => Array (
            "type" => "select",
            "items" => Array (
                Array("LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_order_sort.I.0", "0"),
                Array("LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_order_sort.I.1", "1"),
            ),
            "size" => 1,    
            "maxitems" => 1,
        )
    ),
    "tx_filelist_show_new" => Array (        
        "exclude" => 0,        
        "label" => "LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_show_new",        
        "config" => Array (
            "type" => "input",    
            "size" => "30",    
        )
    ),
	"tx_filelist_fe_user_sort" => Array (        
        "exclude" => 0,        
        "label" => "LLL:EXT:file_list/locallang_db.xml:tt_content.tx_filelist_fe_user_sort",        
        "config" => Array (
            "type" => "check",
        )
    ),
);


t3lib_div::loadTCA("tt_content");
t3lib_extMgm::addTCAcolumns("tt_content",$tempColumns,1);


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='tx_filelist_path;;;;1-1-1, tx_filelist_order_by, tx_filelist_order_sort, tx_filelist_show_new, tx_filelist_fe_user_sort';


t3lib_extMgm::addPlugin(Array('LLL:EXT:file_list/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');


t3lib_extMgm::addStaticFile($_EXTKEY,"pi1/static/","File List");
?>