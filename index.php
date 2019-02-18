<?php
error_reporting(E_ERROR | E_PARSE);

if (stripos($_SERVER['REQUEST_METHOD'], 'HEAD') !== FALSE) exit();

if (isset ($_REQUEST['ID']) && is_numeric ($_REQUEST['ID'])) { 
  include('pg_view.php');
  die();
}

$my_author = trim ($_REQUEST['AUTHOR'], ' ');
$my_descr = trim ($_REQUEST['DESCR'], ' ');

if (isset ($_REQUEST['SEARCH_COMMENTS'])) {
  header ("Location: vw_showall.php?AUTHOR=$my_author&DESCR=$my_descr."); /* Redirect browser */
}

require ("pg_db.php");
$SELECTED = 2;

require ("functions.php");
$my_author = mysql_real_escape_string ($my_author);
$my_descr = mysql_real_escape_string ($my_descr);

if ($_REQUEST['REDIRECT']) {
  echo $_REQUEST['REDIRECT'];
  die ('');
}

$cat = $_REQUEST['CATEG'];
$show_all = (!isset ($_REQUEST['SHOW_ALL']) || $_REQUEST['SHOW_ALL']) ? 1 : 0;
$vote = false;

function show_sections () 
{
  global $total, $LOGO_COLOR, $SECOND_COLOR, $HOWMANY, $DEFAULT_HOWMANY, $__sections;
  if (!isset ($__sections)) {
  $oldfrom = $_REQUEST['FROM'];
  $anchor = $oldfrom / $HOWMANY;
  $second = '';
  for ($i=0; $i < $total; $i += $HOWMANY) {
    $upper = min ($total - $i, $HOWMANY);    
    $_REQUEST['FROM']=$i;	
    $cmdline = buildcmdl ();
    if ($i / $HOWMANY != $anchor) {
      $second .= sprintf ("[&nbsp;<A HREF='".$_SERVER['PHP_SELF']."$cmdline'>%d-%d</a>&nbsp;]\n", 
			  $i+1, $i + $upper);
    } else {
      $second .= sprintf ("[<b>&nbsp;%d-%d&nbsp;</b>]\n", $i+1, $i + $upper);
    }
  }
  $_REQUEST['FROM'] = $oldfrom;
  $second .= '<p><b>'.PPP.'</b>: ';
  $cmdline = buildcmdl ();
  for ($i = 1; $i < 5; $i++) {
    $N = $i * $DEFAULT_HOWMANY;
    if ($N == $HOWMANY)
      $second .= "[&nbsp;$N&nbsp;]\n";
    else
      $second .= "[&nbsp;<A HREF='$PHP_SELF$cmdline&HOWMANY=$N'>$N</a>&nbsp;]\n";
  }

  $__sections="<table width='95%' border='0' cellpadding='1' cellspacing='0' bgcolor='$LOGO_COLOR'>\n<tr><td><table width='100%' border='0' cellpadding='5' cellspacing='0' bgcolor='$SECOND_COLOR'><tr><td align=center>\n<font size=-1>$second</font></table></table><p>";
  }
	echo $__sections;
}

function buildcmdl ()
{
  global $cat, $show_all;
  $cmdline = '?';    
  $kwords = array ('FROM', 'LNG', 'NO_ICONS');
  
  foreach ($kwords as $p) {
    $default = "DEFAULT_".$p;
    global $$default;
    if ($_REQUEST[$p] != $$default) {
      $cmdline .= '&'.$p.'='.$_REQUEST[$p];
    }
  }
  
  $dwords = array ("REGION", "TAKENON", "DESCR", "AUTHOR", "MONTH",'SEARCHSTRING');
  foreach ($dwords as $p) {
    if (isset ($_REQUEST[$p]))
      $cmdline .= '&'.$p.'='.$_REQUEST[$p];
  }

  if ($cat)
    $cmdline .= "&CATEG=$cat";
  if (!$show_all)
    $cmdline .= "&SHOW_ALL=0";
  return $cmdline;
}

