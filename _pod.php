<?
require ('time.php');
$dayofweek = date ('w', $current_time);
$easyLimit = 27;
$easyStep = 3;

$votes = 100000; /* Large number */
$pfx = array ('', '_1', '_2');

if (!empty ($NEWTABLE))
	$maintable = $NEWTABLE;
else
	$maintable = $PICTABLE;
	
function update_limit ($add_extra) 
{
	define ('LOW_WATER', 4);
	global $ASLTABLE, $maintable, $DEFAULT_HOWMANY, $tomorrow;
	$query="SELECT HIGH_PRIORITY COUNT(*) FROM $maintable WHERE competing='Y' AND approved!='A' AND votedon>='$tomorrow'";
	$result = execute_query ($query);
	$row = mysql_fetch_array ($result, MYSQL_NUM);
	mysql_free_result ($result);

	if ($add_extra /* Sunday! */
		|| ($row[0] < ($DEFAULT_HOWMANY - HOW_MANY_NEW) * LOW_WATER)) { /* min supply*/
		if (INIT_SEND_LIMIT != -1) {
			$query = "UPDATE $ASLTABLE SET authors_send_limit_value=LEAST(".INIT_SEND_LIMIT.", authors_send_limit_value+1)";
			execute_query ($query);
		}
	}
}

execute_query ('BEGIN');

if (!isset ($NOVOTE) || !$NOVOTE) {
	if (!empty ($NEWTABLE)) {
		$query = "UPDATE $NEWTABLE,$PICTABLE set $NEWTABLE.votes=$PICTABLE.votes WHERE $NEWTABLE.id=$PICTABLE.id AND $NEWTABLE.votedon='$yesterday'";
		execute_query ($query);
	}

	// Add the average vote to all pictures whose authors voted today
	// ('cuz the authors could not vote for their pictures)
	/*$query = "SELECT ROUND(AVG(score)) FROM $VOTETABLE WHERE votedate='$yesterday' AND score>=0";
	$result = execute_query ($query);
	$avg_score = mysql_fetch_array ($result, MYSQL_NUM);
	mysql_free_result ($result);

	$query = "UPDATE $PICTABLE SET votes=votes+$avg_score[0] WHERE votedon='$yesterday' AND competing='Y' AND author_id IN (SELECT DISTINCT uid FROM $VOTETABLE) AND approved='Y'";
	execute_query ($query);
	if (!empty ($NEWTABLE)) {
		$query = "UPDATE $NEWTABLE SET votes=votes+$avg_score[0] WHERE votedon='$yesterday' AND competing='Y' AND author_id IN (SELECT DISTINCT uid FROM $VOTETABLE) AND approved='Y'";
		execute_query ($query);
	}*/
	
	for ($i = 0; $i<3; $i++) {
		$query="SELECT MAX(votes) FROM $maintable,authors WHERE votedon='$yesterday' AND votes<$votes AND author_id=authors.id";
		$result = execute_query ($query);
		$row = mysql_fetch_array ($result, MYSQL_NUM);
		mysql_free_result ($result);
		if ($row[0] < $easyLimit - $i * $easyStep) /* prune 'easy winners' */
			continue;
		$votes = $row[0];

		if ($votes) {
			$query="UPDATE $PICTABLE SET award=$i WHERE votes=$votes AND votedon='$yesterday'";
			execute_query ($query);
			if (!empty ($NEWTABLE)) {
				$query="UPDATE $NEWTABLE SET award=$i WHERE votes=$votes AND votedon='$yesterday'";
				execute_query ($query);
			}

			$query="SELECT author_id FROM $PICTABLE WHERE votes=$votes AND collection='N' AND votedon='$yesterday'";
			$result = execute_query ($query); 
			while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
				$other_author_id = $row[0];
				$q_picture = "SELECT id FROM $maintable WHERE author_id=$other_author_id AND votes=$votes AND collection='N' AND votedon='$yesterday'";
				$r_picture = execute_query ($q_picture);
				while ($row = mysql_fetch_array ($r_picture, MYSQL_NUM)) {
					$picture_id = $row[0];
					$q_insert = "INSERT INTO pg_stars VALUES ('$PICTABLE',$picture_id,$other_author_id,$i)";
					if (!@mysql_query ($q_insert)) {
						if (mysql_errno()!=1062) 
							die ("$q_insert: Query failed");
					}
				}
				mysql_free_result ($r_picture);
			}
			mysql_free_result ($result);
		}
	}
}

// Update reputation
$query="UPDATE {$PICTABLE}_reputation SET reputation=reputation+1 WHERE reputation<0";
execute_query ($query);

$query="DELETE FROM $VOTETABLE WHERE votedate<'$yesterday'-INTERVAL 7 DAY";
//execute_query ($query);
$query="UPDATE $PICTABLE SET votedon=senton WHERE votedon<'$today'";
execute_query ($query);
if (!empty ($NEWTABLE)) {
	$query="UPDATE $NEWTABLE SET votedon=senton WHERE votedon<'$today'";
	execute_query ($query);

	// Clean up the cache
	$query="DELETE FROM $NEWTABLE WHERE approved='Y' AND senton <= CURDATE() - INTERVAL " . NEW_THRESHOLD;
	execute_query ($query);
}

// Old fotos
$query="SELECT COUNT(*) FROM $maintable WHERE votedon='$today' AND approved='Y' AND competing='Y'";
$result = execute_query ($query);
$row = mysql_fetch_array ($result, MYSQL_NUM);
mysql_free_result ($result);
$howmany = max (0, $DEFAULT_HOWMANY - $row[0]);
if ($howmany) {
	$query="INSERT INTO $maintable (SELECT * FROM $PICTABLE WHERE votes=-10000 AND competing='Y' ORDER BY id LIMIT $howmany)";
	execute_query ($query);
	if (!empty ($NEWTABLE)) {
		$query = "UPDATE $PICTABLE,$NEWTABLE SET $NEWTABLE.votes=0, $NEWTABLE.votedon='$today', $PICTABLE.votes=0, $PICTABLE.votedon='$today' WHERE $PICTABLE.id=$NEWTABLE.id AND $NEWTABLE.votes=-10000 AND $NEWTABLE.competing='Y'";
	} else {
		$query = "UPDATE $PICTABLE SET votedon='$today', votes=0 WHERE votes=-10000 AND competing='Y'";
	}
	execute_query ($query);
}
execute_query ('COMMIT');

update_limit ($dayofweek == 0);

/* Tweet! */
require_once ("tweet.php");
$query="SELECT description,id FROM $maintable WHERE votedon='$today' AND approved='Y'";
$result = execute_query ($query);
while ($row = mysql_fetch_array ($result, MYSQL_NUM)) tweet (stripslashes ($row[0]), GHOST."?ID=$row[1]");
mysql_free_result ($result);
?>
