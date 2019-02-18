<?
error_reporting(E_ERROR | E_PARSE | E_WARNING);
require ("database.php");
if (!($link = mysql_connect ($HOST, $USER, $PASSWD)) 
    || !mysql_select_db ($DBASE)) {
  include ("pg_head.php");
  echo '<font color="red" size="+1"><b>'.$NOTAVAILABLE.'</b></font><br>';
  include ("pg_tail.php");
  die('');
}
$COMMENTS_PER_PAGE = 100;

require ("functions.php");
require ("vw_showcomments.php");
require ($_REQUEST['LNG'].'/edit.php');
require ($_REQUEST['LNG'].'/vw_showall.php');


if (!isset ($_REQUEST['OFFSET'])) {
     $_REQUEST['OFFSET'] = 0;
}
$WEBSITE .= ": $COMMENTS ".($_REQUEST['OFFSET']+1).'-'.($_REQUEST['OFFSET']+$COMMENTS_PER_PAGE);
$SELECTED = 5;
include ("pg_head.php");
echo '<p>';
$kwd = $_REQUEST['DESCR'];
$author = $_REQUEST['AUTHOR'];
if ($_REQUEST['OFFSET'] > 0) {
  $new_offset =  max (0, $_REQUEST['OFFSET'] - $COMMENTS_PER_PAGE);
  echo "$LESS_COMMENTS\n";
  for ($i = 0; $i <  $_REQUEST['OFFSET']; $i += $COMMENTS_PER_PAGE) {
    $k = $i + 1;
    $j = min ($k + $COMMENTS_PER_PAGE - 1, $_REQUEST['OFFSET']);
    echo "<a href='$PHP_SELF?OFFSET=$i&DESCR=$kwd&AUTHOR=$author'>[$k - $j]</a>\n";
  }
  echo '<p>';
}
echo "<center><table width=95% cellpadding=1 cellspacing=0 bgcolor='$LOGO_COLOR'><tr><td>";
echo "<table width=100% cellpadding=5 cellspacing=0><tr bgcolor='white'><td width=50% valign='top'>";

$returned = show_comments (0, $COMMENTS_PER_PAGE / 2, $_REQUEST['OFFSET'], -1, $kwd, $author);
echo "<td width=50% valign='top'>";
$returned = show_comments (0, $COMMENTS_PER_PAGE / 2, 
			   $COMMENTS_PER_PAGE / 2 + $_REQUEST['OFFSET'], -1, $kwd, $author);
?>

</table></table></center><p>
<?
$count = 1000 /* $returned[0] */;

if ($count > $_REQUEST['OFFSET'] + $COMMENTS_PER_PAGE) {
  echo "$MORE_COMMENTS\n";
  for ($i = $_REQUEST['OFFSET'] + $COMMENTS_PER_PAGE; $i < $count; 
       $i += $COMMENTS_PER_PAGE) {
    $k = $i + 1;
    $j = min ($k + $COMMENTS_PER_PAGE - 1, $count);
    echo "<a href='$PHP_SELF?OFFSET=$i&DESCR=$kwd&AUTHOR=$author'>[$k - $j]</a>\n";
  }
}
include ("pg_tail.php");
?>
