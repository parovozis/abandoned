<?
define (APPROVED, $self[6]);
define (AUTHOR_ID, $self[7]);
define (COMPETING, $self[8]);
define (APPROVED_BY, $self[13]);
?>

<script type="text/javascript">
function check()
{
	for (i = 1; i < <? echo count($REASON) ?>; i++)
		if (document.getElementById("REASON"+i).checked) return true;
	alert ("Укажите по крайней мере одну причину удаления!");
	return false;
}
</script>

<?
function explainWhy ()
{
	global $REASON, $PICTABLE, $EXPLAIN_WHY, $ID;
?>

Обязательно укажите причину удаления и при необходимости дайте сопроводительный комментарий (сообщение с указанием причины и с комментарием будет отправлено автору от лица коллектива Галереи по электронной почте):
<p>
<table width="100%" border="0"><tr>
<th width="50%" valign="top" align="left">Причина удаления (можно выбрать несколько причин)
<th width="50%" valign="top" align="left">Краткий комментарий (не обязателен)
<tr><TD valign="top">
<?
	$remark = '';
	$checked = 0;
	if (COMODERATION) {
		$query = "SELECT violations,remark FROM {$PICTABLE}_request4deletion WHERE picture_id=$ID";
		$result = execute_query ($query);
		$story = mysql_fetch_array ($result, MYSQL_NUM);
		mysql_free_result ($result);
		if ($story) {
			$checked = $story[0];
			$remark = $story[1];
		}
	}

	for ($i = 0; $i < count ($REASON); $i++) {
		$selected = $checked ? (($checked % 2 == 1) ? 'checked' : '')
			: ($i ? '' : 'checked');
		$checked /= 2;
		$msg2mod = $i ? '' : ('(<b><font color=red>'.$EXPLAIN_WHY.'</font></b>)');
		echo "<input type=checkbox name=REASON$i id=REASON$i value='$i' $selected>$REASON[$i] $msg2mod<br>\n";
	}
?>
<td valign="top">
<textarea rows="5" cols="50" name="RMCOMMENT">
<? echo $remark; ?>
</textarea>
</table><p>
<?
}

if (APPROVED != 'Y') { // approved?
	echo '<h3>Публикация</h3>';
	if (COMPETING == 'Y') { // competing?
		$i = 1;
		while (1) {
			$date = date ("d", $current_time + ONE_HOUR * ($i * ONE_DAY - POD_OFFSET));
			if (SPECIAL_DATE == "SPECIAL_DATE" || ($date % SPECIAL_DATE) != 0) { /* publish only old pics on that date! */
				$when = date ("Y-m-d", $current_time + ONE_HOUR * ($i * ONE_DAY - POD_OFFSET));
		
				$query="SELECT COUNT(*)<$DEFAULT_HOWMANY-".HOW_MANY_NEW." FROM $PICTABLE WHERE senton='$when' AND approved='Y' AND competing='Y' AND votedon=senton";
				$result = execute_query ($query);
				$available = mysql_fetch_array ($result, MYSQL_NUM);
				mysql_free_result ($result);
		
				$query="SELECT COUNT(*)<".HOW_MANY_MINE." FROM $PICTABLE WHERE senton='$when' AND approved='Y' AND competing='Y' AND votedon=senton AND author_id=$author";
				$result = execute_query ($query);
				$mine_on_that_day = mysql_fetch_array ($result, MYSQL_NUM);
				mysql_free_result ($result);
		
				if ($available[0] && $mine_on_that_day[0])
	  				break;
			}
		$i++;
		}
	} else {
		$when = $tomorrow;
	}

	echo "<form action='$PHP_SELF' method='POST'>";
	echo 'Это фото ещё не опубликовано. ';
	echo '<INPUT TYPE=SUBMIT VALUE=" ОПУБЛИКОВАТЬ '.mydate($when).'">';
	echo '<INPUT TYPE=HIDDEN NAME="APPROVE_ID" VALUE="'.$when.'">';
	echo "<INPUT TYPE=HIDDEN NAME='ID' VALUE='$ID'>";
	echo '</form><p>';
} else if (APPROVED_BY != $author_id && APPROVED_BY != AUTHOR_ID) { // Пропущена, но не мной!
	echo '<h3>Удаление</h3>';
	$query = "SELECT name,email FROM authors WHERE id=".APPROVED_BY;
	$result = execute_query ($query);
	$colleague = mysql_fetch_array ($result, MYSQL_NUM);
	mysql_free_result ($result);
	echo "Если вы считаете, что эту фотографию нужно удалить, <a href='mailto:{$colleague[1]}'>свяжитесь</a> с модератором <b>{$colleague[0]}</b>, который её пропустил.</font>\n";
	return;
}

