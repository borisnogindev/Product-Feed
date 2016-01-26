<?php
session_start();
if(isset($_POST['ids']) && isset($_POST['FeedName']) && isset($_SESSION['shop'])){
	require_once('config.php');
	require_once('functions.php');
	$collection_Name = $_POST['FeedName'];
	$product_Ids = $_POST['ids'];
	$shop = $_SESSION['shop'];
	$data = array('collection'=>$collection_Name,'shop'=>$shop,'data'=>$product_Ids);
	$response = createCustomCollection($data);
	if($response){
		echo 1;
	}
	else{
		echo 0;
	}
}

		