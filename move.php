<?
require ("pg_db.php");
require ('functions.php');

if (!is_numeric($_GET['LAT'])||!is_numeric($_GET['LNG'])||!is_numeric($_GET['ID']))
	die('Bad data');

$query="SELECT HIGH_PRIORITY authors.id FROM $PICTABLE,authors WHERE $PICTABLE.id={$_GET['ID']} AND author_id=authors.id";
$result = execute_query ($query);
$self = mysql_fetch_array ($result, MYSQL_NUM);
mysql_free_result ($result);
$author_id = authorize ();
$dominus = ($MODERATOR_MODE == 2 || ($self && ($author_id == $self[0])));
if (!$dominus) die('Bad access');
$QUERY="UPDATE $PICTABLE set latitude={$_GET['LAT']},longitude={$_GET['LNG']} WHERE id={$_GET['ID']}";
mysql_query ($QUERY) or die ($QUERY. ": query failed");


if (!empty($NEWTABLE)) {
	$QUERY="UPDATE $NEWTABLE set latitude={$_GET['LAT']},longitude={$_GET['LNG']} WHERE id={$_GET['ID']}";
	mysql_query ($QUERY) or die ($QUERY. ": query failed");
}
?>

