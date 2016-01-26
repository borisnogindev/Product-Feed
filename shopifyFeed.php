<?php
session_start();
ob_start(); 
ini_set('display_errors', 1);
require_once('config.php');
require_once("shopify.php");
require_once('functions.php');

if(isset($_GET['shop'])){
	$STORE_URL = $_GET['shop'];
	if(isset($_SESSION['shop'])){
		if($_SESSION['shop'] == $STORE_URL){
			$response = checkShopExists($STORE_URL);
			if($response['status'] == 1){
				$token = $_SESSION['token'] = $response['result']['token'];
				$_SESSION['Id'] = $response['result']['id'];
			}
			else{
				$token = "";
			}
		}else{
			unset($_SESSION);
			$response = checkShopExists($STORE_URL);
			if($response['status'] == 1){
				$token = $_SESSION['token'] = $response['result']['token'];
				$_SESSION['Id'] = $response['result']['id'];
				$_SESSION['shop'] = $STORE_URL;
			}
			else{
				$token = "";
			}
		}
	}else{
		$response = checkShopExists($STORE_URL);
		if($response['status'] == 1){
			$token = $_SESSION['token'] = $response['result']['token'];
			$_SESSION['Id'] = $response['result']['id'];
			$_SESSION['shop'] = $STORE_URL;
		}
		else{
			$token = "";
		}
	}
}
else{
	if(isset($_SESSION['shop'])){
		$response = checkShopExists($_SESSION['shop']);
		if($response['status'] == 1){
			$token = $_SESSION['token'] = $response['result']['token'];
			$_SESSION['Id'] = $response['result']['id'];
		}
		else{
			$token = "";
		}
	}
	else{
		$token = "";
		echo "Shop url Is missing. Please try again";
		die();
	}
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
		//echo $response['result'];
	}else{
		echo $response['error'];
		die();
	}
}else{ 
	if(isset($_GET['code']) && isset($_GET['shop'])){
		$config = array('client_Id' => APIKEY,
						'code' => $_GET['code'],
						'redirect_uri' => REDIRECT_URL,
						'client_Secret' =>  SECRET,
						'url'=> $STORE_URL,
				);		
	}
	else{
		$config  = 	array('client_Id' => APIKEY,
						'redirect_uri' => REDIRECT_URL,
						'url'=> $STORE_URL,
					);
		}
		$productFeed = new shopify($config);
		$response = $productFeed->getAccessToken();
		if($response['status']){
			$data = array('token'=>$response['token'], 'shop'=>$STORE_URL);
			$response = createAccount($data);
			if($response['status']){
				$response = $productFeed->registerShopifyAppUninstallWebhook();
				if(!$response['status']){
					echo $response['error'];
					die();
				}
			}
			else{
				echo $response['error'];
				die();
			}
		}else{
			echo $response['error'];
			die();
		}
}
################ Token Get Process End #####################
$response = $productFeed->getCollection(); 
if($response['status']){
	$product_collections = $response['result'];
	$collection_Id = $response['IDs'];
}
else{
	echo $response['error'];
	die();
}
###############################################
$response = getCollections($_SESSION['shop']);
if($response['status']){
	$collection_Entry = $response['result'];
}
if(isset($collection_Entry)){
	$collection = array_diff( $collection_Id, $collection_Entry);
}else{
	$collection = $collection_Id;
}

if(isset($_POST['deletepixel'])){
	$id = 23;
  deletePixel($id);

}


/* $response = createAssets(); 
if($response['status']){
	
}
else{
	echo $response['error'];
	die();
} */


?>

