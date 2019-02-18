<?php
if (isset ($FUNCTIONS)) {
     return;
}
   
require('setup.php');
require('time.php');

function smtp_headers ($from) {
	global $current_time;
	return "From: Picture Gallery <$from>\n".
			"Reply-To: Picture Gallery <$from>\n".
			"Return-Path: Picture Gallery <$from>\n".
			"Message-ID: <$current_time-$from>\n".
			"X-Mailer: PHP v".phpversion()."\n".
			"Content-Type: text/plain; charset=utf-8\n".
			"Content-Transfer-Encoding: 8bit\n"
//			."Cc: ".ADMIN."\n"
			;
}

define ('WARNING_POST_THRESHOLD',5);
define ('WARNING_COMMENT_THRESHOLD',2);
define ('WARNING_DURATION', 30);
define ('BAN_SEVERITY', 100);
define ('WARNING_SEVERITY', 1);
define ('DELETE_PENALTY', 5);
define ('APPROVE_PENALTY', 1);
define ('EDIT_PENALTY', APPROVE_PENALTY);
define ('SELF_APPROVE_THRESHOLD', 25);
define ('WARNING_THRESHOLD', -65);
define ('TROLL_THRESHOLD',5);

define ('SUNDAY', 0);
define ('SATURDAY', 6);
define ('MAXSTARS', 128);
$months = array ('?', 'I', 'II', 'III', 'IV', 'V',
		 'VI', 'VII', 'VIII', 'IX', 'X',
		 'XI', 'XII');
define ('JHEAD', 'exiftool  -d "%Y-%m-%d %H:%M:%S" -Make -Model -Orientation  -Software -ExposureTime -DateTimeOriginal -ShutterSpeedValue -ApertureValue -MeteringMode -Flash -FocalLength -ISO -Aperture -ImageSize -ExposureMode -WhiteBalance -DigitalZoomRatio -XResolution -YResolution -fast ');

define ('NEW_CAT', -1);
define ('DAY_CAT', -2);
define ('RAND_CAT', -3);

if (isset ($_GET['NLS'])) {
	setcookie('NLS',$_GET['NLS'],$current_time+ONE_HOUR*ONE_DAY*365, '/', COOKIE_DOMAIN);
	header ("Location: index.php"); /* Redirect browser */
			exit();
}

if (isset ($_GET['LNG']) && $_GET['LNG']) { 
  $_REQUEST['LNG'] = @mysql_real_escape_string ($_GET['LNG']);
  setcookie('LNG',$_REQUEST['LNG'],$current_time+ONE_HOUR*ONE_DAY*365, '/', COOKIE_DOMAIN);
} else if (isset ($_COOKIE['LNG']) && $_COOKIE['LNG']) {
  $_REQUEST['LNG'] = @mysql_real_escape_string ($_COOKIE['LNG']);
} else {
  $_REQUEST['LNG'] = DEFAULT_LANGUAGE;
  setcookie('LNG',$_REQUEST['LNG'],$current_time+ONE_HOUR*ONE_DAY*365, '/', COOKIE_DOMAIN);
}
     
if ($_REQUEST['LNG'] != 'RU' && $_REQUEST['LNG'] != 'EN')
{
	$_REQUEST['LNG']= DEFAULT_LANGUAGE;
}

if (isset ($_GET['AROUND'])) { 
  $_REQUEST['AROUND'] = $_GET['AROUND'] ? 1 : 0;
  setcookie('AROUND',$_REQUEST['AROUND'],$current_time+ONE_HOUR*ONE_DAY*365);
} else if (isset ($_COOKIE['AROUND'])) {
  $_REQUEST['AROUND'] = $_COOKIE['AROUND'] ? 1 : 0;
} else {
  $_REQUEST['AROUND'] = 1;
}

