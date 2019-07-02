<?php
chdir('../../');

require_once('pika-danio.php');

pika_init();

require_once('pikaCase.php');
require_once('pikaContact.php');
require_once('pikaCompen.php');
require_once('plFlexList.php');
require_once('pikaTempLib.php');


$case_id = pl_grab_get('case_id');

$case = new pikaCase($case_id);
$a = $case->getValues();

if($a['client_id']) {
	$contact = new pikaContact($a['client_id']);
	$b = $contact->getValues();
} else {$b = array();}

$a = array_merge($a, $b);

$a['primary_client_name'] = pikaTempLib::plugin('text_name','',$a);
$a['username'] = $auth_row['username'];
$a['full_address'] = pikaTempLib::plugin('text_address','',$a,'',array("output=html"));


// generate the billing table
$result = pikaCompen::getCaseCompenBill($case_id);

$bill_list = new plFlexList();
$bill_list->template_file = 'reports/compen_bill/compen_bill.html';

$total_bill = 0;

while ($row = DBResult::fetchRow($result)) {
	$row['billing_date'] = pikaTempLib::plugin('text_date','billing_date',$row['billing_date']);
	$total_bill += $row['billing_amount'];
	
	$bill_list->addRow($row);
}

$a['billing_table'] = $bill_list->draw();
$a['total_bill'] = $total_bill;

$default_template = new pikaTempLib('reports/compen_bill/compen_bill.html',$a);
$buffer = $default_template->draw();

pika_exit($buffer);
?>
