<?php
if(isset($_GET['id']) && isset($_GET['shop']) && isset($_GET['type'])){
	//echo '<pre>';
	//print_r($_GET);
		require_once('config.php');
		require_once('functions.php');
		require_once("shopify.php");
		
		############ Get Token start ################
			$response = checkShopExists($_GET['shop']);
			if($response['status'] == 1){
				$token = $response['result']['token'];
			}
			else{
				echo "Invalid Link sorry!";
				die();
			}
			if($_GET['type'] == "collection"){
				$data = array('id'=>(int)$_GET['id'],'shop'=>$_GET['shop']);
				$resp = checkCollectionId($data);
				if(!$resp['status']){
					echo "Invalid Link Sorry";
					die();
				}
			}else if($_GET['type'] == "custom"){
			$data = array('id'=>(int)$_GET['id'],'shop'=>$_GET['shop']);
			/* echo '<pre>';
			print_r($data); */
				$resp = checkCustomCollectionId($data);
			/* 	echo '<pre>';
			print_r($resp); */
				if(!$resp['status']){
					echo "Invalid Link Sorry";
					die();
				}
			}
		############ Get Tokeb END ################
		if(isset($token) && $token != ""){
			$config =  array('client_Id' => APIKEY,
							 'redirect_uri' => REDIRECT_URL,
							 'client_Secret' =>  SECRET,
							'url'=> $_GET['shop'],
						);
			$productFeed = new shopify($config);
			$response = $productFeed->setAccessToken($token);
			if($response['status']){
				//echo $response['result'];
			}else{
				echo $response['error'];
				die();
			}
		}
		
		$response = $productFeed->getShopData();
		if($response['status']){
			$shopData = array('title'=>$response['result']['shop']['name'],'link'=>$response['result']['shop']['domain']);
		}
		else{
			echo $response['error'];
			die();
		}

		if($_GET['type'] == "collection"){
			$resp = $productFeed->getCollectionProuctData($_GET['id']);
			if($resp['status']){
				$products = $resp['products'];
			}
			else{
				echo $resp['error'];
				die();
			}
			
			$response = $productFeed->parseProductData($products);
			if($response['status']){
				header('Content-Type: application/xml');
				$feed = $productFeed->createFeed($response['data'] , $shopData);
				echo $feed;
			}
			else{
				echo "No Data";
			}
		}
		else if($_GET['type'] == "custom"){
			$response = getCustomCollectionProduct($_GET['id']);
			if($response['status']){
				$customProductId = json_decode($response['result'], true);
				
				$response = $productFeed->getAllProuctData();
				if($response['status']){
					$response = $productFeed->parseProductData($response['products']);
					if($response['status']){
						$product_Array = array();
						foreach($response['data'] as $prod){
							$product_Array[((string)$prod['id'])] = $prod;
						}
						
						foreach($customProductId as $id){
							if(isset($product_Array[((string)$id)])){
								$customproductArray[] = $product_Array[((string)$id)];
							}
						}
						if(isset($customproductArray) && !empty($customproductArray)){
							header('Content-Type: application/xml');
							$feed = $productFeed->createFeed($customproductArray , $shopData);
							echo $feed;	
						}
						else{
							echo "No data";
						}
					}
				}
				else{
					echo $response['error'];
				}
			}
			
		}
		unset($_SESSION);
	}
	else{
		echo 'Oops! Invalid Link..';
	}
