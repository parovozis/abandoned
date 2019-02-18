<?
require ('pg_db.php');
require ('functions.php');
require ($_REQUEST['LNG'].'/hof.php');
require ($_REQUEST['LNG'].'/pg_head.php');

$PAGE = $HOF;
$SELECTED = 6;
include ('pg_head.php');
if(isset($PURIST) || !VOTE_ALLOWED) {
  echo $WHAT_HOF;
} else {
  define (BASE,256);
  $QUERY =  "SELECT name,sum(pow(".BASE.",2-pg_stars_type)),pg_stars_x_authors_id,sum(pow(2,2-pg_stars_type)) AS val FROM pg_stars,authors WHERE pg_stars_x_authors_id=id AND pg_stars_maintable='$PICTABLE' AND pg_stars_x_authors_id>0 GROUP BY pg_stars_x_authors_id ORDER BY val ASC,name";
  
  $result = @mysql_query ($QUERY) or die ($QUERY.": query failed");
  
  echo "<p><center><table width='95%' border='0' cellpadding='1' cellspacing='0' bgcolor='$LOGO_COLOR'>\n";
  echo "<tr><td><table width='100%' border='0' cellpadding='5' cellspacing='0'>\n";
  echo "<tr bgcolor='$OLD_COLOR'><td>$HOF_INTRO";
  
  $thr = 1;
  $begin = 1;
  echo '<p>';
  define ('FACTOR', 4);
  while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
    $gold = (int)($row[1]/(BASE*BASE));
    $silver = (int)((int)($row[1]/BASE)%BASE);
    $bronze = $row[1] % BASE;
    $total = 4 * $gold + 2 * $silver + $bronze;
    if ($total < FACTOR)
      continue;
    if ($total >= FACTOR * $thr) {
      while ($total >= 2 * FACTOR * $thr) {
	$thr *= 2;
      }
      if (!$begin) {
	echo '</table>';
      } else {
	$begin = 0;
      }
      echo '<tr><td bgcolor="'.$FOURTH_COLOR.'"><b>'.get_kind ($thr).'</b><tr bgcolor="white"><td><table border="0" width="100%" cellspacin="0"><tr>';
      $thr *= 2;
      $i = 0;
    }
    echo " <td width='33%'><a href='index.php?LNG=".$_REQUEST['LNG']."&AUTHOR=$row[2]'>$row[0]</a> ";
    show_medals ($gold,$silver,$bronze);
    echo " \n";
    $i++;
    if ($i % 4 == 3) {
      echo '<tr>';
      $i = 0;
    }  
  }
  mysql_free_result ($result);
  echo '</table>';
  echo '</table></table></center><p>';
}
include ('pg_tail.php');
?>