<!--################ product id Get Process End #####################-->
<html>
	<head>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/jquery.js"></script>
	<script type="text/javascript" src="js/DataSet.js"></script>
	<script type="text/javascript" src="js/sweetalert.min.js"></script>
    <script type="text/javascript">
			$(document).ready(function(){
				$('#myTable').DataTable({
					 "lengthChange": false,
					  "searching": false
				}); 
				
				$("#checkAllBox").click(function (){
					$(".checkBoxClass").attr('checked', this.checked)
				});
				
				var table =  $('#myTable1').DataTable(
				{"scrollY":"400px", "scrollCollapse": true});
				$("#button").click(function(){
					var feedName = $("#coll").val();
					if(feedName == ""){
						swal({
							title: "Feed Name Missing",
							text: "Please Enter Feed name",
							timer: 2000,
							showConfirmButton: false
						});
						return false;
						$("#coll").focus();
					}
					var data = table.$('input').serialize();
				$.ajax({
						type: "POST",
						url: "process2.php",
						data: data+'&FeedName='+feedName,
						async: false,
						success: function(data){
							if(data){
								swal({
								  title: "Success",
								  text: "Feed Created Successfully",
								  timer: 1000,
								  showConfirmButton: false
								});
								location.reload();
								$(".modalDialog").css({'opacity':0,'pointer-events':'none'});
								$('.tab').find('input[type=checkbox]:checked').removeAttr('checked');
								$("#coll").val("");
							}
							else{
								$('.tab').find('input[type=checkbox]:checked').removeAttr('checked');
								$("#coll").val("");
								swal({
									title: "Error",
									text: "Please select products",
									timer: 1500,
									showConfirmButton: false
								});
								
							}
						},
						error: function(){
							$('.tab').find('input[type=checkbox]:checked').removeAttr('checked');
							$("#coll").val("");
							swal({
							  title: "Error",
							  text: "Please try again",
							  timer: 1000,
							  showConfirmButton: false
							});
						}
					});
					
					
				});
				
				
				$("#pixcelcode").click(function(){
				/* alert('hello'); */
				   var Pixel_ID = document.getElementById("Pixel_ID").value;
				   var Catalog_ID = document.getElementById("Catalog_ID").value;
				    //alert(Pixel_ID); 
				    //alert(Catalog_ID); 
					 if(Pixel_ID == "" && Catalog_ID == ""){
						swal({ 
							title: "Please insert pixel and catalog id!",
							text: "",
							type: "warning",
							//showCancelButton: true,
							confirmButtonColor: '#DD6B55',
							confirmButtonText: 'Ok',
							closeOnConfirm: true
						});
						
						return false;
					} 
					else{
					
					swal({ 
							title: "Are you sure?",
							text: "",
							type: "warning",
							showCancelButton: true,
							confirmButtonColor: '#DD6B56',
							confirmButtonText: 'Yes',
							closeOnConfirm: true
						},
						
						function(){
							var pixel = $("#Pixel_ID").val();
							var catalogid = $("#Catalog_ID").val();
							$.post("process.php",{method:'pixelAndCatalog',pixelid:pixel,catalog:catalogid},function(data){
							
									 swal({
									  title: "",
									  text: "<span style='color:green'>"+data+"</span>",
									  timer: 1500,
									  html:true,
									  showConfirmButton: false
									}); 
									location.reload();
							}); 
						});
						
					}
				});
				
			
				$("#updatePixelandcatalogId").click(function(){
				
				   var Pixel_ID = document.getElementById("Up_Pixel_ID").value;
				   var Catalog_ID = document.getElementById("Up_Catalog_ID").value;
				    /* alert(Pixel_ID); 
				    alert(Catalog_ID); */  
					 if(Pixel_ID == "" && Catalog_ID == ""){
						swal({ 
							title: "Are you want to update?",
							text: "",
							type: "warning",
							showCancelButton: true,
							confirmButtonColor: '#DD6B55',
							confirmButtonText: 'Yes',
							closeOnConfirm: true
						});
						
						return false;
					} 
					else{
					
					swal({ 
							title: "Are you sure?",
							text: "",
							type: "warning",
							showCancelButton: true,
							confirmButtonColor: '#DD6B55',
							confirmButtonText: 'Yes',
							closeOnConfirm: true
						},
							
				    	function(){
							var pixel = $("#Up_Pixel_ID").val();
							var catalogid = $("#Up_Catalog_ID").val();
							$.post("process.php",{method:'pixelAndCatalogUpdate',pixelid:pixel,catalog:catalogid},function(data){
							
									 swal({
									  title: "",
									  text: "<span style='color:green'>"+data+"</span>",
									  timer: 1500,
									  html:true,
									  showConfirmButton: false
									}); 
									location.reload();
							}); 
						});
						
					}
				});
				
				
		var status = document.getElementById("installpix").value;
			if(status == 1){
			$("#installcode").hide();
			$("#uninstallcode").show();
			}else{
				$("#installcode").show();
				$("#uninstallcode").hide();
				}
			
			    $("#installcode").click(function(){
				
				 var Pixel_ID = document.getElementById("dPixel").value;
				   var Catalog_ID = document.getElementById("dCatalog").value;
				   
					 if(Pixel_ID == "" && Catalog_ID == ""){
						swal({ 
							title: "Please insert pixel and catalog id first!",
							text: "",
							type: "warning",
							//showCancelButton: true,
							confirmButtonColor: '#DD6B55',
							confirmButtonText: 'Ok',
							closeOnConfirm: true
						});
						
						return false;
					} 
					else{
				
					
					swal({ 
							title: "Are you sure?",
							text: "",
							type: "warning",
							showCancelButton: true,
							confirmButtonColor: '#DD6B55',
							confirmButtonText: 'Yes',
							closeOnConfirm: true
								
						},
						
									
				    	function(){
							var dPixel = $("#dPixel").val();
							var dCatalog = $("#dCatalog").val();
							//alert(dPixel);
							//var catalogid = $("#Catalog_ID").val();
							$.post("process.php",{method:'installPixels',dPixel:dPixel,dCatalog:dCatalog},function(data){
							//alert(data);
									  swal({
									  title: "",
									  text: "<span style='color:green'>"+data+"</span>",
									  timer: 1500,
									  html:true,
									  showConfirmButton: false
									  
									});  
									
											$("#installcode").hide();
											$("#uninstallcode").show();
										    location.reload();
									
									}); 
									
							}); 
						
						}
					
				});
				
			$("#uninstallcode").click(function(){
				
					swal({ 
							title: "Are you sure?",
							text: "",
							type: "warning",
							showCancelButton: true,
							confirmButtonColor: '#DD6B55',
							confirmButtonText: 'Yes',
							closeOnConfirm: true
						},
							
				    	function(){
							//var dPixel = $("#dPixel").val();
							//var dCatalog = $("#dCatalog").val();
							$.post("process.php",{method:'uninstallPixels'},function(data){
							
									 swal({
									  title: "",
									  text: "<span style='color:green'>"+data+"</span>",
									  timer: 1500,
									  html:true,
									  showConfirmButton: false
									}); 
									
									$("#uninstallcode").hide();
									$("#installcode").show();
									location.reload();
							}); 
						});
						
					
				});
				
			});
			
	function closeModal(e){
				e.preventDefault();
				$('.tab').find('input[type=checkbox]:checked').removeAttr('checked');
				$("#coll").val("");
				$(".modalDialog").css({'opacity':0,'pointer-events':'none'});
				
			} 
	function feeds(){
				var data = "";
				<?php
					if(is_array($collection) && !empty($collection)){
						foreach($collection as $coll){
							?>
							data += "<h3 onclick='check(<?php echo $product_collections[$coll]['id'];?>,\"<?php echo $product_collections[$coll]['title'];?>\");'>" + '<?php echo $product_collections[$coll]['title'];?>' + "</h3>"
							<?php
						}
						?>
						data = 	"<ul>"+ data +"</ul>";
						<?php
					}
					else{
						?>
						data = "Sorry, No Feed avaialble to Generate!";
						<?php
					}
				?>
				swal({
					title: "Available Feeds",
					text: data,
					showCancelButton: true,
					showConfirmButton: false,
					html: true
				});
			}
			
			function customFeed(){
				swal.close();
				$(".modalDialog").css({'opacity':1,'pointer-events':'auto'});
			}
			function feedss(){
				
					data = "<h3 onclick='feeds();'>" + '<?php echo '<a href="#" class="myButton">Create Collection Feed</a>'; ?>' + "</h3><br><h3 onclick='customFeed();'>" + '<?php echo '<a href="#" class="myButton">Create Custom Feed</a>'; ?>' + "</h3>"
					
				swal({
					title: "Select Feed Source",
					text: data,
					showCancelButton: true,
					showConfirmButton: false,
					html: true
				});
			}
			
			function check(id, title){
				swal({
				  title: "Are you sure to Create "+ title +" Feed?",
				  type: "warning",
				  showCancelButton: true,
				  confirmButtonColor: "#DD6B55",
				  confirmButtonText: "Yes, Create Feed!",
				  cancelButtonText: "No, cancel please!",
				  closeOnConfirm: false,
				  closeOnCancel: false
				},
				function(isConfirm){
				  if (isConfirm) {
				$.post("process.php",{id:id,method:'insert'},function(data){
						if(data == 1){
							swal({
							  title: "Success",
							  text: "Feed Created Successfully",
							  timer: 800,
							  showConfirmButton: false
							});
							location.reload(); 
						}
					});
				  } else {
						swal("Cancelled", "You have cancelled it", "error");
				  }
				});
			}

			function deleteCollection(id, shop, type){
				swal({
					title: "Are you sure To delete?",
					type: "warning",
					showCancelButton: true,
					confirmButtonColor: "#DD6B55",
					confirmButtonText: "Yes, delete it!",
					closeOnConfirm: true
				},
				function(){
					$.post("process.php",{id:id,shop:shop,method:'delete',type:type},function(data){
						if(data == 1){
							swal({
							  title: "Deleted",
							  text: "Feed Deleted Successfully",
							  timer: 800,
							  showConfirmButton: false
							});
							location.reload();
						}
						else{
							swal("", "Error in Deletion", "error");
						}
					});
				});
			}
			
		function deletePixel(){
			    
			swal({
					title: "Are you sure To delete?",
					type: "warning",
					showCancelButton: true,
					confirmButtonColor: "#DD6B55",
					confirmButtonText: "Yes, delete it!",
					closeOnConfirm: true
				},
				function(){
					$.post("process.php",{method:'deletepixel'},function(data){
						//alert(data);
						if(data == 1){
							swal({
							  title: "Deleted",
							  text: "Pixel Deleted Successfully",
							  timer: 1000,
							  showConfirmButton: false
							});
							location.reload();
						}
						else if(data == 2){
							swal({
							  title: "Not Avaialble",
							  text: "No pixcel is available for deletion",
							  timer: 800,
							  showConfirmButton: false
							});
						}else{
							swal("", "Error in Deletion", "error");
						}
					});
				});
			
        }
