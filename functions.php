<?php 
/**
* File with the most common functions of project
*
* This file contain the most common functions which uses
* at the begin files of the project.
* They are classified as SQLite functions, functions of 
* manipulating with css, etc.
*
* @author yudenisov <yudenisov@mail.ru>
* @copyright (C) Юрий А. Денисов(yudenisov) 2016-2017
* @version 19 Beta
*/

// Turn off PHP notices for now:
error_reporting(E_ALL & ~E_NOTICE);

// Define various constants:
define('THFRPATH', dirname(__FILE__) . '/');
define('THFRDIRNAME', basename(dirname(__FILE__)));
define('THFRURL', '/' . THFRDIRNAME . '/');
define('RELATIVETHFRURL', '/' . THFRDIRNAME . '/');
define('THFRUPLOADPATH', THFRPATH . 'uploads');
define('RELATIVETHFRUPLOADURL', RELATIVETHFRURL . 'uploads/');
define('THFRUPLOADURL', RELATIVETHFRURL . 'uploads/');

// Checks if table XY exists
/**
* Function sql_table_exist checks if table $table exist
*
* @param PDO &$sqlite -- SQLite descriptor
* @param string $table -- name of testing table
* @staticvar string $sql -- variable with SQL Query
* @staticvar PDOExeption $e -- variable for PDO Exeption Value
* @return boolean
* @package sqlite database functions
*/
function sql_table_exist(&$sqlite, $table) {
   $sql = "SELECT * FROM " . $table;
   try {
     $sqlite->query($sql);
   } catch (PDOException $e ) {
     return FALSE;
   }
   return TRUE;
}

/**
* Function get_thfr gets cssthfr structure from SQLite
*
* @global PDO $db
* @param void
* @staticvar string $sql -- variable with SQL Query
* @staticvar PDOStatement $result -- variable with sql query results
* @staticvar PDOStatement $row -- variable with a first result row of query
* @return string array
* @package sqlite database functions
*/
function get_thfr() {
	global $db;
  $sql = "
    SELECT option_value
	    FROM newdefault
	    WHERE option_name like 'thfr'
  ";
	$result = $db->query($sql);
	$row = $result->fetch();
	return(unserialize($row[0]));
	# return(json_decode($row[0], TRUE));
}

/**
* Function set_thfr saves thfr_css structure to SQLite file
*
* @global PDO $db -- SQLIte database descriptor
* @global string array $thfr_css -- CSS data array
* @param string $import_file (may be void) is imported data file
* @staticvar string $thfr_css1 - Serialized CSS DATA array
* @staticvar string $sql -- variable with SQL Query
* @staticvar PDOStatement $stmt -- variable with sql query statement
* @staticvar PDOStatement $result -- variable with sql query results
* @staticvar PDOStatement $row -- variable with a first result row of query
* @return void
* @package sqlite database functions
*/
function set_thfr($import_file = '') {
	global $thfr_css;
	global $db;
	if ($import_file != '') {
		$thfr_css1 = file_get_contents($import_file);
		$thfr_css = unserialize( $thfr_css1 );
//		$thfr_css = json_decode(( $thfr_css1, TRUE );
	} else {
		$thfr_css1 = serialize($thfr_css);
		# $thfr_css1 = json_encode($thfr_css);
	}
	$sql =  "
		UPDATE newdefault
			SET option_value = :thfr_css1
      		WHERE option_name like 'thfr'
	  ";
	$stmt = $db->prepare($sql);
	$stmt->bindValue(':thfr_css1', $thfr_css1, PDO::PARAM_STR);
	$stmt->execute();
	$result = $stmt->fetch();
	if( $result )
	{
		$sql = "
			INSERT into newdefault
	      		(option_name, option_value)
	      		VALUES( 'thfr', :thfr_css_prepared );
		";
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':thfr_css_prepared', $thfr_css1, PDO::PARAM_STR);
		$stmt->execute();
	}
	return;
}

