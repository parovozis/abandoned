<?php
define ('AA_NCOLS', 5);
define ('AA_WIDTH', (100/AA_NCOLS));
$QUERY = "SELECT id,name,(login LIKE '%$search_author%'),login FROM authors WHERE name LIKE '%$search_author%' OR login LIKE '%$search_author%' ORDER BY name,login";
$result = mysql_query ($QUERY);
$size = mysql_num_rows ($result);
if ($size) {
	unset($_REQUEST['AUTHOR']);
	if ($cat == NEW_CAT) $cat = '';
	$cndline = buildcmdl ();
	echo "<h2>".CLEARER."</h2>\n";
	echo WE_FOUND." &laquo;$search_author&raquo;:\n";
	echo '<p><table width=100% border=0><tr><td valign=top width='.AA_WIDTH.'%><ul>';
	$i = 0;
	while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
		echo "<li><a href=$cndline&AUTHOR=$row[0]&AUTHOR_ID=$row[0]>$row[1]</a>";
		if ($row[2])
			echo SIMILAR_LOGIN;
		if ($MODERATOR_MODE == 2)
			echo " <font color=$THIRD_COLOR>($row[3]/№$row[0])</font>";
		echo "\n";
		$i++;
		if ($i >= $size / AA_NCOLS) {
			echo '</ul><td valign=top width='.AA_WIDTH.'%><ul>';
			$i = 0;
		}
	}
	echo '</ul></tr></table>';
	mysql_free_result ($result);
} else {
	echo '<h2>'.SORRY.'</h2>';
	echo NO_SUCH_AUTHOR." &laquo;$search_author&raquo;.\n";
}
include ("pg_tail.php");
exit();
?>