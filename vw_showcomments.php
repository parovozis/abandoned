 <?php
define ('QUOTE_IMG','<img src="'.$OTHER_GALLERY.'images/quote.png" height="15" width="14" border="0" align="top">');

$_vowels = array ('а', 'о', 'у', 'е', 'и');   
$_consonants = array ('б','в','г','д','ж','з','к','л','м','н','п','р','с','т','ц','ч','ш');   
$_syllables = array ();
foreach ($_vowels as $v) {
	array_push($_syllables,"$v");
	foreach ($_consonants as $c) {   
		array_push($_syllables,"$c$v");   
	}
}

$_vowels1 = array ('А', 'О', 'У', 'Е', 'И','Э','Я','Ю');   
$_consonants1 = array ('Б','В','Г','Д','Ж','З','К','Л','М','Н','П','Р','С','Т','Ч','Ш');   
$_syllables1 = array ();
foreach ($_vowels1 as $v) {
	array_push($_syllables1,"$v");
}
foreach ($_vowels as $v) {
	foreach ($_consonants1 as $c) {   
		array_push($_syllables1,"$c$v");   
	}
}

$_suffixes = array ('ренко','вьев','рев','нов','кин','вец','зин','кевич','нович','нюк','дзе','швили','нян','нович','йтис','рия','баев','ханов','беков','неску','нер','лиев','вский','лава');
	
function ip2name ($ip) 
{
	if (!$ip) {
		return 'вообще неизвестно, кто';
	}
	global $_syllables1, $_syllables, $_suffixes, $_consonants1;
	$name = md5 ($ip);
  
	$length = ord ($name[0]) % 4 + 1;
	$ret = $_syllables1[(ord($name[1])*ord($name[2]))%88];
	for ($i = 0; $i < $length; $i+=2) {
		$ret .= $_syllables[(ord($name[$i+3])*ord($name[$i+4]))%85];
	}
	
	$ret .= $_suffixes[(ord($name[$length+5])*ord($name[$length+7]))%24].' '.$_consonants1[(ord($name[$length+6])*ord($name[$length+8]))%16].'.';
	
	return $ret;
}


