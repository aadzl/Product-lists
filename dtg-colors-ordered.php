<?php require_once '../app/Mage.php';

/*
 * Initialize Magento. Older versions may require Mage::app() instead.
 */
Mage::init('admin');


$sku = $_GET['sku'] ? $_GET['sku'] : 'HMI116';
/*
 * Get all unique order IDs for items with a particular SKU.
 */
$orderItems = Mage::getResourceModel('sales/order_item_collection')
    ->addFieldToFilter('sku', array('like'=>array($sku."%")))
    ->toArray(array('order_id'));

$orderIds = array_unique(array_map(
    function($orderItem) {
        return $orderItem['order_id'];
    },
    $orderItems['items']
));

/*
 * Now get all unique customers from the orders of these items.
 */
$orderCollection = Mage::getResourceModel('sales/order_collection')
    ->addFieldToFilter('entity_id',   array('in'  => $orderIds))
 //   ->addFieldToFilter('customer_id', array('neq' => 'NULL'))
 ;
$orderCollection->getSelect()->group('entity_id');
?>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.11/css/jquery.dataTables.min.css">
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/select/1.1.2/css/select.dataTables.min.css">
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.1.2/css/buttons.dataTables.min.css">

<script type="text/javascript" language="javascript" src="//code.jquery.com/jquery-1.12.0.min.js">
	</script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.10.11/js/jquery.dataTables.min.js">
	</script>

	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/select/1.1.2/js/dataTables.select.min.js">
	</script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.1.2/js/dataTables.buttons.min.js">
	</script>
	<script type="text/javascript" language="javascript" src="//cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js">
	</script>
	<script type="text/javascript" language="javascript" src="//cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js">
	</script>
	<script type="text/javascript" language="javascript" src="//cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js">
	</script>
	<script type="text/javascript" language="javascript" src="//cdn.datatables.net/buttons/1.1.2/js/buttons.html5.min.js">
	</script>
	<script type="text/javascript" language="javascript" src="//cdn.datatables.net/buttons/1.1.2/js/buttons.print.min.js">
	</script>



	
	
<title>Customers who bought <?php echo $sku ?> </title>
<style>
table {
	border-collapse: collapse;
}
td {
	padding: 5px;
	border: 1px solid #000000;
}
.canceled{color:red;}
</style>
</head>
<body>
<h2>Customers who bought <?php echo $sku ?></h2>
<table class="responsive dynamicTable display table table-bordered">
<thead>
	<tr>
	<th>Row</th>
	<th>Order Number</th>
	<th>First Name</th>
	<th>Last Name</th>
	<th>Email</th>
	<th>Address</th>
	<th>City</th>
	<th>State</th>
	<th>Zip</th>
	<th>Date</th>
	
	<th>Size</th>
	<th>Color</th>

	<th>Shipping Method</th>


</tr>
</thead>
<?php $a = 1; // define the row ?>
<?php
//load the customers info for the order
	foreach ($orderCollection as $order) {
		//	$customer = Mage::getModel('customer/customer')->load($order->getCustomerId());

			// Get all the order Items and implode them; then use $order->getProductSkus() to call them
			$skus = array();
			foreach ($order->getAllVisibleItems() as $item) {
				
					$options = $item->getProductOptions(); 
					$customOptions = $options['options'];   
					
					
						foreach ($customOptions as $option){	    
								$optionTitle = $option['label'];
								$optionId = $option['option_id'];
								$optionType = $option['type'];
								$optionValue = $option['value'];
						}
					
				
		
			}
			
			

			//Get customers Shipping address 
			// var $addy(string)
			if($order->getShippingAddress()){
				$addy = $order->getShippingAddress()->getStreetFull() ."<br> ". $order->getShippingAddress()->getCity() ."<br> ". $order->getShippingAddress()->getRegion()."<br> ".
				$order->getShippingAddress()->getPostcode();
			}else{ 
				$addy =  "";
			}

			//get order total
			$orderTotal = $order->getGrandTotal() - $order->getShippingAmount();
					
					if ($order->getStatus() == 'canceled'){
						echo "<tr class='canceled'>"; 
						$canceled = "X";
					}else{
					echo "<tr>";
					$canceled = " ";
					};
						echo "<td>".$a++." ".$canceled."</td>";
						echo "<td>".$order->getIncrementId()."</td>";
						echo "<td>".$order->getShippingAddress()->getFirstname()."</td>";
						echo "<td>".$order->getShippingAddress()->getLastname()."</td>";
						echo "<td>".$order->getShippingAddress()->getEmail()."</td>";
						
						echo "<td>". $order->getShippingAddress()->getStreetFull()."</td>";
						echo "<td>". $order->getShippingAddress()->getCity()."</td>";
						echo "<td>". $order->getShippingAddress()->getRegion()."</td>";
						echo "<td>". $order->getShippingAddress()->getPostcode()."</td>";
						echo "<td>".$order->getCreatedAt()."</td>";
						
						echo "<td>".$customOptions[0]['value']."</td>";
						echo "<td>".$customOptions[1]['value']."</td>";
						
						
						
						
						echo "<td>".$order->getShippingMethod()."</td>";


					echo "</tr>";
				

$order->clearInstance();

			//Zend_Debug::dump($order->debug());
	}

?>

</table>


<br><h2>Email List</h2><br>

<?php
foreach ($orderCollection as $order) {
//$customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
//echo $customer->getEmail().", ";
echo $order->getShippingAddress()->getEmail().", ";
}

?>
</body>


<script type="text/javascript">
$(document).ready( function () {
    $('.dynamicTable').DataTable( {
		dom: 'Bfrtip',
		buttons: [
				{
					extend: 'collection',
					text: 'Export',
					buttons: [
						'copy',
						'excel',
						'csv',
						'pdf',
						'print'
					]
				}
			]
	
	});
} );

/* 	if($('table').hasClass('dynamicTable')){
		$('.dynamicTable').dataTable({
			"sPaginationType": "full_numbers",
			"bJQueryUI": false,
			"bAutoWidth": false,
			"bLengthChange": true,
			"sDom": 'T<"clear">lfrtip',
		"oTableTools": {
			"sSwfPath": "plugins/tabletools/swf/copy_csv_xls_pdf.swf"
		}
		});
	}; */
</script>
</html>