<?php
require ('pg_db.php');
require ('functions.php');
require ('translit.php');

function tograd ($angle) {
	$sign = $angle < 0 ? '-' : '';
	$angle = abs($angle);
	$grad = (int)$angle;
	$minute = (int)(($angle-$grad)*60);
	$secund = (int)(($angle-$grad-$minute/60)*3600);
	return $sign.$grad."°".$minute."'".$secund.'"';
}

if (!empty($NEWTABLE)) $my_newtable=$NEWTABLE; else $my_newtable=$PICTABLE;

$AROUND_CNT = 40;
$AROUND_DST = (isset($_GET['AROUND_DST']) && is_numeric($_GET['AROUND_DST'])) 
     ? $GET['AROUND_DST'] : 60;
require ($_REQUEST['LNG'].'/pg_view.php');

$ID = is_numeric ($_REQUEST['ID']) ? $_REQUEST['ID'] : -1;
#echo $ID;
$PAGE = $VIEW_PICTURE;
$SELECTED = $ID ? 2 : 3;

$myid = authorize ();

if (isset ($_GET['SHOW_EXIF'])) { 
  if ($_GET['SHOW_EXIF'] == 0) {
    setcookie ('SHOW_EXIF',FALSE,time()-300);
    unset ($_REQUEST['SHOW_EXIF']);
  } else {
    setcookie ('SHOW_EXIF','1',time()+ONE_HOUR*ONE_DAY*365);
  }
} 

$query = "SELECT id FROM $my_newtable WHERE id=$ID";
$result = execute_query ($query);
$size = mysql_num_rows ($result);
mysql_free_result ($result);
if (!$size) $my_newtable = $PICTABLE;

/* VISITOR'S COMMENTS */
if (   COMMENTS_ALLOWED
    && !empty ($_POST['COMMENT'])
    && ($_POST['COMMENT_ID'] == $_POST['ID'])
    && !strstr ($comment,'[url')) {
	require ('pg_addcomment.php');
}

require ('pg_head.php');
require ("vw_showcomments.php");

?>


<SCRIPT LANGUAGE='JavaScript'>
<!--
function textCounter(field,maxlimit) {
if (field.value.length > maxlimit) field.value = field.value.substring(0, maxlimit);
} 
-->
</script>


<?
if ($ID == 0) {
  if ($_REQUEST['PURIST'] || !VOTE_ALLOWED) {
    $query="SELECT HIGH_PRIORITY id FROM $my_newtable WHERE votedon='$today' ORDER BY RAND() LIMIT 1";
  } else {
	  // !!!!!!
    $query="SELECT HIGH_PRIORITY MAX(votes) FROM $my_newtable WHERE votedon='$today' AND approved='Y'";  
    $result = execute_query ($query);
    $row = mysql_fetch_array ($result, MYSQL_NUM);
    if ($row && $row[0]) {
      $top_score = $row[0];
    } else {
      $top_score = 0;
    }
    mysql_free_result ($result);
    $query="SELECT HIGH_PRIORITY id FROM $my_newtable WHERE votedon='$today' AND votes=$top_score  AND approved='Y' ORDER BY viewed DESC LIMIT 1";
  }
  $result = execute_query ($query);
  $row = mysql_fetch_array ($result, MYSQL_NUM);
  mysql_free_result ($result);
  if ($row[0]) {
    $ID = $row[0];
  } else
    $ID = 1;
  $TITLE = $FOTODNYA2;
} 

if ($myid !='NULL') {
	if (!empty ($_GET['SUBSCRIBE'])) {
		if ($_GET['SUBSCRIBE']=='cancel') {
			$query="DELETE FROM $NOTIFYTABLE WHERE notify_author=$myid AND notify_picture=$ID";
		} elseif ($_GET['SUBSCRIBE']=='add') {
			$query="INSERT IGNORE INTO $NOTIFYTABLE VALUES ($myid,$ID)";
		} else {
			$query = 'SELECT 1';
		}
		execute_query ($query);
	}
	$query = "SELECT COUNT(*) FROM $NOTIFYTABLE WHERE notify_author=$myid AND notify_picture=$ID";
	$result = execute_query ($query);
	$watched = mysql_fetch_array ($result, MYSQL_NUM);
	mysql_free_result ($result);
}
  
if ($MODERATOR_MODE == 2) {
  $ID = abs ($ID);
  $approved = "";
} else {
  $approved = " AND approved='Y'  and (isnull(votedon) or votedon<'$tomorrow')";
  $query="UPDATE LOW_PRIORITY $my_newtable SET viewed=viewed+1 WHERE id=$ID AND author_id!='$myid'";
  execute_query ($query);
}

