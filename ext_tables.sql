#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
    tx_filelist_path tinytext NOT NULL,
    tx_filelist_order_by int(11) DEFAULT '0' NOT NULL,
    tx_filelist_order_sort int(11) DEFAULT '0' NOT NULL,
    tx_filelist_show_new tinytext NOT NULL,
    tx_filelist_fe_user_sort tinyint(3) DEFAULT '0' NOT NULL
);