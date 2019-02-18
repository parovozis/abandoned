<?php
define ('MY_ID', 1);
define ('DOMAIN', 'parovoz.com');
define ('ADMIN', 'parovoz@parovoz.com');
define ('COOKIE_DOMAIN', '.parovoz.com');
define ('MODERATOR', 'samusama');
define ('DEFAULT_LANGUAGE', 'RU');
define ('ALLOW_NEW_AUTHORS', true);
define ('SCRATCHPATH', '/tmp/');
define ('START_YEAR', 1880);
define ('BOUNDARY_YEAR', 1960);
define ('ENABLE_TRANSLIT', true);
define ('NO_DELETE', false);
define ('GHOST','http://www.'.DOMAIN.'/AbandonedRails/');
define ('SMALLCOL', 3);
define ('INIT_SEND_LIMIT', 6);
define ('NEW_THRESHOLD', '14 day');
define ('MAX_VOTES', 4);
define ('COMMENTS_ALLOWED', 150);
define ('EXIF_ALLOWED', true);
define ('VOTE_ALLOWED', true);
define ('POST_IMMEDIATELY', 'N');
define ('FAQ_FILE', 'faq.php');
$NO_NEW_PICTURES=false;
define ('PG_REAL_BASE','./gallery/');
define ('PG_BASE', PG_REAL_BASE);
define ('IC_BASE', './gallery/icons/');
define ('DEFAULT_NCOLS', 4);
define ('DEFAULT_NROWS', 12);
define ('HOW_MANY_NEW', 0);
define ('HOW_MANY_MINE',4);
define ('PREFIX_WITH_REG_NAME', false);
define ('PREFIX_WITH_CAT0', false);
define ('COMODERATION', false);
define ('GMAPS_KEY','ABQIAAAAK81knbCXcRkFsj-hblCbcBQ6Nn6snAPKV5niJoIHsF1Bkpa9AxQYauu4ei7E6c5WAsh4IoFSw22rvw');
define ('GOOGLE_SPAN',14);
//define ('SPECIAL_DATE', '10');
//------------------------------------------------------
$OTHER_GALLERY  = 'http://'.DOMAIN.'/newgallery/';
if (isset($_REQUEST['LNG']) && $_REQUEST['LNG']=='EN') {
  $WEBSITE = "Abandoned Rails in the x-USSR";
  $WEBSITE_STRICT = "Abandoned Rails in the x-USSR";
  $WEBSITE_DESCR = $WEBSITE_STRICT.': pictures of abandoned railways.';
  $real_LNG = 'en-US';
} else {
  $WEBSITE = "Заброшенные ж.д. бывшего СССР";
  $WEBSITE_STRICT = "Заброшенные ж.д. бывшего СССР";
  $WEBSITE_DESCR = $WEBSITE_STRICT.': фотографии заброшенных и разобранных линий.';
  $real_LNG = 'ru-RU';
}
$PHO = 'http://'.DOMAIN.'/phpBB2/viewforum.php?f=41';

$TITLECOLOR        = '#191744';
$FIFTH_COLOR       = '#c9dfe0';
$FIRST_COLOR       = '#7d9391';
$SECOND_COLOR      = '#b7e5e2';
$THIRD_COLOR       = '#248e4c';
$FOURTH_COLOR      = '#65c5e5';
$TODAY_COLOR       = '#FFFFFF';
$LOGO_COLOR        = '#214f5a';
$WARNING_COLOR     = $LOGO_COLOR;
$OLD_COLOR         = '#d0e7e6';
$POD_COLOR         = '#eef897';
$UNPUBLISHED_COLOR = '#f48c30';
$BLUE_COLOR        = 'green';

$LOGO = 'fotogaleria.png';

$PICTABLE            = 'abandoned';
$PODTABLE            = $PICTABLE.'_pod';
$CATTABLE            = $PICTABLE.'_pg_cats';
$BASECATTABLE        = $PICTABLE.'_cats';
$EXIFTABLE           = $PICTABLE.'_exif';
$VOTETABLE           = $PICTABLE.'_votes';
$ASLTABLE            = $PICTABLE.'_send_limit';
$FOTO_COMMENTS_TABLE = $PICTABLE.'_comments';
$REGIONTABLE         = 'pictures_regions';
$NOTIFYTABLE         = $PICTABLE.'_notify';

$my_NLS = !empty ($_COOKIE['NLS']);
if ($my_NLS) $REGIONTABLE .= '_natl';

$SIMILAR = array ("([A-Z])?([0-9]+)(\.)([0-9][0-9]+)-[0-9]",
		  "(.+\ )?([^>\ \(]+)(-)([0-9][0-9]+).?");

define ('COUNTERS', '<a target=_new href="http://top.list.ru/jump?from=1473"><img align="right" src="http://top.list.ru/counter?id=1473;t=51" border=0 height=31 width=88 vspace="0" alt="TopList"></a><IMG ALT="Rambler\'s Top100 Service" WIDTH="1" HEIGHT="1" BORDER="0" SRC="http://counter.rambler.ru/top100.cnt?193268>');

$DEFAULT_HOWMANY = DEFAULT_NCOLS * DEFAULT_NROWS;
?>
