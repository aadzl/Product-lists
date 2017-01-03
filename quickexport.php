<?php
error_reporting(E_ALL | E_STRICT);
require_once('../../app/Mage.php'); //Path to Magento
umask(0);
Mage::setIsDeveloperMode(true);
ini_set('display_errors', 1);
Mage::app();




    /**
     * Get the resource model
     */
    $resource = Mage::getSingleton('core/resource');
     
    /**
     * Retrieve the read connection
     */
    $readConnection = $resource->getConnection('core_read');
	
	//$custNo = Mage::app()->getRequest()->getParam('custnumber');
	$sku = Mage::app()->getRequest()->getParam('client');
	$mediaurl =  Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)."catalog/product";
  $export_profile = Mage::app()->getRequest()->getParam('export_profile');

  if (!$export_profile) {
    die("No profile selected");
  }
	
function array2csv(array &$array)
{
   if (count($array) == 0) {
     return null;
   }
   ob_start();
   $df = fopen("php://output", 'w');
   fputcsv($df, array_keys(reset($array)));
   foreach ($array as $row) {
      fputcsv($df, $row);
   }
   fclose($df);
   return ob_get_clean();
}

function download_send_headers($filename) {
    // disable caching
    $now = gmdate("D, d M Y H:i:s");
    header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
    header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
    header("Last-Modified: {$now} GMT");

    // force download  
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");

    // disposition / encoding on response body
    header("Content-Disposition: attachment;filename={$filename}");
    header("Content-Transfer-Encoding: binary");
}

/*
SELECT invoice.InvoiceNo,
invoice.CustomerPONo,
invoice.ShipVia,
tracking.TrackingID AS tracking_num
FROM 
    FromMas_AR_InvoiceHistoryTracking tracking
INNER JOIN     
    FromMas_AR_InvoiceHistoryHeader invoice
ON tracking.InvoiceNo = invoice.InvoiceNo 
WHERE invoice.customerno = '".$custNo."'"
*/

/* old 
SELECT im1.ItemNumber, 
	im1.ItemDescription,
	im1.Category2,
	im2.QtyOnHand
	FROM  FromMas_IM1_InventoryMasterfile im1
	INNER JOIN 
	FromMas_IM2_InventoryItemWhseDetl im2
	ON im1.ItemNumber = im2.ItemNumber
	WHERE im1.Category1 LIKE '%B%'
	AND im2.WhseCode = '000'
        ORDER BY  im1.ItemNumber ASC */

    
    $products = Mage::getModel('catalog/product')
      ->getCollection()
      ->addAttributeToSelect('sku')
      ->setPageSize(100)
      ->addAttributeToFilter('status', 1)
      ->addAttributeToFilter('type_id', 'simple')
      ->addStoreFilter()
      ->setOrder('sku', 'asc'); 



        
  $inventory = "SELECT cpf1.name name, 
    cisi.qty qty,
    cpf1.sku sku,
    cpf1.price price,
    cpf1.size_value size,
    cpf1.color_value color,
    cpf1.brand_value showname
    from catalog_product_flat_1 cpf1
    LEFT JOIN cataloginventory_stock_item cisi
    ON cisi.item_id = cpf1.entity_id
    where cpf1.type_id = 'simple'
    ORDER BY sku"	;

  $best_seller_per_day = "SELECT day, sku, MAX(qty_total) AS qty FROM (
      SELECT DATE(created_at) AS day, sku, SUM(qty_ordered) AS qty_total
      FROM sales_flat_order_item
      WHERE created_at between date_sub(now(),INTERVAL 1 WEEK) and now()
      AND product_type = 'simple'
      GROUP BY sku, day
      ORDER BY qty_total DESC
    ) AS item_count
    GROUP BY day";

  $total_sales_per_day="SELECT DATE(created_at) AS day, SUM(row_total) AS sales
    FROM sales_flat_order_item
    WHERE created_at between date_sub(now(),INTERVAL 1 WEEK) and now()
    GROUP BY day";

    $wishlist="SELECT b.email, c.value AS name, a.updated_at, d.added_at, d.product_id, e.name, SUM(g.qty_ordered) AS purchased
FROM `wishlist` AS a
INNER JOIN customer_entity AS b ON a.customer_id = b.entity_id
INNER JOIN customer_entity_varchar AS c ON a.customer_id = c.entity_id AND c.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'firstname' AND entity_type_id = b.entity_type_id)
INNER JOIN wishlist_item AS d ON a.wishlist_id = d.wishlist_id
INNER JOIN catalog_product_flat_1 AS e ON d.product_id = e.entity_id
LEFT JOIN sales_flat_order AS f ON f.customer_email = b.email
LEFT JOIN sales_flat_order_item AS g ON (f.entity_id = g.order_id AND g.sku LIKE CONCAT(e.sku,'%') AND g.product_type = 'simple')
GROUP BY b.email, c.value, a.updated_at, d.added_at, d.product_id, e.name";

$sales_per_sku = "SELECT soi.name, soi.sku, cpf1.size_value, (
  SELECT eaov.value
  FROM eav_attribute_option_value eaov
  WHERE eaov.option_id = cpf1.product_type
  AND store_id = '0'
) product_type, sum(soi.qty_ordered) total_ordered
FROM sales_flat_order_item soi
LEFT JOIN catalog_product_flat_1 cpf1
ON soi.product_id = cpf1.entity_id
WHERE soi.price > 0 
AND soi.created_at between date_sub(now(),INTERVAL 1 WEEK) and now()
GROUP BY soi.sku 
ORDER BY soi.sku 
LIMIT 1000";
     
    /**
     * Execute the query and store the results in $results
     */
    $results = $readConnection->fetchAll($$export_profile);
     
    /**
     * Print out the results
     */

  //   echo '<pre>' . var_export($results, true) . '</pre>';
	 
 download_send_headers("cg_".$export_profile."_". date("Y-m-d") . ".csv");

echo array2csv($results);

die();




?>