if (isset ($_GET['HOWMANY']) && $_GET['HOWMANY'] > 0) { 
  $HOWMANY = $_GET['HOWMANY'];
  setcookie('HOWMANY',$HOWMANY,$current_time+ONE_HOUR*ONE_DAY*365, '/', COOKIE_DOMAIN);
} else if (isset ($_COOKIE['HOWMANY']) && $_COOKIE['HOWMANY'] > 0) {
  $HOWMANY = $_COOKIE['HOWMANY'];
} else {
  $HOWMANY = isset ($DEFAULT_HOWMANY) ? $DEFAULT_HOWMANY : 100;
}

function secure ($s)
{
	return preg_replace ('@(<[^>]+>)@si', '', mysql_real_escape_string (str_replace ('"', '&quot;', trim ($s))));
}

function mydate ($date)
{
  global $today, $yesterday;
  if ($_REQUEST['LNG'] == 'EN') {
    $BEFORE = 'before';
    $YR = '';
    define('TODAY','today');
    define('YESTERDAY','yesterday');
    define('ON','on ');
  } else {
    $YR = ' г';
    $BEFORE = 'до';
    define('TODAY','сегодня');
    define('YESTERDAY','вчера');
    define('ON','');
  }
  global $months;
  if (!$date)
    return "$BEFORE 03.VIII.2001";

  if ($date == $today)
    return TODAY;
  if ($date == $yesterday)
    return YESTERDAY;
  
  list ($year,$month,$day)= split ("-", $date);
  if ($month == '00')
    return $year . $YR;
  if ($day == '00')
    return $months[ltrim($month,'0')].'.'.$year;
  return ltrim($day,'0').'.'.$months[ltrim($month,'0')].'.'.$year;
}

function compute_kind ($cnt)
{
  $rank = 0;
  while ($cnt >= 1) {
    $rank = $rank + 1;
    $cnt = $cnt / 2;
  }
  return $rank;
}

function get_kind ($cnt)
{
  $RANKS = array('&lt;1','1+','2+','4+','8+','16+','32+',"64+","128+", '256+');
  return $RANKS[compute_kind ($cnt)];
}

function execute_query ($query)
{
	global $MODERATOR_MODE,$MYSQL_LINK;
  $result = @mysql_query ($query);
  if (!$result) { 
    if ($MODERATOR_MODE != 2)
      $query = '&lt;suppressed&gt;';
    die ("При попытке обращения к базе данных по запросу '$query' произошла ошибка: &lt;".mysql_error()."&gt;. Пожалуйста, перешлите это сообщение по адресу <a href='mailto:".ADMIN."?Subject=MURALISTA%20ERROR'>".ADMIN."</a>.");
  } else {
    return $result;
  }
}

function mandatory ($text,$cond=1) {
  if ($cond) {
    return '<font color="red">'.$text.'</font>';
  } else {
    return $text;
  }
}

function MakeRandomPassword ($length = 8) 
{
  $_vowels = array ('a', 'e', 'i', 'o', 'u');   
  $_consonants = array ('b', 'c', 'd', 'f', 'g', 'h', 'k', 'm', 'n','p', 'r', 's', 't', 'v', 'w', 'x', 'z');   
  $_syllables = array ();
  foreach ($_vowels as $v) {
    foreach ($_consonants as $c) {   
      array_push($_syllables,"$c$v");   
      array_push($_syllables,"$v$c");
    }
  }
  
  $newpass = '';
  for ($i=0; $i < ($length / 2); $i++)
    $newpass .= $_syllables[array_rand ($_syllables)];
  
  return $newpass;
}

function authorize ()
{
	global $AUTHOR_NAME;
	$AUTHOR_NAME = isset ($_POST['AUTHOR_NAME'])
			? mysql_real_escape_string ($_POST['AUTHOR_NAME']) : '';
	$id = 'NULL';
	if (isset ($_COOKIE['COOKIE_AUTHOR']) && isset ($_COOKIE['COOKIE_AUTHOR_PASSWORD'])) {
		$pass = mysql_real_escape_string ($_COOKIE['COOKIE_AUTHOR_PASSWORD']);
		$query = "SELECT name,id FROM authors WHERE login='{$_COOKIE['COOKIE_AUTHOR']}' and password='$pass'";
		$result = execute_query ($query);
		if ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
		  $AUTHOR_NAME = $row[0];
		  $id = $row[1];
		}
		mysql_free_result ($result);
	}
	return $id;
}