echo '<h3>Удаление</h3>';
echo "<form action='$PHP_SELF' method='POST'><font color='$LOGO_COLOR'>\n";

if (APPROVED != 'A' && COMODERATION) {
?>
Если Вы считаете, что эта фотография не соответствует правилам фотогалереи, представьте её к удалению. Фотография будет удалена, если с Вашим мнением согласится ещё один модератор.
<?
	if (APPROVED == 'Y') {
		$remove_message = 'Эта фотография уже опубликована. Имейте в виду, что удалять уже опубликованные фотографии крайне нежелательно!';
	} else {
		$remove_message = '';
	}

	explainWhy ();
?>
	</font>
	<p><INPUT TYPE=SUBMIT VALUE=' ПРЕДСТАВИТЬ К УДАЛЕНИЮ ' onclick="javascript:return check()&&confirm('Вы уверены в том, что хотите ПРЕДСТАВИТЬ К УДАЛЕНИЮ эту фотографию? <? echo $remove_message ?>')">

<?
} elseif (APPROVED != 'A' || APPROVED_BY != $author_id) {
	if (COMODERATION) {
		$query = "SELECT name,email FROM authors WHERE id=".APPROVED_BY;
		$result = execute_query ($query);
		$colleague = mysql_fetch_array ($result, MYSQL_NUM);
		mysql_free_result ($result);
		echo "Модератор <a href='mailto:{$colleague[1]}'><b>{$colleague[0]}</a></b> предложил(а) удалить эту фотографию. Если Вы согласны с ним (с ней), удалите фотографию.";
	} else {
?>
		В этой фотогалерее модератор может удалить фото единолично.
<?
	}
	if (APPROVED == 'Y') {
		$remove_message = 'Эта фотография уже опубликована. Имейте в виду, что удалять уже опубликованные фотографии крайне нежелательно!';
	} else {
		$remove_message = '';
	}

	explainWhy ();
?>
	</font>
<?
	if (COMODERATION && APPROVED != 'A')
		$del_message = 'ПРЕДСТАВИТЬ К УДАЛЕНИЮ';
	else
		$del_message = 'УДАЛИТЬ (С ОТСЫЛКОЙ УВЕДОМЛЕНИЯ АВТОРУ)';
?>
	<p><INPUT TYPE=SUBMIT VALUE=' <? echo $del_message ?> ' onclick="javascript:return check()&&confirm('Вы уверены в том, что хотите <? echo $del_message ?> эту фотографию? <? echo $remove_message ?>')">
<?
} else {
?>
	Вы уже представили эту фотографию к удалению.</font>
<?
}


echo "<INPUT TYPE=HIDDEN NAME='ID' VALUE='$ID'>\n";
if (isset ($_REQUEST['FROM']))
	echo "<INPUT TYPE=HIDDEN NAME='FROM' VALUE='{$_REQUEST['FROM']}'>\n";
echo "<INPUT TYPE=HIDDEN NAME='RMID' VALUE='1'>\n";
echo "<INPUT TYPE=HIDDEN NAME='URL' VALUE='{$_REQUEST['URL']}'>\n";

echo '</form><p>';
?>
