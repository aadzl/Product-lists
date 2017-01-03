<?php 
require_once '../../app/Mage.php';
umask(0);
Mage::setIsDeveloperMode(true);
ini_set('display_errors', 1);
Mage::app('wholesale'); 
$time_start = microtime(true); 

$pagesize = 5000; //How Many Product Does Wholesale Site Have. 


$products = Mage::getModel('catalog/product')
			->getCollection()
			->addAttributeToSelect('*')
			->setPageSize($pagesize)
			->addAttributeToFilter('status', 1)
			//->addAttributeToFilter('type_id', 'configurable')
			->addAttributeToFilter('visibility', 4)
			->addStoreFilter()
			->setOrder('sku', 'asc'); 
			
//$products->load();
// open the csv file and write the header in the first line
$fp = fopen('productlist-conf.csv', 'w');
$csvHeader = array("Selloff","Sku","Name","Band","Price","ProductType","New","Sizes","Image"); 
fputcsv( $fp, $csvHeader, $delimiter = ",");

	// iterate through all the products
foreach($products as $_product){

	// load a product object using its sku
		$sku = $_product->getData("sku");
		$product = $_product->load($sku);
	
	if($product){
	
			// check if it is simple or configurable If Config => add sizes if simple => size = none
		if( $_product->getTypeId() == 'configurable' ){
			
				// input is $_product and result is iterating child products
				// $conf = Mage::getModel('catalog/product_type_configurable')->setProduct($_product);
			$childProducts = Mage::getModel('catalog/product_type_configurable')
				->getUsedProducts(null,$product);
							
			$sizes = array();
			
			foreach($childProducts as $simpleproduct){
					//	$simpleproduct = Mage::getModel('catalog/product')->load($id)->addAttributeToSelect('*');
			if ($qty = (int) Mage::getModel("cataloginventory/stock_item")->loadByProduct($simpleproduct)->getQty() > 0){
				$qty = (int) Mage::getModel("cataloginventory/stock_item")->loadByProduct($simpleproduct)->getQty();
			}else{
				$qty = 0;
			};
			
				$sizes[] = $simpleproduct->getAttributeText("size")." (".$qty.")";
					//	$sizes[] = Zend_Debug::dump($simpleproduct);
			};
			
			sort($sizes);	//Put them in ALphabetical Order				
			
			$availsizes = implode(", ", $sizes); // Add them to the list comma separated
							
		};
		
		// if its a simple products set size to none
		if( $product->getTypeId() == 'simple' ){
		
			$availsizes = Mage::getModel("cataloginventory/stock_item")->loadByProduct($product)->getQty(); // Add them to the list	
			
		}
					
					
		$image = 'http://images.jsrdirect.com'.$product->getImage();
				// Maybe later iterate through and add to simple?	$qty = (int) Mage::getModel("cataloginventory/stock_item")->loadByProduct($product)->getQty();
		$cost = $product->setCustomerGroupId(2)->getGroupPrice() ;
		$selloff = ($product->getSelloff() == 1 ? "Sell off" : "");
					
					
		$todayDate  = Mage::app()->getLocale()->date()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
		// $new = ($product->getData("news_from_date") < $todayDate && $product->getData("news_to_date") < $todayDate? 'New': 'OLD');
		$new = ($product->getData("news_to_date") < $todayDate? '': 'New    Until '. Mage::helper('core')->formatDate($product->getData("news_to_date"), 'medium', false));

					
						$product_row = array($selloff,
							$sku,
							$product->getName(),
							$product->getData("bandname"),
							$cost,
							$product->getAttributeText("product_type"),
							$new,						
							$availsizes,
							$image
							
							//$image
						);
						
		fputcsv( $fp, $product_row, $delimiter = ",");
	}
}
//how long?
$time_end = microtime(true);
//dividing with 60 will give the execution time in minutes other wise seconds
$execution_time = ($time_end - $time_start)/60;


echo "Export Complete \n";
 /* Let me know invneotyr export worked 
 $to = "williamboudle@gmail.com";
 $subject = "Product List Exported ". date("m-d-y");
 $body = "<p>Inventory export worked! ".$pagesize." Simple Skus Exported</p><br>Total Execution Time:".$execution_time." mins";
 if (mail($to, $subject, $body)) {
   echo("<p>Inventory export worked! ".$products->count()." Simple Skus Exported</p><br>Total Execution Time: ".$execution_time." mins");
  } else {
   echo("<p>Message delivery failed...</p>");
  } */
?>