$query="SELECT HIGH_PRIORITY url, description, 1, ".PREFIX."regname, authors.name, takenon, senton, baddate, $my_newtable.id, approved, votes, award, latitude, longitude, authors.id, votedon, region, collection, no_comments,region FROM $my_newtable,$REGIONTABLE,authors WHERE region=regid $approved AND $my_newtable.id=$ID and author_id=authors.id";
$result = execute_query ($query);
$self = mysql_fetch_array ($result, MYSQL_NUM);
mysql_free_result ($result);

if (!$self) {
  echo "<center><p><b>$nosuch.</b></center><p>\n";
  include ('pg_tail.php');
  exit;
}

echo "<h3>$self[1]</h3>";
if (file_exists('reklama.php')) {
	echo '<center>';
	include ('reklama.php');
	echo '</center>';
}

echo '<a href=#comments>'.COMMENTS.'</a>...';

/* Сама фотография */
echo "<p><center><a name=picture><table border='0' bgcolor='$LOGO_COLOR' cellpadding='1' cellspacing=0><tr><td>";
echo "<table border='0' bgcolor='white' cellpadding='10' cellspacing=0><tr><td align='center' valign='middle'>";

$lat = $self[12];
$long = $self[13];

$dominus = ($MODERATOR_MODE == 2 || $myid == $self[14]);

$neighbors = array ();
$x = array ();
$y = array ();
$ids = array ();

if ($lat != 0 && $_REQUEST['AROUND']) {
  $gQuery = "select HIGH_PRIORITY url,id,111.32*sqrt(pow(latitude-($lat),2)+pow(cos((latitude+($lat))/114.6)*(longitude-($long)),2)) as dist,description,latitude,longitude from $PICTABLE where not isnull(latitude) and latitude!=0 and id!=$ID".$approved.' and 111.32*sqrt(pow(latitude-('.$lat.'),2)+pow(cos((latitude+('.$lat.'))/114.6)*(longitude-('.$long.')),2))<='.$AROUND_DST.' order by dist asc,takenon asc limit '.$AROUND_CNT;
  $gResult = execute_query ($gQuery);

  while ($row = mysql_fetch_array ($gResult, MYSQL_NUM)) {
    $neighbors[] = $row;
    $x[] = $row[4];
    $y[] = $row[5];
    $ids[] = $row[1];
  }
  mysql_free_result ($gResult);
}

if (isset($_GET['GMAPS'])) {
  $x = implode (",", $x);
  $y = implode (",", $y);
  $ids = implode (",", $ids);
  require ('pg_gmap.php');
} else {
  if (PG_LARGE_BASE!='PG_LARGE_BASE') {
    echo "<a href='".PG_LARGE_BASE."$self[0].jpg'>";
  } else {
    echo "<a href='".PG_BASE."$self[0].jpg'>";
  }
  echo "<img src='".PG_BASE."$self[0].jpg' border='1'></a>";
}
echo '</table></table><p>';

/* Voters */
$its_pod = ($self[15] == $today);

/* Информация о фотографии */
echo '<table border="0" width="98%"><tr><td valign=top with=90%>';
echo "<font size=+1><b>$self[1]</b></font>, <i><a href='pg_blog.php?REGION=$self[19]&&LNG=".$_REQUEST['LNG']."'>$self[3]</a></i>";

$permanent = GHOST."?ID=$ID&LNG=".$_REQUEST['LNG'];
if (!empty($self[5])) {
	$d = mydate ($self[5]);
} else {
	$d = $self[7];
}
if ($self[17]=='Y') {
	$self[4] .= ' (<i>'.COLLECTION.'</i>)';
}
echo "<br>\n$_author: <a href='index.php?AUTHOR=$self[14]&SHOW_ALL=1&LNG=".$_REQUEST['LNG']."'>$self[4]</a>&nbsp;|\n";
echo TAKEN.$d.', '.PUBLISHED.' '.mydate ($self[6]).".\n";

