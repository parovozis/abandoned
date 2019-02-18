<?
function warntable ($who)
{
	global $FOURTH_COLOR, $LOGO_COLOR, $UNPUBLISHED_COLOR, $OLD_COLOR;
	$warnquery =  "SELECT name,severity,date,email,ADDDATE(date,".WARNING_DURATION.") FROM pg_warning,authors WHERE pg_moderators_id=authors.id AND authors_x_id='$who' AND DATEDIFF(NOW(),date)<".WARNING_DURATION.' ORDER BY date DESC';
	$warnresult = execute_query ($warnquery);
	$num_rows = mysql_num_rows ($warnresult);
	if ($num_rows > 0) {
		$summa = 0;
		echo "<tr bgcolor='$FOURTH_COLOR'><td colspan=3><font color='$LOGO_COLOR'><b>Мои предупреждения:</b></font>";
		echo "<table cellpadding=5 border=1 cellspacing=0 bgcolor='$UNPUBLISHED_COLOR'><tr><th align=left>Кем вынесено<th align=left>Дата<th align=left>Баллов<th align=left>Истекает\n";
		while ($warnrow = mysql_fetch_array ($warnresult, MYSQL_NUM)) {
			$date1 = explode (' ', $warnrow[2]);
			$date2 = explode (' ', $warnrow[4]);
			echo "<tr><td><a href=mailto:$warnrow[3]>$warnrow[0]</a><td>$date1[0]<td>$warnrow[1]<td>$date2[0]";
			$summa += $warnrow[1];
}
		mysql_free_result ($warnresult);
		echo "<tr bgcolor='$OLD_COLOR'><td colspan=3>\n";
		if (WARNING_COMMENT_THRESHOLD-$summa > 0)
			echo "Я могу получить штрафных баллов до запрета на комментарии:<td>".(WARNING_COMMENT_THRESHOLD-$summa);
		else
			echo "<b>Мне запрещено оставлять комментарии</b>, пока не истечёт штрафных баллов:<td>".($summa-WARNING_COMMENT_THRESHOLD+1);
		echo "<tr bgcolor='$OLD_COLOR'><td colspan=3>\n";
		if (WARNING_POST_THRESHOLD-$summa > 0)
			echo "Я могу получить штрафных баллов до запрета на публикацию фотографий:<td>".(WARNING_POST_THRESHOLD-$summa);
		else
			echo "<b>Мне запрещено публиковать фотографии</b>, пока не истечёт штрафных баллов:<td>".($summa-WARNING_POST_THRESHOLD+1);
		echo "</table>\n";
		return true;
	} else
		return false;
}
?>
