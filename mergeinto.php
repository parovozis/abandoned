<?
$my_author = $_POST['MERGEINTO'];
$old_author = $_POST['AUTHOR'];

$gallery = array ("pictures", "picture_ng", "picture_intl", "transit_pictures");
foreach ($gallery as $my_gallery) {
	$lquery = "UPDATE $my_gallery SET author_id=$my_author where author_id=$old_author";
	execute_query ($lquery);
}

$lquery = "UPDATE pg_stars SET pg_stars_x_authors_id=$my_author WHERE pg_stars_x_authors_id=$old_author";
execute_query ($lquery);

$lquery = "UPDATE pg_warning SET authors_x_id=$my_author WHERE authors_x_id=$old_author";
execute_query ($lquery);

$wikis = array ("emb_authors", "rp_authors");
foreach ($wikis as $my_wiki) {
	$lquery = "UPDATE ignore $my_wiki SET emb_authors_id=$my_author WHERE emb_authors_id=$old_author";
	execute_query ($lquery);
}

$lquery = "DELETE FROM authors where id=$old_author";
execute_query ($lquery);

$_POST['AUTHOR_ID'] = $my_author;

?>
