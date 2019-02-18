<?
if (!isset($_GET['LNG'])) {$LNG='RU';} else {$LNG=$_GET['LNG'];}
require ('database.php');
require ('setup.php');
require ('time.php');
require ($LNG.'/index.php');
$link = mysql_connect($HOST, $USER, $PASSWD) or die('Cannot connect');
mysql_select_db($DBASE) or die('Could not select database');

function rss_comment ()
{
	global $LNG, $real_LNG, $PICTABLE, $prefix, $REGIONTABLE, $today, $FOTO_COMMENTS_TABLE, $TOP100COMMENTS, $WEBSITE_STRICT, $WEBSITE_DESCR, $LOGO;
  
  header ("Content-Type: text/xml; charset=utf-8");
  echo "<?xml version='1.0' encoding='utf-8' ?>\n";
  echo "<rss version='2.0' xmlns:dc='http://purl.org/dc/elements/1.1/'>\n";
  echo "<channel>\n";
  echo "<title>$WEBSITE_STRICT</title>\n";
  echo "<link>".GHOST."</link>\n";
  echo "<description>$WEBSITE_DESCR $TOP100COMMENTS</description>\n";
  echo "<language>$real_LNG</language>\n";
  echo "<ttl>1440</ttl>\n";
  echo "<image>\n";
  echo "<title>$WEBSITE_STRICT</title>\n";
  echo "<link>".GHOST."</link>\n";
  echo "<url>$LOGO</url>\n";
  echo "</image>\n";  
  
  $QUERY = "SELECT foto_comments_text, foto_comments_date, foto_comments_author, {$prefix}description, foto_comments_picture FROM $FOTO_COMMENTS_TABLE,$PICTABLE WHERE approved='Y' AND (isnull(senton) or senton<='$today') AND foto_comments_picture=id AND foto_comments_deleted='N' ORDER BY foto_comments_date DESC LIMIT 100";
  $result = mysql_query ($QUERY);
  
  while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
	  $title = str_replace ('&', '&amp;', preg_replace ('@(<[^>]+>)@si', '', $row[3]));
	  $descr = str_replace ('&', '&amp;', $row[0]);
	  $author = $row[2] ? str_replace ('&', '&amp;', $row[2]) : 'аноним';
	  echo "<item>\n";
	  echo "<title>$title</title>\n";
	  echo "<description>$descr // $author, $row[1]</description>\n";
	  echo "<pubDate>$row[1]</pubDate>\n";
	  echo "<author>$author</author>\n";
	  echo "<link>".GHOST."?ID=$row[4]#comments</link>\n";
	  echo "</item>\n";
  }
  mysql_free_result ($result);
  echo "</channel>\n";
  echo "</rss>\n";
}

//rss_comment ();
?>
������������� ����� �� �������, ��� ��� ������� ������� �������������.
		