if (!isset ($_REQUEST['FROM']) || $_REQUEST['FROM'] < 0) {
  $_REQUEST['FROM'] = 0;
}

if (!VOTE_ALLOWED) {
  $purist = 0;
  $show_all = 1;
} else {
  if (isset ($_GET['PURIST'])) {
    if ($_GET['PURIST'] == 0) {
      setcookie ('PURIST',FALSE,time()-300);
      $purist = 0;
    } else {
      setcookie ('PURIST','1',time()+ONE_HOUR*ONE_DAY*365);
      $purist = 1;
      $show_all = 1;
    }
  } else if (isset ($_COOKIE['PURIST'])) {
    $purist = 1;
    $show_all = 1;
  }
}

/* handle competing pictures */
if (!$show_all) {
  $competing = ' competing="Y" ';
} else {
  $competing = ' 1 ';
}


if ($MODERATOR_MODE == 2) {
  $approve = '1';
} else {
  $author_id = authorize ();
  $approve = $competing.' AND approved="Y" AND (ISNULL(votedon) OR votedon<"'.$tomorrow.'")';
}
$approve_special = ' approved="Y" AND (ISNULL(votedon) OR votedon<"'.$tomorrow.'")';

require ($_REQUEST['LNG'].'/index.php');

$cond = '';
$moretables = '';


if (!empty ($my_author) || !empty($_REQUEST['AUTHOR_ID'])) {
	if (!empty ($_REQUEST['AUTHOR_ID']) && is_numeric ($_REQUEST['AUTHOR_ID'])) {
		$cond .= "and (author_id={$_REQUEST['AUTHOR_ID']}) ";
	} else {
		if (is_numeric ($my_author)) {
			$cond .= "and (author_id=$my_author) ";
			$_REQUEST['AUTHOR_ID'] = $my_author;
		}
	}
	if ($cat == RAND_CAT) $cat = '';
}



if (!empty($my_descr)) {
	$darray = explode (' ', $my_descr);
	$cond .= 'AND (1';
	foreach ($darray as $clause)
		$cond .= " AND description like '%$clause%'";
	$cond .= ')';
}


if ($cat && $cat != NEW_CAT) {
  if ($cat != DAY_CAT && $cat != NEW_CAT && $cat != RAND_CAT) {
    $cond .= " AND (pg_cats_x_cat_name='$cat' AND pg_cats_x_picture_id=$PICTABLE.id) ";
    $moretables = ",$CATTABLE";
  } else
    $cond .= ' AND award>=0 ';
}
if ($_REQUEST['REGION']) {
  $region = mysql_real_escape_string ($_REQUEST['REGION']);
  $cond .= " and region like '$region%'";
} else {
  $region = '';
}
if ($_REQUEST['TAKENON']) {
  $cond .= "and (takenon like '".$_REQUEST['TAKENON']."-__-__' or baddate like '".$_REQUEST['TAKENON']."%') ";
}
if ($_REQUEST['MONTH']) {
  $cond .= "and (takenon like '____-".$_REQUEST['MONTH']."-__') ";
}

if ($_REQUEST['SEARCHSTRING']) {
  $search_list = implode ('.+',split (' +', $_REQUEST['SEARCHSTRING']));
  $cond .= "and (description rlike '$search_list')";
}

if  ($_REQUEST['SORT_ORDER']=='V') {
  $cond .= 'AND '.$competing.' AND approved="Y" AND (ISNULL(votedon) OR votedon<"'.$today.'")';

}