$(document).ready(function(){
    $(".test").click(function(){
        $(".pixelfa").hide();
    });
      $(".tab-content").click(function(){
        $(".pixelfa").show();
    });  
});

$(document).ready(function(){
    $(".body").click(function(){
        $("#tab-content").hide();
    });
       $(".tested").click(function(){
        $("#tab-content").show();
    });   
});
</script>



<style>
textarea {
    background: none;
    border: none;
    box-sizing: border-box;
    color: #4e5665;
    font-family: Menlo,Monaco,Andale Mono,Courier New,monospace;
    font-size: 14px;
    height: auto;
    line-height: 20px;
    padding: 12px;
    width: 100%;
}


.test1 {

    border-radius: 3px;}
</style>
<img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=718673878207457&ev=PageView&noscript=1"
/>
        <link rel="stylesheet" type="text/css" href="css/style.css">
		<link rel="stylesheet" type="text/css" href="css/DataSet.css">
		<link rel="stylesheet" type="text/css" href="css/sweetalert.css">
		<link rel="stylesheet" type="text/css" href="css/feed.css">
	</head>
	<body>
	
	<div class="container">
		
			<div class="header"></div>
			
			<ul class="nav nav-pills" id = "nav nav-pills" style="margin-top:32px;">
				<li class="active"><a class = "test" data-toggle="pill" href="#productFeed">Product Feeds</a></li>
				<li><a class = "test" data-toggle="pill" href="#facebookPixel">Facebook Pixels</a></li>
				
			</ul>
			<br><br><br>
			
			<div class="tab-content">
				<div id="productFeed" class="tab-pane fade in active">
					 
					<div class="body">
						<p>
							<input type="image" class="feed" src="images/button (3).png" onclick="feedss();">
							<div id="openModal" class="modalDialog">
							<!--a href="#close" title="Close" class="close">X</a>
							<!--a href="" onclick="closeModel()" class="close" >X</a-->
							<div class="tab" style="height:100%; overflow-x: hidden; overflow-y: hidden;">
							<a href="#" onclick="closeModal(event);"  class="close" >X</a>
							<!--div class="test" style="width:100%;text-align:center; height:425px; overflow-y: scroll;"-->
							<form method ="POST">
							
							<div id="output"></div>
							<div class="submit1" align="center">
							Enter Name of Custom Feed&nbsp;&nbsp;&nbsp;<input type="text" name="collection" id="coll" placeholder="Feed Name" class ="txt-box-sty"/>&nbsp;&nbsp;&nbsp;<input type="button" class ="sub-btn-sty" id="button" value="Submit"/>
							</div>
								  
									<table id="myTable1" class="display1" cellspacing="0" >
										<!--input type="checkbox" id="checkAllBox" class = "ck-all-box"-->
										<thead>
											<tr class="thead">
												<th class="text-select">Select</th>
												<th>Title</th>
												<th class="text-image">Image</th>
												
											</tr>
											</thead>
												<tbody>
												
													<?php 
													$response = $productFeed->getAllProuctData();
													if($response['status']){
														$response = $productFeed->parseProductData($response['products']);
														foreach($response['data'] as $prod){
															?>
															<tr><td class="chk-box"><input type="checkbox" class="checkBoxClass " name="ids[]" value="<?php echo $prod['id'];?>"></td><td><?php echo $prod['title'];?></td><td class="img-right"><img src="<?php echo $prod['image_link'];?>" width="60px" height="80px"></td></tr>
															<?php
														} ?>												
														<?php
													}
													else{
														echo $response['error'];
													}
													 ?>
												</tbody>
											</table>
										</form>
									</div>
								<!--/div-->
							</div>
						</p>
						<?php 
						$resp = getCustomCollection($_SESSION['shop']);
						//echo '<pre>';
						//print_r($resp);
						if(isset($collection_Entry) || !empty($resp['result'])){
							?>
							<table id="myTable" class="display" cellspacing="0" style="width:60%;text-align:center;">
								<thead>
									<tr class="thead">
										<th style="text-align:center;">Title</th>
										<th style="text-align:center;">Description</th>
										<th style="text-align:center;">Feed Link</th>
										<th style="text-align:center;">Action</th>
									</tr>
								</thead>
								<tbody>
									<?php
								if(isset($collection_Entry)){
									foreach($collection_Entry as $collect){
										?>
										<tr>
											<td><?php echo $product_collections[$collect]['title'];?></td>
											<td><?php echo $product_collections[$collect]['desc'];?></td>
											<td><a href="collectionFeed.php?type=collection&id=<?php echo $product_collections[$collect]['id'];?>&shop=<?php echo $_SESSION['shop'];?>" target="_blank">Generate Feed</a></td>
											<td><img onclick="deleteCollection('<?php echo $product_collections[$collect]['id'];?>', '<?php echo $_SESSION['shop'];?>','collection');" src="images/delete.png"></td>
										</tr>
										<?php
									} 
								}
								if(!empty($resp['result'])){
									foreach($resp['result'] as $res){
										?>
										<tr>
											<td><?php echo $res['name'];?></td>
											<td><?php echo  $res['name'];?></td>
											<td><a href="collectionFeed.php?type=custom&id=<?php echo $res['id'];?>&shop=<?php echo $_SESSION['shop'];?>" target="_blank">Generate Feed</a></td>
											<td><img onclick="deleteCollection('<?php echo $res['id'];?>', '<?php echo $_SESSION['shop'];?>','custom');" src="images/delete.png"></td>
										</tr>
										<?php
									} 
								}
									?>
								</tbody>
							</table>
							<?php
						}
						else{
							?>
							<div>No Feed Yet Created. Click on Create feed Button to generate feeds.</div>
							<?php
						} ?>
					</div>
				 
			    </div>
				
				<div id="facebookPixel" class="tab-pane fade">
					  
					<ul class="nav nav-pills" style="margin-top: -51px;  padding-left: 135px;">
						<li><a class = "tested" data-toggle="pill" href="#manage">Manage</a></li>
						<li><a class = "tested" data-toggle="pill" href="#instruction">Instructions </a></li>
						<li><a class = "tested" data-toggle="pill" href="#getcode">Get Code </a></li>
					</ul> 
				</div>
    
			</div>
    <div class= "pixelfa">			
        <div class="tab-content " id = "tab-content">
				<div id="manage" class="tab-pane fade" style=" padding-left: 135px;">
					<?php 
					$url = $_SESSION['shop'];
					$checkURL = checkPixelAndCatalogID($url);
					if($checkURL != ""){
					
					$data = getPixelAndCatalogID($url);
					/* echo '<pre>';
                    print_r($data); */
						foreach($data as $rows){
							$shopURLID = $rows['id'];
							$catalog = $rows['catalog_id'];
							$pixel = $rows['pixel_id'];
							$status = $rows['status'];
						}
					}
					?>
					<input type = "hidden" value = "<?php echo $status; ?>" id = "installpix">
					<input type = "hidden" value = "<?php echo $pixel; ?>" id = "dPixel">
					<input type = "hidden" value = "<?php echo $catalog; ?>" id = "dCatalog">
					<input type = "textbox" placeholder = "Pixel Id" id="Pixel_ID" value = "<?php echo $pixel; ?>">&nbsp; &nbsp; &nbsp <input type = "textbox" placeholder = "Catalog Id" id ="Catalog_ID" value = "<?php echo $catalog; ?>">&nbsp; &nbsp; &nbsp <a class = "tast" data-toggle="pill" href="#Save" id="pixcelcode">Save</a>&nbsp; &nbsp;&nbsp 
						
							
							<a class = "tast" data-toggle="pill" href="#UnInstall" id = "uninstallcode">UnInstall</a>
							<a class = "tast" data-toggle="pill" href="#Install" id = "installcode">Install</a>&nbsp; &nbsp;&nbsp  <a class = "tast" href="#" data-toggle="modal" data-target="#editPixelandcatalog">Edit</a>&nbsp; &nbsp;&nbsp <a class = "tast" href="#" data-toggle="modal" data-target="#deletePixelandcatalog" id = "deletepix">Delete</a>
					<div class= "textPixel1" style="padding-top: 20px;">
					
