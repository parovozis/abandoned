<?
if (!isset ($TIME_PHP)) {
  define ('ONE_DAY', 24);
  define ('ONE_HOUR', 3600);
  define ('POD_OFFSET', 3);
  $current_time = time ();
  date_default_timezone_set ("Europe/Moscow");
  $this_year = date ('Y',     $current_time);
  $today =     date ('Y-m-d', $current_time - POD_OFFSET * ONE_HOUR);
  $yesterday = date ('Y-m-d', $current_time - ONE_HOUR * (POD_OFFSET+ONE_DAY));
  $tomorrow =  date ('Y-m-d', $current_time + ONE_HOUR * (-POD_OFFSET+ONE_DAY));
  $TIME_PHP = 1;
}
?>
