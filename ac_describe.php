<?
if (   isset ($_POST['FIXDATE']) 
       && isset ($_POST['ID'])
       && isset ($_POST['YEAR'])
       && isset ($_POST['MONTH'])
       && isset ($_POST['DATE'])
       ) {
  $QUERY = "UPDATE $PICTABLE SET takenon='".$_POST['YEAR']."-".$_POST['MONTH']."-".$_POST['DATE']."' WHERE ID=".$_POST['ID'];
  execute_query ($QUERY);  

  echo "<SCRIPT LANGUAGE='JavaScript'><!--\n";
  echo "window.close();\n";
  echo "//--></SCRIPT>\n";
}
?>

<script type="text/javascript">
function validate_required(field,alerttxt)
{
	with (field) {
		if (value==null||value==""||value=="NULL") {
			alert(alerttxt);
			return false;
		} else {
			return true;
		}
	}
}

function validate_form(thisform)
{
	with (thisform)
	{
		var input = document.getElementById("upload");
		if(input.files && input.files.length == 1 && input.files[0].size > 1048576)
		{alert("<? echo CHK_TOO_BIG ?>"); return false;}
		if (validate_required(IMAGE,"<? echo CHK_SELECT_FILE ?>")==false)
		{IMAGE.focus();return false;}
		if (validate_required(CATEG0,"<? echo CHK_SELECT_CAT ?>")==false)
		{CATEG0.focus();return false;}
		if (validate_required(DESCR1,"<? echo CHK_DESCRIBE ?>")==false)
		{DESCR1.focus();return false;}
		
		var taken = new Date();
		var today = new Date();
		taken.setFullYear(YEAR.value,MONTH.value-1,DATE.value);
		if (taken.getTime() > today.getTime()) {
			alert("<? echo CHK_FIX_DATE ?>");
			return false;
		}

		if (NOT_MINE.checked && (COMMENT.value==null||COMMENT.value=="")) {
			alert("<? echo CHK_FIX_AUTHOR ?>");
			return false;
}
	}
}
</script>

<form action='<? $PHP_SELF ?>' method='POST' enctype='multipart/form-data' name='myForm' onsubmit="return validate_form(this)">
<table cellpadding="3" cellspacing="0" border="0">
<tbody id='ac_describe_table'>
<?
$query = 'SELECT allow_vote FROM authors WHERE login="'.$_REQUEST['AUTHOR'].'"';
$result = execute_query ($query);
$allow_vote = mysql_fetch_array ($result, MYSQL_NUM);
mysql_free_result ($result);

if (!isset ($_REQUEST['REG'])) {
  $query = "SELECT region,COUNT(*) cnt FROM $PICTABLE,authors WHERE author_id=authors.id AND login='{$_REQUEST['AUTHOR']}' AND collection='N' GROUP BY region ORDER BY cnt DESC LIMIT 1";
  $result = execute_query ($query);
  if ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
    $_REQUEST['REG'] = $row[0];
  }
  mysql_free_result ($result);
}

if (!isset ($_REQUEST['CATEG'])) {
  $query = "SELECT pg_cats_x_cat_name,count(*) cnt FROM $PICTABLE,$CATTABLE,authors WHERE author_id=authors.id AND login='".$_REQUEST['AUTHOR']."' AND collection='N' AND pg_cats_x_picture_id=$PICTABLE.id GROUP BY pg_cats_x_cat_name ORDER BY cnt DESC LIMIT 1";
  $result = execute_query ($query);
  if ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
    $_REQUEST['CATEG'] = $row[0];
  }
  mysql_free_result ($result);
}

$prefix = ($LNG == 'EN') ? 'e' : '';
$query = "SELECT name,{$prefix}title FROM $BASECATTABLE WHERE title!='ROOT' AND inuse='Y' ORDER BY title";
$result = execute_query ($query);
$all_cats = array();
$i = 0;
while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
  $all_cats[$i] = $row;
  $i++;
}
mysql_free_result ($result);

$donotvote_checked = ($post_ok && $allow_vote[0] == 'N') ? '' : 'checked';
$nocomments_checked = /*isset ($_POST['NO_COMMENTS']) ? */'checked'/* : ''*/;
$mailcomments_checked = /*isset ($_POST['NO_COMMENTS']) ? */'' /*: 'checked'*/;
echo "<tr valign='top'><td>$NO_COMPETING:<td><input TYPE=checkbox name='NO_COMPETING' $donotvote_checked>\n";
echo "($DONOTCOMPETE_INSTRUCTION)";

