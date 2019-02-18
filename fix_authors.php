<?
error_reporting(E_ERROR | E_PARSE );
require ("database.php");
$link = mysql_connect($HOST, $USER, $PASSWD)
     or die("Could not connect");
mysql_select_db($DBASE) or die("Could not select database");

$QUERY="select id,name from authors order by name";
$result = @mysql_query ($QUERY) or die ($QUERY. ": query failed");
echo '<table>';
while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
  $QUERY="select count(*) from pictures where author_id=$row[0]";
  $result1 = @mysql_query ($QUERY) or die ($QUERY. ": query failed");
  $row1 = mysql_fetch_array ($result1, MYSQL_NUM);
  mysql_free_result ($result1);
  $wide = $row1[0];

  $QUERY="select count(*) from picture_ng where author_id=$row[0]";
  $result1 = @mysql_query ($QUERY) or die ($QUERY. ": query failed");
  $row1 = mysql_fetch_array ($result1, MYSQL_NUM);
  mysql_free_result ($result1);
  $narrow = $row1[0];

  $QUERY="select count(*) from picture_dogpile where author_id=$row[0]";
  $result1 = @mysql_query ($QUERY) or die ($QUERY. ": query failed");
  $row1 = mysql_fetch_array ($result1, MYSQL_NUM);
  mysql_free_result ($result1);
  $dogpile = $row1[0];

  $QUERY="select count(*) from emb_authors where emb_authors_id=$row[0]";
  $result1 = @mysql_query ($QUERY) or die ($QUERY. ": query failed");
  $row1 = mysql_fetch_array ($result1, MYSQL_NUM);
  mysql_free_result ($result1);
  $emb = $row1[0];

  $QUERY="select count(*) from foto_comments where foto_comments_author_id=$row[0]";
  $result1 = @mysql_query ($QUERY) or die ($QUERY. ": query failed");
  $row1 = mysql_fetch_array ($result1, MYSQL_NUM);
  mysql_free_result ($result1);
  $wide_comments = $row1[0];

  $QUERY="select count(*) from foto_comments_ng where foto_comments_author_id=$row[0]";
  $result1 = @mysql_query ($QUERY) or die ($QUERY. ": query failed");
  $row1 = mysql_fetch_array ($result1, MYSQL_NUM);
  mysql_free_result ($result1);
  $narrow_comments = $row1[0];

  $QUERY="select count(*) from foto_comments_dogpile where foto_comments_author_id=$row[0]";
  $result1 = @mysql_query ($QUERY) or die ($QUERY. ": query failed");
  $row1 = mysql_fetch_array ($result1, MYSQL_NUM);
  mysql_free_result ($result1);
  $dogpile_comments = $row1[0];

  $total = $wide + $narrow + $dogpile + $emb + $wide_comments + $narrow_comments + $dogpile_comments;
/*  echo "<tr><td>$row[1] $row[0])<td>$wide<td>$narrow<td>$dogpile<td>$emb<td>$wide_comments<td>$narrow_comments<td>$dogpile_comments<th>$total";*/

  if (!$total) {
    echo "<tr><td>Removing $row[1]<br>\n";
    $QUERY="delete from authors where id=$row[0]";
    @mysql_query ($QUERY) or die ($QUERY. ": query failed");
  }
}
echo '</table>';
mysql_free_result ($result);
?>

