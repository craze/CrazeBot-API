<?php 
	include_once 'class/API.php';
	$api = new API (ltrim ( $_SERVER ['REQUEST_URI'], '/' ));
	echo $api->json();
?>