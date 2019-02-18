<?
error_reporting(E_ERROR | E_PARSE);
require ("pg_db.php");
require ("functions.php");
require ($_REQUEST['LNG'].'/ac_post_mod.php');

$SELECTED = 4;
$ID = abs ($_REQUEST['ID']);
if (!$ID) {
  die ('What are you going to edit? No ID!');
}

$query = "SELECT author_id FROM $PICTABLE WHERE $PICTABLE.id=$ID";
$result = execute_query ($query);
$author = mysql_fetch_array ($result, MYSQL_NUM);
mysql_free_result ($result);
$author = $author[0];

$author_id = authorize ();
if ($MODERATOR_MODE != 2 && $author_id != $author) {
  die ('You are neither a moderator nor the author. Go away!');
}

if ($MODERATOR_MODE == 2) {
  $selectdisabled = '';
} else {
  $selectdisabled = 'disabled';
}

if (isset ($_REQUEST['NEWICON']) && $_REQUEST['NEWICON']) {
  if (!$_REQUEST['URL'])
    die ('No URL for new icon!');
  if ($MODERATOR_MODE == 2) {
	  system ("./makeicon ".$_REQUEST['URL']);
	  $query = "UPDATE $PICTABLE SET icon_url=NULL WHERE url='".$_REQUEST['URL']."'";
	  execute_query ($query);
  }
} else if (isset ($_REQUEST['RMID']) && ($MODERATOR_MODE == 2 || !NO_DELETE)) {
	require ('ed_delete.php');
} else if (isset ($_REQUEST['CHANGE_ID'])) {
	require ('ed_change.php');
} else if (isset ($_REQUEST['APPROVE_ID']) && $MODERATOR_MODE == 2) { /* APPROVE */
	require ('ed_approve.php');
}

$query = "SELECT url, description, region, senton, takenon, baddate, approved, author_id, competing, longitude, latitude, collection, no_comments, approved_by FROM $PICTABLE WHERE id=$ID";
$result = execute_query ($query);
$self = mysql_fetch_array ($result, MYSQL_NUM);
if (!$self) {
	$delete_anyway = true;
	require ('ed_delete.php');
}
mysql_free_result ($result);

$query = "SELECT pg_cats_x_cat_name FROM $CATTABLE WHERE pg_cats_x_picture_id=$ID";
$result = execute_query ($query);
$cats = array ();
$i = 0;
while ($cat = mysql_fetch_array ($result, MYSQL_NUM)) {
  $cats[$i] = $cat[0];
  $i++;
}
define ('MISC',21);
mysql_free_result ($result);
if (!$i) {
  $cats[0] = MISC;
}

$_REQUEST['URL'] = $self[0];

// BEGIN!

include ("pg_head.php");
echo "<p><form action='$PHP_SELF' name='myForm' method='POST'>";

if ($MODERATOR_MODE == 2) $extracond=''; else $extracond='AND inuse="Y"';
$query = "SELECT HIGH_PRIORITY name,title FROM $BASECATTABLE WHERE title!='ROOT' $extracond ORDER BY title";
$result = execute_query ($query);
$all_cats = array();
$i = 0;
while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
  $all_cats[$i] = $row;
  $i++;
}
mysql_free_result ($result);

echo "<center><a href='".PG_BASE."{$_REQUEST['URL']}.jpg'><img src='".IC_BASE."{$_REQUEST['URL']}-s.jpg' border='0' align='left'></a>\n";

echo '<table border="0" cellpadding="2">';
echo "<tr><td width=20%>$COMPETING:\n";
echo $self[8]=='Y' ? "<td><b>$YES</b>" : "<td bgcolor=red><font color=white><b>$NO</font></b>";

