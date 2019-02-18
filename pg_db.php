<?php
require('database.php');
error_reporting(E_ERROR | E_PARSE);
$NOTAVAILABLE = 'Unfortunately, the Gallery database is currently overloaded. Please come back later!';

if (!$CONNECTED) {
  function gracefully_exit ($signal) {
    global $MYSQL_LINK;
    include ('pg_head.php');
    echo '<font color="red" size="+1"><b>'.$NOTAVAILABLE.'</b></font><br>';
    include ('pg_tail.php');
    mysql_close ($MYSQL_LINK);
    exit;
  }
  
  if (   !($MYSQL_LINK = mysql_connect($HOST, $USER, $PASSWD)) 
	 || !mysql_select_db($DBASE)) {
    gracefully_exit ();
  } else {
    $CONNECTED = 1;
  }
}

?>