if ($MODERATOR_MODE == 2 && $self[9] != 'N') { // Who approved this pictures?
	if ($self[9]=='A') {
		$approved_word = 'Представил(а) к удалению';
	} else {
		$approved_word = 'Утвердил(а)';
	}
	$query = "SELECT authors.id,name FROM authors,$my_newtable WHERE authors.id=$my_newtable.approved_by AND $my_newtable.id=$ID";
	$result = execute_query ($query);
	echo "<font color='$LOGO_COLOR'>";
	if ($approved_by = mysql_fetch_array ($result, MYSQL_NUM)) {
		echo "<p>$approved_word: <a href=index.php?AUTHOR=$approved_by[0]>$approved_by[1]</a>\n";
	} else {
		echo "<p>$approved_word: неизвесто, кто.\n";
	}
	echo '</font>';
	mysql_free_result ($result);
}

echo '<ul>';

// EmbiWiki
if (isset ($WIKILINK_ID)) {
	$done = false;

	$query = "SELECT emb_".PREFIX."name,emb_id,emb_content_name FROM $WIKI_TABLE,$WIKICONTENT_TABLE,$WIKILINK_TABLE WHERE emb_id=emb_content_article AND emb_content_article=emb_pictures_article AND emb_pictures_secid=emb_content_secid AND emb_content_version=1 AND emb_pictures_approved='Y' AND emb_pictures_id=$ID AND emb_gallery=$WIKILINK_ID ORDER BY emb_content_secno";

	$result = mysql_query ($query) or die ("$query: Query failed");
	while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
		if (!$done) {
			echo '<br>'.EMB_ADDITIONALLY.': ';
			$done = true;
		}
		echo "<a href='$WIKI_URL?ID=$row[1]#$row[2]'>$row[0] // $row[2]</a>;\n";
	}
	mysql_free_result ($result);
	$query = "SELECT emb_".PREFIX."name,emb_id FROM $WIKI_TABLE,$WIKILINK_TABLE WHERE emb_id=emb_pictures_article AND emb_pictures_secid=0 AND emb_pictures_id=$ID AND emb_gallery=$WIKILINK_ID ORDER BY emb_name";
	$result = mysql_query ($query) or die ("$query: Query failed");
	while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
		if (!$done) {
			echo '<li>'.EMB_ADDITIONALLY.': ';
			$done = true;
		}
		echo "<a href='$WIKI_URL?ID=$row[1]'>$row[0] // ".EMB_PG."</a>;\n";
	}
	mysql_free_result ($result);
}

/* Прочая инфа про фотографию */
if ($self[12] != 0) {
	echo '<li>'.HAS_GOOGLE.'<ul>';
	if ($_REQUEST['AROUND']) {
		if (!isset ($_GET['GMAPS'])) {
			$my_lat = tograd ($self[12]);
			$my_long = tograd ($self[13]);
			echo "<li><a href='$PHP_SELF?GMAPS&amp;ID=$ID'>$SATELLITE Google Maps</a> [{$self[12]}°, {$self[13]}°]&nbsp;".GM."&nbsp;[$my_lat, $my_long]\n";
		}
		echo "<li><a href=#similar>$SIMILAR</a>\n";
	}
	$new_around = $_REQUEST['AROUND'] ? 0 : 1;
	$old_around = !$new_around;
	echo "<li><a href='$PHP_SELF?LNG=".$_REQUEST['LNG']."&ID=$ID&AROUND=$new_around'>".$AROUND_MSG[$old_around].'</a></ul>';
}

echo "<li>$THISPICT: <a href='$permanent#picture'>$permanent</a>\n";
if (!$_REQUEST['PURIST'] && VOTE_ALLOWED && $dominus) {
	echo "<li>$RECOMMENDED\n";
	if ($MODERATOR_MODE == 2) {
		$query="SELECT HIGH_PRIORITY name,id,score FROM $VOTETABLE,authors WHERE uid=id AND fotoid=$ID ORDER BY score DESC,name";
		$result = execute_query ($query);
		while ($lrow = mysql_fetch_array ($result, MYSQL_NUM)) {
			echo "<a href=index.php?AUTHOR=$lrow[1]>$lrow[0] ($lrow[2])</a>,\n";
		}
	} else {
		$query="SELECT HIGH_PRIORITY score,count(*) cnt FROM $VOTETABLE WHERE fotoid=$ID GROUP BY score ORDER BY score DESC";
		$result = execute_query ($query);
		while ($lrow = mysql_fetch_array ($result, MYSQL_NUM)) {
			echo "<b>+$lrow[0]</b> x $lrow[1],\n";
		}
	}
	mysql_free_result ($result);
}
if ($dominus)
	echo "<li><b>[ <a href='pg_edit.php?ID=$ID'>".EDIT."</a> ]</b>\n";
echo '</ul><td valign=top width=10% align=right>';

