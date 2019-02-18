<?
error_reporting(E_ERROR | E_PARSE );
require ("database.php");
$link = mysql_connect($HOST, $USER, $PASSWD)
     or die("Could not connect");
mysql_select_db($DBASE) or die("Could not select database");

$QUERY="update authors set fotoday=0,fotoday_1=0,fotoday_2=0";
@mysql_query ($QUERY) or die ($QUERY. ": query failed");
echo $QUERY.'<br>';
$QUERY="update pictures set award=-1 where approved!='Y'";
$result = @mysql_query ($QUERY) or die ($QUERY. ": query failed");
echo $QUERY.'<br>';
$QUERY="select author_id,award from pictures where award>-1 and approved='Y'";
$result = @mysql_query ($QUERY) or die ($QUERY. ": query failed");
echo $QUERY.'<br>';
while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
  switch ($row[1]) {
  case 0:
    $QUERY="update authors set fotoday=fotoday+1 where id='$row[0]'";
  @mysql_query ($QUERY) or die ($QUERY. ": query failed");
echo $QUERY.'<br>';
  break;
  case 1:
    $QUERY="update authors set fotoday_1=fotoday_1+1 where id='$row[0]'";
  @mysql_query ($QUERY) or die ($QUERY. ": query failed");
echo $QUERY.'<br>';
  break;
  case 2:
    $QUERY="update authors set fotoday_2=fotoday_2+1 where id='$row[0]'";
  @mysql_query ($QUERY) or die ($QUERY. ": query failed");
echo $QUERY.'<br>';
  break;
  }
}
mysql_free_result ($result);
?>