/**
* Function reset_thfr is reset thfr_css structure at SQLite file
*
* This function makes empty the first row at SQLite 3 DATA file
* @global PDO $db -- SQLIte database descriptor
* @staticvar string $thfr_css1 - Serialized CSS DATA array
* @staticvar string $sql -- variable with SQL Query
* @staticvar PDOStatement $stmt -- variable with sql query statement
* @staticvar PDOStatement $result -- variable with sql query results
* @staticvar PDOStatement $row -- variable with a first result row of query
* @return void
* @package sqlite database functions
*/
function reset_thfr() {
	global $db;
	$thfr_css1 = '';
	$sql = "
		UPDATE newdefault
  			SET option_value = :thfr_css1
			WHERE option_name like 'thfr'
		";
	$stmt = $db->prepare($sql);
	$stmt->bindValue(':thfr_css1', $thfr_css1, PDO::PARAM_STR);
	$stmt->execute();
	$result = $stmt->fetch();
	if( $result )
	{
		$sql = "
			INSERT into newdefault
				(option_name, option_value)
				VALUES( 'thfr', :thfr_css1 );
			";
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':thfr_css1', $thfr_css1, PDO::PARAM_STR);
		$stmt->execute();
	}
	echo "Settings have been reset...";
	return;
}

/**
* Function delete_drop_item
*
* Function delete_drop_item is cleaning thfr_css string array of data
* @global string array $thfr_css -- CSS data array
* @staticvar string $itemgroup -- sent item group which must deleted
* @staticvar string $itemname -- sent item name which must deleted
* @staticvar string $extension -- group name extension
* @staticvar string $dropitemname -- is full item name dropped
* @staticvar string array $temparray -- is array of
*/
function delete_drop_item() {
	global $thfr_css;
	$itemgroup = $_POST['itemgroup'];
	$itemname = $_POST['itemname'];
	$extension = "";
	//
	switch ($itemgroup) {
		case "customdroplinks": $extension = '_custlink'; break;
		case "customdropimages": $extension = '_custimg'; break;
	}
	$dropitemname = $itemname . $extension;
	//
	if (isset($thfr_css[$itemgroup][$itemname])) unset($thfr_css[$itemgroup][$itemname]);

	$temparray = array('header', 'centerTop', 'centerBottom', 'footer', 'drop');

	foreach($thfr_css['pagetemplates'] as $template) {
		$newdroparea = array();
		foreach($temparray as $droparea) {
			$newdroparea[$droparea] = array();
			$i = 0;
			while ($thfr_css[$template][$droparea . '-' . $i] != '') {
				if ($thfr_css[$template][$droparea . '-' . $i] != $dropitemname) {
					$newdroparea[$droparea][] = $thfr_css[$template][$droparea . '-' . $i];
				}
				$i++;
			}
			// Clear all existing values:
			for ($i = 0 ; $i < 31 ; $i++){
				if (isset($thfr_css[$template][$droparea . '-' . $i]))
					unset($thfr_css[$template][$droparea . '-' . $i]);
			}
			// Create new values:
			$i = 0;
			foreach($newdroparea[$droparea] as $dropitem) {
				$thfr_css[$template][$droparea . '-' . $i] = $dropitem;
				$i++;
			}
		}
	}

	set_thfr();
	echo $itemname . " has been deleted...";
	return;
}

function delete_page_template() {
	global $thfr_css;
	$tplname = $_POST['tplname'];

	// remove item by value
	if( in_array($tplname, $thfr_css['pagetemplates']))
	$thfr_css['pagetemplates'] = array_diff($thfr_css['pagetemplates'], array($tplname));

	// remove this too
	if (isset($thfr_css[$tplname])) unset($thfr_css[$tplname]);

	set_thfr();
	echo $tplname . ".php has been deleted...";

	return;
}

