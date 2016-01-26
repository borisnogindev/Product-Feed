<?php

function dbConnect(){
	$con = mysql_connect(DBHOST, DBUSER, DBPASS) or Die("Error : ".mysql_error());
	if($con){
		mysql_select_db(DBNAME, $con) or die("Error : ".mysql_error());
	}
}

function dbClose(){
	mysql_close();
}

function checkShopExists($shop){
	if(!$shop){
    	return array ('status'=>0,'Error'=>"Shop Url Is Missing");
	}
	dbConnect();
	$query = "select * from accounts where shop_url = '".$shop."'";
	$result = mysql_query($query);
	if(mysql_num_rows($result) > 0){
		$row = mysql_fetch_assoc($result);
		 dbClose();
		return array('status'=>1, 'result'=>array('id'=>$row['id'],'token'=>$row['token']));
	}else{
		dbClose();
		return array('status'=>0, 'result'=>'Not Exits');
	}
}



function curlResponse($url, $header, $data = ""){
	$ch = curl_init($url);
	if($data){
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	}
	//curl_setopt($x, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
	/* curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS,$data1);
	$y = curl_exec($ch); */
	$y = curl_exec($ch);
	$info = curl_getinfo($ch);
	if($y !== false){
		curl_close($ch);
		if($info['http_code'] == 200 || $info['http_code'] == 200){
			return array('status'=>1, 'result' => json_decode($y, true));
		}
		else{
			return array('status'=>0, 'error' => "There is some error having error code ". $info['http_code']);
		}
	}
	else{
		$error = 'Error: "' .curl_error($ch).'"';
		curl_close($ch);
		return array('status'=>0, 'error'=> $error);
	}
}
//**********************Product id insertion*************************//

function insertProductId($data){

    dbConnect();
	$id = $data;
    $check = "SELECT *FROM product_collection  WHERE `product_ID` = '$id'";
	$ss = mysql_query($check);
	if(mysql_num_rows($ss)){
	}else {
		$query = "INSERT INTO `product_collection` VALUES('','".$data."')";
		mysql_query($query);
	}
} 

function insertScript($data){
	dbConnect();
	$created_Time = date('Y-m-d H:i:s');	
	$query = "select * from accounts where shop_url = '".mysql_real_escape_string($data['store_URL'])."'";
	if(mysql_num_rows(mysql_query($query))){
		$filename = "createScript".time().".js";
		$myfile = fopen("scriptTag/".$filename, "w");
        fwrite($myfile, $data['script_Shop']);
		fclose($myfile);
		
		$query = "SELECT * FROM script_shop WHERE shop_url = '".mysql_real_escape_string($data['store_URL'])."'";
		$result = mysql_query($query);
		if(mysql_num_rows($result)) { 
			$row = mysql_fetch_assoc($result);
			$file = "scriptTag/".$row['script_file'];
			unlink($file);
			$id = $row['id'];
			$scripttag_Id = $row['scripttag_id'];
			$query = "update script_shop set script_file = '".mysql_real_escape_string($filename)."', updated = '$created_Time' where id = ".mysql_real_escape_string($id);
			if(mysql_query($query)) {
				dbClose();
				return array('status'=>2, 'result'=>  array('id'=>$id,'filename'=>$filename,'scriptTag_id'=>$scripttag_Id));
			}
			else{
				dbClose();
				return array('status'=>0,'error'=> 'Not updated');
			}
		} else {
			$query = "INSERT INTO `script_shop`(script_file, shop_url, created, updated) VALUES('".mysql_real_escape_string($filename)."','".mysql_real_escape_string($data['store_URL'])."','$created_Time', '$created_Time')";
			if(mysql_query($query)){
				$id = mysql_insert_id();
				dbClose();
				return array('status'=>1,'result'=> array('id'=>$id,'filename'=>$filename));
			}else{
				dbClose();
				return array('status'=>0,'error'=> 'Not inserted');
			}
		} 	
	}
	else{
		dbClose();
		return array('status'=>0,'error'=> 'Shop doesnot exits');
	}
}

function deleteScriptEntry($shop_url){
	if(empty($shop_url)){
		echo "Shop Url is missing!";
		die();
	}
	dbConnect();
	$query = "select * from script_shop where shop_url = '".$shop_url."'";
	$result = mysql_query($query);
	while($row = mysql_fetch_assoc($result)){
		$file = $row['script_file'];
		unlink("scriptTag/".$file);
	}
	$query = "delete from script_shop where shop_url = '".$shop_url."'";
	mysql_query($query);
	dbClose();
}