function canbemoderator ()
{
  global $PICTABLE;
  $__author = isset ($_COOKIE['COOKIE_AUTHOR']) ? 
     $_COOKIE['COOKIE_AUTHOR'] :
    $_REQUEST['AUTHOR'];
  $password = isset ($_COOKIE['COOKIE_AUTHOR_PASSWORD']) ? 
     $_COOKIE['COOKIE_AUTHOR_PASSWORD'] :
    $_REQUEST['AUTHOR_PASSWORD'];
  if ($__author && $password) {
	  $passcond = ($password!=MODERATOR) ? " AND password='".mysql_real_escape_string ($password)."'" : '';
    $query = "SELECT authors.id FROM pg_moderators,authors WHERE pg_moderators_x_authors_id=authors.id AND authors.login='$__author' $passcond AND pg_moderators_maintable='$PICTABLE'";
    $result = execute_query ($query);
    $size = mysql_num_rows ($result);
    mysql_free_result ($result);
    return $size > 0;
  } else 
    return 0;
}

/* Is he a moderator? */
if (canbemoderator ()) {
	$MODERATOR_MODE = 1;
	/* moderator logout */
	if (isset ($_GET['MOD_LOGOUT'])) {
		setcookie('PG_EDITOR','',time()-300);
	} else if (isset ($_GET['MOD_LOGIN']) || isset ($_COOKIE['PG_EDITOR'])) {
		setcookie ('PG_EDITOR','Y');
		$MODERATOR_MODE = 2;
	}
} else {
	$MODERATOR_MODE = 0;
}

if (isset ($OTHER_GALLERY)) {
  define ('RSS_GIF',
	  "<img src='$OTHER_GALLERY"."images/rss.gif' width='27' height='15' hspace='1' border='0'>");
  define ('GM', 
	  "<img src='$OTHER_GALLERY"."images/globe.gif' width='16' height='16' hspace='1'>");
  define ('EYE', 
	  "<img src='$OTHER_GALLERY"."images/eye.gif' width='16' height='16' hspace='1'>");
  define ('COMM', 
	  "<img src='$OTHER_GALLERY"."images/comment.gif' height='17' width='14' border='0'>");
  define ('THUMBUP', 
	  "<img src='$OTHER_GALLERY"."images/thumbup.gif' width='16' height='16' hspace='1'>");
  define ('QMTHUMBUP', 
	  "<img border=0 src='$OTHER_GALLERY"."images/thumbupqm.gif' width='15' height='15' hspace='1'>");
  define ('MAIL_GIF', 
	  "<img src='$OTHER_GALLERY"."images/mail.gif' width='46' height='29' hspace='1' align='left' border='0'>");
  define ('EXIF_IMG',
	  "<img src='$OTHER_GALLERY"."images/exif.gif' width=20 height=16 border=0>");
  define ('GREEN',
	  "<img src='$OTHER_GALLERY"."images/greenball.gif' width=10 height=10 border=0 align=bottom>");
  define ('POD',
	  "<img src='$OTHER_GALLERY"."images/redstar.gif'    width=16 height=15 border=0 hspace=1>");
  define ('POD_B',
	  "<img src='$OTHER_GALLERY"."images/redstarB_.gif'  width=8  height=15 border=0 hspace=1>");
  define ('POD_1',
	  "<img src='$OTHER_GALLERY"."images/redstar1.gif'   width=16 height=15 border=0 hspace=1>");
  define ('POD_1B',
	  "<img src='$OTHER_GALLERY"."images/redstar1B_.gif' width=8  height=15 border=0 hspace=1>");
  define ('POD_C',
          "<img src='$OTHER_GALLERY"."images/redstarC_.gif' width=13  height=15 border=0 hspace=1>");
 define ('POD_1C',
          "<img src='$OTHER_GALLERY"."images/redstar1C_.gif' width=13  height=15 border=0 hspace=1>");
 define ('POD_2C',
          "<img src='$OTHER_GALLERY"."images/redstar2C_.gif' width=13  height=15 border=0 hspace=1>");
  define ('POD_2',
	  "<img src='$OTHER_GALLERY"."images/redstar2.gif'   width=16 height=15 border=0 hspace=1>");
  define ('POD_2B',
	  "<img src='$OTHER_GALLERY"."images/redstar2B_.gif' width=8  height=15 border=0 hspace=1>");

  define ('POD_D',
          "<img src='$OTHER_GALLERY"."images/redstarD.gif' width=16  height=15 border=0 hspace=1>");
  define ('POD_1D',
          "<img src='$OTHER_GALLERY"."images/redstar1D.gif' width=16  height=15 border=0 hspace=1>");
  define ('POD_2D',
          "<img src='$OTHER_GALLERY"."images/redstar2D.gif' width=16  height=15 border=0 hspace=1>");


  define ('BLACKSTAR',
	  "<img src='$OTHER_GALLERY"."images/warning.gif' width=16 height=16 border=0 hspace=1 alt='Предупреждение'>");
}