echo "<tr><td>$SENT_BY_AUTHOR:\n<td>";
$checked = $self[11]=='N' ? 'checked' : '';
echo "<INPUT TYPE=checkbox NAME='NOTCOLLECTION' $checked>";
echo "<tr><td>".NO_COMMENTS_MSG.":\n<td>";
$checked = ($self[12]=='N') ? '' : 'checked';
$disabled = ($MODERATOR_MODE == 2) ? '' : 'disabled';
echo "<INPUT TYPE=checkbox $disabled NAME='NO_COMMENTS' $checked>";
$k = 0;
foreach ($cats as $cat) {
  echo "<tr><td>$CATEGORY ".($k+1).": \n";
  echo "<td><select name='CATEG$k'> \n";
  echo "<option value='NULL'>".DELETE."\n";
  echo "<option value='NULL'>--------------\n";
  for ($j = 0; $j < $i; $j++) {
    $row = $all_cats[$j];
    $selected = ($cat==$row[0]) ? 'SELECTED' : '';
    echo "<option value='$row[0]' $selected>$row[1]\n";
  }
  echo "</select>\n";
  $k++;
}
echo "<input type=hidden name='CAT_COUNT' value=$k>\n";

if ($MODERATOR_MODE == 2) {
  echo "<tr><td>URL (ID=$ID) <td><input disabled SIZE='45' MAXLENGTH='64' value='".$_REQUEST['URL']."'><input type=hidden name='URL' value='".$_REQUEST['URL']."'>\n";
  if (isset ($_GET['SHOW_AUTHORS']) && $_GET['SHOW_AUTHORS']==1) {
	  $query = 'select id,name,login from authors order by name';
  } else {
	  $query = "select id,name,login from authors WHERE id=$self[7]";
  }
  $result = execute_query ($query);
  echo "<tr><td>$AUTHOR_FIELD: <td>\n";
  echo "<select name='AUTHOR'> \n";
  while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
    if ($self[7]==$row[0]) {
      $selected = "SELECTED";
    } else
      $selected = "";
    printf ("<option value='%d' %s>%s {%s @ %d}\n", $row[0], $selected, $row[1], $row[2], $row[0]);
  }
  echo '</select>';
  mysql_free_result ($result);
  if (isset ($_GET['SHOW_AUTHORS']) && $_GET['SHOW_AUTHORS']==1) {
	  echo "<tr><td>&nbsp;<td><A HREF=?FROM={$_REQUEST['FROM']}&ID={$_REQUEST['ID']}&SHOW_AUTHORS=0>Не показывать других авторов</A>\n";
  } else {
	  $query = "select reputation from {$PICTABLE}_reputation WHERE author=$self[7]";
	  $result = execute_query ($query);
	  $row = mysql_fetch_array ($result, MYSQL_NUM);
	  mysql_free_result ($result);
	  $repo = $row ? $row[0] : 0;
	  
	  echo " Репутация: $repo. ";
	  if ($repo > SELF_APPROVE_THRESHOLD)
		  echo ' Автомодерация.';
	  elseif ($repo < 2 * WARNING_THRESHOLD)
		  echo ' Постинг заблокирован.';
	  elseif ($repo < WARNING_THRESHOLD)
		  echo ' Автор получает предупреждения.';
	  echo "<tr><td>&nbsp;<td><A HREF=?FROM={$_REQUEST['FROM']}&ID={$_REQUEST['ID']}&SHOW_AUTHORS=1>Показать список всех авторов</A>\n";
  }
}
echo "<input name='URL'       type='HIDDEN' value='".$_REQUEST['URL']."'>\n";
echo "<input name='ID'        type='HIDDEN' value='$ID'>\n";
echo "<input name='CHANGE_ID' type='HIDDEN' value='$ID'>\n";
?>
	
<SCRIPT LANGUAGE='JavaScript'>
<!--
function enableNotify(field,maxlimit) {
	document.getElementById('NOTIFYAUTHOR').disabled=false;
	document.getElementById('NOTIFYAUTHOR').checked=true;
	textCounter(field,maxlimit);
}
function textCounter(field,maxlimit) {
if (field.value.length > maxlimit) field.value = field.value.substring(0, maxlimit);
} 
-->
</script>
<tr><td valign=top><? echo $DESCRIPTION ?>: 
<td><TEXTAREA NAME="DESCR1" COLS=45 ROWS=2 
<?/*
if ($MODERATOR_MODE != 2) echo ' READONLY ';
*/?>
onChange="enableNotify(document.myForm.DESCR1,500)"
onKeyDown="enableNotify(document.myForm.DESCR1,500)"
onKeyUp="enableNotify(document.myForm.DESCR1,500)">
<? echo stripslashes ($self[1]) ?>
</TEXTAREA>
<? if ($MODERATOR_MODE == 2) {
	echo "<tr bgcolor='$FIFTH_COLOR'><td colspan=2>&nbsp;&nbsp;&nbsp;<b>Послать уведомление об изменении описания автору?</b> <input name=NOTIFY type=checkbox id='NOTIFYAUTHOR' disabled> Да!";
}
?>
<tr><td><? echo $COORD ?>: 
<td>
<input name="LAT" SIZE="8" MAXLENGTH="8" value="<? echo $self[10] ?>">,
<input name="LONG" SIZE="8" MAXLENGTH="8" value="<? echo $self[9] ?>">

