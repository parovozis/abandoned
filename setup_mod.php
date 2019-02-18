<?
//require ($_REQUEST['LNG'].'/setup_mod.php');

if ($MODERATOR_MODE < 2) {
  die ('You are not a moderator!');
}

$root = 0;
$target = 0;
if (isset ($_POST['MAKESUPER']) 
    && isset ($_POST['CATEG'])
    && is_numeric ($_POST['CATEG'])
    && isset ($_POST['TARGET'])
    && is_numeric ($_POST['TARGET'])
    ) {
  if ($_POST['TARGET'] > 0) {
    $categ = abs($_POST['CATEG']);
    execute_query ('BEGIN');
    $query = "SELECT @SCAT:=supercat FROM $BASECATTABLE WHERE name=".$_POST['TARGET'];
//    execute_query ($query);
    $query = "UPDATE $BASECATTABLE SET virtual=virtual-1 WHERE name=@SCAT";
//    execute_query ($query);
    $query = "UPDATE $BASECATTABLE SET supercat=$categ WHERE name=".$_POST['TARGET'];
//    execute_query ($query);
    $query = "UPDATE $BASECATTABLE SET virtual=virtual+1 WHERE name=$categ";
//    execute_query ($query);
    execute_query ('COMMIT');
  } else {
    $root = 1;
  }
} else if (isset ($_POST['DELCAT']) 
	   && isset ($_POST['CATEG'])
	   && is_numeric ($_POST['CATEG'])) {
  if ($_POST['CATEG'] > 0) {
    execute_query ('BEGIN');
    $query = "DELETE FROM $CATTABLE WHERE pg_cats_x_cat_name=".$_POST['CATEG'];
//    execute_query ($query);
    $query = "DELETE FROM $BASECATTABLE WHERE name=".$_POST['CATEG'].';';
//    execute_query ($query);
    execute_query ('COMMIT');
  } else {
    $root = 1;
  }
} else if (isset ($_POST['ADDCAT']) 
	   && isset ($_POST['NEWNAME'])
	   && $_POST['NEWNAME']
	   && isset ($_POST['CATEG']) 
	   && is_numeric ($_POST['CATEG'])) {
  $super = abs ($_POST['CATEG']);
  $newname = mysql_real_escape_string ($_POST['NEWNAME']);
  execute_query ('BEGIN');
  $query = "INSERT INTO $BASECATTABLE SET supercat=$super,title='$newname'";
  //  execute_query ($query);
  $query = "UPDATE $BASECATTABLE SET virtual=virtual+1 WHERE name=$super";
  //    execute_query ($query);
    execute_query ('COMMIT');
} else if (isset ($_POST['EDITCAT']) 
	   && isset ($_POST['CATEG']) 
	   && is_numeric ($_POST['CATEG'])
	   && isset ($_POST['NEWNAME']) 
	   && $_POST['NEWNAME']) {
  if ($_POST['CATEG'] > 0) {
    $newname = mysql_real_escape_string ($_POST['NEWNAME']);
    $virtual = isset ($_POST['VIRTUAL']) ? 'Y' : 'N';
    $query = "UPDATE $BASECATTABLE SET title=$newname WHERE name=".$_POST['CATEG'];
//    execute_query ($query);
  } else {
    $root = 1;
  }
} else if (isset ($_POST['CHSUPER']) 
	   && isset ($_POST['CATEG'])
	   && is_numeric ($_POST['CATEG'])) {
  if ($_POST['CATEG'] > 0) {
    $target = $_POST['CATEG'];
    $query = 'SELECT title FROM '.$BASECATTABLE.' WHERE name='.$target;
    $result = execute_query ($query);
    $target_info = mysql_fetch_array ($result, MYSQL_NUM);
    mysql_free_result ($result);
  } else {
    $root = 1;
  }
}


require ($_REQUEST['LNG'].'/ac_post_mod.php');
//require ($_REQUEST['LNG'].'/ac_edit_mod.php');
$SELECTED = 4;
include ('pg_head.php');

$AC_MENU_ITEM=3;
$AC_MENU_INTRO=$WELCOME2EDIT;
require ('ac_menu.php');

/* Real thing beings here */
end_menu ();

if ($root) {
  echo '<font color=red><b>The root category cannot be edited.</b></font><p>';
}
echo "<form action='$PHP_SELF' method=POST>";
echo '<table>';
$query = 'SELECT HIGH_PRIORITY my.name,my.title,my.virtual,sc.title FROM '.$BASECATTABLE.' AS my,'.$BASECATTABLE.' AS sc WHERE sc.name=my.supercat ORDER BY sc.title,my.title';
$result = execute_query ($query);
echo '<tr><td>&nbsp;<td><select name="CATEG">';
while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
  $virtual = $row[2] ? '&gt;&gt;&gt;' : '';
  if ($row[3] == 'ROOT') {
    $row[3] = '';
  }
  if ($row[1] == 'ROOT') {
    $row[0] = -$row[0];
  }
  echo "<option value='$row[0]'>$row[3]/$row[1] $virtual\n";
}
echo "</select>\n";
mysql_free_result ($result);
if ($target)  {
  echo '<td><input type=SUBMIT name="MAKESUPER" value=" Make parent for &laquo;'.$target_info[0].'&raquo;">';
  echo "<input type=HIDDEN name=TARGET value='$target'>\n";
} else {
  echo '<td><input type=SUBMIT name="DELCAT" value=" Delete category ">';
  echo '<td><input type=SUBMIT name="CHSUPER" value=" Change parent ">';
  echo '<tr><td>New name<td>';
  echo '<input name="NEWNAME" SIZE="32" MAXLENGTH="32">';
  echo '<td><input type=SUBMIT name="ADDCAT" value=" Add category ">';
  echo '<td><input type=SUBMIT name="EDITCAT" value=" Rename category ">';
}
echo '</table>';
echo '<input type=HIDDEN name=ACTION value="setup_mod.php">';
echo '</form><p>';

/*
$query = 'select  HIGH_PRIORITY regid,'.$prefix.'regname from '.$REGIONTABLE.' order by '.$prefix.'regname';
$result = execute_query ($query);
echo '<select name="REGION">';
while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
  printf ("<option value='%s'>%s\n", $row[0], $row[1]);
}
echo "</select> \n";
mysql_free_result ($result);
*/

include ('pg_tail.php');
?>