if ((!$cond || $cat == NEW_CAT) && !$_REQUEST['ID']) {
$cond = ' and ((votedon > CURDATE() - INTERVAL ' . NEW_THRESHOLD.')';
$TITLE = $NEW_TITLE;
$cat = NEW_CAT;
$table = !empty($NEWTABLE) ? $NEWTABLE : $PICTABLE;
  if ($MODERATOR_MODE == 2) $cond .= " OR approved in ('N','A')";
  $cond .= ')';
} else {
  $table = $PICTABLE;
  $TITLE = $SELECT_TITLE;
}
$PAGE = "$WEBSITE. $TITLE";


/* Voting rights */ 
function can_vote ($user)
{
	define ('MIN_POSTED_TO_VOTE', 0);
	global $PICTABLE;
	//$nquery = "SELECT COUNT(*) FROM pg_stars WHERE pg_stars_maintable='$PICTABLE' AND pg_stars_x_authors_id=$user";
	$nquery = "SELECT COUNT(*) FROM $PICTABLE WHERE author_id=$user AND approved='Y' AND competing='Y' AND collection='N'";
	$nresult = execute_query ($nquery);
	$nrow = mysql_fetch_array ($nresult, MYSQL_NUM);
	mysql_free_result ($nresult);
	return ($nrow[0] >= MIN_POSTED_TO_VOTE);
}

if (VOTE_ALLOWED) {
	$uid = -1;
	if ($cat == NEW_CAT) {
		$_vauthor = $_COOKIE['COOKIE_AUTHOR'];
		$_vpassword = $_COOKIE['COOKIE_AUTHOR_PASSWORD'];
		if ($_vauthor && $_vpassword) {
			$vquery = "SELECT id FROM authors WHERE login='$_vauthor' AND password='$_vpassword'";
			if (($result = mysql_query ($vquery,$MYSQL_LINK)) && mysql_num_rows ($result)) {
				$row = mysql_fetch_array ($result, MYSQL_NUM);
				mysql_free_result ($result);
				$uid = $row[0];
			}
		}
	}
	
	if ($uid > 0) {
		if (!isset ($_REQUEST['VOTE'])) {
			$vquery = "SELECT uid FROM $VOTETABLE WHERE uid='$uid' AND votedate='$today'";
			$result = execute_query ($vquery);
			$vote = !mysql_num_rows ($result) && !$purist && !$_REQUEST['FROM'] && can_vote ($uid);
			mysql_free_result ($result);
		} else {
			$dayofyear = date ('z', $current_time - POD_OFFSET * ONE_HOUR) + 1;
			$vquery = "INSERT INTO $VOTETABLE VALUES ('$uid','$today','-$dayofyear',-1)";
			if (!@mysql_query ($vquery,$MYSQL_LINK)) {
				if (mysql_errno() != 1062) {
					include ("pg_head.php");
					die ("$vquery: Query failed");
				}
			} else {			// he has not voted today!
				if (can_vote ($uid)) { // only real photographers can vote for sure!
					$sum = $count = 0;
					foreach ($_POST as $my_vote => $score) {
						$my_picture = split ('VOTE', $my_vote);
						$score = min ($score, MAX_VOTES);
						if ($my_picture[1] && $score > 0) {
							execute_query ('BEGIN');
							$vquery = "INSERT INTO $VOTETABLE VALUES ('$uid','$today','$my_picture[1]','$score')";
							if (!@mysql_query ($vquery,$MYSQL_LINK)) {
								if (mysql_errno() != 1062) {
									include ("pg_head.php");
									die ("$vquery: Query failed");
								}
							} else {
								$vquery = "UPDATE $PICTABLE SET votes=votes+($score) WHERE id='$my_picture[1]' AND votedon='$today' AND $competing AND approved='Y' AND author_id!=$uid";
								execute_query ($vquery);
								if (!empty($NEWTABLE)) {
								$vquery = "UPDATE $NEWTABLE SET votes=votes+($score) WHERE id='$my_picture[1]' AND votedon='$today' AND $competing AND approved='Y' AND author_id!=$uid";
								execute_query ($vquery);
								}
							}
							execute_query ('COMMIT');
						}
						if ($my_picture[1] && $score > -1) {
							$sum += $score;
							$count++;
						}
					}
					// Add some extra credit to the voter
					if ($count > 0) {
						$sum = (int)($sum / (1.0 * $count) + 0.5);
						$vquery = "UPDATE $PICTABLE SET votes=votes+$sum WHERE votedon='$today' AND competing='Y' AND author_id=$uid AND approved='Y'";
						execute_query ($vquery);
						if (!empty ($NEWTABLE)) {
							$vquery = "UPDATE $NEWTABLE SET votes=votes+$sum WHERE votedon='$today' AND competing='Y' AND author_id=$uid AND approved='Y'";
							execute_query ($vquery);
						}
					}
				}
			}
		}
	}
}