<textarea readonly="1" rows="10" spellcheck="false" onclick="this.focus(); this.select()" tabindex="0" dir="ltr" style="direction: ltr; margin: 0px; border: thin solid; overflow-y:hidden; background-color: lightgray; border-radius: 20px;height: 305px;">

		<?php 
													
				if($status == 0){
					echo 'Pixel not installed. Please install pixel first to get checkout script.';
				}
				 else{
		?>
&lt;!-- Checkout Purchase Pixel Code --&gt; 
&lt;script&gt; 
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod? n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n; n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0; t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script','//connect.facebook.net/en_US/fbevents.js'); fbq('init', '<?php echo "$pixel"?>'); fbq('track', 'PageView'); fbq('track', 'Purchase', { product_catalog_id: '<?php echo "$catalog"?>', content_ids: '{{ variant.id }}', content_type: 'product_group', value: '{{ total_price | money_without_currency }}', currency: 'USD' }); &lt;/script&gt; &lt;noscript&gt;&lt;img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id= <?php echo "$pixel"?> &ev=PageView&noscript=1" /&gt;&lt;/noscript&gt;&lt;noscript&gt;&lt;img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id= <?php echo "$pixel"?>&ev=Purchase&noscript=1" /&gt;
&lt;/noscript&gt; 
&lt;!-- End Facebook Pixel Code --&gt;
 <?php } ?>					
