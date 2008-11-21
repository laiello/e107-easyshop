<?php
/*
+------------------------------------------------------------------------------+
| EasyShop - an easy e107 web shop  | adapted by nlstart
| formerly known as
|	jbShop - by Jesse Burns aka jburns131 aka Jakle
|	Plugin Support Site: e107.webstartinternet.com
|
|	For the e107 website system visit http://e107.org
|
|	Released under the terms and conditions of the
|	GNU General Public License (http://gnu.org).
+------------------------------------------------------------------------------+
*/
// Ensure this program is loaded in admin theme before calling class2
$eplug_admin = true;

require_once("../../class2.php");
require_once(e_HANDLER."userclass_class.php");
require_once(e_ADMIN."auth.php");
require_once("includes/config.php");
require_once("easyshop_class.php");

if(!getperms("P")){ header("location:".e_BASE."index.php"); }

// Keep the active menu option for admin_menu.php (when showing errors on prices)
$pageid = 'admin_menu_01';

function tokenizeArray($array) {
    unset($GLOBALS['tokens']);
    $delims = "~";
    $word = strtok( $array, $delims );
    while ( is_string( $word ) ) {
        if ( $word ) {
            global $tokens;
            $tokens[] = $word;
        }
        $word = strtok ( $delims );
    }
}

//-----------------------------------------------------------------------------+
//---------------------- Handle file upload -----------------------------------+
//-----------------------------------------------------------------------------+
if (isset($_POST['upload'])) {
	$pref['upload_storagetype'] = "1";
	require_once(e_HANDLER."upload_handler.php");
	$files = $_FILES['file_userfile'];
	foreach($files['name'] as $key => $name) {
		if ($files['size'][$key]) {
			$uploaded = file_upload($_POST['upload_dir'][$key]);
		}
	}
}
if (isset($message)) {
	$ns->tablerender("", "<div style=\"text-align:center\"><b>".$message."</b></div>");
  header("Location: admin_config.php");
  exit;
}