/* Голосование */
$votes = $self[10];

if ($votes >= 0 && (!$its_pod || $MODERATOR_MODE == 2)) {
	echo THUMBUP."<font color='$TITLECOLOR'><b>: $votes</b>\n";
}

if ($self[11]>=0) {
	$podmod = array (POD, POD_1, POD_2);
	echo "<a href=hof.php>{$podmod[$self[11]]}</a>\n";
}
echo "</table>\n";
		
/* EXIF and COMMENTS */
echo '<table border="0" width="98%" cellpadding="8">';
echo '<tr><td valign="top" align="center" width="50%">';
echo '<table cellpadding=3 border="3" bgcolor="'.$FOURTH_COLOR.'"><tr><td>';
echo '<table cellspacing=0 cellpadding=3 border=0>';
$new_show_exif = ($_REQUEST['SHOW_EXIF'] || $_COOKIE['SHOW_EXIF']) ? 0 : 1;
echo "<tr><td align='left'><a href='$PHP_SELF?ID=$ID&SHOW_EXIF=$new_show_exif'>".EXIF_IMG.'</a><td align="right"><font color="'.$LOGO_COLOR.'"><tt><b>'.EXIF_DATA_AND_COMMENTS.'</b></tt></font></tr>';

if (EXIF_ALLOWED && ($_REQUEST['SHOW_EXIF'] || $_COOKIE['SHOW_EXIF'])) {
  $query="SELECT exif_data FROM $EXIFTABLE WHERE fotoid=$ID";
  $result = execute_query ($query);
  $exif_data = mysql_fetch_array ($result, MYSQL_NUM);
  mysql_free_result ($result);

  if ($exif_data) {
    $output = explode ("\n", stripslashes ($exif_data[0]));
  } else {
    exec (JHEAD.PG_REAL_BASE."$self[0].jpg", $output);
    $exif_data = mysql_real_escape_string (addslashes (implode ("\n", $output)));
    $query="INSERT INTO $EXIFTABLE VALUES ($ID, '$exif_data')";
    execute_query ($query);
  }
  $nlines = count ($output);
  $nl = 0;
  for ($i = 0; $i < $nlines; $i++) {
    $line = explode (': ', $output[$i]);
    echo '<tr><td bgcolor="'.$LOGO_COLOR.'"><font color="'.$SECOND_COLOR.'"><tt><b>'.$line[0].'</b></tt></font><td bgcolor="'.$SECOND_COLOR.'"><font color="'.$LOGO_COLOR.'"><tt>' . $line[1].'</tt></font></tr>';
    $nl++;
  }
  if ($nl<=1) {
    echo '<tr><td bgcolor="'.$LOGO_COLOR.'"><font color="'.$SECOND_COLOR.'"><tt><b>'.$NOEXIF1.'</b></tt></font><td bgcolor="'.$SECOND_COLOR.'"><font color="'.$LOGO_COLOR.'"><tt>'.$NOEXIF2.'</tt></font></tr>';	
  }
} else {
  echo '&nbsp;';
}
echo '</table></table>';

