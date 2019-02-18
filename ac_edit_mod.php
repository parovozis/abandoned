<?
require ($_REQUEST['LNG'].'/ac_post_mod.php');
require ($_REQUEST['LNG'].'/ac_edit_mod.php');
$SELECTED = 4;
include ('pg_head.php');

$AC_MENU_ITEM=1;
$AC_MENU_INTRO=$WELCOME2EDIT;
require ('ac_menu.php');

/* Real thing beings here */
end_menu ();

define ('W', HOW_MANY_MINE);
function show_pictures ($q, $can_delete, $bgcolor)
{
  global $TODAY_COLOR, $ALL_PUBLISHED, $DELETE, $PICTABLE, $NEWTABLE,
    $FIRST_COLOR, $FOTO_COMMENTS_TABLE, $CATTABLE, $HOW_TO_EDIT;
  $W1 = $can_delete ? (W - 1) : W;
  $result = execute_query ($q);
  $size = mysql_num_rows ($result);

  if ($size && $can_delete) {
    while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
      $inputname ="del$row[0]";
      if ($_POST[$inputname]=='on') {
	global $ASLTABLE;
	$query = "SELECT competing FROM $PICTABLE WHERE (approved!='Y' OR senton>'$today') AND id=$row[0]";
	$r_competing = execute_query ($query);
	if ($r_competing) {
		$competing = mysql_fetch_array ($r_competing, MYSQL_NUM);
		mysql_free_result ($r_competing);
	
		$query = "DELETE FROM $PICTABLE WHERE id=$row[0]";
		execute_query ($query);
		if (!empty($NEWTABLE)) {
			$query = "DELETE FROM $NEWTABLE WHERE id=$row[0]";
			execute_query ($query);
		}
		$query = "DELETE FROM $CATTABLE WHERE pg_cats_x_picture_id=$row[0]";
		execute_query ($query);
		$query = "DELETE FROM $FOTO_COMMENTS_TABLE WHERE foto_comments_picture=$row[0]";
		execute_query ($query);
		if (isset ($WIKILINK_TABLE)) {
			$query = "DELETE FROM $WIKILINK_TABLE WHERE emb_pictures_id=$row[0]";
			execute_query ($query);
		}
	
		if ($competing[0] == 'Y' && INIT_SEND_LIMIT != -1) {
			$query = "update $ASLTABLE set authors_send_limit_value=authors_send_limit_value+1 where authors_send_limit_author=".$_REQUEST['AUTHOR_ID'];
			execute_query ($query);
		}
		
		unlink (PG_BASE."$row[1].jpg");
		unlink (IC_BASE."$row[1]-s.jpg");
	}
      }
    }
  }

  mysql_free_result ($result);
  $result = execute_query ($q);
  $size = mysql_num_rows ($result);
  if ($size > 0) {
    echo $HOW_TO_EDIT.'<p>';
    $width = 100.0 / W;
    $celldef = "<td width='$width%' bgcolor='$bgcolor' align='center'>";
    echo "<p align='center'><table border='1' bgcolor='$TODAY_COLOR' cellpadding='5' cellspacing='5'>\n";
    $count = -1;
    while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
      $count++;
      if (!($count % $W1)) {
	if ($can_delete && $count > 0) { /* End of row */
	  echo "<td width='$width%'>&nbsp;\n";
	  echo $second_row;
	  echo "<td><input type='submit' value='$DELETE'>\n";
	  echo '</form>';
	}
	echo '<tr>';
	if ($can_delete) {	/* Beginning of row */
	  echo '<form action="ac_edit.php" method="POST">';
	  $second_row = '<tr>';
	}
      }
      echo "$celldef<a href='pg_edit.php?ID=$row[0]'><img src='".IC_BASE."$row[1]-s.jpg' border='0' hspace='2' title='$row[0]'></a><br>".mydate ($row[2]);
      if ($can_delete) {
	$second_row .= "<td><input type='checkbox' name='del$row[0]'>\n";
      }
    } /* while */ 
    
    /* Blanc cells */
    for ($i = $count + 1; $i < $W1; $i++) {
      echo $celldef."&nbsp;\n";
      if ($can_delete) {
	$second_row .= "<td>&nbsp;\n";
      }
    }

    /* Last row */
    if ($can_delete) {
      echo $celldef."&nbsp;\n";
      echo $second_row;
      echo "<td align='center'><input type='submit' value='$DELETE'></form>";
    }
    echo '</table><p>';
  } else {
    echo $ALL_PUBLISHED;
  }
  mysql_free_result ($result);
}

echo "<h3><font color=$THIRD_COLOR>$PUBLISHED_BUT_NOT_SHOWN</font></h3>\n";
$query="SELECT $PICTABLE.id,url,senton FROM $PICTABLE,authors WHERE approved='Y' AND senton>'$today' AND author_id=authors.id AND login='{$_REQUEST['AUTHOR']}' ORDER BY senton";
show_pictures ($query, false, $FIRST_COLOR);

echo "<h3><font color=$THIRD_COLOR>$NOT_PUBLISHED_YET</font></h3>\n";
$query="SELECT $PICTABLE.id,url,senton FROM $PICTABLE,authors WHERE approved='N' AND author_id=authors.id AND login='{$_REQUEST['AUTHOR']}' ORDER BY senton";
show_pictures ($query, true, $FIRST_COLOR);

if (COMODERATION == true) {
	echo "<h3><font color=$THIRD_COLOR>$REJECTED</font></h3>\n";
	$query="SELECT $PICTABLE.id,url,senton FROM $PICTABLE,authors WHERE approved='A' AND author_id=authors.id AND login='{$_REQUEST['AUTHOR']}' ORDER BY senton";
	show_pictures ($query, true, $LOGO_COLOR);
}

include ('pg_tail.php');
?>
