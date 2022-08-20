<?php	
// COMPENSATED TAB


require_once('pikaCompen.php');
require_once('plFlexList.php');

$action = pl_grab_get('action');
$compen_id = pl_grab_get('compen_id');

$billing_date = pl_grab_get('billing_date');
$billing_amount = pl_grab_get('billing_amount');
$billing_hours = pl_grab_get('billing_hours');
$payment_date = pl_grab_get('payment_date');
$payment_amount = pl_grab_get('payment_amount');
$notes = pl_grab_get('notes');

$base_url = pl_settings_get('base_url');


switch ($action) {
	case 'edit':
		if($compen_id) {
			$compen = new pikaCompen($compen_id);
			$compen_row = $compen->getValues();
		} else { // New record
			$compen_row = array();
			$compen_row['case_id'] = $case_id;
		}
		$template = new pikaTempLib('subtemplates/case-compen.html',$compen_row,'edit');
		$C .= $template->draw();
		break;
	case 'delete':
		$compen = new pikaCompen($compen_id);
		$compen->delete();
		header("Location:{$base_url}/case.php?case_id={$case_id}&screen=compen");
	case 'update':
		$compen = new pikaCompen($compen_id);
		$compen->billing_date = $billing_date;
		$compen->billing_amount = $billing_amount;
		$compen->billing_hours = $billing_hours;
		$compen->payment_date = $payment_date;
		$compen->payment_amount = $payment_amount;
		$compen->notes = $notes;
		$compen->case_id = $case_id;
		$compen->save();
		header("Location:{$base_url}/case.php?case_id={$case_id}&screen=compen");
		break;
	default:
		
		$result = pikaCompen::getCaseCompen($case_id);
	
		$compen_list = new plFlexList();
		$compen_list->template_file = 'subtemplates/case-compen.html';
	
		$total_billed = $total_payed = $total_hours = 0;
		$row_class = 1;
		while ($row = DBResult::fetchRow($result))
		{
			$row['row_class'] = $row_class;
			if ($row_class > 1) { $row_class = 1;}
			else {$row_class++;}
	
			$total_billed += $row['billing_amount'];
			$total_payed += $row['payment_amount'];
			$total_hours += $row['billing_hours'];
			
			$row['billing_date'] = pikaTempLib::plugin('text_date','billing_date',$row['billing_date']);
			$row['payment_date'] = pikaTempLib::plugin('text_date','payment_date',$row['payment_date']);
			$compen_list->addRow($row);
		}
		
	
	
	
		$a = array();
		
		$a['compen_list'] = $compen_list->draw();
		
		$a['total_billed'] = $total_billed;
		$a['total_payed'] = $total_payed;
		$a['total_hours'] = $total_hours;
		$a['dollars_remaining'] = $case_row['dollars_okd'] - $total_billed;
		$a['hours_remaining'] = $case_row['hours_okd'] - $total_hours;
		
		$a = array_merge($case_row,$a);
	
		$template = new pikaTempLib('subtemplates/case-compen.html',$a,'view');
		$C .= $template->draw();
		
		break;
}



?>