if (isset ($_GET['NOMODID']) && is_numeric ($_GET['NOMODID'])) {
	$query = "UPDATE $FOTO_COMMENTS_TABLE SET foto_comments_moder=0,foto_comments_date=foto_comments_date WHERE foto_comments_id=${_GET['NOMODID']} AND ($MODERATOR_MODE=2 OR foto_comments_author_id='$myid')";
	execute_query ($query);
} else if (isset ($_GET['RMID']) && is_numeric ($_GET['RMID'])) {
	execute_query ('BEGIN');
	$query = "UPDATE $FOTO_COMMENTS_TABLE SET foto_comments_moder=0,foto_comments_deleted='Y',foto_comments_deleted_by='$myid',foto_comments_date= foto_comments_date WHERE foto_comments_id=${_GET['RMID']} AND ($MODERATOR_MODE=2 OR foto_comments_author_id='$myid')";
	execute_query ($query);
	if (mysql_affected_rows () == 1) {
		$query = "UPDATE LOW_PRIORITY $PICTABLE SET n_comments=n_comments-1 WHERE id=$ID";
		execute_query ($query);
		if (!empty($NEWTABLE)) {
			$query = "UPDATE LOW_PRIORITY $my_newtable SET n_comments=n_comments-1 WHERE id=$ID";
			execute_query ($query);
		}
	}
	execute_query ('COMMIT');
} else if (isset ($_GET['BANID']) && is_numeric ($_GET['BANID']) && $MODERATOR_MODE == 2) {
	execute_query ('BEGIN');
	$query = "UPDATE $FOTO_COMMENTS_TABLE SET foto_comments_deleted='Y',foto_comments_date= foto_comments_date,foto_comments_deleted_by='$myid' WHERE foto_comments_id=${_GET['BANID']}";
	execute_query ($query);
	$query = "UPDATE LOW_PRIORITY $PICTABLE SET n_comments=n_comments-1,no_comments='Y' WHERE id=$ID";
	execute_query ($query);
	if (!empty($NEWTABLE)) {
		$query = "UPDATE LOW_PRIORITY $my_newtable SET n_comments=n_comments-1,no_comments='Y' WHERE id=$ID";
		execute_query ($query);
	}
	$query = "INSERT INTO banned_ips VALUES ('".mysql_real_escape_string($_GET['IP'])."',1)";
	if (!@mysql_query ($query)) {
		if (mysql_errno()==1062) {
			$query = "UPDATE banned_ips SET banned_ips_count=banned_ips_count+1 WHERE banned_ips_ip='".mysql_real_escape_string($_GET['IP'])."'";
			execute_query ($query);
		} else {
			die ("$query: Query failed");
		}
	}
	execute_query ('COMMIT');
}  else if (isset ($_GET['WARNID']) && is_numeric ($_GET['WARNID']) && $MODERATOR_MODE == 2) {
	$query = "DELETE FROM pg_warning WHERE DATEDIFF(NOW(),date)>=".WARNING_DURATION;
	execute_query ($query);
	$query = "INSERT IGNORE INTO pg_warning (authors_x_id,pg_moderators_id,severity) VALUES (${_GET['WARNID']},$myid,".WARNING_SEVERITY.")";
	execute_query ($query);

	// Who is the moderator?
	$query = "SELECT name,email FROM authors WHERE id=$myid";
	$result = execute_query ($query);
	$moderator = mysql_fetch_array ($result, MYSQL_NUM);
	mysql_free_result ($result);
	
	// Who is the sinner?
	$query = "SELECT name,email FROM authors WHERE id=${_GET['WARNID']}";
	$result = execute_query ($query);
	$sinner = mysql_fetch_array ($result, MYSQL_NUM);
	mysql_free_result ($result);

	// Send the guy a mail
	mail ($sinner[1],
	      "Предупреждение за некорректный комментарий",
       "Уважаемый $sinner[0]!\n\nВы получили предупреждение за Ваш комментарий к фотографии из фотогалереи (".GHOST."?ID=$ID). Этот комментарий был сочтен некорректным и был удален.\n\n-- \n$moderator[0], Модератор\n",
       		smtp_headers ($moderator[1]));
}

echo '<td valign="top" align="left" width="50%"><a name="comments">';

if (!isset($_GET['QUOTE_N']) || !is_numeric ($_GET['QUOTE_N'])) {
	$_GET['QUOTE_N'] = -1;
}
echo "<table width=95% cellpadding=1 cellspacing=0 bgcolor='$LOGO_COLOR'><tr><td>";
echo "<table width=100% cellpadding=5 cellspacing=0><tr bgcolor='$FOURTH_COLOR'><td valign='top'>";
echo "<a href=rss-comment.php?ID=$ID>".RSS_GIF."</a> <b>$COMMENTS_FOLLOW</b>";
echo "<tr bgcolor='white'><td valign='top'>";
$returned = show_comments ($ID, COMMENTS_ALLOWED, 0, $_GET['QUOTE_N']);
echo '</table></table>';
$count = $returned[0];

