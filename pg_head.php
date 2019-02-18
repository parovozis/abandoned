<HTML>
<HEAD>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<title><? echo $PAGE ?></title>
<LINK REV="MADE" HREF="mailto:<? echo ADMIN ?>">
<link rel="stylesheet" href="muralista.css">
<? if (isset ($SCRIPTS)) require ($SCRIPTS); ?>
<link rel="alternate" type="application/rss+xml" title="Новые фотографии" href="<? echo GHOST ?>rss.php" />
</HEAD>
		
<body <? if (isset ($ONLOAD)) echo "onLoad='$ONLOAD'\n" ?> >

<? 
if (!isset($_REQUEST['LNG'])) {
  $_REQUEST['LNG'] = DEFAULT_LANGUAGE;
}
require ($_REQUEST['LNG'].'/pg_head.php');
?>

<a name="TOP"></a>
<table cellpadding="4" cellspacing="0" border="0" width="100%">
<tr height="30">
<td rowspan="2" width="16%"><a href='<?
if (HOME!='HOME')
	echo HOME;
else
	echo 'index.php';
?>'><img hspace="2" src='<? echo $LOGO ?>' alt='<? echo $WEBSITE_STRICT ?>' width='136' height='69' align='left' border='0'></a>

<?php
define ('RSS', '<a href=rss.php>'.RSS_GIF.'</a>');
/* Top row */
$menu = array (
	       array ($PHO,$PHORUM,true),
	       array (FAQ_FILE,$FAQ,true),
	       array ('index.php?CATEG=-1',$NEW_PICTURES,true),
	       array ('index.php?ID=0',$PODAY,VOTE_ALLOWED),
	       array ('ac_authorize.php',$AUTHORS_CENTER,true),
	       array ('vw_showall.php',COMMENTS,true),
	       array ('hof.php',$HOF,VOTE_ALLOWED),
	       array ('stats.php',$STATS,true),
);
if (!isset($SELECTED)) {
  $SELECTED = 0;
}

$one = $SELECTED ? '' : '1';
echo "<td width='15' background='images/eyebrow-tl$one.png'>\n";

for ($i = 0; $i < count ($menu); $i++) {
	$link = $menu[$i][0];
  	$tag = $menu[$i][1];
  	$enabled = $menu[$i][2];
  	$one = ($i==$SELECTED) ? '1' : '';
  	if ($i+1==$SELECTED) {
    		$two = '1';
  	} else if ($i==$SELECTED) {
    		$two = '2';
  	} else {
    		$two = '';
  	} 
  	$left = "eyebrow-tc$one.png";
  	$right = ($i==count ($menu) - 1) ? "eyebrow-tr0$two.png" : "eyebrow-tr$two.png";
  	echo "<td align='center' valign='center' background='images/$left'>\n";
  	if ($enabled!=false)
		echo "<a href='".$link."'>";
  	echo "<b>$tag</b>";
  	if ($enabled!=false)
		echo "</a>";
  	echo "<td width='15' background='images/$right'>\n";
}
/* Bottom row */
?>

<tr bgcolor="<? echo $FIRST_COLOR ?>" background='images/eyebrow-b.png' valign=middle>
<td colspan="3" align="center" valign="middle"><b><? echo $SEARCH ?>:</b></td>
<form action="index.php" method="POST"><td colspan="7" align="left" valign="middle"> <input type="text" name="SEARCHSTRING" value="<? echo $SEARCHSTRING ?>" size="32" maxlength="32" align="middle"> <input width="17" height="20" type="image" src="images/rightarrow.png" align="top"></td></form>
<FORM>
<td colspan="3" valign="middle">
<? echo RSS ?>

<SELECT ONCHANGE="location = this.options[this.selectedIndex].value;">
<?
$switch_action = "$PHP_SELF?";
if (isset ($_REQUEST['ACTION'])) {
  $switch_action .= '&ACTION='.$_REQUEST['ACTION'];
}
if (isset ($_REQUEST['ID']) && is_numeric ($_REQUEST['ID'])) {
  $switch_action .= '&ID='.$_REQUEST['ID'];
}
$languages = array ('RU' => 'Русский', 
		    'EN' => 'English');
foreach ($languages as $code => $name) {
  $selected = $code == $_REQUEST['LNG'] ? 'selected' : '';
  echo "<OPTION VALUE='$switch_action&LNG=$code' $selected>$name\n";
}
?>
</SELECT>
</FORM>
<td colspan="4" align="middle">

<?
if (($_REQUEST['AUTHOR'] || $_COOKIE['COOKIE_AUTHOR'])
    && ($_REQUEST['AUTHOR_PASSWORD'] || $_COOKIE['COOKIE_AUTHOR_PASSWORD'])) {
  echo '<a href="ac_authorize.php?LOGOUT=1">'.$_COOKIE['COOKIE_AUTHOR'].': '.$LOGOUT.'</a>';
  if ($MODERATOR_MODE == 1) {
    echo '<br><a href="ac_authorize.php?MOD_LOGIN=1">'.DA_MODERATOR.'</a>';
  } else if ($MODERATOR_MODE == 2) {
    echo '<br><a href="ac_authorize.php?MOD_LOGOUT=1">'.NE_MODERATOR.'</a>';
  }
} else {
  echo '<a href="ac_authorize.php">'.$LOGIN.'</a>';  
}
?>
</tr>
<tr bgcolor="<? echo $SECOND_COLOR ?>">
<td colspan="9" align="left"><b><? echo $WEBSITE ?></b>
<td colspan="5" align="center"><? echo $LOCAL_TIME.' '.date ('H:i',$current_time) ?>
<td colspan="4" align="right">
<?
if (isset ($my_NLS)) {
	if ($my_NLS) {
		echo "<a href=index.php?NLS=0>Традиционное написание</a>\n";
	} else {
		echo "<a href=index.php?NLS=1>Национальное написание</a>\n";
	}
} else {
	echo '&nbsp;';
}
?>
</table>
<? flush (); ob_flush () ?>
