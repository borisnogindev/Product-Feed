<?php
class Shopify{
	public $client_Id;
	public $client_Secret;
	public $code;
	public $redirect_Url;
	public $access_Token ;
	public $shop_Url;
	
	public function __construct($config = array()){
		$this->client_Id = $config['client_Id'];
		if(isset($config['client_Secret'])){
			$this->client_Secret = $config['client_Secret'];
		}
		if(isset($config['code'])){
			$this->code = $config['code'];
		}
		$this->redirect_Url = $config['redirect_uri'];	
		$this->shop_Url = $config['url'];
	}
	
	public function getAccessToken(){
		if($this->code){
			$url = "https://".$this->shop_Url."/admin/oauth/access_token";
			$config_Array = array('client_id'=>$this->client_Id,
								  'client_secret'=>$this->client_Secret,
								  'code'=>$this->code
							);
			$data = http_build_query($config_Array);
			$response = $this->http_Curl($url ,$data, null);
			if($response['status']){
				$this->ACCESS_TOKEN =  $response['result']['access_token'];
				return array('status'=>1, 'token'=>$response['result']['access_token']);
			}
			else{
				return array('status'=>0, 'error'=>$response['error']);
			}
		}
		else{
			$this->getUrl();
		}	
 	}
	
	public function getUrl(){
		$url = "https://".$this->shop_Url."/admin/oauth/authorize?client_id=".$this->client_Id."&redirect_uri=".$this->redirect_Url."&scope=read_products,write_products,read_themes,write_themes,read_script_tags,write_script_tags";
		header("Location: $url");
		exit();
	}
	
    
	 
	
	
	
	public function setAccessToken($token){
		if($token == "" ){
		    return array('status'=>0,'Error'=>"Token is missing. Please check");
		}
		$this->ACCESS_TOKEN = $token;
		return array('status'=>1, 'result'=>'Configured');
	}
	
	function getShopData(){
		$shop_Url = 'https://'.$this->client_Id.':'.$this->client_Secret.'@'. $this->shop_Url . '/admin/shop.json';
		$header = array('Accept: application/json', 'Content-Type: application/json','X-Shopify-Access-Token: '.$this->ACCESS_TOKEN);
		return $response = $this->http_Curl($shop_Url, null, $header);		
	}
	
	
	public function getAllProuctData(){
		$i = 1;
		$products = array();
		for($i=1;$i<1000;$i++){
			$product_Url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url . '/admin/products.json?limit=100&page='.$i;
			$header = array('Accept: application/json', 'Content-Type: application/json','X-Shopify-Access-Token: '.$this->ACCESS_TOKEN);
			$response = $this->http_Curl($product_Url, null, $header);
			if($response['status']){
				if(empty($response['result']['products'])){
					break;
				}
				else{
					$products = array_merge($products, $response['result']['products']);
				}
			}
			else{
				return array('status'=>0,'error'=>$response['error']);
			}
		}
		return array('status'=>1,'products'=> $products);
	}
	
	public function getCollectionProuctData($id){
		if(empty($id)){
			return array('status'=>0,'error'=>"Collection Id is missing");
		}
		$i = 1;
		$products = array();
		for($i=1;$i<1000;$i++){
			$product_Url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url . '/admin/products.json?limit=100&collection_id='.$id.'&page='.$i;
			$header = array('Accept: application/json', 'Content-Type: application/json','X-Shopify-Access-Token: '.$this->ACCESS_TOKEN);
			$response = $this->http_Curl($product_Url, null, $header);
			if($response['status']){
				if(empty($response['result']['products'])){
					break;
				}
				else{
					$products = array_merge($products, $response['result']['products']);
				}
			}
			else{
				return array('status'=>0,'error'=>$response['error']);
			}
		}
		return array('status'=>1,'products'=> $products);
	}
	
