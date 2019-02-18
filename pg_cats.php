<?
if (isset ($_GET['CAT']) and is_numeric ($_GET['CAT'])) {
  require ("pg_db.php");
  require ('functions.php');
  require ($_REQUEST['LNG'].'/pg_cats.php');
  require ('setup.php');

  $cat = $_GET['CAT'];
  $query = 'SELECT supercat,title,virtual FROM '.$BASECATTABLE.' WHERE name='.$cat;
  $result = execute_query ($query);
  $super = mysql_fetch_array ($result, MYSQL_NUM);
  mysql_free_result ($result);

  if ($super) {
  	$query = 'SELECT title FROM '.$BASECATTABLE.' WHERE name='.$super[0];
  	$result = execute_query ($query);
  	$row = mysql_fetch_array ($result, MYSQL_NUM);
  	mysql_free_result ($result);

  	echo "$super[0]:$UP_TO $row[0].....:1\n";
  	echo "$cat:=> $super[1].....:$super[2]\n";

  	$query = 'SELECT name,title,virtual FROM '.$BASECATTABLE.' WHERE supercat='.$_GET['CAT'].' AND supercat!=name ORDER BY virtual DESC,title';
  	$result = execute_query ($query);
  	while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
		if ($row[2])
  	  		echo "$row[0]:Down to $row[1].....:$row[2]\n";
		else
			echo "$row[0]:$row[1]:$row[2]\n";
  	}
  	mysql_free_result ($result);
  }
}
?>