function getScript($shop_url){
	if(empty($shop_url)){
		echo "Shop Url is missing!";
		die();
	}
	dbConnect();
	$query = "select * from script_shop where shop_url = '".$shop_url."'";
	if(mysql_num_rows(mysql_query($query)) > 0){
		dbClose();
		return 1;
	}
	else{
		dbClose();
		return 0;
	}
}
function updateScriptTag($data){
	dbConnect();
	$query = "update script_shop set scripttag_id = ".$data['scriptTag']." where id = ".$data['id'];
	mysql_query($query);
	dbClose();
}

function createAccount($data){
	if(empty($data['token']) || empty($data['shop'])){
		return array('status'=>0, 'error'=> 'Token and shop url are missing');
	}
	dbConnect();
	$query = "insert into accounts(id, shop_url, token, created) values('','".mysql_real_escape_string($data['shop'])."','".mysql_real_escape_string($data['token'])."','". date("Y-m-d h:i:s")."')";
	if(mysql_query($query)){
		dbClose();
		return array('status'=>1, 'result'=> 'Account created sucessfully');
	}
	else{
		 dbClose();
		return array('status'=>0, 'error'=> 'Error in insertion'.mysql_error());
	}
}

function getCollections($shop){
	if(empty($shop)){
		return array('status'=>0, 'error'=> 'Shop Name is missing');
	}
	$query = "select * from shop_collections where shop_url = '".$shop."'";
	dbConnect();
	$result = mysql_query($query);
	if(mysql_num_rows($result) > 0){
		while($row = mysql_fetch_assoc($result)){
			$collections[] = $row['collection_id'];
		}
		dbClose();
		return array('status'=>1,'result'=>$collections);
	}
	else{
		 dbClose();
		 return array('status'=>0,'error'=>"No entry");
	}
}

function insertCollection($data){
	if(empty($data['id']) || empty($data['shop'])){
		return array('status'=>0,'error'=>"Shop and Id missing");
	}
	dbConnect();
	$query = "insert into shop_collections values(".mysql_real_escape_string($data['id']).", '".mysql_real_escape_string($data['shop'])."')";
	if(mysql_query($query)){
		dbClose();
		return array('status'=>1,'result'=>"Collection added successfully");
	}
	else{
		dbClose();
		return array('status'=>0,'error'=>"Error in collection addition");
	}
}

function deleteCollection($data){
	if(empty($data['id']) || empty($data['shop']) || empty($data['type'])){
		return array('status'=>0,'error'=>"Shop and Id missing");
	}
	dbConnect();
	if($data['type'] == 'collection'){
		$query = "delete from shop_collections where shop_url = '".$data['shop']."' and collection_id =".$data['id'];
	}
	else if($data['type'] == 'custom'){
		$query = "delete from custom_collections where shop_url = '".$data['shop']."' and id =".$data['id'];
		
		$query2 = "delete from product_collection where custom_collection_id =".$data['id'];
	}
	if(mysql_query($query)){
		if($data['type'] == 'custom'){
			mysql_query($query2);
		}
		dbClose();
		return array('status'=>1,'result'=>"Collection delete successfully");
	}
	else{
		echo  mysql_error();
		dbClose();
		return array('status'=>0,'error'=>"Error in delete collection");
	}
}
function checkCollectionId($data){
	if(empty($data['shop']) || empty($data['id'])){
		return array('status'=>0, 'error'=> 'Shop Name and Id are missing');
	}
	$query = "select * from shop_collections where shop_url = '".$data['shop']."' and collection_id = '".$data['id']."'";
	dbConnect();
	$result = mysql_query($query);
	if(mysql_num_rows($result) > 0){
		dbClose();
		return array('status'=>1);
	}
	else{
		 dbClose();
		 return array('status'=>0);
	}
}

function checkCustomCollectionId($data){
	if(empty($data['shop']) || empty($data['id'])){
		return array('status'=>0, 'error'=> 'Shop Name and Id are missing');
	}
	$query = "select * from custom_collections where shop_url = '".$data['shop']."' and id = '".$data['id']."'";
	dbConnect();
	$result = mysql_query($query);
	if(mysql_num_rows($result) > 0){
		dbClose();
		return array('status'=>1);
	}
	else{
		 dbClose();
		 return array('status'=>0);
	}
}

