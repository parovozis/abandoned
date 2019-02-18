<?
$when = mysql_real_escape_string ($_REQUEST['APPROVE_ID']);
$query="SELECT competing,author_id FROM $PICTABLE WHERE id=$ID";
$result = execute_query ($query);
$row = mysql_fetch_array ($result, MYSQL_NUM);
mysql_free_result ($result);

if ($row[0] == 'Y') {
	$query="SELECT COUNT(*)<$DEFAULT_HOWMANY-".HOW_MANY_NEW." FROM $PICTABLE WHERE senton='$when' AND approved='Y' AND competing='Y' AND votedon=senton";
	$result = execute_query ($query);
	$available = mysql_fetch_array ($result, MYSQL_NUM);
	mysql_free_result ($result);
		
	$query="SELECT COUNT(*)<".HOW_MANY_MINE." FROM $PICTABLE WHERE senton='$when' AND approved='Y' AND competing='Y' AND votedon=senton AND author_id=$row[1]";
	$result = execute_query ($query);
	$mine_on_that_day = mysql_fetch_array ($result, MYSQL_NUM);
	mysql_free_result ($result);
		
	if ($available[0] && $mine_on_that_day[0]) {
		$approve = true;
	} else {
		$approve = false;
	}
} else {
	$approve = true;
}

if ($approve) {
	execute_query ("BEGIN");
	$query = "UPDATE $PICTABLE SET approved='Y',senton='$when',votedon='$when',approved_by=$author_id WHERE id=$ID";
	execute_query ($query);
	/* BACKUP */
	if (!empty($NEWTABLE)) {
		$query = "UPDATE $NEWTABLE SET approved='Y',senton='$when',votedon='$when',approved_by=$author_id WHERE id=$ID";
		execute_query ($query);
	}
	
	$query = "UPDATE pg_moderators SET approved=approved+1 WHERE pg_moderators_maintable='$PICTABLE' and pg_moderators_x_authors_id=$author_id";
	execute_query ($query);

	if (COMODERATION) {
		$query = "DELETE FROM {$PICTABLE}_request4deletion WHERE picture_id=$ID";
		execute_query ($query);
	}

	if ($author_id != $author) penalize ($author, APPROVE_PENALTY);
	execute_query ("COMMIT");
}
?>