function tf_new_postcontainer($newPostContainer, $model) {
	global $thfr_css;

	if ( isset($thfr_css['postcontainers'][$model]) ) {
		if( isset($thfr_css['postcontainers'][$newPostContainer]) ) {
			echo "<span style='color:green'>Successfully created new post container type '$newPostContainer'</span>";
			return;
		} else {
			$thfr_css['postcontainers'][$newPostContainer] = $thfr_css['postcontainers'][$model];
			set_thfr();
			echo "<span style='color:green'>Successfully created new post container type '$newPostContainer'</span>";
			return;
		}
	}

	echo "The post container '$model' does not exist";
	return;
}

//
function file_extension($filename) {
	$path_info = pathinfo($filename);
	return $path_info['extension'];
}

// Check if DB "thfrdb" exists, else create it or display error

try {
    $db = new PDO(
        'sqlite:'.dirname(__FILE__).'/sqlite/thfrdb.sqlite3'
    );
} catch (PDOException $e) {
    print "Connection error: " . $e->getMessage() . "<br/>";
    die();
}
  // if table "default" does not exist, create it
if(sql_table_exist( $db, 'newdefault') === FALSE ){
  try {
	// Create table
    $sql = "CREATE TABLE newdefault (
      id           INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
      option_name  TEXT,
      option_value TEXT
      )";
    $db->query($sql);
} catch(PDOException $e){
    print "Creating table error!: " . $e->getMessage() . "<br/>";
    die();
}
}

$thfr_css = get_thfr();