function createCustomCollection($data){
	if(empty($data['shop']) || empty($data['collection']) || empty($data['data'])){
		return array('status'=>0, 'error'=> 'Shop Name and Collection name are missing');
	}
	dbConnect();
	$query = "select * from custom_collections where shop_url = '".mysql_real_escape_string($data['shop'])."' and name = '".mysql_real_escape_string($data['collection'])."'";
	$result = mysql_query($query);
	if(mysql_num_rows($result) > 0){
		dbClose();
		return array('status'=>0, 'error'=>"This collection Already exists. Please choose other name");
	}
	else{
		$query = "insert into custom_collections (name, description, shop_url) values('".mysql_real_escape_string($data['collection'])."' , '', '".mysql_real_escape_string($data['shop'])."')";
		if(mysql_query($query) > 0){
			$collectionId = mysql_insert_id();
			$ids = json_encode($data['data']);
			$query = "insert into product_collection(product_ids, custom_collection_id) values('".mysql_real_escape_string($ids)."', ".$collectionId.")";
			if(mysql_query($query)){
				dbClose();
				return 1;
			}else{
				dbClose();
				return 0;
			}
		}
	}
}

function getCustomCollection($shop_Url){
	$query = "select id, name from custom_collections where shop_url = '".$shop_Url."'";
	dbConnect();
	$result = mysql_query($query);
	if(mysql_num_rows($result) > 0){
		while($row = mysql_fetch_assoc($result)){
			$customs[] = array('id'=>$row['id'], 'name'=>$row['name']);
		}
		dbClose();
		return array('status'=>1, 'result'=> $customs);
	}
	else{
		dbClose();
		return 0;
	}	
}

function getCustomCollectionProduct($id){
	$query = "select product_ids from product_collection where custom_collection_id = $id";
	dbConnect();
	$result = mysql_query($query);
	if(mysql_num_rows($result) > 0){
		$row = mysql_fetch_assoc($result);
		dbClose();
		return array('status'=>1, 'result'=> $row ['product_ids']);
	}
	else{
		dbClose();
		return 0;
	}	
}

function deleteShopOnAppUninstall($shop){
	if(empty($shop)){
		return array('status'=>0, 'error'=> 'Shop Name is missing');
	}
	$query = "delete from accounts where shop_url = '".$shop."'";
	dbConnect();
	if(mysql_query($query)){
		$query = "delete from shop_collections where shop_url = '".$shop."'";
		if(mysql_query($query)){
		}
		else{
			 dbClose();
			return array('status'=>0, 'error'=> 'Error in Collection deletion');
		}
		
		$query = "select id from custom_collections where shop_url = '".$shop."'";	
		$result = mysql_query($query);
		while($row = mysql_fetch_assoc($result)){
			$query = "delete from product_collection where custom_collection_id = ".$row['id'];
			mysql_query($query);
		}
		$query = "delete from custom_collections where shop_url = '".$shop."'";
		mysql_query($query);
		$query = "select * from script_shop where shop_url = '".$shop."'";
		$result = mysql_query($query);
		while($row = mysql_fetch_assoc($result)){
			$file = "js/".$row['script_file'];
			unlink($file);
		}
		$query = "delete from script_shop where shop_url = '".$shop."'";
		mysql_query($query);
		dbClose();
		return array('status'=>1);
	}
	else{
		 dbClose();
		return array('status'=>0, 'error'=> 'Error in account deletion');
	}
}