include ("pg_head.php");

/* POD time? */
$vquery = "SELECT last_modified FROM $PODTABLE LIMIT 1";
$result = execute_query ($vquery);
$row = mysql_fetch_array ($result, MYSQL_NUM);
$last_modified = $row[0];
mysql_free_result ($result);

	
if ($last_modified != $today) {
	/* do the POD */
	$vquery = "UPDATE $PODTABLE SET last_modified='$today'";
	execute_query ($vquery);
	if (VOTE_ALLOWED) {
		include ('_pod.php');
	}
}
$query="SELECT HIGH_PRIORITY url,description,1,".$prefix."regname,name,takenon,senton,baddate,$table.id,approved,votes,1,votedon,viewed,award,longitude,author_id,1,1,1,n_comments,competing,collection,icon_url from $table USE INDEX(takenon),$REGIONTABLE,authors $moretables where $approve and region=regid and (author_id=authors.id)";

if  ($_REQUEST['SORT_ORDER']=='V') {
  $query .= $cond.' ORDER BY viewed '.$_REQUEST['SORT_DIR'].' LIMIT '.$_REQUEST['FROM'].','.$HOWMANY;
  $cquery = 'SELECT '.$HOWMANY;
} else if ($cat == NEW_CAT || $cat == DAY_CAT) {
  if ($cat == DAY_CAT) {
    $query .= $cond.' order by votes desc,award,votedon desc,';
  } else {
    if ($MODERATOR_MODE == 2) {
      $query .= $cond.' order by approved, votedon asc,';
    } else {
      $query .= $cond.' order by votedon desc,competing desc,';
      if ($vote && !$_REQUEST['FROM'])
	$query .= 'RAND(),';
    }
  }
  $query .= 'takenon,senton desc limit '.$_REQUEST['FROM'].','.$HOWMANY;
  $cquery = "SELECT HIGH_PRIORITY count(*) from $table,$REGIONTABLE,authors where $approve and region=regid  and (author_id=authors.id) $cond";
} else if ($cat != RAND_CAT || $my_author || $_REQUEST['AUTHOR_ID']) {
  $query .= $cond.' order by takenon limit '.$_REQUEST['FROM'].','.$HOWMANY;
  $cquery = "SELECT HIGH_PRIORITY  count(*) from $table,$REGIONTABLE,authors $moretables where $approve and region=regid  and (author_id=authors.id) $cond";
} else {
  $query .= ' order by RAND() LIMIT '.$HOWMANY;
  $cquery = 'SELECT  HIGH_PRIORITY ' . $HOWMANY;
}

$result = execute_query ($cquery);
$row = mysql_fetch_array ($result, MYSQL_NUM);
$total = $row[0];
mysql_free_result ($result);

$approved_count = array ();
$count_query = "SELECT HIGH_PRIORITY count(*),competing from $PICTABLE WHERE $approve_special GROUP BY competing";
$result = execute_query ($count_query);
$row = mysql_fetch_array ($result, MYSQL_NUM);
$approved_count[$row[1]] = $row[0];
$row = mysql_fetch_array ($result, MYSQL_NUM);
$approved_count[$row[1]] = $row[0];
mysql_free_result ($result);

