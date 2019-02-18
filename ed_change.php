<?
$new_author = false;
if (is_numeric ($_POST['YEAR'])
		   && is_numeric ($_POST['MONTH'])
		   && is_numeric ($_POST['DATE'])) {
		$baddate = 'NULL';
		$takenon = "'".$_POST['YEAR']."-".$_POST['MONTH']."-".$_POST['DATE']."'";
} else {
			   $baddate = "'".mysql_real_escape_string ($_POST['YEAR'])."'";
			   $takenon = 'NULL';
}

$latitude = $_REQUEST['LAT'];
$longitude = $_REQUEST['LONG'];
if (is_numeric ($latitude) && is_numeric ($longitude) 
	&& $latitude>=-90 && $latitude<=90
	&& $longitude>=-180 && $longitude<=180) {
	$coords = ",latitude=$latitude,longitude=$longitude";
} else {
	$coords = ',latitude=NULL,longitude=NULL';
}

  $collection = isset ($_POST['NOTCOLLECTION']) ? 'N' : 'Y';
  $no_comments = isset ($_POST['NO_COMMENTS']) ? 'Y' : 'N';
  $url = trim (mysql_real_escape_string ($_REQUEST['URL']));
  $description = trim (mysql_real_escape_string ($_REQUEST['DESCR1']));
  $region = trim (mysql_real_escape_string ($_REQUEST['REG']));

  if ($MODERATOR_MODE == 2 && isset ($_POST['NOTIFY'])) {
	  /* Notify the author */
	  $query = "SELECT description,email,name FROM $PICTABLE,authors WHERE $PICTABLE.id=$ID AND author_id=authors.id";
	  $result = execute_query ($query);
	  $old_description = mysql_fetch_array ($result, MYSQL_NUM);
	  mysql_free_result ($result);
	  
	  if ($old_description[0] != $description) {
		  $query = "SELECT name,email FROM authors WHERE login='".$_COOKIE['COOKIE_AUTHOR']."'";
		  $result = execute_query ($query);
		  $moderator = mysql_fetch_array ($result, MYSQL_NUM);
		  mysql_free_result ($result);
		  mail ($old_description[1],
			"Описание фотографии изменено",
   "Уважаемый $old_description[2]!\n\nЯ изменил(а) описание, регион или категорию присланной Вами фотографии как несоответствующее правилам оформления: ".GHOST."faq.php. Старое описание:\n\n<<<$old_description[0]>>>\n\nНовое описание:\n\n<<<{$_REQUEST['DESCR1']}>>>\r\nПожалуйста, сравните описания и постарайтесь в будущем оформлять фотографии по правилам. Возможно, Вам стоит обратить внимание на правописание и пунктуацию русского языка.\n\n-- \n$moderator[0], Модератор\n",
   smtp_headers ($moderator[1]));
}
}
  
execute_query ("BEGIN");
$query = "UPDATE $PICTABLE SET url='$url', description='$description', region='$region', takenon=$takenon, baddate=$baddate,no_comments='$no_comments',collection='$collection'";
$query1 = "UPDATE $NEWTABLE SET url='$url', description='$description', region='$region', takenon=$takenon, baddate=$baddate,no_comments='$no_comments',collection='$collection'";
if ($MODERATOR_MODE == 2 && is_numeric ($_REQUEST['AUTHOR']) && $author != $_REQUEST['AUTHOR']) {
	$author = $_REQUEST['AUTHOR'];
	$query .= ",author_id=$author";
	$query1 .= ",author_id=$author";
	$new_author = true;
}
$query .= $coords." WHERE id=$ID";
execute_query ($query);
$query1 .= $coords." WHERE id=$ID";
if(!empty($NEWTABLE))
	execute_query ($query1);

$QUERY = "DELETE FROM $CATTABLE WHERE pg_cats_x_picture_id=$ID";
execute_query ($QUERY);
$ncats = $_REQUEST['CAT_COUNT'];
for ($i = 0; $i < $ncats; $i++) {
	$catname = "CATEG$i";
	$category = $_REQUEST[$catname];
	if ($category && $category != 'NULL') {
		$category = mysql_real_escape_string ($category);
		$QUERY = "INSERT INTO $CATTABLE VALUES ($ID, $category)";
		if (!@mysql_query ($QUERY) && mysql_errno()!=1062) 
			die ("$QUERY: Query failed");
	}
}

if ($MODERATOR_MODE == 2) {
	$query = "UPDATE pg_moderators SET changed=changed+1 WHERE pg_moderators_maintable='$PICTABLE' and pg_moderators_x_authors_id=$author_id";
	execute_query ($query);
	if (!$new_author && $author_id != $author) {
		penalize ($author, -EDIT_PENALTY);
		//execute_query ("UPDATE authors SET reputation=reputation-".EDIT_PENALTY." WHERE id=$author");
	}
}

execute_query ("COMMIT");
?>