function parseProductData($products){
	$product_Array = array();
	foreach($products as $product){
		$product_Detail = array();
		$images_Array = array();
		
		if(isset($product['images'])){
			foreach($product['images'] as $images){
				if(isset($images['variant_ids'][0])){
					foreach($images['variant_ids'] as $link){
						$images_Array[$link] = $images['src'];
					}
				}
				else{
					$images_Array['additional'][] = $images['src'];
				}
			}
		}
		
		if(isset($product['image'])){
			if(isset($product['image']['variant_ids'][0])){
				foreach($product['image']['variant_ids'] as $id){
					$images_Array['default'][$id] = $product['image']['src'];
				}
			}else{
				$images_Array['default'] = $product['image']['src'];
			}
		}
		
		//$product_Detail['combine'] = isset($images_Array)? $images_Array : '';
		//print_r($images_Array);
		if(isset($product['variants'])){
			foreach($product['variants'] as $prod){
				$product_Detail['id'] = $prod['id'];
				$quantity = $prod['inventory_quantity'];
				if($quantity <= 0){
					$product_Detail['availability'] = "out of stock";
				}
				else{
					$product_Detail['availability'] = "in stock";
				}
				$product_Detail['condition'] = "new";
				$product_Detail['description'] = $product['body_html'];	
				if(!empty($images_Array)){
					if(isset($images_Array[$prod['id']])){
						$product_Detail['image_link'] = $images_Array[$prod['id']];
						unset($images_Array[$prod['id']]);
					}else{
						if(isset($images_Array['default'][$prod['id']])){
							$product_Detail['image_link'] = $images_Array['default'][$prod['id']];
							unset($images_Array['default'][$prod['id']]);
						}else{
							if(is_array($images_Array['default'])){
								foreach($images_Array['default'] as $key => $value){
									$product_Detail['image_link'] = $value;
									unset($images_Array['default'][$key]);
									break;
								}
							}
							else{
								$product_Detail['image_link'] = $images_Array['default'];
								unset($images_Array['default']);
							}
						}
					}
					
					foreach($images_Array as $key => $img){
						if($key != 'additional'){
							if($key == 'default'){
								if(is_array($img)){
									foreach($img as $im){
										if(isset($images_Array['additional'])){
											array_push($images_Array['additional'], $im);
										}
										else{
											$images_Array['additional'][] = $im;
										}
									}
								}else{
									if(isset($images_Array['additional'])){
										array_push($images_Array['additional'], $img);
									}
									else{
										$images_Array['additional'][] = $img;
									}
								}
							}
							else{
								if(isset($images_Array['additional'])){
									array_push($images_Array['additional'], $img);
								}
								else{
									$images_Array['additional'][] = $img;
								}
							}
						}
					}
				//	print_r($images_Array['additional']);
					$images_Array['additional'] = array_unique($images_Array['additional']);
				}	
				$product_Detail['link'] = "https://mom-with-a-dot-com.myshopify.com/products/".trim($product['handle']);
				if($prod['title'] == "Default Title"){
					$product_Detail['title'] = $product['title'];
				}
				else{
					$product_Detail['title'] = $prod['title'];
				}
				$product_Detail['price'] = $prod['price'];
				$product_Detail['brand'] = $product['vendor'];
				$product_Detail['item_group_id'] = $prod['product_id'];
				//$product_Detail['final'] = $images_Array['additional'] ? $images_Array['additional'] : '';
				if(!empty($images_Array['additional']) && !empty($product_Detail['image_link'])){
					$product_Detail['additional_Images'] = array_diff($images_Array['additional'], array($product_Detail['image_link']));
				}
				else{
					$product_Detail['additional_Images'] = '';
				}
				
				if(isset($product_Detail['image_link'])){
				}
				else{
					$product_Detail['image_link'] = '';
				}
				
				array_push($product_Array, $product_Detail);
			}
		}
	}
	
	if($product_Array != ""){
		return array('status'=> 1, 'data'=>$product_Array);
	}
	else{
		return array('status'=> 0);
	}
}

function deletePixel($id){
	
	dbConnect();
	$query = "DELETE FROM `script_shop` WHERE `shop_url` = '$url'";
	$result = mysql_query($query);
	if($result){
	echo "deleted successfully";
	}
}

function getDeletePixel($data){
	dbConnect();
	$url = $data;
    $query = "select * from `script_shop` WHERE `shop_url` = '$url'";	            $result = mysql_query($query);
	while($row=mysql_fetch_array($result)){
			$fetchData[] = array('id' => $row['id'], 'script_file' => $row['script_file'], 'shop_url' => $row['shop_url']);
	}
	if($fetchData){  
			return $fetchData;
	}else{
		return 0;
	}
		
}

function checkDeletePixel() {
	
	dbConnect();
	$query = "select * from `script_shop`";
	$result = mysql_query($query);
	$row = mysql_num_rows($result);
	if($row) {
		return $row;
	}
}

function createFeed($products, $shop){
	$data = '<?xml version="1.0" encoding="UTF-8" ?>';
	$data .= '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">';
	$data .= '<channel>';
	$data .= '<title>'.trim(htmlspecialchars($shop['title'])).'</title>';
	$data .= '<link>'.trim(htmlspecialchars($shop['link'])).'</link>';
	$data .= '<description>'.trim(htmlspecialchars($shop['title'])).'</description>';
	foreach ($products as $product){
		$data .= '<item>';
		$data .= '<g:id>'.trim(htmlspecialchars($product['id'])).'</g:id>';
		$data .= '<g:item_group_id>'.trim(htmlspecialchars($product['item_group_id'])).'</g:item_group_id>';
        $data .= '<g:title>'.trim(htmlspecialchars($product['title'])).'</g:title>';
        $data .= '<g:description>'.trim(htmlspecialchars($product['description'])).'</g:description>';
        $data .= '<g:description>Test Products</g:description>';
        $data .= '<g:link>'.trim(htmlspecialchars($product['link'])).'</g:link>';
        $data .= '<g:image_link>'.trim($product['image_link']).'</g:image_link>';
		if(!empty($product['additional_Images'])){
			foreach($product['additional_Images'] as $images){
				 $data .= '<g:additional_image_link>'.trim($images).'</g:additional_image_link>';
			}
		}
        $data .= '<g:brand>'.trim(htmlspecialchars($product['brand'])).'</g:brand>';
        $data .= '<g:condition>'.trim(htmlspecialchars($product['condition'])).'</g:condition>';
        $data .= '<g:availability>'.trim(htmlspecialchars($product['availability'])).'</g:availability>';
        $data .= '<g:price>'.trim(htmlspecialchars($product['price'])).' '.'USD</g:price>';
		$data .= '</item>';
	}
	$data .= '</channel>';
	$data .= '</rss>';
	return $data;
	
}

