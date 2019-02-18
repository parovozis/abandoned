<?
function webalize ($text)
{
  return trim (mysql_real_escape_string (str_replace ('"', '&quot;', $text)), " \n\t\r\0\x0B.,!;:");
}

function followup () {
  global $WARNING_COLOR, $WILL_LOOK_LIKE_THIS, $NO_TRAMS_PLEASE, $WILL_BE_PUBLISHED, $PICTABLE, $DEFAULT_HOWMANY, $url;

  if (IC_REAL_BASE != "IC_REAL_BASE") {
	$icon = IC_BASE."$url-s.jpg";
  	$real_icon = IC_REAL_BASE."$url-s.jpg";
  } else {
	$icon = IC_BASE."$url-s.jpg";
	$real_icon = $icon;
  }
  if (file_exists ($real_icon)) {
    echo "<img src='$icon' border='0'>\n";
    echo "<br clear='all'><font color='$WARNING_COLOR'>$WILL_LOOK_LIKE_THIS</font>\n";
  
    $query = "select date_add(curdate(),interval (greatest(1,count(*)/$DEFAULT_HOWMANY)) day) from $PICTABLE where senton>now() and competing='Y'";
    $result = execute_query ($query);
    $row = mysql_fetch_array ($result, MYSQL_NUM);
    $lag = mydate ($row[0]);
    mysql_free_result ($result);
    
    echo "<p>$WILL_BE_PUBLISHED: $lag.<p>";
  } else {
    echo "<img src='".IC_BASE."/stop.gif' border='0'>\n";
    echo "<br clear='all'>\n";
  }
}
$competing = isset ($_POST['NO_COMPETING']) ? 'N' : 'Y';
$query = "select authors_send_limit_value from $ASLTABLE where authors_send_limit_author=$author_id";
$result = execute_query ($query);
$sendlimit = mysql_fetch_array ($result, MYSQL_NUM);  
mysql_free_result ($result);

if ($NO_NEW_PICTURES 
|| ($sendlimit && $sendlimit[0] <= 0 && INIT_SEND_LIMIT != -1 && $competing == 'Y')) {
  return;
}

if (   is_numeric ($_POST['YEAR'])
    && is_numeric ($_POST['MONTH'])
    && is_numeric ($_POST['DATE'])) {
  $baddate = 'NULL';
  $takenon = "'".$_POST['YEAR']."-".$_POST['MONTH']."-".$_POST['DATE']."'";
} else {
    $baddate = "'".mysql_real_escape_string ($_POST['YEAR'])."'";
    $takenon = 'NULL';
  }

$post_when = POST_IMMEDIATELY == 'Y' ? $today : $tomorrow;
$approved_by = 'NULL';
$approved = 'N';
if ($competing == 'N') { // Самомодерация!
	$result = execute_query ("select {$PICTABLE}_reputation.reputation from {$PICTABLE}_reputation where author=$author_id");
	$reputation = mysql_fetch_array ($result, MYSQL_NUM);
	mysql_free_result ($result);
	if ($reputation && $reputation[0] >= SELF_APPROVE_THRESHOLD) {
		$approved = 'Y';
		$approved_by = $author_id;
	}
} else {
	$approved = POST_IMMEDIATELY;
}

$latitude = $_POST['LAT'];
$longitude = $_POST['LONG'];
if (is_numeric ($latitude) && is_numeric ($longitude)
	&& $latitude>=-90 && $latitude<=90
	&& $longitude>=-180 && $longitude<=180) {
  $coords = ",latitude=$latitude,longitude=$longitude";
} else {
  $coords = ',latitude=NULL,longitude=NULL';
}

$collection = $_POST['NOT_MINE'] ? 'Y' : 'N';
$no_comments = /*isset ($_REQUEST['NO_COMMENTS']) ?*/ 'Y'/* : 'N'*/;

$caption = webalize (substr ($_POST['DESCR1'], 0, 2 * 500));
$url = mysql_real_escape_string ($_POST['URL']);
$region = mysql_real_escape_string ($_POST['REG']);
execute_query ('BEGIN');
$QUERY = "INSERT INTO $PICTABLE SET url='$url', description='$caption', region='$region', senton='$post_when', takenon=$takenon, baddate=$baddate, approved='$approved', approved_by=$approved_by, author_id=$author_id, votedon='$post_when', award=-10000, competing='$competing', no_comments='$no_comments', collection='$collection' $coords";
execute_query ($QUERY);
$id = mysql_insert_id ();

/* BACKUP */
if ($NEWTABLE) {
	$QUERY = "INSERT INTO $NEWTABLE (SELECT * FROM $PICTABLE WHERE id=$id)";
	execute_query ($QUERY);
}

/* now, take care of the picture */
$url = date ('Ymd_'.$id);