// Default settings
if (!isset($thfr_css['savedonce'])) {

	$thfr_css = '';
	$thfr_css = unserialize(file_get_contents(THFRPATH . 'styles/default.txt'));
/*
	# $thfr_css['read_more_text'] = "Continue reading &raquo; <a href='%permalink%'>%title%</a>";

	$thfr_css['read_more_text'] = "Read More &raquo;";


	$thfr_css['index']['sidebarsleft'] = "1";
	$thfr_css['index']['sidebarsright'] = "1";
	$thfr_css['index']['header-0'] = "searchform_item";
	$thfr_css['index']['header-1'] = "sitetitle_item";
	$thfr_css['index']['header-2'] = "rsscommentslink_item";
	$thfr_css['index']['header-3'] = "rsslink_item";
	$thfr_css['index']['header-4'] = "tagline_item";
	$thfr_css['index']['header-5'] = "hormenu2_item";
	$thfr_css['index']['header-6'] = "headerimage_item";
	$thfr_css['index']['header-7'] = "hormenu_item";
	$thfr_css['index']['header-8'] = "breadcrumb_item";
	$thfr_css['index']['centerTop-0'] = "pagetitle_item";
	$thfr_css['index']['centerTop-1'] = "multinav_item";
	$thfr_css['index']['centerBottom-0'] = "multinav2_item";
	$thfr_css['index']['footer-0'] = "footer_item";
	$thfr_css['index']['drop-0'] = "breadcrumb2_item";
	$thfr_css['index']['drop-1'] = "twitter_item";
	$thfr_css['index']['drop-2'] = "facebook_item";
	$thfr_css['index']['drop-3'] = "feedburner_item";
	$thfr_css['index']['drop-4'] = "widgetarea_1";
	$thfr_css['index']['drop-5'] = "widgetarea_1_2";
	$thfr_css['index']['drop-6'] = "widgetarea_1_3";
	$thfr_css['index']['drop-7'] = "widgetarea_2";
	$thfr_css['index']['drop-8'] = "widgetarea_2_2";
	$thfr_css['index']['drop-9'] = "widgetarea_3";
	$thfr_css['index']['drop-10'] = "widgetarea_4";
	$thfr_css['index']['drop-11'] = "widgetarea_5";
	$thfr_css['index']['drop-12'] = "widgetarea_6";

	$thfr_css['home']['sidebarsleft'] = "1";
	$thfr_css['home']['sidebarsright'] = "1";
	$thfr_css['home']['header-0'] = "searchform_item";
	$thfr_css['home']['header-1'] = "sitetitle_item";
	$thfr_css['home']['header-2'] = "rsscommentslink_item";
	$thfr_css['home']['header-3'] = "rsslink_item";
	$thfr_css['home']['header-4'] = "tagline_item";
	$thfr_css['home']['header-5'] = "hormenu2_item";
	$thfr_css['home']['header-6'] = "headerimage_item";
	$thfr_css['home']['header-7'] = "hormenu_item";
	$thfr_css['home']['header-8'] = "breadcrumb_item";
	$thfr_css['home']['centerTop-0'] = "pagetitle_item";
	$thfr_css['home']['centerTop-1'] = "multinav_item";
	$thfr_css['home']['centerBottom-0'] = "multinav2_item";
	$thfr_css['home']['footer-0'] = "footer_item";
	$thfr_css['home']['drop-0'] = "breadcrumb2_item";
	$thfr_css['home']['drop-1'] = "twitter_item";
	$thfr_css['home']['drop-2'] = "facebook_item";
	$thfr_css['home']['drop-3'] = "feedburner_item";
	$thfr_css['home']['drop-4'] = "widgetarea_1";
	$thfr_css['home']['drop-5'] = "widgetarea_1_2";
	$thfr_css['home']['drop-6'] = "widgetarea_1_3";
	$thfr_css['home']['drop-7'] = "widgetarea_2";
	$thfr_css['home']['drop-8'] = "widgetarea_2_2";
	$thfr_css['home']['drop-9'] = "widgetarea_3";
	$thfr_css['home']['drop-10'] = "widgetarea_4";
	$thfr_css['home']['drop-11'] = "widgetarea_5";
	$thfr_css['home']['drop-12'] = "widgetarea_6";

	$thfr_css['single']['sidebarsleft'] = "1";
	$thfr_css['single']['sidebarsright'] = "1";
	$thfr_css['single']['header-0'] = "searchform_item";
	$thfr_css['single']['header-1'] = "sitetitle_item";
	$thfr_css['single']['header-2'] = "rsscommentslink_item";
	$thfr_css['single']['header-3'] = "rsslink_item";
	$thfr_css['single']['header-4'] = "tagline_item";
	$thfr_css['single']['header-5'] = "hormenu2_item";
	$thfr_css['single']['header-6'] = "headerimage_item";
	$thfr_css['single']['header-7'] = "hormenu_item";
	$thfr_css['single']['header-8'] = "breadcrumb_item";
	$thfr_css['single']['centerTop-0'] = "singlenav_item";
	$thfr_css['single']['centerBottom-0'] = "singlenav2_item";
	$thfr_css['single']['footer-0'] = "footer_item";
	$thfr_css['single']['drop-0'] = "breadcrumb2_item";
	$thfr_css['single']['drop-1'] = "twitter_item";
	$thfr_css['single']['drop-2'] = "facebook_item";
	$thfr_css['single']['drop-3'] = "feedburner_item";
	$thfr_css['single']['drop-4'] = "widgetarea_1";
	$thfr_css['single']['drop-5'] = "widgetarea_1_2";
	$thfr_css['single']['drop-6'] = "widgetarea_1_3";
	$thfr_css['single']['drop-7'] = "widgetarea_2";
	$thfr_css['single']['drop-8'] = "widgetarea_2_2";
	$thfr_css['single']['drop-9'] = "widgetarea_3";
	$thfr_css['single']['drop-10'] = "widgetarea_4";
	$thfr_css['single']['drop-11'] = "widgetarea_5";
	$thfr_css['single']['drop-12'] = "widgetarea_6";

	$thfr_css['page']['sidebarsleft'] = "1";
	$thfr_css['page']['sidebarsright'] = "1";
	$thfr_css['page']['header-0'] = "searchform_item";
	$thfr_css['page']['header-1'] = "sitetitle_item";
	$thfr_css['page']['header-2'] = "rsscommentslink_item";
	$thfr_css['page']['header-3'] = "rsslink_item";
	$thfr_css['page']['header-4'] = "tagline_item";
	$thfr_css['page']['header-5'] = "hormenu2_item";
	$thfr_css['page']['header-6'] = "headerimage_item";
	$thfr_css['page']['header-7'] = "hormenu_item";
	$thfr_css['page']['header-8'] = "breadcrumb_item";
	$thfr_css['page']['footer-0'] = "footer_item";
	$thfr_css['page']['drop-0'] = "breadcrumb2_item";
	$thfr_css['page']['drop-1'] = "twitter_item";
	$thfr_css['page']['drop-2'] = "facebook_item";
	$thfr_css['page']['drop-3'] = "feedburner_item";
	$thfr_css['page']['drop-4'] = "widgetarea_1";
	$thfr_css['page']['drop-5'] = "widgetarea_1_2";
	$thfr_css['page']['drop-6'] = "widgetarea_1_3";
	$thfr_css['page']['drop-7'] = "widgetarea_2";
	$thfr_css['page']['drop-8'] = "widgetarea_2_2";
	$thfr_css['page']['drop-9'] = "widgetarea_3";
	$thfr_css['page']['drop-10'] = "widgetarea_4";
	$thfr_css['page']['drop-11'] = "widgetarea_5";
	$thfr_css['page']['drop-12'] = "widgetarea_6";

	$thfr_css['postcontainers'] = array( 'Default-Multi-Posts' => '',
										'Default-Single-Posts' => '',
										'Default-Static-Pages' => '');
	$thfr_css['pagetemplates'] = array( 'index', 'home', 'single', 'page' );
	$thfr_css['thumbsizes'] = array( 'thumbnail', 'medium', 'large' );
	$thfr_css['codeinserts'] = array( 'head' => '',
									'bodytag' => '',
									'bodytop' => '',
									'bodybottom' => '');
	$thfr_css['nextprevnav'] =array( 'multi-left' => '',
									'multi-right' => '',
									'multi2-left' => '',
									'multi2-right' => '',
									'single-left' => '',
									'single-right' => '',
									'single2-left' => '',
									'single2-right' => '',
									'comments' => '',
									'comments2' => '');
	*/
	# $thfr_css['readmore'] = 'Continue Reading <a href=\'". get_permalink() . "\'>" . get_the_title('', '', false) . "</a>"';


}