	public function getCollection(){
		$collection_Url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url . '/admin/custom_collections.json';
		$header = array('Accept: application/json', 'Content-Type: application/json','X-Shopify-Access-Token: '.$this->ACCESS_TOKEN);
		
		$response = $this->http_Curl($collection_Url, null, $header);
		if($response['status']){
			$collections = (array)$response['result']['custom_collections'];
			$collection_ID = array();
			$product_collections = array();
			foreach($collections as $collection){
				array_push($collection_ID, $collection['id']);
				$product_collections[$collection['id']] = array('id'=>(string)$collection['id'],'title'=>(string)$collection['title'],'desc'=>(string)$collection['body_html']);
			}
			if(!empty($product_collections)){
				return array('status'=>1,'result'=>$product_collections,'IDs'=>$collection_ID);
			}
			else{
				return array('status'=>0,'result'=>"No Collection are there!");
			}
		}
		else{
			return array('status'=>0,'result'=>$response['error']);
		}
	}
	public function parseProductData($products){
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
										//unset($images_Array['default'][$key]);
										break;
									}
								}
								else{
									$product_Detail['image_link'] = $images_Array['default'];
									//unset($images_Array['default']);
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
					$product_Detail['link'] = "http://".$this->shop_Url."/products/".trim($product['handle']);
					if($prod['title'] == "Default Title"){
						$product_Detail['title'] = ucfirst($product['title']);
					}
					else{
						$product_Detail['title'] = ucfirst($product['title']).' - '.ucfirst($prod['title']);
					}
					$product_Detail['price'] = $prod['price'];
					$product_Detail['brand'] = $product['vendor'];
					$product_Detail['item_group_id'] = $prod['product_id'];
					//$product_Detail['final'] = $images_Array['additional'] ? $images_Array['additional'] : '';
					if(!empty($images_Array['additional']) && !empty($product_Detail['image_link'])){
						$product_Detail['additional_Images'] = array_diff($images_Array['additional'], array($product_Detail['image_link']));
						if(count($product_Detail['additional_Images']) > 10){
							//$morethan10IMG[] = $product_Detail;
						}
					}
					else{
						$product_Detail['additional_Images'] = '';
					}
					
					if(isset($product_Detail['image_link'])){
					}
					else{
						$product_Detail['image_link'] = '';
						//$noImage[] = $product_Detail;
					}
					array_push($product_Array, $product_Detail);
				}
			}
		}
		
		if($product_Array != ""){
			//return array('status'=> 1, 'data'=>$product_Array, 'withoutImage'=> $noImage, 'morethan10IMG'=>$morethan10IMG);
			return array('status'=> 1, 'data'=>$product_Array);
		}
		else{
			return array('status'=> 0);
		}
	}
	
	public function createFeed($products, $shop){
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
			$data .= '<g:link>'.trim(htmlspecialchars($product['link'])).'</g:link>';
			$data .= '<g:image_link>'.trim($product['image_link']).'</g:image_link>';
			if(!empty($product['additional_Images'])){
				$i=0;
				foreach($product['additional_Images'] as $images){
					 $data .= '<g:additional_image_link>'.trim($images).'</g:additional_image_link>';
					 $i++;
					 if($i == 10){
						break;
					 }
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
	
	
	public function getthemeId(){
	    $url = "https://".$this->shop_Url."/admin/themes.json";
		$header = array('Accept: application/json', 'Content-Type: application/json','X-Shopify-Access-Token: '.$this->ACCESS_TOKEN);
		return $response = $this->http_Curl($url, null, $header);
	}
	
	public function getProductTemplate(){
	
	    $response = $this->getthemeId();
		$themes = $response['result']['themes'];

		foreach($themes as $theme){
			if($theme['role'] == 'main'){
				$theme_id = $theme['id'];
			}
		}
	    $shop_Url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url .'/admin/themes/'.$theme_id.'/assets.json?asset[key]=templates/product.liquid&theme_id=$theme_id';
		 
		
		 
		$header = array(	
					    'Accept: application/json',
						'Content-Type: application/json',
						'X-Shopify-Access-Token: '.$this->ACCESS_TOKEN
					);
					
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $shop_Url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER , $header);
		$y = curl_exec($ch);
		$themes = json_decode($y, true);
		return $themes['asset']['value'];
    } 
	 
	
	
	public function getCartTemplate(){
	
	    $response = $this->getthemeId();
		$themes = $response['result']['themes'];

		foreach($themes as $theme){
			if($theme['role'] == 'main'){
				$theme_id = $theme['id'];
			}
		}
	   
	   $shop_Url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url .'/admin/themes/'.$theme_id.'/assets.json?asset[key]=templates/cart.liquid&theme_id=$theme_id'; 
		$header = array(	
					    'Accept: application/json',
						'Content-Type: application/json',
						'X-Shopify-Access-Token: '.$this->ACCESS_TOKEN
					);
					
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $shop_Url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER , $header);
	  	$y = curl_exec($ch);
		$themes = json_decode($y, true);
		return $themes['asset']['value'];
	/* 	echo '<pre>';
	    print_r($themes);
		die(); */
	}
	
	
	
	public function getthemeHeader(){
	
	    $response = $this->getthemeId();
		$themes = $response['result']['themes'];

		foreach($themes as $theme){
			if($theme['role'] == 'main'){
				$theme_id = $theme['id'];
			}
		}
	 
        $shop_Url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url .'/admin/themes/'.$theme_id.'/assets.json?asset[key]=layout/theme.liquid&theme_id=$theme_id'; 
		$header = array(	
					    'Accept: application/json',
						'Content-Type: application/json',
						'X-Shopify-Access-Token: '.$this->ACCESS_TOKEN
					);
					
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $shop_Url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER , $header);
	  	$y = curl_exec($ch);
		$themes = json_decode($y, true);
		/*  echo '<pre>';
	    print_r($themes);
		die(); */ 
		return $themes['asset']['value'];
		
	}
	
	
	public function createAssets($scriptname,$script){
	
		$response = $this->getthemeId();
		$themes = $response['result']['themes'];

		foreach($themes as $theme){
			if($theme['role'] == 'main'){
				$theme_id = $theme['id'];
			}
		}

 
		$shop_Url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url . '/admin/themes/'.$theme_id.'/assets.json';
		
	    $data = array(
					'asset'=> array(
							"key"=>"snippets/$scriptname",
							"value"=>$script
						)
			    );
                                             
		$data = json_encode($data); 
		
		
        $header = array(	
						'Accept: application/json',
						'Content-Type: application/json',
						'X-Shopify-Access-Token: '.$this->ACCESS_TOKEN
					);
		
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $shop_Url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER , $header);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
		$y = curl_exec($ch);
		$themes = json_decode($y, true);
	/*  echo "<pre>";
		print_r($themes); */ 
		
		
	}

	
	
	public function putProductTemplate(){
	
	    $response = $this->getthemeId();
		$themes = $response['result']['themes'];
		foreach($themes as $theme){
			if($theme['role'] == 'main'){
				$theme_id = $theme['id'];
			}
		}
	   
	   $shop_Url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url .'/admin/themes/'.$theme_id.'/assets.json'; 
	   
	   
	    $response = $this->getProductTemplate();
		$row = $response."{% include 'pixelProduct' %}";
  
	    $data = array(
					'asset'=> array(
							"key"=>"templates/product.liquid",
							"value"=>$row
							)
					);		
		$data = json_encode($data);
		
		$header = array(	
		
					    'Accept: application/json',
						'Content-Type: application/json',
						'X-Shopify-Access-Token: '.$this->ACCESS_TOKEN
					);
					
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $shop_Url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER , $header);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
		$y = curl_exec($ch);
		$themes = json_decode($y, true); 
	    
	}

    public function putCartTemplate(){
	
	    $response = $this->getthemeId();
		$themes = $response['result']['themes'];
		foreach($themes as $theme){
			if($theme['role'] == 'main'){
				$theme_id = $theme['id'];
			}
		}
	   
	   $shop_Url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url .'/admin/themes/'.$theme_id.'/assets.json'; 
	   
	   
	    $response = $this->getCartTemplate();
		$row = "{% include 'pixelCart' %}".$response;
  
	    $data = array(
					'asset'=> array(
							"key"=>"templates/cart.liquid",
							"value"=>$row
							)
					);		
		$data = json_encode($data);
		
		$header = array(	
		
					    'Accept: application/json',
						'Content-Type: application/json',
						'X-Shopify-Access-Token: '.$this->ACCESS_TOKEN
					);
					
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $shop_Url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER , $header);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
		$y = curl_exec($ch);
		$themes = json_decode($y, true); 
	    
	}


     public function putThemeTemplate(){
	
	    $response = $this->getthemeId();
		$themes = $response['result']['themes'];
		foreach($themes as $theme){
			if($theme['role'] == 'main'){
				$theme_id = $theme['id'];
			}
		}
	   
	   $shop_Url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url .'/admin/themes/'.$theme_id.'/assets.json'; 
	   
	   $response = $this->getthemeHeader();
	   $res = explode("</head>",$response);
	   /* echo '<pre>';
	   print_r($res); */
	    
	    $res[0] .= "{% include 'pixelTheme' %}";
	    $final = implode("</head>",$res);
	    $data = array(
					'asset'=> array(
							"key"=>"layout/theme.liquid",
							"value"=> $final
							)
					);		
		$data = json_encode($data);
		
		$header = array(	
		
					    'Accept: application/json',
						'Content-Type: application/json',
						'X-Shopify-Access-Token: '.$this->ACCESS_TOKEN
					);
					
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $shop_Url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER , $header);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
		$y = curl_exec($ch);
		$themes = json_decode($y, true); 
	   
	}		
	
    public function copyThemeliquid(){
	
	    $response = $this->getthemeId();
		$themes = $response['result']['themes'];

		foreach($themes as $theme){
			if($theme['role'] == 'main'){
				$theme_id = $theme['id'];
			}
		}
	
	    $shop_Url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url .'/admin/themes/'.$theme_id.'/assets.json';
	
        $data = array(
					'asset'=> array(
							"key"=>"layout/theme_back.liquid",
							"source_key"=> "layout/theme.liquid"
							)
					);		
		$data = json_encode($data);
        
		$header = array(	
		
					    'Accept: application/json',
						'Content-Type: application/json',
						'X-Shopify-Access-Token: '.$this->ACCESS_TOKEN
					);
					
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $shop_Url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER , $header);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
		$y = curl_exec($ch);
		$themes = json_decode($y, true); 		
	
	}
	
	
	public function copyCartliquid(){
	
	    $response = $this->getthemeId();
		$themes = $response['result']['themes'];

		foreach($themes as $theme){
			if($theme['role'] == 'main'){
				$theme_id = $theme['id'];
			}
		}
	
	    $shop_Url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url .'/admin/themes/'.$theme_id.'/assets.json';
	
        $data = array(
					'asset'=> array(
							"key"=>"templates/cart_back.liquid",
							"source_key"=> "templates/cart.liquid"
							)
					);		
		$data = json_encode($data);
        
		$header = array(	
		
					    'Accept: application/json',
						'Content-Type: application/json',
						'X-Shopify-Access-Token: '.$this->ACCESS_TOKEN
					);
					
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $shop_Url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER , $header);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
		$y = curl_exec($ch);
		$themes = json_decode($y, true); 		
	
	}
	
	
	public function copyProductliquid(){
	
	    $response = $this->getthemeId();
		$themes = $response['result']['themes'];

		foreach($themes as $theme){
			if($theme['role'] == 'main'){
				$theme_id = $theme['id'];
			}
		}
	
	    $shop_Url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url .'/admin/themes/'.$theme_id.'/assets.json';
	
        $data = array(
					'asset'=> array(
							"key"=>"templates/product_back.liquid",
							"source_key"=> "templates/product.liquid"
							)
					);		
		$data = json_encode($data);
        
		$header = array(	
		
					    'Accept: application/json',
						'Content-Type: application/json',
						'X-Shopify-Access-Token: '.$this->ACCESS_TOKEN
					);
					
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $shop_Url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER , $header);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
		$y = curl_exec($ch);
		$themes = json_decode($y, true); 		
	
	}
	
	
	public function copyputThemeliquid(){
	
	    $response = $this->getthemeId();
		$themes = $response['result']['themes'];

		foreach($themes as $theme){
			if($theme['role'] == 'main'){
				$theme_id = $theme['id'];
			}
		}
	
	    $shop_Url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url .'/admin/themes/'.$theme_id.'/assets.json';
	
        $data = array(
					'asset'=> array(
							"key"=>"layout/theme.liquid",
							"source_key"=> "layout/theme_back.liquid"
							)
					);		
		$data = json_encode($data);
        
		$header = array(	
		
					    'Accept: application/json',
						'Content-Type: application/json',
						'X-Shopify-Access-Token: '.$this->ACCESS_TOKEN
					);
					
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $shop_Url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER , $header);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
		$y = curl_exec($ch);
		$themes = json_decode($y, true); 		
	
	}
	
	
	public function copyputCartliquid(){
	
	    $response = $this->getthemeId();
		$themes = $response['result']['themes'];

		foreach($themes as $theme){
			if($theme['role'] == 'main'){
				$theme_id = $theme['id'];
			}
		}
	
	    $shop_Url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url .'/admin/themes/'.$theme_id.'/assets.json';
	
        $data = array(
					'asset'=> array(
							"key"=>"templates/cart.liquid",
							"source_key"=> "templates/cart_back.liquid"
							)
					);		
		$data = json_encode($data);
        
		$header = array(	
		
					    'Accept: application/json',
						'Content-Type: application/json',
						'X-Shopify-Access-Token: '.$this->ACCESS_TOKEN
					);
					
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $shop_Url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER , $header);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
		$y = curl_exec($ch);
		$themes = json_decode($y, true); 		
	
	}
	
	
	public function copyputProductliquid(){
	
	    $response = $this->getthemeId();
		$themes = $response['result']['themes'];

		foreach($themes as $theme){
			if($theme['role'] == 'main'){
				$theme_id = $theme['id'];
			}
		}
	
	    $shop_Url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url .'/admin/themes/'.$theme_id.'/assets.json';
	
        $data = array(
					'asset'=> array(
							"key"=>"templates/product.liquid",
							"source_key"=> "templates/product_back.liquid"
							)
					);		
		$data = json_encode($data);
        
		$header = array(	
		
					    'Accept: application/json',
						'Content-Type: application/json',
						'X-Shopify-Access-Token: '.$this->ACCESS_TOKEN
					);
					
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $shop_Url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER , $header);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
		$y = curl_exec($ch);
		$themes = json_decode($y, true); 		
	
	}
	
	
	public function deleteAssets(){
	
	    $response = $this->getthemeId();
		$themes = $response['result']['themes'];

		foreach($themes as $theme){
			if($theme['role'] == 'main'){
				$theme_id = $theme['id'];
			}
		}

		$shop_Url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url . '/admin/themes/'.$theme_id.'/assets.json?asset[key]=snippets/pixelCart.liquid';
		$header = array('Accept: application/json', 'Content-Type: application/json','X-Shopify-Access-Token: '.$this->ACCESS_TOKEN);
		$this->deleteCurl($url, $header);
	}	
	
	public function http_Curl($url ,$post_field, $header){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		if($header){
			curl_setopt($ch, CURLOPT_HTTPHEADER , $header);
		}
	    if(!empty($post_field)){	
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);
		}	
		$y = curl_exec($ch);
		$info = curl_getinfo($ch);
		if($y !== false){
			curl_close($ch);
			if($info['http_code'] == 200 || $info['http_code'] == 201){
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
	
	
	public function registerShopifyAppUninstallWebhook(){
		$url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url . '/admin/webhooks.json';
		
		$data = array('webhook'=>array('topic'=>'app/uninstalled','address'=>'https://shopiapps.io/apps/hook.php','format'=>'json'));
		$data = json_encode($data);
		$header = array('Accept: application/json', 'Content-Type: application/json','X-Shopify-Access-Token: '.$this->ACCESS_TOKEN);
		$response = $this->http_Curl($url, $data, $header);
		return $response;   
    }
	
	public function createFacebookPixcel($file){
		$url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url . '/admin/script_tags.json';
		 $data = array("script_tag"=> array("event"=>"onload","src"=>$file));
		 $data = json_encode($data);
		 $header = array('Accept: application/json', 'Content-Type: application/json','X-Shopify-Access-Token: '.$this->ACCESS_TOKEN);
		 $response = $this->http_Curl($url, $data, $header);
		 return $response;
	}
	
	 public function deleteFacebookPixcel($id){
		$url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url . '/admin/script_tags/'.$id.'.json';
		$header = array('Accept: application/json', 'Content-Type: application/json','X-Shopify-Access-Token: '.$this->ACCESS_TOKEN);
		$this->deleteCurl($url, $header);
	} 
	public function getFacebookPixcel(){
		$url = 'https://' . $this->client_Id . ':' . $this->client_Secret . '@' . $this->shop_Url . '/admin/script_tags.json';
		$header = array('Accept: application/json', 'Content-Type: application/json','X-Shopify-Access-Token: '.$this->ACCESS_TOKEN);
		$response = $this->http_Curl($url,"", $header);
		return $response;
	}
	
	public function deleteCurl($url, $header){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch, CURLOPT_HTTPHEADER , $header);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
		$y = curl_exec($ch);
		curl_close($ch);
	}
}	
?>