</textarea></div><br>
                     
					    
						<h4 style= "padding-left: 185px; margin-top: 1px;";>Install this code in Settings > Checkout >Additional content and scripts</h4> 
			    </div>
					
				 <!-- Modal -->
            <div class="modal fade" id="editPixelandcatalog" role="dialog">
				<div class="modal-dialog">
					
					  <!-- Modal content-->
					<div class="modal-content">
						<div class="modal-header">
						  <!--button type="button" class="close" data-dismiss="modal">&times;</button-->
						  <h4 class="modal-title">Update pixel & catalog Id</h4>
						</div>
						<div class="modal-body">
							<?php 
								
								if($checkURL == ""){
									echo 'No pixel for Updation';
								}
								else{
								
							?>
								<h4>Pixel Id &nbsp; &nbsp; &nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; &nbsp; &nbsp;&nbsp; &nbsp; &nbsp;&nbsp; &nbsp; &nbsp;&nbsp; &nbsp; &nbsp; Catalog Id</h4><input type = "textbox" id = "Up_Pixel_ID" value = "<?php echo $pixel; ?>">&nbsp; &nbsp; &nbsp; <input type = "textbox" value = "<?php echo $catalog; ?>" id ="Up_Catalog_ID">&nbsp; &nbsp;&nbsp <a class = "tast" href="#update" id = "updatePixelandcatalogId">Update</a>
								<?php } ?>
						</div>
						<div class="modal-footer">
						  <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
						</div>
					</div>
					  
				</div>
            </div>
  
  
  
  
            <div class="modal fade" id="deletePixelandcatalog" role="dialog">
				<div class="modal-dialog">
				
				  <!-- Modal content-->
				    <div class="modal-content">
						<div class="modal-header">
						  <!--button type="button" class="close" data-dismiss="modal">&times;</button-->
						  <h4 class="modal-title">Delete Pixel & Catalog Id</h4>
						</div>
						<div class="modal-body">
							<?php 
								
								if($checkURL == ""){
								   
								      echo 'No pixel for deletion';
								}
								else if($status == 1){
								
								
									echo 'Please Uninstall Pixel First';
								}
								else{
								
							?>
								
						    <div id="myTable_wrapper" class="dataTables_wrapper no-footer">
								<table cellspacing="0" style="width:85%; text-align:center;" class = "display dataTable no-footer" id="myTable" role="grid" aria-describedby="myTable_info">
										<thead>
											<tr class="thead" role="row">
											<th style="text-align:center;" class="sorting_asc" tabindex="0" aria-controls="myTable" rowspan="1" colspan="1" style="width: 80px; " aria-sort="ascending" aria-label="Title: activate to sort column descending">ID</th>
											<!--th style="text-align:center;" class="sorting" tabindex="0" aria-controls="myTable" rowspan="1" colspan="1" style="width: 173px;" aria-label="Description: activate to sort column ascending">File Name</th-->
											<th style="text-align:center;"class="sorting" tabindex="0" aria-controls="myTable" rowspan="1" colspan="1" style="width: 179px;" aria-label="Feed Link: activate to sort column ascending">URL</th>
											<th style="text-align:center;" class="sorting" tabindex="0" aria-controls="myTable" rowspan="1" colspan="1" style="width: 108px;" aria-label="Action: activate to sort column ascending">Action</th></tr>
										</thead>
											<tbody>
												<?php								
												$url = $_SESSION['shop'];            $data =  getDeletePixel($url);	
												foreach($data as $row){
												
												
												?>
													<tr>
													<td><?php echo $row['id']?></td>
													<!--td></td-->
													<td><?php echo $row['shop_url']?></td>
													<td><img src="images/delete.png" onclick="deletePixel()"></td>
												</tr>
												<?php
												}
												?>
											</tbody>
								</table>
							</div>	
							
							
							<?php } ?>
					    </div>
							<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
							</div>
				    </div>
				  
		        </div>
            </div>
				
				
			<div id="instruction" class="tab-pane fade" >
					  
					<div class="row">
						<div class="col-lg-3"></div>  
						<div class="col-lg-6" style="margin-left:-11px;">
							<form role="form" method="post" action=""> 
								<div class="form-group">
								 
									<div  class="form-control" name="pixel_id" style="height:65%; margin-top: 5px;">
										 <h4> Find your Custom Audience pixel.</h3>
										
										<ul> 
									  <li> Visit your Facebook Ads Manager at <a href ="https://www.facebook.com/ads/manage" target="_blank">www.facebook.com/ads/manage</a> In the left hand side navigation, Click Custom Audience Pixel from the left menu or click on 'create Advert'.
									   </li>
									   <li>
										   Login to your facebook developer account.
									   </li>
									   
										<li>
										   After that open the facebook developer link in new tab <a href ="https://developers.facebook.com/">developers.facebook.com/</a> 
										   </li>
										   
											<li>
										  Go on 'tool and support' and click on 'Ads Manager'. 
										  </li>
										  
										  <li>
										   Go to the tool menu and click on audience.</li>
										  </li>
										   
										   <li>
										   Click on Create Custom audience and after that click on website traffic.</li>
										  </li>
										   <li>
										   Select the option and enter the audience name and click on create button.</li>
										  </li>
										 <li>
										 Your code is created now, you can check it by select the audience and click on 'action' menu and click on 'view pixel'.
										 </li>
										   <li>
									
										   Copy and paste this code into pixel tab.
										   </li>
									 
									</div>
										 
								</div>
							</form>
						</div>
						<div class="col-lg-3"></div>  
				    </div>
							
				</div>
				
				<div id="getcode" class="tab-pane fade" style=" padding-left: 136px;padding-top: 10px;">
					
					<button type="button" class="btn btn-info btn-lg" data-toggle="modal" data-target="#getPixelScrip">Get Page View Script</button>
					 
					<button type="button" class="btn btn-info btn-lg" data-toggle="modal" data-target="#getCatalogScrip">Get View Content Script</button>
					  
					<button type="button" class="btn btn-info btn-lg" data-toggle="modal" data-target="#getCartScrip">Get Cart Script</button>
					   
					<button type="button" class="btn btn-info btn-lg" data-toggle="modal" data-target="#getCheckoutScrip">Get Checkout Script</button>
						
						
				
				</div>
				
				<!-- Pixel Modal -->
			<div class="modal fade" id="getPixelScrip" role="dialog">
				<div class="modal-dialog">
						
						  <!-- Modal content-->
				    <div class="modal-content">
									<div class="modal-header">
									  <!--button type="button" class="close" data-dismiss="modal">&times;</button-->
									  <h4 class="modal-title">Add in Theme.liquid  between &lt;head&gt;&lt;/head&gt; </h4>
									</div>
						<div class="modal-body">
									<?php 
											
											if($status == 0){
												echo 'Pixel not found!';
											}
											else{
											
											
										?>
<div class="test1"><textarea readonly="1" rows="10" spellcheck="false" onclick="this.focus(); this.select()" tabindex="0" dir="ltr" style="direction: ltr; margin: 0px;">
&lt;!-- Facebook Pixel Code --&gt; 
&lt;script&gt; 
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod? n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n; n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0; t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script','//connect.facebook.net/en_US/fbevents.js');
fbq('init', '<?php echo "$pixel"?>'); fbq('track', 'PageView'); 
&lt;/script&gt; 
&lt;noscript&gt;
&lt;img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id= <?php echo "$pixel"?>&ev=PageView&noscript=1" /&gt;
&lt/noscript&gt; 
&lt;!-- End Facebook Pixel Code --&gt;
</textarea></div>
											<?php } ?>
						</div>
									<div class="modal-footer">
									  <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
									</div>
				    </div>
						  
				</div>
			</div>

  
  <!-- Catalog Modal -->
            <div class="modal fade" id="getCatalogScrip" role="dialog">
		        <div class="modal-dialog">
				
					  <!-- Modal content-->
					<div class="modal-content">
						<div class="modal-header">
						  <!--button type="button" class="close" data-dismiss="modal">&times;</button-->
						  <h4 class="modal-title">Add in product.liquid at the top</h4>
						</div>
						<div class="modal-body">
							<?php 
									
									if($status == 0){
										echo 'Pixel not found!';
									}
									else{
									
								?>
							<div class="test1"><textarea readonly="1" rows="10" spellcheck="false" onclick="this.focus(); this.select()" tabindex="0" dir="ltr" style="direction: ltr; margin: 0px;">
&lt;!-- Facebook Pixel Code --&gt; 
 &lt;script&gt; !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod? n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n; n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0; t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script','//connect.facebook.net/en_US/fbevents.js'); fbq('track', 'ViewContent', { content_ids: '{{ variant.id }}', product_catalog_id: '<?php echo "$catalog"?>', content_type: 'product_group', }); &lt;/script&gt; &lt;noscript&gt;&lt;img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?php echo "$pixel"?>&ev=ViewContent&noscript=1" />&lt;/noscript&gt; &lt;!-- End Facebook Pixel Code --&gt;</textarea></div>
							
							<?php } ?>
							
							<div class="modal-footer">
							  <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
							</div>
							
								
						
						</div>
					  
					</div>
	            </div>
            </div>		



