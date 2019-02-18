<?
require ($_REQUEST['LNG'].'/ed_wiki.php');
echo "<h3>$WIKI</h3><a name=WIKI>".CAN_LINK_TO_WIKI;
if (isset ($_REQUEST['LABEL1'])) {
	$query = "INSERT INTO $WIKILINK_TABLE VALUES ({$_REQUEST['ARTICLE']}, {$_REQUEST['LABEL1']}, $ID, 'N')";
	if (!@mysql_query ($query) && mysql_errno()!=1062) {
		die ("$query: Query failed");
	}
}

echo "<form action='$PHP_SELF#WIKI' name='form' method='POST'>\n<p>\n";
$query = "SELECT emb_name,emb_content_name FROM $WIKI_TABLE,$WIKICONTENT_TABLE,$WIKILINK_TABLE WHERE emb_id=emb_content_article AND emb_content_article=emb_pictures_article AND emb_pictures_secid=emb_content_secid AND emb_content_version=1 AND emb_pictures_id=$ID AND emb_gallery=".MY_ID." ORDER BY emb_content_secno";

$has_labels = false;
$result = execute_query ($query);
while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
	if (!$has_labels) {
		echo $LABELS.': <font color='.$THIRD_COLOR.'><b>';
		$has_labels = true;
	}
	echo "$row[0] // $row[1];\n";
}

mysql_free_result ($result);
$query = "SELECT emb_name FROM $WIKI_TABLE,$WIKILINK_TABLE WHERE emb_id=emb_pictures_article AND emb_pictures_secid=0 AND emb_pictures_id=$ID AND emb_gallery=".MY_ID." ORDER BY emb_name";
$result = execute_query ($query);
while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
	if (!$has_labels) {
		  echo $LABELS.': <font color='.$THIRD_COLOR.'><b>';
		  $has_labels = true;
	}
	echo "$row[0] // $EMBIWIKI_ARTICLE;\n";
}
mysql_free_result ($result);
if ($has_labels) echo '</b></font>';

if (isset ($_REQUEST['LABEL0'])) {
	echo "<INPUT TYPE=HIDDEN NAME='ARTICLE' VALUE='".$_REQUEST['LABEL0']."'>\n";
	echo '<P>'.$EMBIWIKI_SECTIONS.': <select name="LABEL1">';
	$query = "SELECT emb_content_name,emb_content_secid FROM $WIKICONTENT_TABLE WHERE  emb_content_version=1 AND emb_content_article='{$_REQUEST['LABEL0']}' ORDER BY emb_content_secno";
} else {
	echo '<P>'.$MORE_EMBIWIKI_ARTICLES.': <select name="LABEL0">';
	$query = "SELECT emb_name,emb_id FROM $WIKI_TABLE,$WIKIREGION_TABLE WHERE emb_regions_article=emb_id AND emb_regions_regcode='{$self[2]}' AND emb_gallery=".MY_ID." ORDER BY emb_name";
}
$result = execute_query ($query);
if (isset ($_REQUEST['LABEL0']))
	echo "<OPTION VALUE='0'>$EMBIWIKI_ARTICLE\n";

while ($row = mysql_fetch_array ($result, MYSQL_NUM)) {
	echo "<OPTION VALUE='$row[1]'>$row[0]\n";
}

mysql_free_result ($result);

echo "</select>\n";
echo "<INPUT TYPE=HIDDEN NAME='ID' VALUE='$ID'>\n";
echo "<INPUT TYPE=SUBMIT VALUE='$CONTINUE'>\n";
echo '</form>';
?>
