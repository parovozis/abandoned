<?
$comment = mysql_real_escape_string ($_POST['COMMENT']);
$query = "SELECT no_comments FROM $my_newtable WHERE id=$ID";
$result = execute_query ($query);
$row = mysql_fetch_array ($result, MYSQL_NUM);
mysql_free_result ($result);

if (($myid && is_numeric($myid) && (warnings ($myid) < WARNING_COMMENT_THRESHOLD)) /*|| $row[0] == 'N'*/) {
	$remote_ip = $_SERVER['REMOTE_ADDR'];
	$query = "SELECT banned_ips_count FROM banned_ips WHERE banned_ips_ip='$remote_ip'";
	$result = execute_query ($query);
	$row = mysql_fetch_array ($result, MYSQL_NUM);
	mysql_free_result ($result);

	if (!$row || $row[0] < TROLL_THRESHOLD) {
		define ('TROLL_TIMEOUT',180);
		$query = "SELECT COUNT(*) FROM $FOTO_COMMENTS_TABLE WHERE foto_comments_ip='$remote_ip' AND foto_comments_picture=$ID AND NOW()-foto_comments_date < ".TROLL_TIMEOUT;
		$result = execute_query ($query);
		$row = mysql_fetch_array ($result, MYSQL_NUM);
		mysql_free_result ($result);
		if (!$row[0]) {		/* NOT A TROLL */
			if (!is_numeric($myid)) {
				$query = "SELECT COUNT(*) FROM authors WHERE name='$AUTHOR_NAME' or login='$AUTHOR_NAME'";
				$result = execute_query ($query);
				$row = mysql_fetch_array ($result, MYSQL_NUM);
				mysql_free_result ($result);
				if ($row[0]) {
					require ('pg_head.php');
					printf (GET_AUTHORIZED, $AUTHOR_NAME, $AUTHOR_NAME);
					require ('pg_tail.php');
					exit;
				}
			}
			if (ENABLE_TRANSLIT && isset ($_POST['TRANSLIT'])) {
				$comment = "\{".FROM_TRANSLIT.'} '.str_replace ($TRANSLIT_ALPHA,
						$UTF8_ALPHA,
						 $comment);
			}

			$query = "SELECT COUNT(*) FROM emb_taboo WHERE '$comment' LIKE CONCAT('%',word,'%')";
			$result = execute_query ($query);
			$row = mysql_fetch_array ($result, MYSQL_NUM);
			mysql_free_result ($result);
		
			if ($row[0]>0) {
				header ('Location: http://go.fuck.urself');
				exit ();
			}
			
			$comment = mb_substr ($comment, 0, 512, "UTF-8");

			$to_moderator = isset ($_POST['TO_MODERATOR']) ? 1 : 0;
			$query = "INSERT INTO $FOTO_COMMENTS_TABLE (foto_comments_picture, foto_comments_text,foto_comments_author_id,foto_comments_author, foto_comments_ip,foto_comments_moder,foto_comments_date) VALUES ($ID, '$comment', $myid, '$AUTHOR_NAME','$remote_ip',$to_moderator,NOW())";
			execute_query ($query);
			$query = "UPDATE LOW_PRIORITY $PICTABLE SET n_comments=n_comments+1 WHERE id=$ID";
			execute_query ($query);
			if (!empty($NEWTABLE)) {
				$query = "UPDATE LOW_PRIORITY $my_newtable SET n_comments=n_comments+1 WHERE id=$ID";
				execute_query ($query);
			}

			/* Notify all interested parties */
			$maillist = '';
			$query = "SELECT name,email FROM authors,$NOTIFYTABLE WHERE authors.id=notify_author AND notify_picture=$ID";
			if (is_numeric($myid))
				$query .= " AND authors.id!=$myid";
			$result = execute_query ($query);
			while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
				if ($maillist)
					$maillist .= ',';
				$maillist .= "$row[0] <$row[1]>";
			}
			mysql_free_result ($result);
			if ($maillist) {
				$query = "SELECT description FROM $my_newtable WHERE id=$ID";
				$result = execute_query ($query);
				$row = mysql_fetch_array ($result, MYSQL_NUM);
				mysql_free_result ($result);

				mail ($maillist, '['.NEW_COMMENT.'] '.stripslashes($row[0]),
				      sprintf (NEW_COMMENT_FMT, stripslashes($row[0]), $ID, $_REQUEST['LNG'], $AUTHOR_NAME, stripslashes($comment), $ID),
					       smtp_headers (ADMIN));
			} /* End notify */

#			require_once ("tweet.php");
#			tweet (stripslashes ($comment), GHOST."?ID=$ID");
			header("Location: $PHP_SELF?ID=$ID");
			exit;
		} else {
			require ('pg_head.php');
			echo TOO_OFTEN;
			require ('pg_tail.php');
			exit;
		}
	} else {
		require ('pg_head.php');
		printf (YOU_ARE_BLOCKED, $remote_ip);
		require ('pg_tail.php');
		exit;
	}
}
?>
