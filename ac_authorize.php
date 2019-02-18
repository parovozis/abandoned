<?

require ('pg_db.php');
require ('functions.php');

if (!isset ($_REQUEST['ACTION'])) {
  $_REQUEST['ACTION'] = 'ac_post_mod.php';
}

if (isset ($_POST['AUTHOR'])) {
  $_REQUEST['AUTHOR'] = $_POST['AUTHOR'];
  if (isset ($_REQUEST['AUTHOR_REMEMBER']))
    setcookie ('COOKIE_AUTHOR',$_REQUEST['AUTHOR'],time()+ONE_HOUR*ONE_DAY*365,
	       '/', COOKIE_DOMAIN);
  else
    setcookie ('COOKIE_AUTHOR',$_REQUEST['AUTHOR'],0,
	       '/', COOKIE_DOMAIN);
} else if (isset ($_GET['AUTHOR'])) {
  $_REQUEST['AUTHOR'] = $_GET['AUTHOR'];
} else if (isset ($_COOKIE['COOKIE_AUTHOR'])) {
  $_REQUEST['AUTHOR'] = $_COOKIE['COOKIE_AUTHOR'];
}

if (isset ($_POST['AUTHOR_PASSWORD'])) {
  $_REQUEST['AUTHOR_PASSWORD'] = $_POST['AUTHOR_PASSWORD'];
  if (isset ($_REQUEST['AUTHOR_REMEMBER']))
    setcookie('COOKIE_AUTHOR_PASSWORD',$_REQUEST['AUTHOR_PASSWORD'],time()+ONE_HOUR*ONE_DAY*365, '/', COOKIE_DOMAIN);
  else
    setcookie('COOKIE_AUTHOR_PASSWORD',$_REQUEST['AUTHOR_PASSWORD'], 0, '/', COOKIE_DOMAIN);
} else if (isset ($_COOKIE['COOKIE_AUTHOR_PASSWORD'])) {
  $_REQUEST['AUTHOR_PASSWORD'] = $_COOKIE['COOKIE_AUTHOR_PASSWORD'];
}

$PAGE = $AUTHORS_CENTER;

if (!isset ($_REQUEST['LOGOUT']) && $_REQUEST['AUTHOR']) { /* He is a guy. Is he a right one? */
  $_REQUEST['AUTHOR']=str_replace('"','&quot;',$_REQUEST['AUTHOR']);
  $query = 'select password,id from authors where login="'.$_REQUEST['AUTHOR'].'" and not isnull(password)';
  $result = execute_query ($query);
  $row = mysql_fetch_array ($result, MYSQL_NUM);  
  if ($row) {
    $_REQUEST['AUTHOR_ID'] = $row[1]; // !!!!
    $author_id = $row[1];
    $real_password = $row[0];
    if (   $real_password != $_REQUEST['AUTHOR_PASSWORD'] 
	&& MODERATOR != $_REQUEST['AUTHOR_PASSWORD']) {
      if ($_REQUEST['AUTHOR_PASSWORD'])
	$BADPASSWORD = 1;
      else
	$BADPASSWORD = 2;
      setcookie('COOKIE_AUTHOR_PASSWORD',FALSE,time()-300, '/', COOKIE_DOMAIN);
      $_REQUEST['AUTHOR'] = '';
      $_REQUEST['AUTHOR_PASSWORD'] = '';
      require ('ac_login.php');
    } else { /* He is the right guy! */
	     $query = "UPDATE authors SET last_login=NOW() WHERE id=$author_id";
	     execute_query ($query);
	     require ($_REQUEST['ACTION']);
    }
  } else {
    $BADPASSWORD = 1;
    setcookie('COOKIE_AUTHOR_PASSWORD',FALSE,time()-300, '/', COOKIE_DOMAIN);
    $_REQUEST['AUTHOR'] = '';
    $_REQUEST['AUTHOR_PASSWORD'] = '';
    require ('ac_login.php');
  }
} else {
  $BADPASSWORD = 0;
  setcookie('COOKIE_AUTHOR_PASSWORD',FALSE,time()-300, '/', COOKIE_DOMAIN);
  setcookie('COOKIE_AUTHOR',FALSE,time()-300, '/', COOKIE_DOMAIN);
  $_REQUEST['AUTHOR'] = '';
  $_REQUEST['AUTHOR_PASSWORD'] = '';
  $MODERATOR_MODE = 0;
  setcookie('PG_EDITOR','',time()-300);
  require ('ac_login.php');
}

?>