$AC_MENU_ITEM = -1;
$AC_MENU_INTRO = '';
require ('view_menu.php');
end_menu ();

if (!empty ($my_author) && !is_numeric ($my_author)) {
	$search_author = $my_author;
	require ('all_authors.php');
	include ("pg_tail.php");
	exit();
}

echo "<center>";
if (!$purist && $cat == NEW_CAT && $vote) {
  echo "<form action='$PHP_SELF' method='POST'>";
  echo '<INPUT TYPE=SUBMIT VALUE=" '.$FOTODNYA1.' "><p>';
}

if (file_exists('reklama.php'))
     include ('reklama.php');

if ($_REQUEST['AUTHOR_ID']) {
  echo '<p>';
  include ('authorcenter.php');
}

//echo $query.'<br>';
$result = execute_query ($query);
$size = mysql_num_rows ($result);

if ($size > 0) {
  if ($region && !$my_author) {
    echo "<p><table width='95%' border='0' cellpadding='1' cellspacing='0' bgcolor='$LOGO_COLOR'>\n";
    echo "<tr><td><table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
    echo "<tr><td $table_bg><table border=0 width='100%' cellpadding=5 cellspacing=0>\n<tr bgcolor='$FOURTH_COLOR'><td>\n";

    $reg_query = "SELECT regname FROM $REGIONTABLE WHERE regid='$region'";
    $reg_result = execute_query ($reg_query);
    $reg_row = mysql_fetch_array ($reg_result, MYSQL_NUM);
    mysql_free_result ($reg_result);
    echo "<h3>$reg_row[0]</h3>\n";

    echo "<tr><td bgcolor=white colspan=2>\n";
    echo "<h4>".TOP10AUTHORS."</h4>";
    $reg_query = "SELECT name,author_id,COUNT(*) cnt FROM authors,$PICTABLE WHERE author_id=authors.id AND region='$region' AND approved='Y' AND collection='N' GROUP BY name ORDER BY cnt DESC LIMIT 10";
    $reg_result = execute_query ($reg_query);
    $first = 1;
    while ($reg_row = mysql_fetch_array ($reg_result, MYSQL_NUM)) {
      if ($first)
	$first = 0;
      else
	echo (', ');
      echo "<a href=?AUTHOR=$reg_row[1]&LNG=$LNG&amp;REGION=$region>$reg_row[0]</a> ($reg_row[2])";
    }
    echo '</table></table></table><p>';
    mysql_free_result ($reg_result);
  }

  show_sections ();
  echo "<p><table width='95%' border='0' cellpadding='1' cellspacing='0' bgcolor='$LOGO_COLOR'>\n";
  echo "<tr><td><table width='100%' border='0' cellpadding='5' cellspacing='0'>\n";
  $i = 0; 
  $second_row = '';
  $first_row = '';
  while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
    if ($i % DEFAULT_NCOLS == 0) {
      echo "<tr>\n";
    }

    if ($row[14]>=0 && !$purist && VOTE_ALLOWED) {
      $cellbg = $POD_COLOR;
    } else {
	    if ($MODERATOR_MODE == 2 && $row[9]!='Y') {
	      if ($row[9]=='N') {
		      $cellbg = $UNPUBLISHED_COLOR;
	      } else {
		      $cellbg = $LOGO_COLOR;
	      }
	$row[8] = -$row[8];
      } else {
	if ($row[6] == $today) {
	  $cellbg = $SECOND_COLOR;
	} else {
	  $cellbg = $OLD_COLOR;
	}
      }
    }

    $first_row .= "<td align='left' valign='top' width='".(100 / DEFAULT_NCOLS - SMALLCOL)."%' bgcolor='$cellbg'>";
    if (!$_REQUEST['NO_ICONS']) {
	    $icon = $row[23] ? $row[23] : $row[0];
	    echo "<td align=left valign='top' width='".(100 / DEFAULT_NCOLS - SMALLCOL)."%' bgcolor='white'>\n";
      $image = '<img src="'.IC_BASE.$icon.'-s.jpg" border="0" hspace="2" vspace="2" height=128>';
      echo "<a href='pg_view.php?ID=$row[8]&LNG=".$_REQUEST['LNG']."#picture'>$image</a>";
    }
    
    if ($MODERATOR_MODE == 2 || $row[16] == $author_id) {
      $first_row .= "<a href='pg_edit.php?FROM=$oldfrom&ID=$row[8]'><img src='$OTHER_GALLERY"."images/edit.png' border=0 align=right alt='".EDIT."' title='".EDIT."' width=25 height=25></a>\n";
    }

    if (!$purist && VOTE_ALLOWED) {
      switch ($row[14]) {
      case 0:
	$first_row .= POD;
	break;
      case 1:
	$first_row .= POD_1;
	break;
      case 2:
	$first_row .= POD_2;
	break;
      default:
	break;
      }
    }

    if ($row[9]=='A') $first_row .= '<blink><b>Удалить?</b></blink> ';
    if ($row[9]=='N') $first_row .= '<b>Опубликовать?</b> ';
    $first_row .= "<a href='pg_view.php?ID=$row[8]&LNG=".$_REQUEST['LNG']."#picture'>$row[1]</a>";
    if ($row[5])
      $d = mydate ($row[5]);
    else
      $d = $row[7];
    $first_row .= ",\n<i>$row[3]</i>, $d,\n";

    $oldauthor = $my_author;
    $oldcat = $cat;
    $oldfrom = $_REQUEST['FROM'];
    $oldshowall = $show_all;
    if ($cat == NEW_CAT) {
      $region = '';
      $cat = '';
      $_REQUEST['TAKENON'] = '';
      $_REQUEST['MONTH'] = '';
     $_REQUEST['DESCR'] = '';
    }
    $_REQUEST['FROM'] = 0;
    $_REQUEST['AUTHOR'] = $row[16];
    $show_all = 1;
    $cmdline = buildcmdl ();
    $author = stripslashes ($row[4]);
    if ($row[22]=='Y') {
      $author .= ' (<i>'.COLLECTION.'</i>)';
    }

    $first_row .= "<a href='$PHP_SELF$cmdline'>$author</a>,\n";
    $row[6] = mydate ($row[6]);
    $first_row .= "<font color='$LOGO_COLOR'>".PUBLISHED." $row[6]</font>\n";

    $cat = $oldcat;
    $_REQUEST['FROM'] = $oldfrom;
    $show_all = $oldshowall;

    $i += 1;

    if (isset ($SIMILAR)) {
      if (ereg ($SIMILAR[0], $row[1], $number) ||
	  ereg ($SIMILAR[1], $row[1], $number)) {
	$first_row .= "<br><font size=-1>[<a href='?SHOW_ALL=1&LNG=".$_REQUEST['LNG']."&DESCR=$number[2]'><font size=-1>$more $number[2]...</font></A>]";
	$first_row .= " [<a href='?SHOW_ALL=1&LNG=".$_REQUEST['LNG']."&DESCR=$number[2]$number[3]$number[4]'><font size=-1>$more $number[2]$number[3]$number[4]...</font></A>]</font>";
      }
    }

    $second_row .= "<td align='left' valign='top' width='".(100 / DEFAULT_NCOLS - SMALLCOL)."%' valign=top bgcolor='$FOURTH_COLOR'>";

    /* Voting */
    if (   $vote
	&& $row[16]!=$uid	/* The author himself */
	&& $row[12]==$today 
	&& !$purist 
	&& $row[21]=='Y') {
	$_voting = THUMBUP.": \n";
	for ($grade = 0; $grade <= MAX_VOTES; $grade++) {
	  $selected = $grade ? '' : 'checked';
	  $_voting .= "<INPUT TYPE=radio NAME='VOTE$row[8]' VALUE='$grade' $selected>$grade\n";
	}
    } else if ($row[10] >= -1000 
	       && !$purist 
	       && VOTE_ALLOWED 
	       && $row[21]=='Y') {
      $_voting = THUMBUP.": <font color='$TITLECOLOR'><b>$row[10]</b></font>\n";
    } else if ($row[21]=='Y' && !$purist && VOTE_ALLOWED) {
      $_voting = QMTHUMBUP."\n";
    } else {
      $_voting = '';
    }
    $second_row .= $_voting;

    /* Viewings */
    if ($cat == NEW_CAT ) {
    	$_viewings = EYE.": $row[13]\n";
    	$second_row .= $_viewings;
    }

    /* Coordinates? */
    if ($row[15]) {
      $second_row .= GM;
    }

    if ($row[20]) {
      $second_row .= COMM.': <b><font color="red">'.$row[20].'</font></b> ';
    }

    if ($i % DEFAULT_NCOLS == 0) {
      echo '<tr>'.$first_row;
      $first_row='';
      echo '<tr>'.$second_row;
      $second_row='';
    }
  }
  
  $i %= DEFAULT_NCOLS;
  if ($i > 0) {
    for ( ; $i<DEFAULT_NCOLS ; $i+=1) {
      echo "<td width='".(100 / DEFAULT_NCOLS - SMALLCOL)."%' bgcolor='white'>&nbsp;\n";
      $first_row .= "<td bgcolor='$OLD_COLOR'>&nbsp;\n";
      $second_row .= "<td bgcolor='$FOURTH_COLOR'>&nbsp;\n";
    }
  }
  echo '<tr>'.$first_row;
  echo '<tr>'.$second_row;
  echo "</table></table>\n";
  echo "<p>";
  
  show_sections ();
} else {
  echo '<hr>'.NOTHING_FOUND.'.';
}

