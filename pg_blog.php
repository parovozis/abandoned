<?php
require ('pg_db.php');
require ('functions.php');
require ($_REQUEST['LNG'].'/pg_view.php');
require ($_REQUEST['LNG'].'/index.php');

define ('COUNT', 25);
define ('CONDITION', "approved='Y' AND region='${_GET['REGION']}' AND (ISNULL(senton) OR senton<='$today')");
$query = "SELECT regname FROM $REGIONTABLE WHERE regid='${_GET['REGION']}'";
$result = execute_query ($query);
$row = mysql_fetch_array ($result, MYSQL_NUM);

if ($row) {
	$cquery = "SELECT COUNT(*) FROM $PICTABLE WHERE ".CONDITION;
	$cresult = execute_query ($cquery);
	$crow = mysql_fetch_array ($cresult, MYSQL_NUM);
	$total = $crow[0];
	mysql_free_result ($cresult);
	
	$from = $_GET['FROM'] ? $_GET['FROM'] : 0;
	
	$PAGE = "Фотохронология &laquo;Паровоза ИС&raquo;: $row[0]";
	require ('pg_head.php');
	echo "<h2>$PAGE</h2>";
	mysql_free_result ($result);
	
	if (!$from)
		echo 'Фотохронология - блогообразная коллекция всех фотографий, сделанных в этом регионе, с комментариями авторов и посетителей. Если вы хотите добавить свой комментарий, щёлкните по мини-фотографии.<p>';
	
	$legend = '';
	$gap = false;
	for ($i = 0; $i < $total; $i += COUNT) {
		if ($i > COUNT && $i < $total - 2 * COUNT && abs ($i - $from) > 9 * COUNT && ($i%(COUNT*20))) {
			if (!$gap) {
				$gap = true;
				$legend .= '... ';
			}
			continue;
		} else {
			$gap = false;
		}
		$begin1 = $i + 1;
		$end1 = min ($total, $begin1 + COUNT - 1);
		if ($i == $from) {
			$open = '<b><font color='.$LOGO_COLOR.'>';
			$close = '</font></b>';
		} else {
			$open = "<a href=?FROM=$i&LNG=$LNG&REGION=${_GET['REGION']}>";
			$close = '</a>';
		}
		$legend .= "$open$begin1-$end1$close ";
	}
	$legend .= '<p>';	
	echo $legend;
} else {
	$PAGE = "Неизвестный регион!";
	require ('pg_head.php');
	echo "<h2>$PAGE</h2>";
	include ('pg_tail.php');
	exit;
}

$query = "SELECT $PICTABLE.id,url,takenon,description,name,collection,baddate FROM $PICTABLE,authors WHERE ".CONDITION." AND author_id=authors.id ORDER BY takenon,baddate LIMIT $from,".COUNT;
$result = execute_query ($query);

echo '<table border=0 cellspacing=3>';
while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
	echo "<tr><td valign=top bgcolor=$FOURTH_COLOR><a href=index.php?ID=$row[0]><img src='".IC_BASE."$row[1]-s.jpg' border='0' height=128 hspace=5 vspace=5 align=right></a>\n";
	
	$date = $row[2] ? mydate ($row[2]) : $row[6];	
	echo "<td valign=top><font color=$LOGO_COLOR><b>$row[3]</b>, $date</font><br>";
	$collection = $row[5]=='Y' ? (' ('.COLLECTION.')') : '';
	echo "<i>$row[4]$collection</i><p>\n";
	
	$cquery = "SELECT foto_comments_text, foto_comments_date, foto_comments_author, foto_comments_author_id FROM $FOTO_COMMENTS_TABLE,$PICTABLE WHERE foto_comments_picture=$row[0] AND foto_comments_picture=$PICTABLE.id AND approved='Y' AND (ISNULL(senton) OR senton<='$today') AND foto_comments_deleted='N' ORDER BY foto_comments_date";
	$cresult = execute_query ($cquery);
	$has_comments = false;
	
	echo '<table border=0 cellspacing=0 cellpadding=3 width=100%>';
	while ($comment = mysql_fetch_array ($cresult, MYSQL_NUM)) {
		$has_comments = true;
		
		$author = $comment[2] ? $comment[2] : 'Аноним';
		$text =  preg_replace ('@http://([\d\w/\.\-\?\&\=\*%\+#!~,]+[\d\w/\-\?&])@si', '[<a href="http://\1"><img src="'.$OTHER_GALLERY.'images/up2.gif" width=11 height=9 border=0 align=bottom></a>]', $comment[0]);
		$smiles_symbols = array (':)', ':(',':-)',':-(','[quote]','[/quote]');
		$smiles_pictures 
				= array ('<img width=15 height=15 src='.$OTHER_GALLERY.'/images/smilie1.gif>',
					 '<img width=15 height=15 src='.$OTHER_GALLERY.'/images/smilie2.gif>',
      '<img width=15 height=15 src='.$OTHER_GALLERY.'/images/smilie1.gif>',
      '<img width=15 height=15 src='.$OTHER_GALLERY.'/images/smilie2.gif>',
      '&gt; <i><font color='.$BLUE_COLOR.'>',
      '</font></i><br>');
		$text = str_replace ($smiles_symbols, $smiles_pictures, $text);
		echo "<tr><td valign=top align=right width=15%><b>$author</b>:<td valign=top>$text</font> (<font size=-1>$comment[1]</font>)\n";
	}
	mysql_free_result ($cresult);
	if (!$has_comments) {
		echo "<tr><td valign=top align=right width=15%><b>$_author</b>:<td valign=top>$NO_COMMENTS_YET</font>\n";
	}
	echo "</table><br clear=left>\n";
}

echo '</table><p>';
echo $legend;
include ('pg_tail.php');
?>

