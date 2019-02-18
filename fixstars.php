<?php
$lquery = "DELETE FROM pg_stars WHERE pg_stars_x_authors_id={$_REQUEST['AUTHOR']}";
execute_query ($lquery);
$all_galleries = array ('pictures','picture_ng','picture_intl','transit_pictures','abandoned');
foreach ($all_galleries as $gallery) {
  $lquery = "INSERT INTO pg_stars (SELECT '$gallery',id,{$_REQUEST['AUTHOR']},award FROM $gallery WHERE author_id={$_REQUEST['AUTHOR']} AND award>=0)";
  execute_query ($lquery);
}
?>
