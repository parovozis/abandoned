<?php
if (isset ($_POST['FIXSTARS'])) {    
  require ('fixstars.php');
} else if (isset ($_REQUEST['BANAUTHOR'])) {
  require ('banauthor.php');
} else if (isset ($_REQUEST['MERGEINTO'])) {
	require ('mergeinto.php');
}

if ($_REQUEST['AUTHOR_ID'] || is_numeric ($_REQUEST['AUTHOR'])) {
  if (isset ($_REQUEST['AUTHOR_ID']))
    $_REQUEST['AUTHOR'] = $_REQUEST['AUTHOR_ID'];
  $lquery = "SELECT name,bio,foto_url,email,id,show_email,login,last_login,authors_modified FROM authors where id='".$_REQUEST['AUTHOR']."'";
} else {
	$lquery = "SELECT name,bio,foto_url,email,id,show_email,login,last_login,authors_modified FROM authors where (name like '%".$_REQUEST['AUTHOR']."%' or login='".$_REQUEST['AUTHOR']."')";
}
$result = execute_query ($lquery);
$size = mysql_num_rows ($result);
if ($size != 1) {
  return;
}

$author_info = mysql_fetch_array ($result, MYSQL_NUM);
mysql_free_result ($result);

if (!is_numeric($_REQUEST['AUTHOR']))
	$_REQUEST['AUTHOR'] = stripslashes ($author_info[0]);

$aquery =  "SELECT pg_stars_type,count(*) FROM pg_stars WHERE pg_stars_x_authors_id=".$author_info[4]." AND pg_stars_maintable='$PICTABLE' GROUP BY pg_stars_type ORDER BY pg_stars_type ASC";
$result = execute_query ($aquery);
$awards = array(0, 0, 0);
while ($stars = mysql_fetch_array ($result, MYSQL_NUM)) {
  $awards[$stars[0]] = $stars[1];
}
mysql_free_result ($result);

$cnt = $awards[0] + $awards[1] / 2.0 + $awards[2] / 4.0;

$kind = get_kind ($cnt);
$table_bg = 'bgcolor="white"';
echo "<p><table width='95%' border='0' cellpadding='1' cellspacing='0' bgcolor='$LOGO_COLOR'>\n";
echo "<tr><td><table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
echo "<tr><td $table_bg><table border='0' width='100%' cellpadding=5 cellspacing=0>\n<tr bgcolor='$SECOND_COLOR'>\n";

// Предупреждения
$warn = warnings ($author_info[4]);
if ($warn >= WARNING_POST_THRESHOLD)
	echo '<td width=30%><b><strike>'.stripslashes ($author_info[0]).'</strike></b>';
else {
	echo '<td width=30%><b>'.stripslashes ($author_info[0]).'</b>';
	for ($i = 0; $i < $warn; $i++) echo BLACKSTAR;
}

// Не модератор ли он?
$modquery =  "SELECT count(*) FROM pg_moderators WHERE pg_moderators_x_authors_id=$author_info[4] AND pg_moderators_maintable='$PICTABLE'";
$modresult = execute_query ($modquery);
$modcount = mysql_fetch_array ($modresult, MYSQL_NUM);
mysql_free_result ($modresult);
if ($modcount[0]) echo " <b>[МОДЕРАТОР]</b>";
/*
if ($_REQUEST['AUTHOR_PASSWORD']==MODERATOR) {
	if ($modcount[0])
		echo '[УДАЛИТЬ]';
	else
		echo '[СДЕЛАТЬ МОДЕРАТОРОМ]';
}
*/	
echo ": $MYGALLERY\n<td width=50% align=center colspan=2>";

if (!$_REQUEST['PURIST'] && VOTE_ALLOWED) {
  echo "<a href='hof.php'>$kind</a> {";
  show_medals ($awards[0], $awards[1], $awards[2]);
  echo '}';
}

echo "<td align=right width=20%><a href='ac_authorize.php?AUTHOR_LOGIN=$author_info[6]'>$LOGIN</a>\n";
echo "<tr><td $table_bg colspan=4><table width=100% border=0 cellpadding=5 cellspacing=0><tr>\n";
echo '<td valign=top rowspan=4 width=160>';
if (!$author_info[2]) {
  $author_info[2]='NULL/dummy';
} else {
  echo "<a href='$OTHER_GALLERY/../../".PG_BASE."/$author_info[2].jpg'>";
}
echo "<img src='$OTHER_GALLERY/../../".IC_BASE."/$author_info[2]-s.jpg' hspace='5' vspace='5' alt='$author_info[0]' align='left' border='0'>";
if ($author_info[2]!='NULL/dummy') {
  echo "</a>\n";
}

echo '<td align=left>';
if ($author_info[1]) {	
  echo "<b>$MYBIO:</b><br>".stripslashes ($author_info[1]);
} else {
  echo "<b>$NOBIO.</b><br>\n";
}

