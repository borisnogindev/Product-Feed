<?php
include('config.php');
include('functions.php');
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);
if(isset($data['myshopify_domain'])){
	$shop = $data['myshopify_domain'];
	$response = deleteShopOnAppUninstall($shop);
	if(empty($response['status'])){
		echo $response['error'];
	}
}