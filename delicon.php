<?
error_reporting(E_ERROR | E_PARSE);
require ("pg_db.php");
require ("functions.php");

if (!isset ($_GET['QUANTITY'])) {
	$quantity = 1;
} else {
	$quantity = $_GET['QUANTITY'];
}

for ($i = 0; $i < $quantity; $i++) {
	$query = "SELECT url FROM $PICTABLE WHERE ISNULL(icon_url) AND votes!=-10000 AND NOT (votedon!=senton) ORDER BY id LIMIT 1";
	$result = execute_query ($query);
	if (mysql_num_rows ($result) != 1)
		die ('No more icons???');

	$url = mysql_fetch_array ($result, MYSQL_NUM);	
	$query = "UPDATE $PICTABLE SET icon_url='NULL/noicon' WHERE url='$url[0]'";
	execute_query ($query);
	echo $query.'<br>';
	echo IC_BASE."$url[0]-s.jpg<br>";
	unlink (IC_BASE."$url[0]-s.jpg");
}

?>