echo "<p>\n";
if (is_numeric($myid)) {
	if ($watched[0]) {
		$action = 'cancel';
		$actionword = STOP_NOTIFY;
	} else {
		$action = 'add';
		$actionword = ADD_NOTIFY;
	}
	echo "<a href=?SUBSCRIBE=$action&ID=$ID#comments>$actionword</a>...<p>";
}
echo "<table width=95% cellpadding=1 cellspacing=0 bgcolor='$LOGO_COLOR'><tr><td>";
echo "<table width=100% cellpadding=5 cellspacing=0><tr bgcolor='$FOURTH_COLOR'><td valign='top'>";
if (COMMENTS_ALLOWED) {
	$query = "select timestampdiff (minute, created, now())/(60*24) from authors where id=$myid";
        $result = execute_query ($query);
        $row = mysql_fetch_array ($result, MYSQL_NUM);
        mysql_free_result ($result);

	$msg = "";
        if (!empty ($WAIT_PERIOD) && $row[0] < $WAIT_PERIOD)
           $msg = sprintf ("Похоже, что вы создали свою учетную запись совсем недавно. Вы сможете оставлять комментарии через %.2f суток. Пожалуйста, извините за неудобство!", $WAIT_PERIOD - $row[0]);
	else if (is_numeric ($myid) && ($nwarn = warnings ($myid)) >= WARNING_COMMENT_THRESHOLD) 
                $msg = "У Вас слишком много предупреждений ($nwarn). В данный момент Вы не можете оставлять комментарии.";
	else if ($self[18] == 'Y' && !is_numeric($myid))
                $msg = COMMENTS_NOT_ALLOWED;

	echo '<a name="addcomment">';
	if ($msg) {
		echo "<font color='$WARNING_COLOR'><b>$msg</b></font>\n";
	} else if ($count < COMMENTS_ALLOWED) {
		$quoted = $returned[1];
		echo "<b>$ADD_COMMENT</b>\n";
		echo "<tr bgcolor='white'><td valign='top'>";
		echo "<FORM ACTION='$PHP_SELF#comments' METHOD='POST' name='myForm'>\n";
		echo '<TEXTAREA NAME="COMMENT" COLS=45 ROWS=5 ';
		echo 'onChange="textCounter(document.myForm.COMMENT,500)" ';
		echo 'onKeyDown="textCounter(document.myForm.COMMENT,500)" ';
		echo 'onKeyUp="textCounter(document.myForm.COMMENT,500)">';
		if ($quoted) {
			$q_1 = array ('&gt; <i>','</i><br>');
			$q_2 = array ('[quote]','[/quote]');
			$quoted = str_replace ($q_1, $q_2, $quoted);
			echo '[quote]'.$quoted."[/quote]\n";
		}
		echo '</TEXTAREA>';
		echo "<p>$WHOIS_AUTHOR <INPUT NAME='AUTHOR_NAME' VALUE='$AUTHOR_NAME' SIZE='24' MAXLENGTH=24>";
		if (ENABLE_TRANSLIT) {
			echo '<p><INPUT TYPE="checkbox" NAME=TRANSLIT>'.TRANSLIT.' (<a href="show_translit.php" target="_new">'.WHATS_TRANSLIT.QMTHUMBUP.'</a>)';
		}
		echo '<p><INPUT TYPE="checkbox" NAME=TO_MODERATOR> '.TO_MODERATOR;
		echo '<p><font size=-1>'.COMMENT_WARNING.'</font>';
		echo "<p><INPUT TYPE=SUBMIT VALUE=' $ADD_COMMENT '>";
		echo "<INPUT TYPE=HIDDEN NAME='COMMENT_ID' VALUE=$ID>";
		echo "<INPUT TYPE=HIDDEN NAME='ID' VALUE=$ID>";
		echo "<INPUT TYPE=HIDDEN NAME='LNG' VALUE='".$_REQUEST['LNG']."'>";
		echo "</FORM>";
	} else {
		echo "<b>$COMMENTS_CLOSED</b>. <a href=$PHO>$GOTOFORUM</a>.";
	}
} else {
	echo NO_COMMENTS;
}
echo '</table></table></table><p>';

/* Neighborhood and Google */

if (count ($neighbors) > 0) {
  echo "<p><table width=95% border='0' bgcolor='$LOGO_COLOR' cellpadding='1' cellspacing=0><tr><td>\n";
  echo "<table border='0' bgcolor='white' cellpadding='10' cellspacing=0 width=100%><tr><td bgcolor='$LOGO_COLOR'>\n";
  echo "<a name=similar><font color='$SECOND_COLOR'><b>$SIMILAR</b></font></a>\n";
  echo "<tr><td align=center>\n";

  foreach ($neighbors as $nmb) {
    $title = $nmb[3];
    $title = preg_replace ('@(<[^>]+>)@si', '', $title);
    $title = preg_replace ('@({[^}]+})@si', '', $title);
    
    $title .= ($nmb[2] < 0.5) ? ", $HERE" : (", ".(int)($nmb[2]+0.5)." ".$KM);
    
    echo "<a href='$PHP_SELF?ID=$nmb[1]'><img src='".IC_BASE."$nmb[0]-s.jpg' border='0' height='".(int)(128.5-2*$nmb[2])."' title='$title'></a>\n";
  }
  
  echo "</table></table>\n";
}

echo '</center>';
include ('pg_tail.php');
?>

