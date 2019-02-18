<?
require ("database.php");
$link = mysql_connect($HOST, $USER, $PASSWD) or die('Cannot connect');
mysql_select_db($DBASE) or die("Could not select database");
require ("functions.php");
require ("_pod.php");
echo 'Done!';
?>
