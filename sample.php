<?php

require_once ('wp-mxn-helper.php');

// Ensure this code is invoked before the wp_head and wp_enqueue_scripts hooks are fired.

$mxn = new WP_MXNHelper;
$cloudmade_key = 'your-cloudmade-key';
$google_key = 'your-google-key';
$nokia_appid = 'your-nokia-app-id';
$nokia_authtoken = 'your-nokia-auth-token';

$provider = 'nokia';
//$provider = 'cloudmade';
//$provider = 'googlev3';

if ($mxn->set_provider ($provider)) {
	$mxn->register_callback ('cloudmade', 'cloudmade_callback');
	$mxn->register_callback ('nokia', 'nokia_callback');
	$mxn->register_callback ('googlev3', 'google_callback');
}

function cloudmade_callback () {
	return array ('key' => $cloudmade_key);
}

function nokia_callback () {
	return araay ('app-id' => $nokia_appid, 'auth-token' => $nokia_authtoken);
}

function google_callback () {
	return array ('key' => $cloudmade_key, 'sensor' => 'false');
}