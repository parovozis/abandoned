<p><table cellpadding="0" cellspacing="6" border="0"><tr>
<td width="136" valign="top"><b><? echo $TITLE ?>:</b>
<td valign="top">

<?
$OLD_SHOW_ALL = $show_all;
$show_all = !$show_all;
$cmdline_show_all = buildcmdl ();
$show_all = $OLD_SHOW_ALL;
$SHOW_ALL_MSG = $show_all ? $SHOW_COMPETING_ONLY : $SHOW_ALL;

$OLD_NO_ICONS = $_REQUEST['NO_ICONS'] ? '1':'0';
$NO_ICONS_MSG = $_REQUEST['NO_ICONS'] ? $SHOW_ICONS : $HIDE_ICONS;
$_REQUEST['NO_ICONS'] = $_REQUEST['NO_ICONS'] ? '0':'1';
$cmdline3 = buildcmdl ();
$_REQUEST['NO_ICONS'] = $OLD_NO_ICONS;

if (!$purist && VOTE_ALLOWED) {
  $purist_message = $IAMPURIST;
  $purist_now = 1;
} else {
  $purist_message = $IAMNOTPURIST;
  $purist_now = 0;
}

$items = array (
		array ($cmdline3, $NO_ICONS_MSG)
);
if (VOTE_ALLOWED) {
  $items[1] = array ($cmdline_show_all, $SHOW_ALL_MSG);
  $items[2] = array ('?PURIST='.$purist_now, $purist_message);
}
for ($i = 0; $i < count ($items); $i++) {
  $item = $items[$i];
  if ($i == $AC_MENU_ITEM) {
    echo "<font color='$THIRD_COLOR'>$item[1]</font>";
  } else {
    echo "<a href='$item[0]'><b>$item[1]</b></a>";
  }
  echo ' <b>|</b> ';
}
?>

<b>::</b>
<tr><td>&nbsp;
<td><ul><font color="<? echo $THIRD_COLOR ?>">
<? 
echo '<li>';
printf ($TOTAL_APPROVED, $approved_count['Y'] + $approved_count['N'], 
	$approved_count['Y'], $approved_count['N']);

$approved_query = "SELECT HIGH_PRIORITY approved,competing,count(*) FROM $PICTABLE WHERE ((approved='Y' AND votedon>='$tomorrow') OR approved='N' OR approved='A') GROUP BY approved,competing";
$result = execute_query ($approved_query);
$future = array ();
for ($i = 0; $i < 8; $i++) {
  $row = mysql_fetch_array ($result, MYSQL_NUM);
  $future[$row[0].$row[1]] = $row[2];
}
mysql_free_result ($result);

echo '<li>';
printf ($TOTAL_APPROVED1, 
	$future['NY'] + $future['NN'], $future['YY'] + $future['YN'], $future['AY'] + $future['AN'],
	(int)(ceil(($future['NY'] + $future['YY']) / (1.0 * ($DEFAULT_HOWMANY - HOW_MANY_NEW)))));

if (!$purist && VOTE_ALLOWED) {
  $vquery = "SELECT COUNT(DISTINCT uid) FROM $VOTETABLE WHERE votedate='$today'";
  $result = execute_query ($vquery);
  $row = mysql_fetch_array ($result, MYSQL_NUM);
  $total_voted = $row[0];
  mysql_free_result ($result);
  echo "<li>$TOTAL_VOTED_TODAY: <font color='$BLUE_COLOR'>$total_voted</font>.\n";

  if ($MODERATOR_MODE == 2) {
    $result = execute_query ("SELECT HIGH_PRIORITY count(*) from $PICTABLE where votedon='$tomorrow' AND competing='Y' AND approved='Y' and votedon=senton");
    $row = mysql_fetch_array ($result, MYSQL_NUM);
    mysql_free_result ($result);
    $approved_today = $row[0];
    $approved_more = $DEFAULT_HOWMANY - HOW_MANY_NEW - $row[0];
    echo '<li>'.APPROVE_MORE.": <font color='$BLUE_COLOR'>$approved_more</font>";
}

}
?>
</font>  

<?
function end_menu ()
{
  echo '</ul></table><p>';
}
?>