if (!$purist && $cat == NEW_CAT && $vote) {
	echo "<input type='hidden' name='VOTE' value='0'>";
	echo "<p><INPUT TYPE=SUBMIT VALUE=' $FOTODNYA1 '>\n";
	echo '</form>';
}
mysql_free_result ($result);

echo "<form action='$PHP_SELF'>";
echo "<table border=0 cellspacing=0 cellpadding=1 bgcolor='$LOGO_COLOR' width=95%><tr><th align=left><font color='$OLD_COLOR'>$searchme</font><tr><td><table border=0 cellspacing=0 cellpadding=5 bgcolor='$OLD_COLOR' width=100%>";
	
echo '<tr><td>'.$_catname;
if ($cat == NEW_CAT) {
    $oldcat = $cat;
    $cat = '';
}

$query = 'SELECT HIGH_PRIORITY name,title FROM '.$BASECATTABLE.' WHERE title!="ROOT" ORDER BY title';
$result = execute_query ($query);
echo '<td><select name="CATEG">';

$speccats = array ('' => $_any,
		   NEW_CAT => $_new,
		   RAND_CAT => $_rand,
		   DAY_CAT => $FOTODNYA2);
foreach ($speccats as $sptag => $spname) {
  $active = ($cat == $sptag) ? 'SELECTED' : '';
  echo "<option value='$sptag' $active>$spname\n";
}
printf ("<option value=''>-----\n");

