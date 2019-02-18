<?
error_reporting(E_ERROR | E_PARSE);
require ("pg_db.php");
require ("functions.php");
require ("setup.php");
?>
		
<h2>Модераторы</h2>
<?php title ('Q9999', 'Кто является модераторами &laquo;Галереи&raquo;?' ) ?>
<p><table cellpadding=3 cellspacing=0 border=0>
<tr><Th>Модератор<th>Адрес
<!-- <th>Производительность* -->
		
<?
$query = "SELECT id,name,changed,approved,deleted,email FROM authors,pg_moderators WHERE pg_moderators_maintable='$PICTABLE' AND id=pg_moderators_x_authors_id ORDER BY changed+approved+deleted DESC,name";
$result = execute_query ($query);
while ($moder = mysql_fetch_array ($result, MYSQL_NUM)) {
	echo "<tr><td><a href=index.php?AUTHOR=$moder[0]>$moder[1]</a> <td>". str_replace('@','<img src='.$OTHER_GALLERY.'images/at.gif width=12 height=13 border=0 vspace=0 hspace=0>',str_replace('.','&middot;',$moder[5]))//."<td><tt><font color=brown>$moder[2]</font>/<font color=green>+$moder[3]</font>/<font color=red>-$moder[4]</font></tt>"
;
}
mysql_free_result ($result);
?>
		
</table><p>
<!-- *Число исправленных, утверждённых и удалённых фотографий. -->
