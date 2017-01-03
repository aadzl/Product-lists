<?php

require_once('../../app/Mage.php'); //Path to Magento
umask(0);
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

        
        
    $query = "
	SELECT im1.ItemNumber AS sku, 
	im1.ItemDescription AS name,
	im1.Category2 AS band,
	im2.QtyOnHand AS qty,
	gp.value AS price,
	im1.Category1 AS sale_code
	FROM  FromMas_IM1_InventoryMasterfile im1
	INNER JOIN 
	FromMas_IM2_InventoryItemWhseDetl im2
	ON im1.ItemNumber = im2.ItemNumber
	JOIN 
	catalog_product_flat_2 cpf2
	ON im1.ItemNumber = cpf2.sku
	JOIN catalog_product_entity_group_price gp
	ON cpf2.entity_id = gp.entity_id	
	WHERE im1.Category1 LIKE '%Z%'
	AND im2.WhseCode = '000'
        ORDER BY  im1.ItemNumber ASC
	"
	;
     
    /**
     * Execute the query and store the results in $results
     */
    $results = $readConnection->fetchAll($query);
     
    /**
     * Print out the results
     */
    // var_dump($results);

	 
download_send_headers("blowout_tees_inventory_". date("Y-m-d") . ".csv");

echo array2csv($results);

die();




?>