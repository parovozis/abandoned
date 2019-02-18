<p><table cellpadding="0" cellspacing="6" border="0"><tr>
<td width="136" valign="top"><b><? echo $AUTHORS_CENTER ?>:</b>
<td valign="top">

<?
$items = array (array ('ac_post_mod.php', $ADD_PICTURE),
		array ('ac_edit_mod.php', $EDIT_UNPUBLISHED),
		array ('ac_author_mod.php', $EDIT_LOGIN));
if ($MODERATOR_MODE == 2) {
  array_push ($items, array ('setup_mod.php', 'SETUP'));
}
for ($i = 0; $i < count ($items); $i++) {
  $item = $items[$i];
  if ($i == $AC_MENU_ITEM) {
    echo "<font color='$THIRD_COLOR'>$item[1]</font>";
  } else {
    echo "<a href='ac_authorize.php?ACTION=$item[0]'><b>$item[1]</b></a>";
  }
  echo ' <b>|</b> ';
}
?>

<b>::</b>
<tr><td>&nbsp;
<td><font color="<? echo $THIRD_COLOR ?>"><? echo $AC_MENU_INTRO; ?></font>  
<tr><td>&nbsp;<td>
<ul>

<?
function end_menu ()
{
  echo '</ul></table><p>';
}
?>