while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
  $active = ($cat==$row[0]) ? 'SELECTED' : '';
  printf ("<option value='%s' %s>%s\n", $row[0], $active, $row[1]);
}
echo "</select>\n";
mysql_free_result ($result);
$cat = $oldcat;

if ($cat==RAND_CAT || $cat==NEW_CAT) {
    $region = '';
    $_REQUEST['TAKENON'] = '';
    $_REQUEST['MONTH'] = '';
    $my_author = '';
    $my_descr = '';
}

echo '<td>'.$_regname;
$query = 'select  HIGH_PRIORITY regid,'.$prefix.'regname from '.$REGIONTABLE.' order by '.$prefix.'regname';
$result = execute_query ($query);
echo '<td><select name="REGION">';
$active = ($region == '') ? 'SELECTED' : '';
printf ("<option value='' %s>%s\n", $active, $_any1);
printf ("<option value=''>-----\n");
while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
  $active = ($region == $row[0]) ? 'SELECTED' : '';
  printf ("<option value='%s' %s>%s\n", $row[0], $active, $row[1]);
}
echo "</select> \n";
mysql_free_result ($result);

echo '<td>'.$_takenon;
echo '<td><select name="TAKENON">';
$active = ($_REQUEST['TAKENON'] == '') ? 'SELECTED' : '';
printf ("<option value='' %s>%s\n", $active, $_any1);
printf ("<option value=''>-----\n");
for ($year = $this_year; $year > BOUNDARY_YEAR; $year--) {
    $active = ($_REQUEST['TAKENON'] == $year) ? 'SELECTED' : '';
    printf ("<option value='%s' %s>%s\n", $year, $active, $year);
}
for ($decade = BOUNDARY_YEAR / 10; $decade >= START_YEAR / 10; $decade--) {
    $active = ($_REQUEST['TAKENON'] == $decade.'_') ? 'SELECTED' : '';
    printf ("<option value='%s_' %s>%s?\n", $decade, $active, $decade);
}
echo "</select>\n";

