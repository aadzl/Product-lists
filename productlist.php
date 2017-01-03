<?php 
require_once '../../app/Mage.php';
umask(0);
Mage::setIsDeveloperMode(true);
ini_set('display_errors', 1);
Mage::app('wholesale'); 
$time_start = microtime(true); 

$pagesize = 3500; //How Many Product Does Wholesale Site Have. 


$products = Mage::getModel('catalog/product')
			->getCollection()
			->addAttributeToSelect('*')
			->setPageSize($pagesize)
			->addAttributeToFilter('status', 1)
			->addAttributeToFilter('type_id', 'simple')
			->addStoreFilter()
			->setOrder('sku', 'asc'); 

//$aliastable= Mage::getSingleton('core/resource')->getTableName('FromMas_IM_AliasItem');   
			
//$products->distinct(true)->joinTable('FromMas_IM_AliasItem','ItemCode = sku', array('upccode'=>'AliasItemNo'));
$products->distinct(true)->getSelect()->joinLeft('FromMas_IM_AliasItem','ItemCode = sku', array('upccode'=>'AliasItemNo'));
		
//$products->load();
// open the csv file and write the header in the first line
$fp = fopen('productlist.csv', 'w');
$csvHeader = array("enter_order_qty", "sku","size","name","band","price","product type","qty available","selloff", "UPC" /* ,"Image" */); 
fputcsv( $fp, $csvHeader, $delimiter = ",");

	// iterate through all the products
	foreach($products as $_product){

	// load a product object using its sku
		$sku = $_product->getData("sku");
		$product = $_product->load($sku);
		$producttype = $product->getAttributeText("product_type");
		$producttype = is_array($producttype) ? implode(" ",$producttype) : $producttype;
		
	
		if($product)
			{
			//	$image = 'http://www.jsrdirect.com/media/catalog/product'.$product->getImage();
			if ($qty = (int) Mage::getModel("cataloginventory/stock_item")->loadByProduct($product)->getQty() > 0){
				$qty = (int) Mage::getModel("cataloginventory/stock_item")->loadByProduct($product)->getQty();
			}else{
				$qty = 0;
			};
			
			if ($product->getData("upccode")){
				$upc= $product->getData("upccode");
			}else{
				$upc= "";
			};
			
				
				$cost = $product->setCustomerGroupId(2)->getGroupPrice() ;
				$selloff = ($product->getSelloff() == 1 ? "Sell off" : "");
					
					$product_row = array("", 
						$sku,
						$product->getAttributeText("size"),
						$product->getName(),
						$product->getData("bandname"),
						$cost,
						$producttype,
						$qty,
						$selloff,
						$upc
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
 $body = "Wholesale inventory has been exported";
 if (mail($to, $subject, $body)) {
   echo("<p>Inventory export worked! ".$pagesize." Simple Skus Exported</p><br>Total Execution Time:".$execution_time." mins");
  } else {
   echo("<p>Message delivery failed...</p>");
  }
  */
?>