<? 
echo $COORD_INSTR;

$query = 'SELECT HIGH_PRIORITY regid,regname FROM '.$REGIONTABLE.' ORDER BY regname';
$result = execute_query ($query);

printf ("<tr><td>$REGION: <td><select name='REG'> \n");
while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
  if ($self[2]==$row[0])
    $selected = "SELECTED";
  else
    $selected = "";
  printf ("<option value='%s' %s>%s\n", $row[0], $selected, $row[1]);
}
echo "</select>\n";
mysql_free_result ($result);

$NOW = explode ("-", $self[4]);
if (count ($NOW) != 3) {
  $NOW = array (0,'??',START_YEAR);
}
echo "<tr><td>$TAKEN_ON: <td>\n";
echo '<select name="DATE">';
for($i=0;$i<=31;$i++) {
  if ($NOW[2] == $i)
    $select = "SELECTED";
  else
    $select = '';
  printf ("<option value='%02d' %s>%02d", $i, $select, $i);
}
echo "</select> . <select name='MONTH'>\n";
for($i=0;$i<=12;$i++) {
  $active = ($NOW[1] == $i) ? 'SELECTED' : '';
  printf ("<option value='%02d' %s>%s", $i, $active, $months[$i]);
}
echo "</select> . <select name='YEAR'>\n";

$curyear = isset ($self[5]) ? $self[5] : $NOW[0];
for($i = START_YEAR; $i <= $this_year; $i++) {
	if (!($i % 100)) {
		$active = ($curyear == (($i/100).'??')) ? 'SELECTED' : '';
		printf ("<option value='%2d??' %s>%2d**\n", $i / 100, $active, $i /100);
	}
	if (!($i % 10)) {
		$active = ($curyear == (($i/10).'?')) ? 'SELECTED' : '';
		printf ("<option value='%3d?' %s>%3d*\n", $i / 10, $active, $i /10);
	}
	$active = ($curyear == $i) ? 'SELECTED' : '';
	printf ("<option value='%4d' %s>%4d\n", $i, $active, $i);
}
echo "</select>\n";

echo "<tr><td>$PUBLISHED_ON: <td>".mydate ($self[3]);

$CQUERY = "select foto_comments_text from $FOTO_COMMENTS_TABLE, $PICTABLE where foto_comments_picture=$ID and foto_comments_author_id=author_id and id=foto_comments_picture order by foto_comments_date limit 1";
$result = execute_query ($CQUERY);
echo "<tr><td valign=top>$INITIAL_COMMENT: <td>";
if ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
	echo "<b><i>$row[0]</i></b>";
	mysql_free_result ($result);
} else {
	echo '&nbsp;';
}
?>

<tr>
<td><INPUT TYPE=SUBMIT VALUE=" <? echo $CHANGE ?> "></form>
<td>&nbsp;
<?
if ($MODERATOR_MODE == 2) {
	echo "<form action='$PHP_SELF' method='GET'>";
	echo "<INPUT TYPE=SUBMIT VALUE=' $UPDATE_ICON '>";
	echo "<INPUT TYPE=HIDDEN NAME='ID' VALUE='$ID'>";
	echo "<INPUT TYPE=HIDDEN NAME='NEWICON' VALUE='1'>";
	echo "<INPUT TYPE=HIDDEN NAME='URL' VALUE='{$_REQUEST['URL']}'>";
	echo "</form>";
}
?>
</table></center>

<?
if ($MODERATOR_MODE == 2) require ('ed_buttons.php'); else echo '<hr>';

/* EMB Categories */
if (isset ($WIKILINK_TABLE)) require ('ed_wiki.php');

include ("pg_tail.php");
?>
