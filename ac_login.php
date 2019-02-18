<?
require_once ('pg_db.php');
require_once ('functions.php');
require ($_REQUEST['LNG'].'/ac_login.php');

$PAGE = $REGISTER_LOGIN;
$SELECTED = 4;
include ('pg_head.php');

$login = secure ($_REQUEST['AUTHOR_LOGIN']);
if ($_REQUEST['AUTHORR']) {
	$name = secure ($_REQUEST['AUTHORR']);
} else {
	$name = $login;
}
$email = secure (strtolower ($_REQUEST['EMAIL']));
	
echo '<ul>';
if (isset ($_REQUEST['LOGIN_REMIND'])) {
  $query = "select email,password from authors where login='$login'";
  $result = execute_query ($query);
  $author_info = mysql_fetch_array ($result, MYSQL_NUM);
  mysql_free_result ($result);

  mail ($author_info[0], "Your password at Picture Gallery", 
	THANKS_FOR_REGISTERING.
	"$LOGIN_PROMPT: $login, $PWD_PROMPT: ".$author_info[1]."\n\n--\n$WEBSITE_DESCR\n\n".GHOST,
 	smtp_headers (ADMIN));
  echo "<li><font color='red'>$PASSWORD_SENT</font>\n";

/*
		echo "<li><font color='red'>Извините, эта функция временно недоступна.</font>\n";
*/
} else if (isset ($_REQUEST['LOGIN_CREATE'])) {
  if (!$login || !$email) {
    echo '<li><font color="red">'.$BAD_LOGIN_OR_EMAIL.'</font>';
  } else {
	  if (is_numeric ($login))
		  $login = 'login'.$login;
    $i = '';
    do {
      $query = "select id from authors where login='$login$i'";
      execute_query ($query);
      $result = execute_query ($query);
      $row = mysql_fetch_array ($result, MYSQL_NUM);
      mysql_free_result ($result);
      $i = $i + 1;
    } while ($row);

    $i = $i - 1;
    if ($i) {
      printf ('<li><font color="red">'.$BAD_LOGIN.'</font>', $login.$i);
    } else {
	$query = "select login from authors where email='$email'";
      execute_query ($query);
      $result = execute_query ($query);
      $row = mysql_fetch_array ($result, MYSQL_NUM);
      mysql_free_result ($result);

      if ($row) {
	printf ('<li><font color="red">'.$BAD_EMAIL.'</font>', $row[0]);
      } else {	
	$pass = MakeRandomPassword ();
	if (isset ($_REQUEST['HIDE_EMAIL']))
	  $show_email = 0;
	else
		$show_email = 1;
	$query = "INSERT INTO authors VALUES (NULL, '$name', NULL, NULL, NULL, NULL, '$email', '$pass', '$login', '$show_email', 'N', NULL, now(), now())";
	execute_query ($query);
	$_REQUEST['AUTHOR'] = mysql_insert_id ();
	printf ('<li><font color="'.$WARNING_COLOR.'">'.$CONGRATS_NEW_ACCT.'</font>', $login, $email);
	
	mail (ADMIN, "New User at Picture Gallery",
	      "Name: $name\nEmail: $email\nLogin: $login\nPassword: $pass\nIP Address: ${_SERVER['REMOTE_ADDR']}\nURL: ".GHOST."?AUTHOR=${_REQUEST['AUTHOR']}\n\n",
       	smtp_headers (ADMIN));
	mail ($email, "Your password at Picture Gallery",
	      "Login: $login, password: $pass\n\n", smtp_headers (ADMIN));
      }
    }
  }
}
?>

<li><? echo $ONLY_REGISTERED ?>
<?
if (!$_REQUEST['AUTHOR']) 
     unset ($_REQUEST['AUTHOR']);
if ($BADPASSWORD == 1) {
  echo '<li>'.$PWD_DOES_NOT_MATCH;
} elseif ($BADPASSWORD == 2) {
  echo '<li>'.$ENTER_PWD;
}
?>
<li><? echo $FORGOT_PASSWORD ?>
</ul>

<p>
<center><table><tr>
<td valign="top" width=50%>
<fieldset><legend><? echo $REGISTERED_ENTRANCE ?></legend>
<form action='ac_authorize.php' method='POST'>
<table border=0>
<tr><td><? echo $LOGIN_PROMPT ?>: <td><input NAME="AUTHOR" value="<? echo $login ?>"><td>
<tr><td><? echo $PWD_PROMPT ?>: <td><input type="password" name="AUTHOR_PASSWORD" maxlength="64"><td>
<tr><td colspan="2"><? echo $REMEMBER_ME ?> 
<input type="checkbox" name="AUTHOR_REMEMBER">
<td><input type=image src="images/rightarrow.png" width="17" height="20" align="middle" name="LOGIN" value='1'>
</form>
</table>
</fieldset>
<td valign="top">
<form action='ac_login.php' method='POST'>
<fieldset><legend><? echo $REGISTER_NEW ?></legend>
<table border=0>
		<tr><td width=50%>

<?
if (ALLOW_NEW_AUTHORS==true) {
  echo "$LOGIN_PROMPT: ";
  echo "<td><input name='AUTHOR_LOGIN' maxlength='32' value='$login'>\n";
  echo "<td>E-mail: \n";
  echo "<td><input NAME='EMAIL' value='$email'>\n";
  echo "<tr>\n";
  echo "<td>$NAME_PROMPT: \n";
  echo "<td><input NAME='AUTHORR' maxlength='32' value='$name'>\n";
  $checked = isset ($_POST['HIDE_EMAIL']) ? 'checked' : '';
  echo "<td colspan='2'><input type='checkbox' $checked name='HIDE_EMAIL'> $DONTSHOWEMAIL\n";
  echo "<tr><td colspan='4'>$BLANC_FIELDS!\n";
  echo "<input type=image src='images/rightarrow.png' width='17' height='20' align='middle'>\n";
  echo "<INPUT TYPE='HIDDEN' NAME='LOGIN_CREATE' VALUE='1'>\n";
} else {
  echo NO_NEW_AUTHORS;
}
?>

</table>
</fieldset>
</form>

<tr><td valign="top">
<fieldset><legend><? echo $FORGET_ENTRANCE ?></legend>
<form action='ac_authorize.php' method='POST'>
<table border=0>
<center>
<td><? echo $LOGIN_PROMPT ?>: 
<td><input NAME="AUTHOR_LOGIN"><input type=hidden NAME="LOGIN_REMIND">
<td><input type=image src="images/rightarrow.png" width="17" height="20" align="middle">
</form>
</table>
</fieldset>
<td valign="top"><? if (ALLOW_NEW_AUTHORS==true) {echo $NO_DELETE;} ?>
</table></center>

<?
include ('pg_tail.php');
?>