include(THFRPATH . 'includes/tf_create_css_code.php');
include(THFRPATH . 'includes/tf_create_php_file.php');
# include(THFRPATH . 'includes/tf_create_php_file_comments.php');
include(THFRPATH . 'includes/tf_create_functions_file.php');
include(THFRPATH . 'includes/tf_import_settings_now.php');
include(THFRPATH . 'includes/tf_print_css_code.php');
include(THFRPATH . 'includes/tf_print_form.php');
include(THFRPATH . 'includes/tf_print_js.php');
include(THFRPATH . 'includes/tf_save_options.php');
include(THFRPATH . 'includes/tf_pagetemplate.php');
include(THFRPATH . 'includes/tf_pagetemplate_include.php');
include(THFRPATH . 'includes/tf_drop_area.php');
include(THFRPATH . "includes/tf_selectors.php");
# include(THFRPATH . "includes/tf_selectors_new.php");
include(THFRPATH . "includes/zip.lib.php");
include(THFRPATH . "includes/tf_ftp_transfer.php");
include(THFRPATH . "includes/tf_postcontainer_preview.php");
include(THFRPATH . "includes/tf_postcontainer_code.php");

include(THFRPATH . "includes/tf_custom_drop_image.php");
include(THFRPATH . "includes/tf_custom_drop_link.php");

# when using later (move from pagetemplate.php) define $body_class
#include(THFRPATH . 'functions/tf_page_items.php');
#include(THFRPATH . "selectors.php");
?>