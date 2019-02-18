<?
require ($_REQUEST['LNG'].'/ac_post_mod.php');
require ($_REQUEST['LNG'].'/ac_author_mod.php');
require ($_REQUEST['LNG'].'/ac_login.php');

$SELECTED = 4;
include ('pg_head.php');

$AC_MENU_ITEM=2;
$AC_MENU_INTRO=$WELCOME2AUTHOR;
require ('ac_menu.php');

/* Real thing begins here */
$login = secure ($_REQUEST['AUTHOR']);
if (isset ($_REQUEST['NEW_USER_PASSWD'])) {
	$pass = trim ($_REQUEST['NEW_USER_PASSWD']);
	if (strlen ($pass) >= 6) {
		$query = "UPDATE authors SET password='$pass' where login='$login'";
		execute_query ($query);
	
		$query = "SELECT email FROM authors WHERE login='$login'";
		$result = execute_query ($query);
		$row = mysql_fetch_array ($result, MYSQL_NUM);
		$email = $row[0];
		mysql_free_result ($result);

		mail ($email, "Your password at Picture Gallery", "Your new password at picture gallery is: $pass\n\n", smtp_headers (ADMIN));
	} else {
		echo '<li><b><font color="red">'.$BADPASSWORD.'</font></b><p>';
	}
} elseif (isset($_REQUEST['DELFOTO'])) {
	$query = "SELECT id FROM authors WHERE login='$login'";
	$result = execute_query ($query);
	$row = mysql_fetch_array ($result, MYSQL_NUM);
	mysql_free_result ($result);
	$fname = "____$row[0]-s.jpg";
	
	$query = "UPDATE authors SET foto_url=NULL where login='$login'";
	execute_query ($query);

	unlink (PG_BASE.$fname);
	unlink (IC_BASE.$fname);
	echo '<li><b><font color="'.$WARNING_COLOR.'">'.$SUCCESS.'</font></b><p>';
} elseif (isset($_REQUEST['CHANGE'])) {
	/* APPLY CHANGES */
	$query = "SELECT id FROM authors WHERE login='$login'";
	$result = execute_query ($query);
	$row = mysql_fetch_array ($result, MYSQL_NUM);
	mysql_free_result ($result);
	
	$fname = '____'.$row[0];
	if ($_FILES['IMAGE']['tmp_name']
		&& move_uploaded_file ($_FILES['IMAGE']['tmp_name'],SCRATCHPATH.$fname.'.JPG')) {
		if (!system ("./prepare-nofilt $fname 0 0 NULL", $retval) && $retval) {
			echo "<li>$fname.JPG: error $retval...";
			echo '<b><font color="red">'.$COULDNOTCHANGEPICT.'</font></b><p>';
		} else {
			$NEW_IMAGE = ",foto_url='NULL/$fname'";
		}
	}
	
	$name = secure ($_REQUEST['AUTHORR']);
	if (!name)
		$name = $login;
	$bio = secure ($_REQUEST['CREDOR']);
	$email = secure (strtolower($_REQUEST['EMAIL']));

	$SHOW_EMAIL = isset ($_REQUEST['HIDE_EMAIL']) ? 0 : 1;
	$DONT_ALLOW_VOTE = isset ($_REQUEST['NO_VOTE']) ? 'Y' : 'N';
	$query = "UPDATE authors SET name='$name',bio='$bio',show_email='$SHOW_EMAIL',allow_vote='$DONT_ALLOW_VOTE' $NEW_IMAGE where login='$login'";
	execute_query ($query);

	if (!eregi ("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$", $email)) {
		echo '<li><b><font color="red">'.sprintf($COULDNOTCHANGEEMAIL1,$email).'</font></b>';
	} else {
		$query = "SELECT email FROM authors WHERE login='$login'";
		$result = execute_query ($query);
		$row = mysql_fetch_array ($result, MYSQL_NUM);
		$oldemail = $row[0];
		mysql_free_result ($result);

		$query = "UPDATE authors SET email='$email' where login='$login'";
		if (!@mysql_query ($query)) {
			if (mysql_errno() != 1062)
				die ("$query: Query failed");
			else {
				$query = "SELECT login FROM authors WHERE email='$email'";
				$result = execute_query ($query);
				$row = mysql_fetch_array ($result, MYSQL_NUM);
				mysql_free_result ($result);
				echo '<li><b><font color="red">'.sprintf($COULDNOTCHANGEEMAIL,$email,$row[0]).'</font></b>';
			}
		} else {
			echo '<li><b><font color="'.$WARNING_COLOR.'">'.$SUCCESS.'</font></b>';
			if ($oldemail != $email) {
				$pass = MakeRandomPassword ();
				$query = "UPDATE authors SET password='$pass' where login='$login'";
				execute_query ($query);
				mail ($email, "Your password at Picture Gallery", "Your new password at picture gallery is: $pass\n\n", smtp_headers (ADMIN));
			}
		}
	}
} 
end_menu ();

include ('ac_describe_author.php');
include ('pg_tail.php');
?>
