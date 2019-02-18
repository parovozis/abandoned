<?php
require ('pg_db.php');
require ('functions.php');
$LNG = $_REQUEST['LNG'];
require ($LNG.'/pg_head.php');
require ($LNG.'/stats.php');

$PAGE = $STATS;
$SELECTED = 7;
include ('pg_head.php');

if ($LNG == 'EN') {
    $prefix = 'e';
} else {
    $prefix = '';
}

define ('limit', 50);

printf ('<p>'.$STATS_INTRO, limit, limit, limit);
flush (); 
ob_flush ();

echo '<p><center><table width="95%%" border="3" cellpadding="3">';
echo '<tr bgcolor="'.$SECOND_COLOR.'">';
echo '<th width="25%">'.$AUTHORS;
echo '<th width="25%">'.$CATS;
echo '<th width="25%">'.$REGIONS;
echo '<th width="25%">'.$YEARS;
echo '<tr>';

$approved = "approved='Y' ";
/*
if ($MODERATOR_MODE != 2) {
 $approved .= "and (isnull(votedon) or votedon<'$tomorrow') ";
}
*/
$QUERIES = 
array (
       "name,count(*) as z,author_id from $PICTABLE,authors where author_id=authors.id and $approved group by author_id" => 'AUTHOR', 
       $prefix."title,count(*) as z,name from $CATTABLE,$BASECATTABLE where pg_cats_x_cat_name=name group by name" => 'CATEG',
       $prefix."regname,count(*) as z,regid from $PICTABLE,$REGIONTABLE where region=regid and $approved group by regname" => 'REGION',
       "substring(takenon,1,4) as z,count(*) as b from $PICTABLE where isnull(baddate) and $approved group by z" => 'TAKENON'
      );

/* Get the total */
$QUERY = 'select HIGH_PRIORITY count(*) from '.$PICTABLE.' where '.$approved;
$result = execute_query ($QUERY);
$row = mysql_fetch_array ($result, MYSQL_NUM);
$total = $row[0];
mysql_free_result ($result);

foreach ($QUERIES as $q => $id) {
  $query = "select HIGH_PRIORITY $q order by z desc limit ".limit;
  $result = execute_query ($query);
  echo '<td valign="top"><ol>';
  while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
    if ($row[2] == '') $key = $row[0]; else $key = $row[2];
    printf ("<li><a href='index.php?SHOW_ALL=1&LNG=$LNG&$id=$key'>$row[0]</a> (%d)\n", $row[1]);
  }
  mysql_free_result ($result);
  echo "</ol>";
}

echo '</table></center><p>';

echo $STATS_INTRO1;
echo STATS_LEGEND;
flush (); 
ob_flush ();

echo '<p><center><table width="95%%" border="3" cellpadding="3">';
echo '<tr bgcolor="'.$SECOND_COLOR.'"><th width="50%" valign="top">'.$ACTIVITY;
echo '<th width="50%" valign="top">'.$PUBLISHING;
echo "<tr>\n";
echo '<td valign="top" align="left">';

define ('HALFPAGE',350);
$QUERY = "select HIGH_PRIORITY substring(takenon,6,2) as b,count(*),competing from $PICTABLE where substring(takenon,6,2)!='00' and $approved group by b,competing order by b desc,competing desc";
$result = execute_query ($QUERY);

$cached = 0;
while ($cached || $row = mysql_fetch_array ($result, MYSQL_NUM)) {  
  if ($cached) {
    $row = $cached;
    $cached = 0;
  }
  echo '<b>'.$row[0].'</b>&nbsp;';
  $percent = $row[1] / $total * 100;
  $w = min (20 * $percent, HALFPAGE);
  echo "<a href=index.php?MONTH=$row[0]&SHOW_ALL=0><img border=0 hspace=0 vspace='2' height='10' src='$OTHER_GALLERY/images/red.png' width=$w></a>\n";
  $count = $row[1];
  $row = mysql_fetch_array ($result, MYSQL_NUM);
  if ($row[2] == 'N') {
    $percent1 = $row[1] / $total * 100;
    $w = min (20 * $percent1, HALFPAGE);
    echo "<a href=index.php?MONTH=$row[0]&SHOW_ALL=1><img border=0 hspace=0 vspace='2' height='10' src='$OTHER_GALLERY/images/blue.gif' width=$w></a>\n";
    $count += $row[1];
  } else {
    $cached = $row;
  }
  printf ('%.0f%%<br>', $percent + $percent1);
}
mysql_free_result ($result);

echo '<td valign="top" align="left">';

$QUERY = "select HIGH_PRIORITY substring(senton,1,7) as b,count(*) as z,competing from $PICTABLE where !isnull(senton) and approved='Y' and senton<='$today' group by b,competing order by b desc,competing desc limit 24";
$result = execute_query ($QUERY);
$cached = 0;
while ($cached || $row = mysql_fetch_array ($result, MYSQL_NUM)) {  
  if ($cached) {
    $row = $cached;
    $cached = 0;
  }
  echo '<b>'.$row[0].'</b>&nbsp;';
  $w = min ($row[1]*0.1, HALFPAGE);
  echo "<img hspace=0 vspace='2' height='10' src='$OTHER_GALLERY/images/red.png' width=$w>\n";
  $count = $row[1];
  $row = mysql_fetch_array ($result, MYSQL_NUM);
  if ($row[2] == 'N') {
    $w = min ($row[1]*0.1, HALFPAGE);
    echo "<img hspace=0 vspace='2' height='10' src='$OTHER_GALLERY/images/blue.gif' width=$w>\n";
    $count += $row[1];
  } else {
    $cached = $row;
  }
  echo "&nbsp;$count<br>\n";
}
echo '</table></center>';

include ('pg_tail.php');
?>
