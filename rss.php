<?
if (!isset($_GET["LNG"])) {$LNG='RU';} else {$LNG=$_GET["LNG"];}
require ("database.php");
require ("setup.php");
require('time.php');
require ($LNG.'/index.php');
$link = mysql_connect($HOST, $USER, $PASSWD) or die('Cannot connect');
mysql_select_db($DBASE) or die("Could not select database");

function rss ()
{
  global $DEFAULT_HOWMANY, $LNG, $real_LNG, $WEBSITE_STRICT, $WEBSITE_DESCR, $PICTABLE, $prefix, $OTHER_GALLERY, $LOGO, $REGIONTABLE, $today;
  header ("Content-Type: text/xml; charset=utf-8");
  echo "<?xml version='1.0' encoding='utf-8' ?>\n";
  echo "<rss version='2.0' xmlns:dc='http://purl.org/dc/elements/1.1/'>\n";
  echo "<channel>\n";
  echo "<title>$WEBSITE_STRICT</title>\n";
  echo "<link>".GHOST."</link>\n";
  echo "<description>$WEBSITE_DESCR</description>\n";
  echo "<language>$real_LNG</language>\n";
  echo "<ttl>1440</ttl>\n";
  echo "<image>\n";
  echo "<title>$WEBSITE_STRICT</title>\n";
  echo "<link>".GHOST."</link>\n";
  echo "<url>$LOGO</url>\n";
  echo "</image>\n";  
  
  $QUERY="SELECT HIGH_PRIORITY description,name,{$prefix}regname,takenon,baddate,$PICTABLE.id,url from $PICTABLE,$REGIONTABLE,authors where approved='Y' and senton='$today' and region=regid and (author_id=authors.id)  order by senton desc,takenon limit $DEFAULT_HOWMANY";
  $result = mysql_query ($QUERY);
  
  list ($year,$month,$day)= split ("-", $today);
  $time = mktime (0,0,0,$month,$day,$year);
  $today_rfc = date ("r", $time);

  while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
    echo "<item>\n";
    echo "<title>$row[0]</title>\n";
    echo "<link>".GHOST."?ID=$row[5]&amp;LNG=$LNG</link>\n";
    echo "<description>$row[0], $row[2], $row[1]";
    if ($row[3]) {
      echo ", $row[3]\n"; 
    } else {
      echo ", $row[4]\n"; 
    }
    echo "</description>\n";
    echo "<pubDate>$today_rfc</pubDate>\n";
    echo "<author>$row[1]</author>\n";
	echo "<enclosure url='".GHOST.IC_BASE."$row[6]-s.jpg' type='image/jpeg' />\n";
    echo "</item>\n";
  }
  mysql_free_result ($result);
  echo "</channel>\n";
  echo "</rss>\n";
}

rss();
?>
