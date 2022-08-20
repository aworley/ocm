<?php

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

require_once('plBase.php');
// 2012 point release modification to update last_changed
/**
* Something.
*
* @author Aaron Worley <amworley@pikasoftware.com>;
* @version 1.0
* @package Danio
*/
class pikaActivity extends plBase 
{	
	private $recalc_sms_send_time = false;
	
	public function __construct($id = null)
	{
		
		$this->db_table = 'activities';		
		parent::__construct($id);
		
		if (is_null($id)) 
		{
			// MySQL 4.1+ will accept the old TIMESTAMP syntax, so use that.
			$this->setValue('created', date('YmdHis'));
		}
		
		return true;
	}
	
	public function setValue($name, $value) 
	{
		// If sms_mins_before is being set, set a flag so that sms_send_time
		// is recalculated when we save this record.
		if ($name == 'sms_mins_before' && is_null($this->sms_act_id)
				&& $this->sms_send_failures != 1)
		{
			$this->recalc_sms_send_time = true;
		}
		
		return parent::setValue($name, $value);
	}
	
	public static function getActivitiesCaseClient($filter, &$row_count, $order_field='act_date', 
							$order='ASC', $first_row='0', $list_length='100') {
		
		foreach ($filter as $key => $val)
		{
			$ignored_fields = array('user_list','pba_list');
			if(!in_array($key,$ignored_fields)) {
				$filter[$key] = DB::escapeString($val);
			}
		}
		
		$filter_sql = '';
		
		if (isset($filter['act_date']) && $filter['act_date']){
			$filter_sql .= " AND activities.act_date='{$filter['act_date']}'";
		}
		
		$user_sql = $pba_sql = '';
		if (isset($filter['user_list']) && is_array($filter['user_list'])){
			if(count($filter['user_list']) > 0 ) {
				$tmp = implode(',',$filter['user_list']);
				$tmp = pl_process_comma_vals($tmp);
				$user_sql = "activities.user_id IN {$tmp}";
			}
		} elseif (isset($filter['user_list']) && strlen($filter['user_list']) > 0) {
			$tmp = pl_process_comma_vals($filter['user_list']);
			$user_sql = "activities.user_id IN {$tmp}";
		} elseif (isset($filter['user_id']) && is_numeric($filter['user_id'])){
			$user_sql = "activities.user_id='{$filter['user_id']}'";
		}
		
		if (isset($filter['pba_list']) && is_array($filter['pba_list'])){
			if(count($filter['pba_list']) > 0 ) {
				$tmp = implode(',',$filter['pba_list']);
				$tmp = pl_process_comma_vals($tmp);
				$pba_sql = "activities.pba_id IN {$tmp}";
			}
		} elseif (isset($filter['pba_list']) && strlen($filter['pba_list']) > 0) {
			$tmp = pl_process_comma_vals($filter['pba_list']);
			$pba_sql = "activities.pba_id IN {$tmp}";
		} elseif (isset($filter['pba_id']) && is_numeric($filter['pba_id'])){
			$pba_sql = "activities.pba_id='{$filter['pba_id']}'";
		}
		
		if(strlen($user_sql) > 0 && strlen($pba_sql) > 0) 
		{
			$filter_sql .= " AND (" . $user_sql . " OR " . $pba_sql . ")";
		}
		elseif(strlen($user_sql) > 0) 
		{
			$filter_sql .= " AND ".$user_sql;
		}
		elseif(strlen($pba_sql) > 0) 
		{
			$filter_sql .= " AND ".$pba_sql;			
		}
		
		
		
		if (isset($filter['start_date']) && $filter['start_date'] && $filter['start_date'] != '0000-00-00'){
			$safe_start_date = DB::escapeString(pl_date_mogrify($filter['start_date']));
			$filter_sql .= " AND act_date >= '{$safe_start_date}'";
		}
		
		if (isset($filter['end_date']) && $filter['end_date'] && $filter['end_date'] != '0000-00-00'){
			$safe_end_date = DB::escapeString(pl_date_mogrify($filter['end_date']));
			$filter_sql .= " AND act_date <= '{$safe_end_date}'";
		}
		
		if (isset($filter['no_date']) && $filter['no_date']){
			$filter_sql .= " AND activities.act_date IS NULL";
		}
		
		if (isset($filter['funding']) && $filter['funding']){
			$filter_sql .= " AND activities.funding='{$filter['funding']}'";
		}
		
		if (isset($filter["act_type"]) && $filter["act_type"]){
			$filter_sql .= " AND activities.act_type='{$filter["act_type"]}'";
		}
		
		if (isset($filter['completed']) && is_numeric($filter['completed'])){
			$filter_sql .= " AND activities.completed={$filter['completed']}";
		}
		
		if (isset($filter['office']) && strlen($filter['office']) > 0){
			$filter_sql .= " AND office='{$filter['office']}'";
		}

		if (isset($filter['number']) && strlen($filter['number']) > 0){
			$filter_sql .= " AND number LIKE '{$filter['number']}'";
		}
		
		if (isset($filter['category']) && strlen($filter['category']) > 0){
			$tmp = pl_process_comma_vals($filter['category']);
			$filter_sql .= " AND category IN {$tmp}";
		}
		
		
		
		$sql = 'SELECT COUNT(*) AS count 
				FROM activities 
				LEFT JOIN cases ON activities.case_id=cases.case_id 
				LEFT JOIN contacts ON cases.client_id=contacts.contact_id 
				WHERE 1' . $filter_sql;
		$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
		$row_count = 0;
		if(DBResult::numRows($result) > 0) {
			$row = DBResult::fetchRow($result);
			$row_count = $row['count'];
		}
		
		
		// next, re-run the query, and only retrieve the records that will be
		// displayed on this screen.
		
		$sql = 'SELECT activities.*, 
				cases.number, cases.office, cases.client_id, 
				contacts.last_name, contacts.first_name, contacts.area_code, contacts.phone, contacts.phone_notes
				FROM activities 
				LEFT JOIN cases ON activities.case_id=cases.case_id 
				LEFT JOIN contacts ON cases.client_id=contacts.contact_id 
				WHERE 1' . $filter_sql;
		
		$safe_order_field = DB::escapeString($order_field);
		$safe_order = DB::escapeString($order);
		
		if ($order_field == 'last_name' && $order){
			$sql .= " ORDER BY last_name, first_name {$safe_order}";
		} else if ($order_field == 'date-user-time' && $order){
			$sql .= " ORDER BY act_date {$safe_order}, user_id {$safe_order}, act_time {$safe_order}";
		} else if ($order_field && $order){
			$sql .= " ORDER BY {$safe_order_field} {$safe_order}";
		}
		
		
		if ($first_row && $list_length){
			$sql .= " LIMIT $first_row, $list_length";
		} elseif ($list_length){
			$sql .= " LIMIT $list_length";
		}
		
		$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
		return $result;
	}
	