if ($_POST['add_item'] == '1') {
    // Add new Product
    
  // Check: name is mandatory
  if ($tp->toDB($_POST['item_name']) == "") {
     $text .= EASYSHOP_CONFEDIT_ITM_10."<br/>";
  }
  // First check on valid pricing
  if (General::validateDecimal($tp->toDB($_POST['item_price']))) {
      // This is a valid price with 2 decimals
      } else {
      // invalid price alert
      $text .= EASYSHOP_CONFEDIT_ITM_04."<br />";
      }
  // Check Shipping cost for first product, too
  if (General::validateDecimal($tp->toDB($_POST['shipping_first_item']))) {
      // This is a valid price with 2 decimals
      } else {
      // invalid price alert
      $text .= EASYSHOP_CONFEDIT_ITM_05."<br />";
      }
  // Check Shipping cost for each additional product, too
  if (General::validateDecimal($tp->toDB($_POST['shipping_additional_item']))) {
      // This is a valid price with 2 decimals
      } else {
      // invalid price alert
      $text .= EASYSHOP_CONFEDIT_ITM_06."<br />";
      }
  // Check Handling cost, too
  if (General::validateDecimal($tp->toDB($_POST['handling_override']))) {
      // This is a valid price with 2 decimals
      } else {
      // invalid price alert
      $text .= EASYSHOP_CONFEDIT_ITM_07."<br />";
      }

      if ($text <> "") {
      $text .= "<br/><center><input class='button' type=button value='".EASYSHOP_CONFEDIT_ITM_08."' onClick='history.go(-1)'></center>";
     	// Render the value of $text in a table.
      $title = EASYSHOP_CONFEDIT_ITM_09;
      $ns -> tablerender($title, $text);
      require_once(e_ADMIN."footer.php");
      // Leave on error
      exit;
      }

    if (isset($_POST['item_active_status']))
    {
        $item_active_status = 2;
    } else
    {
        $item_active_status = 1;
    }
    // Actual database insert of new product
    $sql -> db_Insert(DB_TABLE_SHOP_ITEMS,
    "0,
		'".$tp->toDB($_POST['category_id'])."',
		'".$tp->toDB($_POST['item_name'])."',
		'".$tp->toDB($_POST['item_description'])."',
		'".$tp->toDB($_POST['item_price'])."',
		'".$tp->toDB($_POST['sku_number'])."',
		'".$tp->toDB($_POST['shipping_first_item'])."',
		'".$tp->toDB($_POST['shipping_additional_item'])."',
		'".$tp->toDB($_POST['handling_override'])."',
		'".$tp->toDB($_POST['item_image'])."',
    '".$tp->toDB($item_active_status)."',
		1,
    '',
		1,
    '".$tp->toDB($_POST['prod_prop_1_id'])."',
    '".$tp->toDB($_POST['prod_prop_1_list'])."',
    '".$tp->toDB($_POST['prod_prop_2_id'])."',
    '".$tp->toDB($_POST['prod_prop_2_list'])."',
    '".$tp->toDB($_POST['prod_prop_3_id'])."',
    '".$tp->toDB($_POST['prod_prop_3_list'])."',
    '".$tp->toDB($_POST['prod_prop_4_id'])."',
    '".$tp->toDB($_POST['prod_prop_4_list'])."',
    '".$tp->toDB($_POST['prod_prop_5_id'])."',
    '".$tp->toDB($_POST['prod_prop_5_list'])."',
    '".$tp->toDB($_POST['prod_discount_id'])."',
    '".$tp->toDB($_POST['item_instock'])."',
    '".$tp->toDB($_POST['item_track_stock'])."',
    '".$tp->toDB($_POST['download_product'])."',
    '".$tp->toDB($_POST['download_filename'])."'
    ");
    header("Location: admin_config.php");
    exit;

} else if ($_POST['item_dimensions'] == '1') {
    $sql->db_Update(DB_TABLE_SHOP_PREFERENCES,
    "items_per_page='".$tp->toDB($_POST['items_per_page'])."',
     num_item_columns='".$tp->toDB($_POST['num_item_columns'])."'
  	 WHERE
  	 store_id=1");
    header("Location: admin_config.php");
    exit;

} else if ($_POST['change_order'] == '1') {
    // Change item order
    for ($x = 0; $x < count($_POST['item_order']); $x++) {
        tokenizeArray($_POST['item_order'][$x]);
        $newItemOrderArray[$x] = $tokens;
    }
    
    for ($x = 0; $x < count($newItemOrderArray); $x++) {
        $sql -> db_Update(DB_TABLE_SHOP_ITEMS,
            "item_order=".$tp->toDB($newItemOrderArray[$x][1])."
             WHERE item_id=".$tp->toDB($newItemOrderArray[$x][0]));
    }

    // Change item active status
    $sql2 = new db;
    $sql2 -> db_Update(DB_TABLE_SHOP_ITEMS,
        "item_active_status=1
		     WHERE category_id=".$tp->toDB($_POST['category_id']));

    foreach ($_POST['item_active_status'] as $value) {
    	$sql2 -> db_Update(DB_TABLE_SHOP_ITEMS,
            "item_active_status=2
             WHERE item_id=".$tp->toDB($value));
    }

    // Change item 'Out Of Stock' status
    $sql3 = new db;
    $sql3 -> db_Update(DB_TABLE_SHOP_ITEMS,
          "item_out_of_stock=1
	  	     WHERE category_id=".$tp->toDB($_POST['category_id']));
        
    foreach ($_POST['item_out_of_stock'] as $value) {
    	$sql3 -> db_Update(DB_TABLE_SHOP_ITEMS,
            "item_out_of_stock=2
             WHERE item_id=".$tp->toDB($value));
    }

    // Change item 'Out Of Stock' explanation
    $sql4 = new db;
    foreach ($_POST['item_out_of_stock_explanation'] as $key => $value) {
      $sql4 -> db_Update(DB_TABLE_SHOP_ITEMS,
            "item_out_of_stock_explanation='".$tp->toDB($value)."'
             WHERE item_id=".$tp->toDB($key));
    }

    header("Location: admin_config.php");
    exit;

} else if ($_POST['edit_item'] == '2') {
  // Pushed 'Apply Changes' button on Edit Product
  // Check: name is mandatory
  if ($tp->toDB($_POST['item_name']) == "") {
     $text .= EASYSHOP_CONFEDIT_ITM_10."<br/>";
  }
  // First check on valid pricing
  if (General::validateDecimal($tp->toDB($_POST['item_price']))) {
      // This is a valid price with 2 decimals
      } else {
      // invalid price alert
      $text .= EASYSHOP_CONFEDIT_ITM_04."<br />";
      }
  // Check Shipping cost for first product, too
  if (General::validateDecimal($tp->toDB($_POST['shipping_first_item']))) {
      // This is a valid price with 2 decimals
      } else {
      // invalid price alert
      $text .= EASYSHOP_CONFEDIT_ITM_05."<br />";
      }
  // Check Shipping cost for each additional product, too
  if (General::validateDecimal($tp->toDB($_POST['shipping_additional_item']))) {
      // This is a valid price with 2 decimals
      } else {
      // invalid price alert
      $text .= EASYSHOP_CONFEDIT_ITM_06."<br />";
      }
  // Check Handling cost, too
  if (General::validateDecimal($tp->toDB($_POST['handling_override']))) {
      // This is a valid price with 2 decimals
      } else {
      // invalid price alert
      $text .= EASYSHOP_CONFEDIT_ITM_07."<br />";
      }

      if ($text <> "") {
      $text .= "<br/><center><input class='button' type=button value='".EASYSHOP_CONFEDIT_ITM_08."' onClick='history.go(-1)'></center>";
     	// Render the value of $text in a table.
      $title = EASYSHOP_CONFEDIT_ITM_09;
      $ns -> tablerender($title, $text);
      require_once(e_ADMIN."footer.php");
      // Leave on error
      exit;
      }

    // Checkbox will only post value if it is checked
    if (isset($_POST['item_active_status']))
    {
        $item_active_status = 2;
    } else
    {
        $item_active_status = 1;
    }
    $sql -> db_Update(DB_TABLE_SHOP_ITEMS,
    "category_id='".$tp->toDB($_POST['category_id'])."',
		item_name='".$tp->toDB($_POST['item_name'])."',
		item_description='".$tp->toDB($_POST['item_description'])."',
		sku_number='".$tp->toDB($_POST['sku_number'])."',
		item_price='".$tp->toDB($_POST['item_price'])."',
		shipping_first_item='".$tp->toDB($_POST['shipping_first_item'])."',
		shipping_additional_item='".$tp->toDB($_POST['shipping_additional_item'])."',
		handling_override='".$tp->toDB($_POST['handling_override'])."',
		item_image='".$tp->toDB($_POST['item_image'])."',
		item_active_status='".$tp->toDB($item_active_status)."',
    prod_prop_1_id='".$tp->toDB($_POST['prod_prop_1_id'])."',
    prod_prop_1_list='".$tp->toDB($_POST['prod_prop_1_list'])."',
    prod_prop_2_id='".$tp->toDB($_POST['prod_prop_2_id'])."',
    prod_prop_2_list='".$tp->toDB($_POST['prod_prop_2_list'])."',
    prod_prop_3_id='".$tp->toDB($_POST['prod_prop_3_id'])."',
    prod_prop_3_list='".$tp->toDB($_POST['prod_prop_3_list'])."',
    prod_prop_4_id='".$tp->toDB($_POST['prod_prop_4_id'])."',
    prod_prop_4_list='".$tp->toDB($_POST['prod_prop_4_list'])."',
    prod_prop_5_id='".$tp->toDB($_POST['prod_prop_5_id'])."',
    prod_prop_5_list='".$tp->toDB($_POST['prod_prop_5_list'])."',
    prod_discount_id='".$tp->toDB($_POST['prod_discount_id'])."',
    item_instock='".$tp->toDB($_POST['item_instock'])."',
    item_track_stock='".$tp->toDB($_POST['item_track_stock'])."',
    download_product='".$tp->toDB($_POST['download_product'])."',
    download_filename'".$tp->toDB($_POST['download_filename'])."'
		WHERE item_id=".$tp->toDB($_POST['item_id'])); // or die (mysql_error());
    header("Location: admin_config.php?cat.".$_POST['category_id']);
    exit;

} else if ($_GET['delete_item'] == '1') {
	// Verify deletion
    $text = "
    <br /><br />
    <center>
        ".EASYSHOP_CONFEDIT_ITM_01."
        <br /><br />
        <table width='100'>
            <tr>
                <td>
                    <a href='admin_config_edit.php?delete_item=2&item_id=".$_GET['item_id']."&category_id=".$_GET['category_id']."'>".EASYSHOP_CONFEDIT_ITM_02."</a>
                </td>
                <td>
                    <a href='admin_config.php?cat.".$_GET['category_id']."'>".EASYSHOP_CONFEDIT_ITM_03."</a>
                </td>
            </tr>
        </table>
    </center>";

    // Render the value of $text in a table.
    $title = "<b>".EASYSHOP_CONFEDIT_ITM_00."</b>";
    $ns -> tablerender($title, $text);
    //*/

} else if ($_GET['delete_item'] == '2') {
  // Delete item from tables when delete_item = 2 (user selected Yes to delete)
	$itemId = $tp->toDB($_GET['item_id']);
  $sql -> db_Delete(DB_TABLE_SHOP_ITEMS, "item_id=$itemId");
  header("Location: admin_config.php?cat.".$_GET['category_id']);
  exit;
}

require_once(e_ADMIN."footer.php");
?>