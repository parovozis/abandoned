<?
execute_query ('BEGIN');
$query = "SELECT email,name,description,competing,approved,approved_by FROM $PICTABLE,authors WHERE $PICTABLE.id=$ID AND author_id=authors.id";
$result = execute_query ($query);
$foto_info = mysql_fetch_array ($result, MYSQL_NUM);
mysql_free_result ($result);

if (empty($delete_anyway) 
    && COMODERATION 
    && (($author_id != $author)
    	&& (($foto_info[4] != 'A')
    		|| $foto_info[5]==$author_id))) { // Has this picture been 'deleted' before?
	if ($_COOKIE['COOKIE_AUTHOR_PASSWORD'] == MODERATOR)
		$deleter = 'NULL';
	else
		$deleter = $author_id;
	$query = "UPDATE $PICTABLE SET approved='A', approved_by=$deleter, senton=NOW(), votedon=NOW() WHERE id=$ID";
	execute_query ($query);
	/* BACKUP */
	if (!empty($NEWTABLE)) {
		$query = "UPDATE $NEWTABLE SET approved='A', approved_by=$deleter, senton=NOW(), votedon=NOW() WHERE id=$ID";
		execute_query ($query);
	}

	// Instructions to another moderator
	$my_reason = 0;
	for ($i = count ($REASON) - 1; $i >= 0; $i--) {
		$my_reason *= 2;
		if (isset ($_POST["REASON$i"])) $my_reason += 1;
	}
	if (!empty($_REQUEST['RMCOMMENT']))
		$comment = '"'.mysql_real_escape_string($_REQUEST['RMCOMMENT']).'"';
	else
		$comment = 'NULL';
	$query = "INSERT INTO {$PICTABLE}_request4deletion VALUES ($ID, $deleter, $my_reason, $comment)";
	execute_query ($query);

	execute_query ('COMMIT');
	if (!isset ($_REQUEST['FROM']) || empty($_REQUEST['FROM'])) $_REQUEST['FROM'] = 0;

	header ("Location: index.php?CATEG=".NEW_CAT."&FROM=".$_REQUEST['FROM']); /* Redirect browser */
	exit();
}

// Has been 'deleted' before, and by a different moderator!
if ($foto_info[3] == 'Y' && INIT_SEND_LIMIT != -1) {
	$query = "UPDATE $ASLTABLE SET authors_send_limit_value=LEAST(".INIT_SEND_LIMIT.",authors_send_limit_value+1) WHERE authors_send_limit_author=".$author;
	execute_query ($query);
}

$query = "DELETE FROM $PICTABLE WHERE id=$ID";
execute_query ($query);

/* BACKUP */
if (!empty($NEWTABLE)) {
	$query = "DELETE FROM $NEWTABLE WHERE id=$ID";
	execute_query ($query);
}

$query = "DELETE FROM $CATTABLE WHERE pg_cats_x_picture_id=$ID";
execute_query ($query);

$query = "DELETE FROM $FOTO_COMMENTS_TABLE WHERE foto_comments_picture=$ID";
execute_query ($query);

$query = "DELETE FROM $NOTIFYTABLE WHERE notify_picture=$ID";
execute_query ($query);

if ($author) {
	$query = "DELETE FROM pg_stars WHERE pg_stars_maintable='$PICTABLE' AND pg_stars_x_picture_id=$ID AND pg_stars_x_authors_id=$author";
	execute_query ($query);
}

if (isset ($_REQUEST['URL']) && $_REQUEST['URL']) {
	if (file_exists (PG_BASE.$_REQUEST['URL'].'.jpg')) 
		unlink (PG_BASE.$_REQUEST['URL'].".jpg");
	if (file_exists (IC_BASE.$_REQUEST['URL'].'-s.jpg')) 
		unlink (IC_BASE.$_REQUEST['URL']."-s.jpg");
	if (PG_LARGE_BASE != 'PG_LARGE_BASE' && file_exists (PG_LARGE_BASE.$_REQUEST['URL'].'.jpg')) 
		unlink (PG_LARGE_BASE.$_REQUEST['URL']."-s.jpg");
}

if (empty ($_REQUEST['FROM']))
	$_REQUEST['FROM'] = 0;

if ($MODERATOR_MODE == 2
    && $author
    && !isset ($_POST["REASON1"])) { /* by author's request!*/
	/* Notify the author */
	if (!$foto_info[5])
		$foto_info[5] = $author_id;
	$query = "SELECT name,email FROM authors WHERE id in ($foto_info[5], $author_id)";
	$result = execute_query ($query);
	$moderator1 = mysql_fetch_array ($result, MYSQL_NUM);
	$moderator2 = mysql_fetch_array ($result, MYSQL_NUM);
	mysql_free_result ($result);

	$my_reason = "\n\n";
	for ($i = 1; $i < count ($REASON); $i++) {
		if (isset ($_POST["REASON$i"]))
			$my_reason .= ($REASON[$i]."\n");
	}
	if ($my_reason == "\n\n")
		$my_reason .= ($REASON[0]."\n");
	$my_reason .= "\n";
	
	if ($_REQUEST['LNG']=='RU') {
		$subject = 'Фотография удалена';
		$body = "Уважаемый $foto_info[1]!\n\nЯ удалил(а) присланную Вами фотографию \"$foto_info[2]\" из фотогалереи. Причина или причины: $my_reason";
		if (!empty ($_POST['RMCOMMENT']))
			$body .= "\n\nКомментарий модератора:\n\n>>>>>\n\n{$_POST['RMCOMMENT']}\n\n<<<<<";
		$body .= "\n\nПожалуйста, ознакомьтесь с правилами галереи перед тем, как посылать фотографии: ".GHOST."faq.php. ПОЖАЛУЙСТА, не публикуйте это фото повторно как оно есть. Оно будет снова  удалено.\n\n-- \n"/*$moderator1[0] и $moderator2[0], */."Модераторы\n";

		mail ($foto_info[0], $subject, $body, smtp_headers (ADMIN/*$moderator1[1]*/));
	} else {
		$subject = 'Picture deleted';
		$body = "Dear $foto_info[1],\n\nI have removed your picture \"$foto_info[2]\" from the picture gallery for the following reason(s): $my_reason";
		if (!empty ($_POST['RMCOMMENT']))
			$body .= "\n\nModerator's Comments:\n\n>>>>>\n\n{$_POST['RMCOMMENT']}\n\n<<<<<";
		$body .= "\n\nPLEASE do not republish this picture \"as is.\" It will be removed again.\n\n--  \n"/*$moderator1[0] and $moderator2[0], */."Moderators\n";
	}

	mail ($foto_info[0], $subject, $body, smtp_headers (ADMIN/*$moderator1[1]*/));

	$query = "UPDATE pg_moderators SET deleted=deleted+1 WHERE pg_moderators_maintable='$PICTABLE' and pg_moderators_x_authors_id=$author_id";
	execute_query ($query);

	if (COMODERATION) {
		$query = "DELETE FROM {$PICTABLE}_request4deletion WHERE picture_id=$ID";
		execute_query ($query);
	}

	if ($author_id != $author) {
		penalize ($author, -DELETE_PENALTY, true);
	}
}

if (!empty ($WIKILINK_TABLE)) {
	$query = "DELETE FROM $WIKILINK_TABLE WHERE emb_pictures_id=$ID";
	execute_query ($query);
}
execute_query ('COMMIT');

header ("Location: index.php?CATEG=".NEW_CAT."&FROM=".$_REQUEST['FROM']); /* Redirect browser */
exit();
?>
