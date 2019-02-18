<?php
$myid = authorize ();
$query = "DELETE FROM pg_warning WHERE DATEDIFF(NOW(),date)>=".WARNING_DURATION;
execute_query ($query);
$query = "INSERT IGNORE INTO pg_warning (authors_x_id,pg_moderators_id,severity) VALUES (${_REQUEST['AUTHOR']},$myid,".BAN_SEVERITY.")";
execute_query ($query);
?>