for ($k = 0; $k <= $_REQUEST['CAT_COUNT']; $k++) {
  $catno = "CATEG$k";
  echo '<tr><td>'.mandatory($CATEGORY).": <td><select name='$catno'>\n";
  echo "<option value='NULL'>$EXTRA_CATEGORY1\n";
  echo "<option value='NULL'>--------------\n";
  for ($j = 0; $j < $i; $j++) {
    $row = $all_cats[$j];
    $active = ($_REQUEST[$catno]==$row[0])? 'selected' : '';
    echo "<option value='$row[0]' $active>$row[1]\n";
    $counter = 1;
  }
  echo "</select>\n";
}

echo "<script LANGUAGE='JavaScript'><!--\n";
echo "var iteration = $k;\n";
echo "function insertDropdownBox() {\n";
echo "var ac_describe_table = document.getElementById ('ac_describe_table');\n";
echo "var row = ac_describe_table.insertRow (1+iteration);\n";
echo "var cell1 = row.insertCell (0);\n";
echo "var textNode = document.createTextNode ('$EXTRA_CATEGORY '+iteration+':');\n";
echo "cell1.appendChild (textNode);\n";
echo "var cell2 = row.insertCell (1);\n";
echo "var sel = document.createElement('select');\n";
echo "sel.name = 'CATEG' + iteration;\n";
echo "sel.options[0] = new Option('$EXTRA_CATEGORY1', 'NULL');\n";
echo "sel.options[1] = new Option('--------------', 'NULL');\n";
for ($j = 0; $j < $i; $j++) {
  $row = $all_cats[$j];
  echo "sel.options[".($j+2)."] = new Option('$row[1]', '$row[0]');\n";
}
echo "cell2.appendChild(sel);\n";
echo "var counter = document.getElementById ('cat_count');\n";
echo "counter.value = iteration;\n";
echo "iteration++;\n";
echo "}\n";
echo "//--></SCRIPT>\n";

echo "<input type='button' value='$ADD_CATEGORY' onclick='insertDropdownBox();' />\n";
echo "<input type='hidden' value='0' name='CAT_COUNT' id='cat_count' />\n";
?>

<SCRIPT type="text/javascript" language="JavaScript">
<!--
function textCounter(field,maxlimit) {
if (field.value.length > maxlimit) field.value = field.value.substring(0, maxlimit);
} 
-->
</script>

<tr><td valign=top><? echo mandatory ($DESCRIPTION, 1) ?>:
<td><TEXTAREA NAME="DESCR1" COLS=45 ROWS=3
onChange="textCounter(document.myForm.DESCR1,500)"
onKeyDown="textCounter(document.myForm.DESCR1,500)"
onKeyUp="textCounter(document.myForm.DESCR1,500)">
<? echo stripslashes ($_REQUEST['DESCR1']) ?>
</TEXTAREA>,
<?
//printf ("<tr><td>%s: <td><select name='REG'> \n", mandatory($REGION));
echo "<select name='REG'>\n";
$query = 'select regid,'.$prefix.'regname from '.$REGIONTABLE.' order by '.$prefix.'regname';
$result = execute_query ($query);
while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
  $active = ($_REQUEST['REG']==$row[0]) ? 'selected' : '';
  printf ("<option value='%s' %s>%s\n", $row[0], $active, $row[1]);
}
echo "</select>\n";
mysql_free_result ($result);
?>

<SCRIPT type="text/javascript" language="JavaScript">
<!--
function pasteCoords(field) {
	return true;
}
-->
</SCRIPT>

<tr><td><? echo $COORD ?>: 
<td>
<input onpaste="return pasteCoords(0)" name="LAT" SIZE="8" MAXLENGTH="8" value="<? echo $_REQUEST['LAT'] ?>">,
<input onpaste="return pasteCoords(1)" name="LONG" SIZE="8" MAXLENGTH="8" value="<? echo $_REQUEST['LONG'] ?>">

<?
echo $COORD_INSTR;

$now = explode ('-', date('Y-m-d'));

echo '<tr><td>'.mandatory($TAKEN_ON).': <td>';

echo '<select name="DATE">';
$curdate = isset ($_REQUEST['DATE']) ? $_REQUEST['DATE'] : $now[2];
$active = (!$curdate) ? 'SELECTED' : '';
printf ("<option value='0' %s>??", $active);
for($i=1;$i<=31;$i++) {
  $curdate = isset ($_REQUEST['DATE']) ? $_REQUEST['DATE'] : $now[2];
  $active = ($curdate == $i) ? 'SELECTED' : '';
  printf ("<option value='%02d' %s>%02d", $i, $active, $i);
}
?>
</select>

