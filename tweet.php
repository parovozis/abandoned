<?php

function get_tiny_url($url) {  
	$ch = curl_init();  
	$timeout = 5;  
	curl_setopt($ch,CURLOPT_URL,'http://tinyurl.com/api-create.php?url='.$url);  
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);  
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);  
	$data = curl_exec($ch);  
	curl_close($ch);  
	return $data;  
}

function tweet($message,$url)
{
$consumerKey = 'eqh1JMnRfzUseZRw3lvccQ';
$consumerSecret = '4Hy1uN09umor3FXr4uHEVhB56wGl8Mlw6c4uVz0xzE';
$OAuthToken = '1861598982-24ApnN88kEJuEclHt5WC1OzAd8l5ZMOvdmbECxI';
$OAuthSecret = '5Kb3J4iHCvJYpagbZnOVxwTJT6tgmb4I2a148kxzI';

require_once(__DIR__.'/twitteroauth.php');

$tiny = get_tiny_url($url);
// create new instance
$tweet = new TwitterOAuth($consumerKey, $consumerSecret, $OAuthToken, $OAuthSecret);

$message = mb_substr ($message, 0, 140 - strlen($tiny)-1, "UTF-8") . ' ' .  $tiny;
 
// Send tweet 
$tweet->post('statuses/update', array('status' => "$message"));
}

?>