if (move_uploaded_file ($_FILES['IMAGE']['tmp_name'], SCRATCHPATH."$url.JPG")) {
  	switch ($_POST['LOGOPOS']) {
  	case 0: $x = 5; $y = 5; break;
  	case 1: $x = -140; $y = 5; break;
  	case 3: $x = -140; $y = -37; break;
  	case 2: 
  	default:			// bottom left
    	$x = 5; $y = -37; break; 
	}
  $filter = isset ($_POST['DONOTFILTER']) ? '' : '-nofilt';

  $destination = '';
  if (PREFIX_WITH_REG_NAME == true) {
	  $destination = $region.'/';
  } else if (PREFIX_WITH_CAT0 == true) {
	  $destination = $_POST['CATEG0'].'/';
  }
  $out = system ("./prepare$filter $url $x $y $destination", $retval);

  if (!$out && $retval) {
    echo '<li>'.mandatory ($NOTUPLOADED);
    $query = "DELETE FROM $PICTABLE WHERE id=$id";
    execute_query ($query);
    if (!empty($NEWTABLE)) {
	    $query = "DELETE FROM $NEWTABLE WHERE id=$id";
	    execute_query ($query);
    }
  } else {
	  $url = $destination.$url;
    $query = "UPDATE $PICTABLE SET url='$url' WHERE id=$id";
    execute_query ($query);
    if (!empty($NEWTABLE)) {
	    $query = "UPDATE $NEWTABLE SET url='$url' WHERE id=$id";
	    execute_query ($query);
    }
 
    for ($i = 0; $i <= $_POST['CAT_COUNT']; $i++) {
	    $catname = "CATEG$i";
	    $category = $_POST[$catname];
	    if ($category && is_numeric ($category)) {
		    $QUERY = "INSERT INTO $CATTABLE VALUES ($id, $category)";
		    if (!@mysql_query ($QUERY) && mysql_errno() != 1062)
			    die ("$QUERY [catname=$catname]: Query failed");
	    }
    }

    if ($competing == 'Y' && INIT_SEND_LIMIT != -1) {
      $query = "UPDATE $ASLTABLE SET authors_send_limit_value=authors_send_limit_value-1 WHERE authors_send_limit_author=$author_id";
      execute_query ($query);
    }

    /* EXIF */
    exec (JHEAD.PG_REAL_BASE."$url.jpg", $output);
    $exif_data = addslashes (implode ("\n", $output));
    $query="INSERT INTO $EXIFTABLE VALUES ($id, '$exif_data')";
    execute_query ($query);

    foreach ($output as $attribute) {
      if (substr ($attribute, 0, 9) == "Date/Time") {
	$dateline = split (':', $attribute);
        $date = split ('[- ]+', $dateline[1]);

	if (   $_POST['YEAR'] != $date[1] 
	       || $_POST['MONTH'] != $date[2]
	       || $_POST['DATE'] != $date[3]) {
	  $alert = sprintf ("<center>".EXIF_MISMATCH."<p><form method=post onSubmit='setTimeout(\\\"window.close\\\",5000)'><input type=hidden name=ID value=$id><input type=hidden name=FIXDATE><input type=hidden name=YEAR value=$date[1]><input type=hidden name=MONTH value=$date[2]><input type=hidden name=DATE value=$date[3]><input type=submit value='".FIXIT."'></form></center>", mydate ("$date[1]-$date[2]-$date[3]"), mydate ($_POST['YEAR'].'-'.$_POST['MONTH'].'-'.$_POST['DATE']));
	  echo "<Script Language=\"JavaScript\">\n";
	  echo "<!-- \n";
	  echo 'myWindow = window.open("", "'.ALERT.'", \'width=150,height=150\');'."\n"; 
	  echo 'myWindow.document.write("'.$alert.'");'."\n";
	  echo "myWindow.document.close();\n";
	  echo "// -->\n";
	  echo "</Script>\n";
	}
      }
    }
  }

  // EMB stuff
  if (isset ($WIKILINK_TABLE) 
      && isset ($_REQUEST['LABEL1'])
      && is_numeric ($_REQUEST['LABEL1'])
      && isset ($_REQUEST['ARTICLE'])
      && is_numeric ($_REQUEST['ARTICLE'])) {
    $query = "INSERT INTO $WIKILINK_TABLE VALUES (".$_REQUEST['ARTICLE'].",".$_REQUEST['LABEL1'].",$id,'N')";
    if (!@mysql_query ($query) && mysql_errno()!=1062) {
      die ("$query: Query failed");
    }
  }
  execute_query ('COMMIT');
} else {
// ?
}

/* now, take care of the comment */
if ($_POST['COMMENT']) {
  $comment = mysql_real_escape_string ($_POST['COMMENT']);
  
  $query = "SELECT name FROM authors WHERE id=$author_id";
  $result = execute_query ($query);
  $author_name = mysql_fetch_array ($result, MYSQL_NUM);  
  $author_name = $author_name[0];  
  mysql_free_result ($result);
  
  execute_query ('BEGIN');
  $query = "INSERT INTO $FOTO_COMMENTS_TABLE (foto_comments_picture, foto_comments_text,foto_comments_author_id,foto_comments_author,foto_comments_ip,foto_comments_date) VALUES ($id, '$comment', $author_id, '$author_name','${_SERVER['REMOTE_ADDR']}',NOW())";
  execute_query ($query);
  $query = "UPDATE $PICTABLE SET n_comments=n_comments+1 WHERE id=$id";
  execute_query ($query);
  if (!empty($NEWTABLE)) {
	  $query = "UPDATE $NEWTABLE SET n_comments=n_comments+1 WHERE id=$id";
	  execute_query ($query);
  }
  execute_query ('COMMIT');
}

// Does the author care about comments?
if (isset($_POST['MAIL_COMMENTS'])) {
	$query="INSERT INTO $NOTIFYTABLE VALUES ($author_id,$id)";
	execute_query ($query);
}

?>
