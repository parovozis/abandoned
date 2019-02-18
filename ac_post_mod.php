<?
require ($_REQUEST['LNG'].'/ac_post_mod.php');
$SELECTED = 4;
include ('pg_head.php');

$AC_MENU_ITEM=0;
$AC_MENU_INTRO=AC_POST_MOD_WELCOME . '<br>' . AC_POST_MOD_WELCOME_SITE_SPECIFIC;
require ('ac_menu.php');

if ($_FILES['IMAGE']['tmp_name']) {
  include ('ac_insert.php');
} else {
  function followup () { echo '&nbsp;'; }
}

if ($NO_NEW_PICTURES) {
	echo '<li>'.mandatory('Приём новых фотографий в эту фотогалерею прекращён.');
	end_menu ();	
	include ('pg_tail.php');
	return;
}

$query = "SELECT {$PICTABLE}_reputation.reputation FROM {$PICTABLE}_reputation,authors WHERE author=id AND login='{$_REQUEST['AUTHOR']}'";
$result = execute_query ($query);
$reputation = mysql_fetch_array ($result, MYSQL_NUM);
mysql_free_result ($result);

$query = "select authors_send_limit_value from $ASLTABLE,authors where authors_send_limit_author=authors.id and login='{$_REQUEST['AUTHOR']}'";
$result = execute_query ($query);
$sendlimit = mysql_fetch_array ($result, MYSQL_NUM);
mysql_free_result ($result);

$warn = warnings ($author_id);
if ($warn >= WARNING_POST_THRESHOLD) {
	$gfys = true;
	echo '<li><b>'.mandatory("Вы забанены!").'</b>';
} else {
	if ($sendlimit || INIT_SEND_LIMIT <= 0) {
		if (INIT_SEND_LIMIT > 0)
			$count = $sendlimit[0];
		else
			$count = -1;
	} else {
		$query = "select authors.id from authors where login='".$_REQUEST['AUTHOR']."'";
		$result = execute_query ($query);
		$identity = mysql_fetch_array ($result, MYSQL_NUM);
		mysql_free_result ($result);

		$count = INIT_SEND_LIMIT;
		$myid = $identity ? $identity[0] : -1;
		$query = "insert into $ASLTABLE values ($myid,$count)";
		execute_query ($query);
	}
	$gfys = false;
}
	
if ($reputation) {
	if ($reputation[0] < 2 * WARNING_THRESHOLD) {
		$gfys = true;
		echo '<li><b>'.mandatory(sprintf (VERY_BAD_AUTHOR, 2 * WARNING_THRESHOLD - $reputation[0])).'</b>';
	} else if ($reputation[0] < WARNING_THRESHOLD) {
		echo '<li><b>'.mandatory(BAD_AUTHOR).'</b>';
	}
} 

if (!$gfys) {
	if ($count == 0 || $count < -1) {
		echo '<li>'.$NO_MORE_PICS_TODAY;
		$post_ok = false;
	} else if ($count == -1) {
		printf ('<li>'.$YOU_CAN_PUBLISH_ALOT);
		$post_ok = true;
	} else {
		printf ('<li>'.$YOU_CAN_PUBLISH, $count);
		$post_ok = true;
	}
	echo '<li>'.mandatory($MANDATORY);
	end_menu ();

	echo '<table width="100%" cellpadding="1" cellspacing="0" border="0" bgcolor='.$THIRD_COLOR.'><tr><td>';
	echo '<table width="100%" cellpadding="2" cellspacing="0" border="0" bgcolor=white  background=bg.jpg><tr>';
	echo '<td valign="top" align="left" width=25%>';
	followup ();
	echo '<td width=75%>';
	include ('ac_describe.php');
}
echo '</table></table>';

include ('pg_tail.php');
?>