<!-- Cart Modal -->
			<div class="modal fade" id="getCartScrip" role="dialog">
				<div class="modal-dialog">
				
				  <!-- Modal content-->
					<div class="modal-content">
						<div class="modal-header">
						  <!--button type="button" class="close" data-dismiss="modal">&times;</button-->
						  <h4 class="modal-title">Add in cart.liquid at top</h4>
						</div>
						<div class="modal-body">
							<?php 
								
								if($status == 0){
									echo 'Pixel not found!';
								}
								else{
								
							?>
					
					
							<div class="test1"><textarea readonly="1" rows="10" spellcheck="false" onclick="this.focus(); this.select()" tabindex="0" dir="ltr" style="direction: ltr; margin: 0px;">
&lt;!-- Facebook Pixel Code --&gt; &lt;script&gt; !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod? n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n; n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0; t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script','//connect.facebook.net/en_US/fbevents.js'); fbq('track', 'AddToCart'); &lt;/script&gt;&lt;noscript>&lt;img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?php echo "$pixel"?>&ev=AddToCart&noscript=1" />&lt;/noscript&gt; &lt;!--End Facebook Pixel Code --&gt;</textarea></div>
					   
						
								<?php } ?>
						 
							<div class="modal-footer">
							  <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
							</div>
						</div>
					</div>
				</div>
			</div>

                   <!-- Catalog Modal -->
  
			<div class="modal fade" id="getCheckoutScrip" role="dialog">
				<div class="modal-dialog">
					
					  <!-- Modal content-->
					<div class="modal-content">
						<div class="modal-header">
						  <!--button type="button" class="close" data-dismiss="modal">&times;</button-->
						  <h4 class="modal-title">Install in Settings > Checkout > Additional Scripts</h4>
						</div>
						<div class="modal-body">
					
							<?php 
								
								if($status == 0){
									echo 'Pixel not found!';
								}
								else{
								
							?>
					
								<div class="test1"><textarea readonly="1" rows="10" spellcheck="false" onclick="this.focus(); this.select()" tabindex="0" dir="ltr" style="direction: ltr; margin: 0px;">
&lt;!-- Facebook Pixel Code --&gt; 
&lt;script&gt; 
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod? n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n; n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0; t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script','//connect.facebook.net/en_US/fbevents.js'); fbq('init', <?php echo "$pixel"?>'); fbq('track', 'PageView'); fbq('track', 'Purchase', { product_catalog_id: '<?php echo "$catalog"?>', content_ids: '{{ variant.id }}', content_type: 'product_group', value: '{{ total_price | money_without_currency }}', currency: 'USD' }); &lt;/script&gt; &lt;noscript&gt;&lt;img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id= <?php echo "$pixel"?> &ev=PageView&noscript=1" /&gt;&lt;/noscript&gt;&lt;noscript&gt;&lt;img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id= <?php echo "$pixel"?>&ev=Purchase&noscript=1" /&gt;
&lt;/noscript&gt; 
&lt;!-- End Facebook Pixel Code --&gt;</textarea></div>

									

								<?php } ?>
							<div class="modal-footer">
							  <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
							</div>
						</div>
					</div>
				</div>
			</div>
        </div>
		
		</div>
		
    </div>
    </body>
</html>