	public function save()
	{
		global $auth_row;  // AMW - 2012-12-21 - last_changed_user_id request from NMLA.


		// 2012 point release update
		$this->last_changed = date('YmdHis');

		// AMW - 2012-12-21 - last_changed_user_id request from NMLA.
		$this->last_changed_user_id = $auth_row['user_id'];
		// End AMW

		// When sms_mins_before has changed, recalculate sms_send_time.  We recalc-
		// ulate it here in case act_date and/or act_time have changed during this
		// page view.
		if ($this->recalc_sms_send_time)
		{
			if ($this->sms_mins_before == -1 || strlen($this->sms_mins_before) == 0)
			{
				$this->sms_send_time = null;
			}
			
			else 
			{
				$this->sms_send_time = strtotime($this->act_date . ' ' . $this->act_time);
				$this->sms_send_time -= ($this->sms_mins_before * 60);				
			}
		}
		
		parent::save();
	}
	
	public static function roundHoursByInterval($hours = null, $interval = null)
	{
		if (is_null($hours) || !is_numeric($hours) || $hours <= 0)
		{ // Must be a numeric positive number - otherwise return 0
			return 0;
		}
		
		if (is_null($interval) || !is_numeric($interval) || $interval <= 0)
		{ // Must be a numeric positive number - otherwise return $hours
			return $hours;
		}
		
		$minutes = round($hours * 60);
		
		$remainder = $minutes % $interval;
		if($remainder) { // Round up to act interval
			$hours = ($minutes - $remainder) + $interval;
			$hours = ($hours/$interval) * ($interval/60);
		}
		else 
		{
			$hours = ($minutes/$interval) * ($interval/60);
		}
		
		return $hours;
	}
	
}

?>