echo "<tr><td bgcolor='$FOURTH_COLOR'>$_author:\n";
echo "<td bgcolor='$FOURTH_COLOR'><input name='AUTHOR' SIZE='32' MAXLENGTH='32' VALUE='$my_author'> ";
echo "<td bgcolor='$FOURTH_COLOR'>$_keys\n";
echo "<td bgcolor='$FOURTH_COLOR'><input name='DESCR' SIZE='32' MAXLENGTH='32' VALUE='$my_descr'> ";

echo '<td>'.MONTH.':';
echo '<td><select name="MONTH">';
if ($_REQUEST['MONTH']=='') {
  $active = "SELECTED";
}
printf ("<option value='' %s>%s\n", $active, $_any1);
printf ("<option value=''>-----\n");
$i = 1;
foreach ($months as $y) {
  if ($y == '?')
    continue;
  $active = ($_REQUEST['MONTH']==$i) ? 'SELECTED' : '';
  printf ("<option value='%02d' %s>%s\n", $i, $active, $y);
  $i++;
}
echo "</select>\n";

$checked1 = $show_all ? 'checked' : '';
$checked2 = $show_all ? '' : 'checked';
$search_comments_flag = isset ($_REQUEST['SEARCH_COMMENTS']) ? 'checked' : '';
echo "<tr><td>".PHOTO.":<td align=right><input TYPE=radio name='SHOW_ALL' value='1' $checked1> ".ALL." <input TYPE=radio name='SHOW_ALL' value='0' $checked2> ".COMPETING_ONLY."\n";
echo "<td bgcolor='$FOURTH_COLOR'>&nbsp;<td bgcolor='$FOURTH_COLOR'>".COMMENTS_ONLY.": <input  TYPE=checkbox name='SEARCH_COMMENTS' $search_comments_flag>\n";

echo "<td><INPUT TYPE=SUBMIT VALUE=' ".$_search." '>\n";
echo "<input type='hidden' name='HOWMANY' value='$HOWMANY'>\n";
echo "<input type='hidden' name='LNG' value='".$_REQUEST['LNG']."'>\n";
echo "<input type='hidden' name='NO_ICONS' value='".$_REQUEST['NO_ICONS']."'>\n";

echo "</table>\n</table>\n</form>\n</center>\n";

include ("pg_tail.php");
?>
