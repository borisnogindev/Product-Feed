<?php
session_start();
if(isset($_POST['id']) && isset($_POST['method']) && isset($_SESSION['shop'])){
	require_once('config.php');
	require_once('functions.php');
	if($_POST['method'] == "insert"){
		$data = array('id'=>$_POST['id'], 'shop'=>$_SESSION['shop']);
		$response  = insertCollection($data);
		if($response['status']){
			echo 1;
		}
		else{
			echo 0;
		}	
	}
	else if($_POST['method'] == 'delete'){
		if(isset($_POST['shop']) && $_POST['shop'] == $_SESSION['shop']){
			$data = array('id'=>$_POST['id'], 'shop'=>$_SESSION['shop'],'type'=>$_POST['type']);
			$response  = deleteCollection($data);
			if($response['status']){
				echo 1;
			}
			else{
				echo 0;
			}
		
		}
	}
}

 /* if(isset($_POST['method']) && $_POST['method'] == 'installPixels' && isset($_SESSION['shop'])){
	require_once('config.php');
	require_once('functions.php');
	require_once("shopify.php");
	
	$response = checkShopExists($_SESSION['shop']);
	if($response['status'] == 1){
		$token = $response['result']['token'];
	}
	else{
		echo "Shop Doesnot exists";
		die();
	}
	if(isset($token) && $token != ""){
		$config =  array('client_Id' => APIKEY,
						 'redirect_uri' => REDIRECT_URL,
						 'client_Secret' =>  SECRET,
						 'url'=> $_SESSION['shop'],
					);
		$productFeed = new shopify($config);
		$response = $productFeed->setAccessToken($token);
		if($response['status']){
		}else{
			echo $response['error'];
			die();
		}
	}
	$script = str_replace("<script>","",$_POST['data']);
	$script = str_replace("</script>","",$script);
	$script = str_replace("</noscript>","');",$script);
	$script = str_replace("<noscript>","document.body.appendChild('",$script);
	$data = array('store_URL'=>$_SESSION['shop'],'script_Shop'=>$script);
	$response = insertScript($data);
	if($response['status']){
		if($response['status'] == 1){
			$response = $response['result'];
			$id = $response['id'];
			$check = 1;
		}
		else if($response['status'] == 2){
			$response = $response['result'];
			$id = $response['id'];
			$scriptTag_id = $response['scriptTag_id'];
			$response = $productFeed->deleteFacebookPixcel($scriptTag_id);
			$check = 2;
		}
		$filename = $response['filename'];
		$file = FILE_LOCATION.$filename;
		$response = $productFeed->createFacebookPixcel($file);
		if($response['status']){
			$scriptTag_Id = $response['result']['script_tag']['id'];
			$data = array('id'=>$id, 'scriptTag'=>$scriptTag_Id);
			updateScriptTag($data);
			if($check == 1){
				echo "Facebook Pixcel inserted sucessfully";
			}
			else{
				echo "Facebook Pixcel updated  sucessfully";
			}
 		}else{
			echo $response['error'];
		}
	}else{
		echo $response['error'];
	} 
}  */
 
if(isset($_POST['method']) && $_POST['method'] == 'deletepixel-old' && isset($_SESSION['shop'])){		
	require_once('config.php');
	require_once('functions.php');
	require_once("shopify.php");	
	$response = checkShopExists($_SESSION['shop']);
	if($response['status'] == 1){
		$token = $response['result']['token'];
	}
	else{
		echo "Shop Doesnot exists";
		die();
	}
	$response = getScript($_SESSION['shop']);
	if($response){
		if(isset($token) && $token != ""){
			$config =  array('client_Id' => APIKEY,
							 'redirect_uri' => REDIRECT_URL,
							 'client_Secret' =>  SECRET,
							 'url'=> $_SESSION['shop'],
						);
			$productFeed = new shopify($config);
			$response = $productFeed->setAccessToken($token);
			if($response['status']){
			}else{
				echo $response['error'];
				die();
			}
		}	
		$response = $productFeed->getFacebookPixcel();
		if($response['status'] == 1){
			foreach($response['result']['script_tags'] as $scriptTag){	
				$productFeed->deleteFacebookPixcel($scriptTag['id']);	
			}
			deleteScriptEntry($_SESSION['shop']);
			echo 1;
		}
		else{
			echo $response['error'];
		}
	}
	else{
		echo 2;
	}
}	


if(isset($_POST['method']) && $_POST['method'] == 'deletepixel' && isset($_SESSION['shop'])){		
	require_once('config.php');
	require_once('functions.php');
	require_once("shopify.php");	
	//$data = array('store_URL'=>$_SESSION['shop']);
	$response = deletePixelAndCatalogID($_SESSION['shop']);
	if($response['status']){
		if($response['status'] == 1){
			echo 1;
		}else {
			echo 2;
		}
	}else{
		echo $response['error'];
	} 
}	



if(isset($_POST['method']) && $_POST['method'] == 'pixelAndCatalog' && isset($_SESSION['shop'])){
	require_once('config.php');
	require_once('functions.php');
	require_once("shopify.php");
	
	
	$data = array('store_URL'=>$_SESSION['shop'],'pixelid'=>$_POST['pixelid'],'catalog' => $_POST['catalog']);
	$response = insertPixelAndCatalog($data);
	if($response['status']){
		if($response['status'] == 1){
			echo 'Successfully inserted';
		}elseif($response['status'] == 2){
			echo 'Pixel Alredy Exist';
		}else {
			echo 'Error in insertion';
		}
	}else{
		echo $response['error'];
	} 
	
}