function show_comments ($picture, $howmany, $offset = 0, $quote = -1, 
			$kwd = '', $author = '')
{
	define ('RED','red');
	define ('POSSIBLY', $_REQUEST['LNG']=='EN' ? 'unverified': 'не авторизован(а)');

	global $myid, $COMMENTS_FOLLOW, $NO_COMMENTS_YET, $LOGO_COLOR, $IBASE, $FOTO_COMMENTS_TABLE, $OTHER_GALLERY, $PICTABLE, $today, $MODERATOR_MODE, $THIRD_COLOR;
	
	$remember = '';
	$new = $MODERATOR_MODE==2 ? '' : " AND approved='Y' AND (isnull(senton) or senton<='$today')";
	$extra_tables = '';
	$limitation = "WHERE 1 ";
	if ($picture) {
		$limitation .= "AND foto_comments_picture=$picture AND foto_comments_picture=$PICTABLE.id $new";
	} else {
		$query = "SELECT COUNT(*) FROM $FOTO_COMMENTS_TABLE";
		$result = execute_query ($query);
		$row = mysql_fetch_array ($result, MYSQL_NUM);
		mysql_free_result ($result);
		$count = $row[0];
		$limitation .= "AND foto_comments_picture=$PICTABLE.id $new";
	}
	if ($kwd) {
		$kwd_safe = mysql_real_escape_string ($kwd);
		$limitation .= " AND foto_comments_text LIKE '%$kwd_safe%'";
	}

	if ($author) {
		$author_safe = mysql_real_escape_string ($author);
		$limitation .= " AND (foto_comments_author_id=authors.id AND (authors.login='$author_safe' OR authors.name like '%$author_safe%'))";
		$extra_tables .= ',authors';
	}
	$query = "SELECT foto_comments_text, foto_comments_date, foto_comments_id, foto_comments_author, foto_comments_picture, foto_comments_author_id, foto_comments_ip, foto_comments_moder, url, foto_comments_deleted, foto_comments_deleted_by FROM $FOTO_COMMENTS_TABLE,$PICTABLE $extra_tables $limitation order by foto_comments_date";
	if (!$picture)
		$query .= " desc";
	if ($howmany && is_numeric ($howmany))
		$query .= " limit $offset, $howmany";
	$result = execute_query ($query);
	$has_comments = false;

	$start = $offset;
	$olddate = 'сегодня';
	
	$i = 1;
	while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
		 if ($row[9]!='N' && !$picture) continue;
		 $offset++;
		 if (!$has_comments) {
			 $has_comments = true;
			 echo $picture ? '<ol>' : '<ul>';
		 }
		 $date = substr ($row[1],0,10);
		 if ($date != $olddate) {
			 if ($olddate) {
			 	echo $picture ? '</ol>' : '</ul>';
				echo '<b><u>'.mydate($date).'</u></b>';
			 	echo $picture ? '<ol>' : '<ul>';
			 }
			 $olddate = $date;
		}
		$date = /*mydate ($date).', '.*/substr ($row[1],11,2).':'.substr ($row[1],14,2);
		echo "\n<li value=$i>";
		$i++;
		 if ($row[7]) {
			 echo '<font color="'.RED.'"><b>'.MODERATOR_DO_SOMETHING.'</b></font><br>';
		 }

		$he_s_a_moder_too = false;
		 // "Рычаги управления"
		if ($MODERATOR_MODE == 2 && $row[5] && $row[5] != $myid) {
			 $query = "SELECT * FROM pg_moderators WHERE pg_moderators_x_authors_id=$row[5] AND pg_moderators_maintable='$PICTABLE'";
			 $moder_rq = execute_query ($query);
			 $he_s_a_moder_too = (mysql_num_rows ($moder_rq) == 1);
			 mysql_free_result ($moder_rq);
		}
		 
		 if ((($MODERATOR_MODE == 2 && !$he_s_a_moder_too) || $row[5]==$myid)
				     && $picture && $row[9]=='N') {
			 echo "<a href='pg_view.php?ID=$row[4]&RMID=$row[2]#comments'><font size=-1 face='Arial'>[".DELETE."]</font></a> ";
		 }
		 if ($MODERATOR_MODE == 2 && $picture && $row[5] != $myid && !$he_s_a_moder_too) {
			 if ($row[5])
				 echo "<a href='pg_view.php?ID=$row[4]&WARNID=$row[5]#comments'><font size=-1 face='Arial'>[Вынести предупреждение]</font></a> ";
			 echo "<a href='pg_view.php?ID=$row[4]&BANID=$row[2]&IP=$row[6]#comments'><font size=-1 face='Arial'>[Запретить IP адрес автора]</font></a> ";
			 if ($row[7]) {
				 echo "<a href='pg_view.php?ID=$row[4]&NOMODID=$row[2]#comments'><font size=-1 face='Arial'>[".NOTFORMOD."]</font></a> ";
			 }
			 echo '<br>';
		 }
		
		if ($row[9]!='N') echo '<strike>'; 
		 if ($offset == $quote + 1 && $row[9] != 'Y') $remember = $row[0];

		 if ($row[9]=='N' || $MODERATOR_MODE == 2) {
			 $row[0] = preg_replace ('@(<[^>]+>)@si', '', $row[0]);
			 $row[0] = preg_replace ('@(https?)://([\d\w/\.\-\?\&\=\*%\+#!~,]+[\d\w/\-\?&])@si', '[<a href="\1://\2"><img src="'.$OTHER_GALLERY.'images/up2.gif" width=11 height=9 border=0 align=bottom></a>]', $row[0]);
			 $smiles_symbols = array (':)', ':(',':-)',':-(','[quote]','[/quote]');
			 $smiles_pictures = array ('<img width=15 height=15 src='.$OTHER_GALLERY.'/images/smilie1.gif>', '<img width=15 height=15 src='.$OTHER_GALLERY.'/images/smilie2.gif>', '<img width=15 height=15 src='.$OTHER_GALLERY.'/images/smilie1.gif>', '<img width=15 height=15 src='.$OTHER_GALLERY.'/images/smilie2.gif>', '&gt; <i>', '</i><br>');
			 $row[0] = str_replace ($smiles_symbols, $smiles_pictures, $row[0]);
			 if ($kwd) {
				 $row[0] = str_replace ($kwd, "<font color='".RED."'><b>$kwd</b></font>", $row[0]);
			 }
			 echo stripslashes ($row[0]);
		}
		if ($row[9]!='N') {
			 echo '</strike>';	
			if ($MODERATOR_MODE == 2) echo '<br>';
			 $bywhom = ($row[10]==$row[5]) ? 'автором' : "<a href='index.php?AUTHOR_ID=$row[10]'>модератором</a>";
			 echo "<i>*** Этот комментарий удален $bywhom ***.</i>";
		}

		if ($row[9] == 'Y' && !$row[5])
			$name = 'Аноним';
		else
			$name = stripslashes (str_replace ('"','&quot;',$row[3]));
		echo " -- <font face='Arial' size=-1><b>";
		echo $row[5] ? " <a href='index.php?AUTHOR_ID=$row[5]'><font color='$LOGO_COLOR'>" : "<font color='$THIRD_COLOR'>" ;
		echo "$name</b>";
		echo $row[5] ? '</a>' : ' ('.ip2name ($row[6]).', '.POSSIBLY.')';
		echo '</font></font>';
		if ($picture) {
			 $quote_id = $offset - 1;
			 $count = $offset - $start;
			 echo " <a href='pg_view.php?ID=$row[4]&LNG=$LNG&QUOTE_N=$quote_id#addcomment'>".QUOTE_IMG."</a>";
		}
		echo ",\n<font color='$LOGO_COLOR'><i>$date</i></font>";
		if (!$picture)
			 echo " <a href='pg_view.php?ID=$row[4]#picture'><b>&gt;&gt;&gt;</b></a>";
		echo '</i>';		// just in case
	} // while

	mysql_free_result ($result);
	if ($has_comments) {
		echo $picture ? '</ol>' : '</ul>';
	}
	return array ($count, $remember);
}

?>