echo '<tr><td>';
if ($author_info[3] && ($author_info[5] || $MODERATOR_MODE==2 || $author_info[4] == $myid)) {
  echo "<b>$MYMAIL:</b><br>".str_replace('@','<img src='.$OTHER_GALLERY.'images/at.gif width=12 height=13 border=0 vspace=0 hspace=0>',str_replace('.','&middot;',$author_info[3]));
  if (!$author_info[5])
	  echo ' (спрятан)';
} else {
  echo "<b>$NOEMAIL.</b><br>\n";
}

echo "<tr><td><b>$MYREG:</b><br>\n";
$lquery = "SELECT count(*) as cnt,".$prefix."regname,region FROM $PICTABLE,$REGIONTABLE where $approve and author_id='$author_info[4]' and region=regid and collection='N' group by region order by cnt desc";
$result = execute_query ($lquery);
$first = 1;
while ($cnt = mysql_fetch_array ($result, MYSQL_NUM)) {
  if ($first)
    $first = 0;
  else
    echo (', ');
  echo $cnt[1]." (<a href=\"?LNG=$LNG&AUTHOR=$author_info[4]&REGION=$cnt[2]\">$cnt[0] $FOTO</a>)";
}
mysql_free_result ($result);

echo "<tr><td><b>$MYPREF:</b><br>\n";
$lquery = "SELECT COUNT(*) AS cnt,$BASECATTABLE.".$prefix."title,pg_cats_x_cat_name FROM $PICTABLE,$CATTABLE,$BASECATTABLE WHERE $approve AND author_id='$author_info[4]' AND $PICTABLE.id=pg_cats_x_picture_id AND pg_cats_x_cat_name=$BASECATTABLE.name AND collection='N'  GROUP BY pg_cats_x_cat_name ORDER BY cnt DESC LIMIT 10";
$result = execute_query ($lquery);
$first = 1;
while ($cnt = mysql_fetch_array ($result, MYSQL_NUM)) {
  if ($first)
    $first = 0;
  else
    echo (', ');
  echo $cnt[1]." (<a href=\"?LNG=$LNG&AUTHOR=$author_info[4]&CATEG=$cnt[2]\">$cnt[0] $FOTO</a>)";
}

if ($MODERATOR_MODE == 2) {
	require ('warntable.php');
	warntable ($_REQUEST['AUTHOR']);
}

mysql_free_result ($result);
echo "</table>\n\n";


//  echo "<p><b>$MYOTHERGALLERIES:</b><br>\n";


if ($MODERATOR_MODE == 2) {
  echo '<tr bgcolor="'.$SECOND_COLOR.'" valign=top>';
  if (!$author_info[7])
    $author_info[7] = 'Не был на сайте с 02.II.2007.';
  echo "<td align=left>";
  echo 'Последний раз был на сайте: '.$author_info[7] .' ';
  echo 'Информация обновлена: '.$author_info[8].'.';
  echo "<td align=center>\n";

	if (MODERATOR == $_COOKIE['COOKIE_AUTHOR_PASSWORD']) {
  echo "<form action='$PHP_SELF' method='POST'>\n";
  if (empty($_POST['PREPAREMERGE'])) {
	  echo "<input name=DOIT value='Объединить...' type='submit'> ";
	  echo "<input name='PREPAREMERGE' type='hidden' value='1'>";
  } else {
	  echo "<input name=DOIT value='Слить с:' type='submit'> ";
	  echo '<select name="MERGEINTO">';
	  $au_query = 'select id,name,login from authors order by name';
	  $au_result = execute_query ($au_query);
	  while ($au_row = mysql_fetch_array ($au_result, MYSQL_NUM)) {
		  echo "<option value=$au_row[0]>$au_row[1] # $au_row[2]\n";
	  }
	  mysql_free_result ($au_result);
	  echo '</select> ';
  }
  echo "<input name='AUTHOR' type='hidden' value='$author_info[4]'>";
  echo '</form>';
}
  
  echo "\n\n<td align=right>";
  echo "<form action='$PHP_SELF' method='POST'>";
  echo "<input value='Починить звёзды' type='submit'>";
  echo "<input name='FIXSTARS' type='hidden'>";
  echo "<input name='AUTHOR' type='hidden' value='$author_info[4]'>";
  echo '</form>';
  echo "\n\n<td align=right>";
  echo "<form action='$PHP_SELF' method='POST'>";
  echo "<input value='Временно запретить автора' type='submit'>";
  echo "<input name='BANAUTHOR' type='hidden'>";
  echo "<input name='AUTHOR' type='hidden' value='$author_info[4]'>";
  echo '</form>';
}
echo "</table></table></table><p>\n\n\n\n";
?>