<select name="MONTH">
<?
for($i=0;$i<=12;$i++) {
  $curmonth = isset ($_REQUEST['MONTH']) ? $_REQUEST['MONTH'] : $now[1];
  $active = ($curmonth == $i) ? 'SELECTED' : '';
  printf ("<option value='%02d' %s>%s", $i, $active, $months[$i]);
}
?>
</select>

<select name="YEAR">
<?
$curyear = isset ($_REQUEST['YEAR']) ? $_REQUEST['YEAR'] : $now[0];
for($i=START_YEAR;$i<=$this_year;$i++) {
  if (!($i % 100)) {
	  $active = ($curyear == (($i/100).'??')) ? 'SELECTED' : '';
	  printf ("<option value='%2d??' %s>%2d**", $i / 100, $active, $i /100);
  }
  if (!($i % 10)) {
	  $active = ($curyear == (($i/10).'?')) ? 'SELECTED' : '';
	  printf ("<option value='%3d?' %s>%3d*", $i / 10, $active, $i /10);
  }
  $active = ($curyear == $i) ? 'SELECTED' : '';
  printf ("<option value='%4d' %s>%4d", $i, $active, $i);
}
?>
</select>

<? echo $YR ?>

<tr><td><? echo mandatory($FILE_FIELD) ?>:
<input type="hidden" name="MAX_FILE_SIZE" value="1048576">
<td><INPUT TYPE=FILE NAME="IMAGE" SIZE=48 id="upload">
<tr><td><? echo $CORNER_FIELD ?>:
<td><select name="LOGOPOS">
<option value='0'><? echo $CORNER_0 ?>
<option value='1'><? echo $CORNER_1 ?>
<option value='2'><? echo $CORNER_2 ?>
<option value='3' selected><? echo $CORNER_3 ?>
</select>
<tr><td valign=top><? echo $INITIAL_COMMENT ?>:
<td><TEXTAREA NAME="COMMENT" COLS=45 ROWS=6>
<? echo $_REQUEST['COMMENT'] ?>
</TEXTAREA>

<tr valign='top'><td>
<?
// NO ANONIMOUS COMMENTS!
echo NO_COMMENTS_MSG.":<td><input TYPE=checkbox disabled name='NO_COMMENTS' $nocomments_checked>\n";
echo '('.NO_COMMENTS_INSTRUCTION.')';
?>
<tr valign='top'><td>
<?
echo MAIL_COMMENTS_MSG.":<td><input TYPE=checkbox name='MAIL_COMMENTS' $mailcomments_checked>\n";
echo '('.MAIL_COMMENTS_INSTRUCTION.')';
?>
<p>
<INPUT TYPE="HIDDEN" NAME="LNG" VALUE="<? echo $_REQUEST['LNG'] ?>">
<INPUT TYPE="HIDDEN" NAME="AUTHOR" VALUE="<? echo $_REQUEST['AUTHOR'] ?>">
<INPUT TYPE="HIDDEN" NAME="AUTHOR_PASSWORD" VALUE="<? echo $_REQUEST['AUTHOR_PASSWORD'] ?>"><INPUT TYPE="HIDDEN" NAME="UPLOADING" VALUE="1">

<!-- NO PIRACY! -->
</tbody></table><tr><td colspan=2 colspan=2 STYLE="padding-bottom : 5px; padding-left : 5px; padding-right : 5px; padding-top : 5px;"><? echo DISCLAIMER_IP ?>:
<input TYPE=checkbox name='NOT_MINE'>

<!-- NO RETURNS!  -->
<tr bgcolor=<? echo $FOURTH_COLOR ?>><td colspan=2 STYLE="padding-bottom : 5px; padding-left : 5px; padding-right : 5px; padding-top : 5px;"><p><i><? echo $DISCLAIMER ?></i>
<tr bgcolor=<? echo $THIRD_COLOR ?>><td valign=top colspan=2 STYLE="padding-bottom : 5px; padding-left : 5px; padding-right : 5px; padding-top : 5px;"><b><? echo $WAIT_INSTRUCTION ?></b><br><INPUT TYPE=SUBMIT VALUE=" <? echo $ADD_PICTURE ?> "> 
</form>