if(isset($_POST['method']) && $_POST['method'] == 'pixelAndCatalogUpdate' && isset($_SESSION['shop'])){
	require_once('config.php');
	require_once('functions.php');
	require_once("shopify.php");
	
	
	$data = array('store_URL'=>$_SESSION['shop'],'pixelid'=>$_POST['pixelid'],'catalog' => $_POST['catalog']);
	$response = updatePixelAndCatalogId($data);
	if($response['status']){
		if($response['status'] == 1){
			echo 'Successfully Updated!';
		}else {
			echo 'Error in updation';
		}
	}else{
		echo $response['error'];
	} 
	
}



 if(isset($_POST['method']) && $_POST['method'] == 'installPixels' && isset($_SESSION['shop'])){
	require_once('config.php');
	require_once('functions.php');
	require_once("shopify.php");
	
	$response = checkShopExists($_SESSION['shop']);
	
	if($response['status'] == 1){
		$token = $response['result']['token'];
	}
	else{
		echo "Shop Doesnot exists";
		die();
	}
	
	if(isset($token) && $token != ""){
		$config =  array('client_Id' => APIKEY,
						 'redirect_uri' => REDIRECT_URL,
						 'client_Secret' =>  SECRET,
						 'url'=> $_SESSION['shop'],
					);
		
		$productFeed = new Shopify($config);
		
		$response = $productFeed->setAccessToken($token);
		if($response['status']){
		}else{
			echo $response['error'];
			die();
		}
		
			
	}
	$status = 1;
    $data = array('store_URL'=>$_SESSION['shop'],'status'=> $status);	
	$response = updateStatus($data);
	$pixel = $_POST['dPixel'];
	$catalog = $_POST['dCatalog'];
	
	$themescript = "pixelTheme.liquid";
	$productscript = "pixelProduct.liquid";
	$cartscript = "pixelCart.liquid";
	
	$pixelThemeScript = " 
	<script>
	!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
	n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
	n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
	t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
	document,'script','//connect.facebook.net/en_US/fbevents.js');

	fbq('init', '$pixel');
	fbq('track', 'PageView');</script>
	<noscript><img height='1' width='1' style='display:none'
	src='https://www.facebook.com/tr?id=$pixel&ev=PageView&noscript=1'
	/></noscript>";
	
	$pixelProductScript = "
	<script> !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod? n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n; n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0; t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script','//connect.facebook.net/en_US/fbevents.js'); fbq('track', 'ViewContent', { content_ids: '{{ variant.id }}', product_catalog_id: '$catalog', content_type: 'product_group', }); </script> <noscript><img height='1' width='1' style='display:none' src='https://www.facebook.com/tr?id=$pixel&ev=ViewContent&noscript=1' /></noscript>";

	$pixelCartScript = "<script> !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod? n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n; n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0; t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script','//connect.facebook.net/en_US/fbevents.js'); fbq('track', 'AddToCart'); </script><noscript><img height='1' width='1' style='display:none' src='https://www.facebook.com/tr?id=$pixel&ev=AddToCart&noscript=1' /></noscript> ";
	
	$productFeed->createAssets($themescript,$pixelThemeScript);
	
	$productFeed->createAssets($productscript,$pixelProductScript);
	
	$productFeed->createAssets($cartscript,$pixelCartScript);
	     $response = $productFeed->copyThemeliquid();
		 $response = $productFeed->copyCartliquid();
		 $response = $productFeed->copyProductliquid();
		 $response = $productFeed->putThemeTemplate();
         $response = $productFeed->putCartTemplate();
	     $response = $productFeed->putProductTemplate();
  		
		
      /*  $response = $productFeed->getthemeHeader();
	   print_r(explode("head",$response)); */
	echo ("Pixel Successfully Installed");
} 


 if(isset($_POST['method']) && $_POST['method'] == 'uninstallPixels' && isset($_SESSION['shop'])){
	require_once('config.php');
	require_once('functions.php');
	require_once("shopify.php");
	
	$response = checkShopExists($_SESSION['shop']);
	
	if($response['status'] == 1){
		$token = $response['result']['token'];
	}
	else{
		echo "Shop Doesnot exists";
		die();
	}
	
	if(isset($token) && $token != ""){
		$config =  array('client_Id' => APIKEY,
						 'redirect_uri' => REDIRECT_URL,
						 'client_Secret' =>  SECRET,
						 'url'=> $_SESSION['shop'],
					);
		
		$productFeed = new Shopify($config);
		
		$response = $productFeed->setAccessToken($token);
		if($response['status']){
		}else{
			echo $response['error'];
			die();
		}
		
		/* echo "<pre>";
		print_r($productFeed); */
			
	} 
    
    //$pixel = $_POST['dPixel'];
	//$catalog = $_POST['dCatalog'];
	$status = 0;
	$data = array('store_URL'=>$_SESSION['shop'],'status'=> $status);	
	$response = updateStatus($data);
	$themescript = "pixelTheme.liquid";
	
	$productscript = "pixelProduct.liquid";
	$cartscript = "pixelCart.liquid";
	
	$pixelThemeScript = "";
	
	$pixelProductScript = "";

	$pixelCartScript = "";
	
	$productFeed->createAssets($themescript,$pixelThemeScript);
	
	$productFeed->createAssets($productscript,$pixelProductScript);
	
	$productFeed->createAssets($cartscript,$pixelCartScript);
    
	$response = $productFeed->copyputThemeliquid();	
	$response = $productFeed->copyputCartliquid();	
	$response = $productFeed->copyputProductliquid();	
	
	echo ("Pixel Successfully Uninstalled");
} 