function insertPixelAndCatalog($data){
	dbConnect();
	$created_Time = date('Y-m-d H:i:s');
	$url=$data['store_URL'];
	       $query = "SELECT * from `script_shop` where `shop_url` = '$url'";
		   $result = mysql_query($query );
		   if(mysql_num_rows($result)){
			   dbConnect();
			   return array('status'=>2);
			    
		   }else{
			$query = "INSERT INTO `script_shop`(shop_url, catalog_id, pixel_id, created, updated) VALUES('".mysql_real_escape_string($data['store_URL'])."','".$data['catalog']."','".$data['pixelid']."','$created_Time', '$created_Time')";
			if(mysql_query($query)){
				
				dbClose();
				return array('status'=>1);
			}else{
				dbClose();
				return array('status'=>0,'error'=> 'Not inserted');
			}
		   }	
	
}

 function installPixelAndCatalogId($data){
/*  echo '<pre>';
 print_r($data); */
 dbConnect();
	    $url = $data['store_URL'];
	     
		   $query = "SELECT * from `script_shop` where `shop_url` = '$url'";
		    $result = mysql_query($query );
			while($row = mysql_fetch_array($result)){
			$data[] = array('catalog' => $row['catalog_id'], 'pixel' => $row['pixel_id']);
			}
			/* if($data){
			  return $data;
			}else{
			   return 0;
			} */
			
			 if($data){
				
				dbClose();
				return array('status'=>1,'data' => $data);
			}else{
				dbClose();
				return array('status'=>0,'error'=> 'Not Installed');
			} 
		   	
	
}

function updatePixelAndCatalogId($data){
	dbConnect();
	$created_Time = date('Y-m-d H:i:s');
	$url=$data['store_URL'];
	       
			$query = "UPDATE `script_shop` SET catalog_id = '".$data['catalog']."', pixel_id = '".$data['pixelid']."',updated = '$created_Time' WHERE shop_url = '$url'";
			if(mysql_query($query)){
				dbClose();
				return array('status'=>1);
			}else{
				dbClose();
				return array('status'=>0,'error'=> 'Not updated');
			}
		   	
	
}

function updateStatus($data){
    dbConnect();
	$url = $data['store_URL'];
	$status = $data['status'];
    $query = "UPDATE `script_shop` SET status = '$status' WHERE shop_url = '$url'";
	mysql_query($query);

}

function getPixelAndCatalog(){
	dbConnect();
	$query = "select * from `script_shop`";
	$result = mysql_query($query);
	while($row=mysql_fetch_array($result)){
			$fetchData[] = array('catalog_id' => $row['catalog_id'], 'pixel_id' => $row['pixel_id']);
	}
	if($fetchData){  
			return $fetchData;
	}else{
		return 0;
	}
		
}

function getPixelAndCatalogID($data){
	dbConnect();
	$url = $data;
	$created_Time = date('Y-m-d H:i:s');	
	$query = "SELECT * FROM script_shop WHERE `shop_url` = '$url'";
	$result = mysql_query($query);
	while($row=mysql_fetch_array($result)){
	
	/* echo '<pre>';
	print_r($row); */
	
		$fetchData[] = array('id' => $row['id'], 'pixel_id' => $row['pixel_id'], 'catalog_id' => $row['catalog_id'], 'status' => $row['status']);
	}
	if($fetchData){  
			return $fetchData;
	}else{
		return 0;
	}
		
}

function checkPixelAndCatalogID($data) {
	
	dbConnect();
	$url = $data;
	$query = "SELECT * FROM script_shop WHERE `shop_url` = '$url'";
	$result = mysql_query($query);
	$row = mysql_num_rows($result);
	if($row) {
		return $row;
	}
}

function deletePixelAndCatalogID($data){
		dbConnect();
		
		$query = "DELETE FROM `script_shop` WHERE `shop_url` = '$data'";
		$result = mysql_query($query);
		if($result){
				
				dbClose();
				return array('status'=>1);
			}else{
				dbClose();
				return array('status'=>0,'error'=> 'Not delete');
			}

}


	