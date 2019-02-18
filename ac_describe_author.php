<?
$query = "SELECT name,bio,foto_url,email,password,show_email,id,allow_vote,UNIX_TIMESTAMP(authors_modified) FROM authors where login='$login'";
$result = execute_query ($query);
$row = mysql_fetch_array ($result, MYSQL_NUM);
mysql_free_result ($result);

$name = stripslashes ($row[0]);
$bio = stripslashes ($row[1]);
$PICT = ($row[2] ? $row[2] : 'NULL/dummy');
$email = stripslashes ($row[3]);
require ('warntable.php');
?>
<center>
<table cellpadding="1" cellspacing="0" border="0" width='95%' bgcolor=<? echo $LOGO_COLOR ?>><tr><td>
<?
echo "<font color='$SECOND_COLOR'><b>".UPDATED.":</b>\n".mydate (date('Y-m-d', $row[8]))."</font>\n"; 
?>
<tr><td><table cellpadding="5" cellspacing="0" border="0" width='100%'>
<tr bgcolor=<? echo $UNPUBLISHED_COLOR ?>><td valign="top" align="center" rowspan=4>
<form action='<? echo $PHP_SELF ?>?ACTION=ac_author_mod.php' method='POST'>
<img src='<? echo $OTHER_GALLERY.IC_BASE.$PICT ?>-s.jpg' hspace='5' vspace='5' alt='<? echo $row[0] ?>'>
<? 
if ($row[2]) {
  echo '<br><input type="submit" value="'.$DELPICT.'">';
}
?>
<input type="hidden" NAME="AUTHOR" value="<? echo $login ?>">
<input type="hidden" NAME="DELFOTO" value="1">
</form>
<form action='<? echo $PHP_SELF ?>?ACTION=ac_author_mod.php' method='POST' enctype='multipart/form-data' name='form'>

<?
echo "<td bgcolor='$FOURTH_COLOR'>$NAME:<td colspan=2 bgcolor='$FOURTH_COLOR'><input name='AUTHORR' size=32 maxlength='64' value='$name' readonly='readonly'>\n";
echo '<tr valign="top" bgcolor="'.$FOURTH_COLOR.'"><td colspan=3>'.NAME_NOT_LOGIN;
echo '<tr valign="top" bgcolor="'.$FOURTH_COLOR.'">';
echo '<td>'.$CREDO.':<td colspan=2><TEXTAREA NAME="CREDOR" ROWS=5 cols=48>'.$bio.'</TEXTAREA>';
if (!$row[5]) {
  $hide_checked='checked';
}
if ($row[7]=='Y') {
  $vote_checked='checked';
}
echo '<tr bgcolor="'.$FOURTH_COLOR.'"><td>E-mail:<td><input name="EMAIL" size=32 maxlength="128" value="'.$email.'"><td><input type="checkbox" name="HIDE_EMAIL" '.$hide_checked.'>'.$DONTSHOWEMAIL;
echo '<tr bgcolor="'.$UNPUBLISHED_COLOR.'"><td colspan=3>'.$PICT_SPECS;
echo '<td valign=top bgcolor="'.$FOURTH_COLOR.'"><input type="checkbox" name="NO_VOTE" '.$vote_checked.'>'.$DONTVOTE;

echo '<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />';
echo '<tr><td bgcolor="'.$UNPUBLISHED_COLOR.'">'.$PICTENTRY.':<td  bgcolor="'.$UNPUBLISHED_COLOR.'" colspan=2><INPUT TYPE=FILE NAME="IMAGE" SIZE=32>';
echo '<td align="right" bgcolor="'.$FOURTH_COLOR.'">'.$CHANGEENTRY.' <input type=image src="images/rightarrow.png" width="17" height="20" align="middle">';
echo '<input type="hidden" NAME="AUTHOR" value="'.$login.'">';
?>

<input type="hidden" NAME="CHANGE" value="1">
</form>
<?
echo "<tr bgcolor='$OLD_COLOR'><form action='$PHP_SELF?ACTION=ac_author_mod.php' method='POST'>";
echo '<td align="right" valign="top" colspan=2>'.$NEWPASSENTRY.' ('.$SUGGESTED_PASSWORD.', <b>'.MakeRandomPassword ().'</b>): ';
echo '<td valign="top"><input type="password" size=32 name="NEW_USER_PASSWD" maxlength="64"> ';

echo '<td valign="top" align=right>'.$CHANGEENTRY.' <input type=image src="images/rightarrow.png" width="17" height="20" align="middle">';
echo '<input type="hidden" NAME="AUTHOR" value="'.$login.'">';
echo '</form>';

if (warntable ($row[6]))
	echo "<td bgcolor='$UNPUBLISHED_COLOR'>&nbsp;\n";
?>
</table>
</table>
</table>
</center>