function show_medals ($gold,$silver,$bronze)
{
  define ('MEDAL_VALUE', 5);
  define ('MEDAL_VALUE2', MEDAL_VALUE*MEDAL_VALUE);
  define ('MEDAL_VALUE3', MEDAL_VALUE*MEDAL_VALUE*MEDAL_VALUE);
  for ($i = 0; $i + MEDAL_VALUE3 <= $gold; $i+=MEDAL_VALUE3) { echo POD_D; }
  for (      ; $i + MEDAL_VALUE2 <= $gold; $i+=MEDAL_VALUE2) { echo POD_C; }
  for (      ; $i + MEDAL_VALUE <= $gold; $i+=MEDAL_VALUE) { echo POD_B; }
  for (      ; $i < $gold; $i++) { echo POD; }
  for ($i = 0; $i + MEDAL_VALUE3 <= $silver; $i+=MEDAL_VALUE3) { echo POD_1D; }
  for (      ; $i + MEDAL_VALUE2 <= $silver; $i+=MEDAL_VALUE2) { echo POD_1C; }
  for (      ; $i + MEDAL_VALUE <= $silver; $i+=MEDAL_VALUE) { echo POD_1B; }
  for (      ; $i < $silver; $i++) { echo POD_1; }
  for ($i = 0; $i + MEDAL_VALUE3 <= $bronze; $i+=MEDAL_VALUE3) { echo POD_2D; }
  for (      ; $i + MEDAL_VALUE2 <= $bronze; $i+=MEDAL_VALUE2) { echo POD_2C; }
  for (      ; $i + MEDAL_VALUE <= $bronze; $i+=MEDAL_VALUE) { echo POD_2B; }
  for (      ; $i < $bronze; $i++) { echo POD_2; }
}

function penalize ($author, $penalty, $absolute = false) 
{
	global $PICTABLE;
	if ($absolute)
		execute_query ("UPDATE {$PICTABLE}_reputation SET reputation=LEAST(0,reputation+($penalty)) WHERE author=$author");
	else
		execute_query ("UPDATE {$PICTABLE}_reputation SET reputation=reputation+($penalty) WHERE author=$author");
	if (mysql_affected_rows () <= 0)
		execute_query ("INSERT INTO {$PICTABLE}_reputation values ($author,$penalty)");
}

function warnings ($author)
{
	$warnquery =  "SELECT SUM(severity) FROM pg_warning WHERE authors_x_id=$author AND DATEDIFF(NOW(),date)<".WARNING_DURATION;
	$warnresult = execute_query ($warnquery);
	$warncount = mysql_fetch_array ($warnresult, MYSQL_NUM);
	mysql_free_result ($warnresult);
	return $warncount[0];
}

$FUNCTIONS = 1;

?>
