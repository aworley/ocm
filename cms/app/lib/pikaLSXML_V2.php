<?php

/**********************************/
/* Pika CMS (C) 2013 			  */
/* Pika Software, LLC 			  */
/* http://pikasoftware.com        */
/**********************************/

/**
* Either takes LS-XML file as a string and converts it or 
* Takes a case_id number and converts it to LS-XML
* 
* @author Matthew Friedlander <matt@pikasoftware.com>;
* @version 1.0.2
* @package CaseQ 2.0 (Client & Server)
* @todo Need to create a way for each CaseQ transfer option to have its own mapping and post processing of data (via XSL)
*       to allow for additional customizability
* 
* Version 1.0.2
* - (MDF 20130726) Added config file for lookupXMLValue (to allow orgs to specify their own mappings)
* Version 1.0.1
* - (MDF 20130620) Added Expenses per client LSC request (AZ)
* - (MDF 20130620) Fixed Income/Expense import (now calculates total number of fields and sums additional fields that wont fit)
* Version 1.0
* - (MDF 20091013) Initial release of LSXML_V2
* 
*/
class pikaLSXML

{
	public $xml_obj;
	private $xpath;
	private $log;
	public $logging_enabled = false;
	
	public $contacts_fields = array('contact_id', 'first_name', 'middle_name', 'last_name', 'address',
	'address2','city','state','zip','county','area_code','phone','phone_notes','phone_notes_alt',
	'phone_alt','area_code_alt','ssn','birth_date','gender','ethnicity','marital', 'relation_code',
	'role', 'email');
	public $contacts_fields_blocked = array('mp_first', 'mp_last', 'mp_alt', 'conflict_id', 'case_id');
	public $cases_fields = array('case_id', 'problem', 'sp_problem', 'intake_date', 'open_date',
	'intake_type','close_date','close_code','outcome','adults','children','client_id',
	'citizen', 'user_id', 'cocounsel1', 'cocounsel2', 'intake_user_id','elig_notes', 'pba_id1','pba_id2','pba_id3',
	'annual0','annual1','annual2','annual3','annual4','annual5','annual6','annual7','annual8',
	'income_type0','income_type1','income_type2','income_type3','income_type4','income_type5','income_type6','income_type7','income_type8',
	'asset0','asset1','asset2','asset3','asset4','asset5','asset6','asset7','asset8',
	'asset_type0','asset_type1','asset_type2','asset_type3','asset_type4','asset_type5','asset_type6','asset_type7','asset_type8');
	public $cases_fields_blocked = array('number');
	public $activities_fields = array('act_id','user_id','pba_id','case_id','summary','notes','act_date');
	public $activities_fields_blocked = array('hours');

	public $lookupXMLValue_settings_file = null;

	


	public function __construct($xml_text = null)
	{
		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = true;
		$doc->formatOutput = true;

		if (!is_null($xml_text)){
			$doc->loadXML($xml_text);
		}
		
		$this->lookupXMLValue_settings_file = getcwd() . '-custom/config/caseq_settings.php';
		

		$this->xml_obj = $doc;
		$this->log = "";

		return true;
	}

	public function exportXML ($case_id = null) {
		require_once('pikaCase.php');
		require_once('pikaContact.php');

		if (is_null($case_id) && !is_numeric($case_id)) {
			return false;
		}
		$case = new pikaCase($case_id);
		$case_row = $case->getValues();
		if (!$case) { return false; }
		// Create the Contacts Array
		$contactsDB = $case->getContactsDb();
		$contacts = $attorneys = array();

		while ($row = mysql_fetch_assoc($contactsDB)) {
			$contacts[] = $row;
		}

		// Create the document root element
		$this->setXMLValue('/', array('ClientIntake' => ''));

		if(count($contacts)) {
			$this->setXMLValue('/ClientIntake', array('Contacts' => ''));
		}
		$contact_count = $custom_count = 1;
		foreach ($contacts as $contact) {
			$contact_obj = new pikaContact($contact['contact_id']);
			$this->setXMLValue('/ClientIntake/Contacts', array('Contact' => ''));
			$contact_xml_prefix = "/ClientIntake/Contacts/Contact[{$contact_count}]";
			$this->setXMLValue($contact_xml_prefix, array('@ContactID' => $contact['contact_id']));
			$is_primary = 'false';
			if($case->client_id == $contact['contact_id']) { $is_primary = 'true'; }
			$this->setXMLValue($contact_xml_prefix, array('@Primary' => $is_primary));
			$this->setXMLValue($contact_xml_prefix, array('Role' => $this->lookupXMLValue('relation_codes',$contact['relation_code'])));
			$this->setXMLValue($contact_xml_prefix, array('First_Name' => $contact['first_name']));
			$this->setXMLValue($contact_xml_prefix, array('Middle_Name' => $contact['middle_name']));
			$this->setXMLValue($contact_xml_prefix, array('Last_Name' => $contact['last_name']));

			$this->setXMLValue($contact_xml_prefix, array('Address' => ''));
			$this->setXMLValue($contact_xml_prefix . '/Address', array('AddressLine1' => $contact['address']));
			$this->setXMLValue($contact_xml_prefix . '/Address', array('AddressLine2' => $contact['address2']));
			$this->setXMLValue($contact_xml_prefix . '/Address', array('City' => $contact['city']));
			$this->setXMLValue($contact_xml_prefix . '/Address', array('State' => $contact['state']));
			$this->setXMLValue($contact_xml_prefix . '/Address', array('ZipCode' => $contact['zip']));
			$this->setXMLValue($contact_xml_prefix . '/Address', array('County' => $contact['county']));

			$this->setXMLValue($contact_xml_prefix, array('Telephone' => ''));
			$this->setXMLValue($contact_xml_prefix . '/Telephone[1]', array('AreaCode' => $contact['area_code']));
			$this->setXMLValue($contact_xml_prefix . '/Telephone[1]', array('PhoneNumber' => $contact['phone']));
			$this->setXMLValue($contact_xml_prefix . '/Telephone[1]', array('Extension' => $contact['phone_notes']));

			$this->setXMLValue($contact_xml_prefix, array('Telephone' => ''));
			$this->setXMLValue($contact_xml_prefix . '/Telephone[2]', array('AreaCode' => $contact['area_code_alt']));
			$this->setXMLValue($contact_xml_prefix . '/Telephone[2]', array('PhoneNumber' => $contact['phone_alt']));
			$this->setXMLValue($contact_xml_prefix . '/Telephone[2]', array('Extension' => $contact['phone_notes_alt']));

			$this->setXMLValue($contact_xml_prefix, array('SSN' => $contact['ssn']));
			$this->setXMLValue($contact_xml_prefix, array('DOB' => $contact['birth_date']));
			$this->setXMLValue($contact_xml_prefix, array('Gender' => $this->lookupXMLValue('gender',$contact['gender'])));
			$this->setXMLValue($contact_xml_prefix, array('Ethnicity' => $this->lookupXMLValue('ethnicity',$contact['ethnicity'])));
			$this->setXMLValue($contact_xml_prefix, array('Language' => $this->lookupXMLValue('language',$contact['language'])));
			$this->setXMLValue($contact_xml_prefix, array('Marital' => $this->lookupXMLValue('marital',$contact['marital'])));
			if($case->client_id == $contact['contact_id']) 
			{
				$this->setXMLValue($contact_xml_prefix, array('Citizenship' => $this->lookupXMLValue('citizen',$case->citizen)));
			}
			$this->setXMLValue($contact_xml_prefix, array('Email' => $contact['email']));
			
			
			$aliases = array();
			$aliasesDB = $contact_obj->getAliasesDb();
			while($row = mysql_fetch_assoc($aliasesDB)) {
				if(isset($row['primary_name']) && !$row['primary_name']) {
					$aliases[] = $row;
				}
			}
			$alias_count = 1;
			foreach ($aliases as $alias) {
				$this->setXMLValue($contact_xml_prefix, array('Alias' => ''));
				$this->setXMLValue($contact_xml_prefix . "/Alias[{$alias_count}]", array('First_Name' => $alias['first_name']));
				$this->setXMLValue($contact_xml_prefix . "/Alias[{$alias_count}]", array('Middle_Name' => $alias['middle_name']));
				$this->setXMLValue($contact_xml_prefix . "/Alias[{$alias_count}]", array('Last_Name' => $alias['last_name']));
				$alias_count++;
			}
			
			$custom_count = 1;
			foreach ($contact as $field_name => $field_value) {
				if(!in_array($field_name,$this->contacts_fields)&& !in_array($field_name,$this->contacts_fields_blocked)) {
					$custom_xml_prefix = $contact_xml_prefix . "/Custom[{$custom_count}]";
					//$field_value = iconv("ISO-8859-1","UTF-8",$field_value);
					$this->setXMLValue($contact_xml_prefix, array("Custom" => $field_value));
					$this->setXMLValue($custom_xml_prefix , array("@FieldName" => $field_name));
					$custom_count++;
				}
			}
			$contact_count++;
		}

		// Eligibility
		$this->setXMLValue('/ClientIntake', array('Eligibility' => ''));
		$elig_xml_prefix = '/ClientIntake/Eligibility';

		$this->setXMLValue($elig_xml_prefix, array('Adults' => $case->adults));
		$this->setXMLValue($elig_xml_prefix, array('Children' => $case->children));

		// Get number of local db asset & income fields
		
		$asset_fields_array = self::getAssetFields();
		
		
		$this->setXMLValue($elig_xml_prefix, array('Assets' => ''));

		$asset_count = 1;
		foreach ($asset_fields_array as $field_name) {
			$asset = 0;
			$asset_type = '';
			if(isset($case_row[$field_name]) && $case_row[$field_name]) {
				$asset = $case_row[$field_name];
			}
			$asset_type_field = "asset_type" . substr(strrev($field_name),0,1);
			if(isset($case_row[$asset_type_field]) && $case_row[$asset_type_field]) {
				$asset_type = $case_row[$asset_type_field];
			}
			if($asset > 0) {
				$this->setXMLValue($elig_xml_prefix . '/Assets', array('Asset' => $asset));
				$this->setXMLValue($elig_xml_prefix . "/Assets/Asset[{$asset_count}]", array('@AssetType' => $this->lookupXMLValue('asset_type',$asset_type)));
				$asset_count++;
			}
		}
		
		$income_fields_array = self::getIncomeFields();
		
		$this->setXMLValue($elig_xml_prefix, array('Incomes' => ''));
		$this->setXMLValue($elig_xml_prefix, array('Expenses' => ''));
		$income_count = $expense_count = 1;
		foreach ($income_fields_array as $field_name) {
			$income = 0;
			$income_type = '';
			if(isset($case_row[$field_name]) && $case_row[$field_name]) {
				$income = $case_row[$field_name];
			}
			$income_type_field = "income_type" . substr(strrev($field_name),0,1);
			if(isset($case_row[$income_type_field]) && $case_row[$income_type_field]) {
				$income_type = $case_row[$income_type_field];
			}
			if($income > 0) {
				$this->setXMLValue($elig_xml_prefix . '/Incomes', array('Income' => $income));
				$this->setXMLValue($elig_xml_prefix . "/Incomes/Income[{$income_count}]", array('@IncomeType' => $this->lookupXMLValue('income_type',$income_type)));
				$this->setXMLValue($elig_xml_prefix . "/Incomes/Income[{$income_count}]", array('@IncomeFrequencyType' => 'Annual'));
				$income_count++;
			}
			elseif($income < 0)
			{
				$this->setXMLValue($elig_xml_prefix . '/Expenses', array('Expense' => $income));
				$this->setXMLValue($elig_xml_prefix . "/Expenses/Expense[{$expense_count}]", array('@ExpenseType' => $this->lookupXMLValue('expense_type',$income_type)));
				$this->setXMLValue($elig_xml_prefix . "/Expenses/Expense[{$expense_count}]", array('@ExpenseFrequencyType' => 'Annual'));
				$expense_count++;
			}
		}
		
		$this->setXMLValue($elig_xml_prefix, array('EligibilityNotes' => $case->elig_notes));

		// Case Information
		$this->setXMLValue('/ClientIntake', array('CaseInformation' => ''));
		$case_xml_prefix = '/ClientIntake/CaseInformation';

		$this->setXMLValue($case_xml_prefix, array('LSCProblemCode' => $case->problem));
		$this->setXMLValue($case_xml_prefix, array('LSCSubProblemCode' => $case->sp_problem));
		$this->setXMLValue($case_xml_prefix, array('IntakeDate' => $case->created));
		$this->setXMLValue($case_xml_prefix, array('OpenDate' => $case->open_date));
		$this->setXMLValue($case_xml_prefix, array('IntakeMethod' => $this->lookupXMLValue('intake_type',$case->intake_type)));
		$this->setXMLValue($case_xml_prefix, array('Funding' => $case->funding));
		$this->setXMLValue($case_xml_prefix, array('CloseDate' => $case->close_date));
		$this->setXMLValue($case_xml_prefix, array('LSCClosingCode' => $case->close_code));
		$this->setXMLValue($case_xml_prefix, array('Outcome' => $this->lookupXMLValue('outcome',$case->outcome)));

		$custom_count = 1;
		foreach ($case_row as $field_name => $field_value) {

			if(!in_array($field_name,$this->cases_fields) && !in_array($field_name,$this->cases_fields_blocked)) {

				$custom_xml_prefix = $case_xml_prefix . "/Custom[{$custom_count}]";
				//$field_value = iconv("ISO-8859-1","UTF-8",$field_value);
				$this->setXMLValue($case_xml_prefix, array("Custom" => $field_value));
				$this->setXMLValue($custom_xml_prefix, array("@FieldName" => $field_name));
				$custom_count++;
			}
		}

		// Notes
		$sql = "SELECT * FROM activities WHERE case_id = '{$case->case_id}';";
		$notes = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		$this->setXMLValue('/ClientIntake', array('Notes' => ''));
		$notes_count = 1;
		while ($row = mysql_fetch_assoc($notes)) {
			$this->setXMLValue('/ClientIntake/Notes', array('Note' => ''));
			$note_xml_prefix = "/ClientIntake/Notes/Note[{$notes_count}]";

			$clean_summary = htmlentities($row['summary']);
			$clean_notes = htmlentities(trim($row['notes']));

//			$this->setXMLValue($note_xml_prefix,array('NoteSummary' => $row['summary']));
//			$this->setXMLValue($note_xml_prefix,array('NoteText' => trim($row['notes'])));
			$this->setXMLValue($note_xml_prefix,array('NoteSummary' => $clean_summary));
			$this->setXMLValue($note_xml_prefix,array('NoteText' => $clean_notes));
			
			$this->setXMLValue($note_xml_prefix,array('NoteDate' => $row['act_date']));
			$custom_count = 1;
			foreach ($row as $field_name => $field_value) {
				
				if(!in_array($field_name,$this->activities_fields) && !in_array($field_name,$this->activities_fields_blocked)) {
					
					$custom_xml_prefix = $note_xml_prefix . "/Custom[{$custom_count}]";
					$this->setXMLValue($note_xml_prefix, array("Custom" => $field_value));
					$this->setXMLValue($custom_xml_prefix, array("@FieldName" => $field_name));
					$custom_count++;
				}
			}
			$notes_count++;
		}

		return $this->xml_obj->saveXML();
	}

	public function importXML ($save = true) {

		if(!is_object($this->xml_obj)) {return false;}
		
		$activities_array = $contacts_array = array();
		// Case Information
		$case_row = array();
		$case_row['problem'] = $this->getXMLValue('/ClientIntake/CaseInformation/LSCProblemCode');
		$case_row['sp_problem'] = $this->getXMLValue('/ClientIntake/CaseInformation/LSCSubProblemCode');
		$case_row['created'] = $this->getXMLValue('/ClientIntake/CaseInformation/IntakeDate');
		$case_row['open_date'] = $this->getXMLValue('/ClientIntake/CaseInformation/OpenDate');
		$case_row['intake_type'] = $this->getXMLValue('/ClientIntake/CaseInformation/IntakeMethod');
		$case_row['intake_type'] = $this->lookupXMLValue('intake_type',$case_row['intake_type']);
		$case_row['funding'] = $this->getXMLValue('/ClientIntake/CaseInformation/Funding');
		$case_row['close_date'] = $this->getXMLValue('/ClientIntake/CaseInformation/CloseDate');
		$case_row['close_code'] = $this->getXMLValue('/ClientIntake/CaseInformation/LSCClosingCode');
		$case_row['outcome'] = $this->getXMLValue('/ClientIntake/CaseInformation/Outcome');
		$case_row['outcome'] = $this->lookupXMLValue('outcome',$case_row['outcome']);
		
		// Custom
		$tmp1 = $this->xml_obj->getElementsByTagName('Custom');
		
		foreach ($tmp1 as $tmp2)
		{
			if ($tmp2->getNodePath()  == '/ClientIntake/CaseInformation/Custom'
					|| $tmp2->getNodePath() == '/ClientIntake/Eligibility/Custom')
			{
				foreach ($tmp2->childNodes as $tmp3) 
				{
					if ($tmp3->nodeName != '#text')
					{
						$case_row[$tmp3->nodeName] = $tmp3->nodeValue;
					}
				}
			}
		}
		
		// Eligibility
		$case_row['adults'] = $this->getXMLValue('/ClientIntake/Eligibility/Adults');
		$case_row['children'] = $this->getXMLValue('/ClientIntake/Eligibility/Children');
		$case_row['persons_helped'] = $case_row['adults'] + $case_row['children'];
		$case_row['elig_notes'] = $this->getXMLValue('/ClientIntake/Eligibility/EligibilityNotes');

		
		// Determine the number of fields in the receving database to hold the asset information
		
		$asset_fields_array = self::getAssetFields();
		$asset_field_count = count($asset_fields_array);
		
		
		$assets = $this->getXMLValue('/ClientIntake/Eligibility/Assets/Asset',true);
		$asset_total = 0;
		$count = 0;
		if(is_array($assets))
		{
			foreach ($assets as $field) {
				$asset_type_field = "asset_type{$count}";
				$asset_type = $this->getXMLValue($field . "/@AssetType");
				$asset_field = "asset{$count}";
				$fieldvalue = $this->getXMLValue($field);
				$case_row[$asset_type_field] = $this->lookupXMLValue('asset_type',$asset_type);
				$case_row[$asset_field] = $fieldvalue;
				if($count >= $asset_field_count)
				{
					// Need to summarize remaining asset data and list in eligibility notes
					// for the purposes of this placeholder we'll try to default to Other
					$asset_total += $fieldvalue;
					$case_row['elig_notes'] .= "\nAsset {$asset_type}:\${$fieldvalue}";
				}
				else 
				{
					$case_row[$asset_field] = $fieldvalue;
					$count++;	
				}
			}
			if($asset_total > 0)
			{ // We have extra asset information with nowhere to display (we've run out of asset fields)
				$case_row["asset_type" . $count] = "";
				$case_row["asset" . $count] = $asset_total;
				$count++;
			}
		}
		
		// First need to determine number of available Pika income fields
		
		$income_fields_array = self::getIncomeFields();
		$income_field_count = count($income_fields_array);
		
		
		
		$incomes = $this->getXMLValue('/ClientIntake/Eligibility/Incomes/Income',true);
		$count = 0;
		$income_total = 0;
		
		if(is_array($incomes))
		{
			foreach ($incomes as $field) {
				$income_type_field = "income_type{$count}";
				$income_type = $this->getXMLValue($field . "/@IncomeType");
				$income_freq_field = "income_freq{$count}";
				$income_freq = $this->getXMLValue($field . "/@IncomeFrequencyType");
				$income_field = "annual{$count}";
				$fieldvalue = $this->getXMLValue($field);
				$case_row[$income_type_field] = $this->lookupXMLValue('income_type',$income_type);
				$case_row[$income_freq_field] = $this->lookupXMLValue('income_freq',$income_freq);
				if(
					is_numeric($case_row[$income_freq_field]) && 
					in_array($case_row[$income_freq_field],array('1','12','26','52')) &&
					is_numeric($fieldvalue) ) 
					{
					$fieldvalue = $fieldvalue * $case_row[$income_freq_field];
					$case_row[$income_freq_field] = 'A';
				}
				if($count >= $income_field_count - 2)
				{ 
					// Need to summarize remaining income data and list in eligibility notes
					// for the purposes of this placeholder we'll try to default to Other
					$income_total += $fieldvalue;
					$case_row['elig_notes'] .= "\nIncome {$income_type}:\${$fieldvalue}";
				}
				else 
				{
					$case_row[$income_field] = $fieldvalue;
					$count++;	
				}
			}
			
			if($income_total > 0)
			{ // We have extra income information with nowhere to display (we've run out of income fields)
				$case_row["income_type" . $count] = "";
				$case_row["annual" . $count] = $income_total;
				$count++;
			}
		}
		$expense_total = 0;
		$expenses = $this->getXMLValue('/ClientIntake/Eligibility/Expenses/Expense',true);
		if(is_array($expenses))
		{
			foreach ($expenses as $field) {
				$income_type_field = "income_type{$count}";
				$expense_type = $this->getXMLValue($field . "/@ExpenseType");
				$income_freq_field = "income_freq{$count}";
				$expense_freq = $this->getXMLValue($field . "/@ExpenseFrequencyType");
				$income_field = "annual{$count}";
				$fieldvalue = $this->getXMLValue($field);
				$case_row[$income_type_field] = $this->lookupXMLValue('income_type',$expense_type);
				$case_row[$income_freq_field] = $this->lookupXMLValue('income_freq',$expense_freq);
				if(
					is_numeric($case_row[$income_freq_field]) && 
					in_array($case_row[$income_freq_field],array('1','12','26','52')) &&
					is_numeric($fieldvalue) ) 
					{
						$fieldvalue = $fieldvalue * $case_row[$income_freq_field];
						$case_row[$income_freq_field] = 'A';
				}
				if($count >= $income_field_count - 1)
				{ 
					// Need to summarize remaining expense data and list in eligibility notes
					// for the purposes of this placeholder we'll try to default to Other
					$expense_total += abs($fieldvalue);
					$case_row['elig_notes'] .= "\nExpense {$expense_type}:-\$". abs($fieldvalue);
				}
				else 
				{
					$case_row[$income_field] = -abs($fieldvalue);
					$count++;	
				}
			}
			
			if($expense_total > 0)
			{ // We have extra income information with nowhere to display (we've run out of income fields)
				//$case_row["income_type" . $count] = $this->lookupXMLValue('income_type','Other');
				$case_row["income_type{$count}"] = "";
				$case_row["annual" . $count] = -abs($expense_total);
			}
		}
		

		// Custom Eligibility elements are handled earlier.
		// print_r($case_row);
		

		// Notes Information
		$notes = $this->getXMLValue('/ClientIntake/Notes/Note');
		foreach ($notes as $note) {
			$activity_row = array();
			$note_summary = $this->getXMLValue($note . "/NoteSummary");
			$note_text = $this->getXMLValue($note . "/NoteText");
			$note_date = $this->getXMLValue($note . "/NoteDate");

			$activity_row['summary'] = $note_summary;
			$activity_row['notes'] = $note_text;
			$activity_row['act_date'] = $note_date;
			
			// Custom Notes elements are not supported.
			
			$activities_array[] = $activity_row;
			//print_r($activity_row);
		}

		// Contacts Information
		
		$contacts = $this->getXMLValue('/ClientIntake/Contacts/Contact');
		foreach ($contacts as $contact) {
			$contact_row = array();
			$contact_id = $this->getXMLValue($contact . "/@ContactID");
			$contact_primary = $this->getXMLValue($contact . "/@Primary");
			$contact_role = $this->getXMLValue($contact . "/Role");
			//echo $contact_role;
			$contact_fname = $this->getXMLValue($contact . "/First_Name");
			$contact_mname = $this->getXMLValue($contact . "/Middle_Name");
			$contact_lname = $this->getXMLValue($contact . "/Last_Name");
			$contact_ssn = $this->getXMLValue($contact . "/SSN");
			$contact_dob = $this->getXMLValue($contact . "/DOB");
			$contact_gender = $this->getXMLValue($contact . "/Gender");
			$contact_ethnicity = $this->getXMLValue($contact . "/Ethnicity");
			$contact_language = $this->getXMLValue($contact . "/Language");
			$contact_marital = $this->getXMLValue($contact . "/Marital");
			$contact_citizenship = $this->getXMLValue($contact . "/Citizenship");
			$contact_email = $this->getXMLValue($contact . "/Email");
			
			$contact_row['contact_id_old'] = $contact_id;
			$contact_row['contact_primary'] = $contact_primary;
			
			$contact_row['role'] = $this->lookupXMLValue('relation_codes',$contact_role);
			$contact_row['first_name'] = $contact_fname;
			$contact_row['middle_name'] = $contact_mname;
			$contact_row['last_name'] = $contact_lname;
			$contact_row['ssn'] = $contact_ssn;
			$contact_row['birth_date'] = $contact_dob;
			$contact_row['gender'] = $this->lookupXMLValue('gender',$contact_gender);
			$contact_row['ethnicity'] = $this->lookupXMLValue('ethnicity',$contact_ethnicity);
			$contact_row['language'] = $this->lookupXMLValue('language',$contact_language);
			$contact_row['marital'] = $this->lookupXMLValue('marital',$contact_marital);
			$case_row['citizen'] = $this->lookupXMLValue('citizen',$contact_citizenship);
			$contact_row['email'] = $contact_email;
			
			// Address
			
			$addresses = $this->getXMLValue($contact . '/Address');
			$address_count = 1;
			foreach ($addresses as $address) {
				$address1 = $this->getXMLValue($address . '/AddressLine1');
				$address2 = $this->getXMLValue($address . '/AddressLine2');
				$city = $this->getXMLValue($address . '/City');
				$state = $this->getXMLValue($address . '/State');
				$zipcode = $this->getXMLValue($address . '/ZipCode');
				$county = $this->getXMLValue($address . '/County');
				
				if($address_count == 1) {
					$contact_row['address'] = $address1;
					$contact_row['address2'] = $address2;
					$contact_row['city'] = $city;
					$contact_row['state'] = $state;
					$contact_row['zip'] = $zipcode;
					$contact_row['county'] = $county;
				} else {
					$contact_row['notes'] .= "Address[{$address_count}: {$address1} {$address2} {$city},{$state} {$zipcode}\n";
				}
				$address_count++;
			}
			
			// Telephone
			
			$telephone_nums = $this->getXMLValue($contact . '/Telephone');
			$telephone_fields = array('','_alt');
			$telephone_count = 0;
			foreach ($telephone_nums as $telephone) {
				$area_code = $this->getXMLValue($telephone . '/AreaCode');
				$telephone_number = $this->getXMLValue($telephone . '/PhoneNumber');
				$extension = $this->getXMLValue($telephone . '/Extension');
				$telephone_type = $this->getXMLValue($telephone . '/TelephoneType');
				
				if(isset($telephone_fields[$telephone_count])) {
					$suffix = $telephone_fields[$telephone_count];
					$contact_row["area_code{$suffix}"] = $area_code;
					$contact_row["phone{$suffix}"] = $telephone_number;
					$contact_row["phone_notes{$suffix}"] = $extension . " " . $telephone_type;
					$telephone_count++;
				}
				else
				{
					$contact_row['notes'] .= "Addl Phone ({$telephone_type}): {$area_code}-{$telephone_number}";
				}
				
			}
			
			$contact_row['aliases'] = array();
			$aliases = $this->getXMLValue($contact . '/Alias');
			if(is_array($aliases))
			{
				foreach ($aliases as $alias) {
					$first_name = $this->getXMLValue($alias . '/First_Name');
					$middle_name = $this->getXMLValue($alias . '/Middle_Name');
					$last_name = $this->getXMLValue($alias . '/Last_Name');
					$contact_row['aliases'][] = array('first_name' => $first_name, 'middle_name' => $middle_name, 'last_name' => $last_name);
				}
			}
			
			// See if this contact has any custom fields.
			$tmp0 = explode('/', $contact);
			$this_contact_node_name = array_pop($tmp0);
			
			$tmp1 = $this->xml_obj->getElementsByTagName('Custom');
			
			foreach ($tmp1 as $tmp2)
			{
				if ($tmp2->getNodePath()  == '/ClientIntake/Contacts/' . $this_contact_node_name . '/Custom')
				{
					foreach ($tmp2->childNodes as $tmp3) 
					{
						if ($tmp3->nodeName != '#text')
						{
							$contact_row[$tmp3->nodeName] = $tmp3->nodeValue;
						}
					}
				}
			}
			
			$contacts_array[] = $contact_row;
			
			//print_r($contact_row);
		}
		if($save) {
			require_once('pikaCase.php');
			require_once('pikaActivity.php');
			require_once('pikaContact.php');
			$case = new pikaCase();
			$case->setValues($case_row);
			$case->save();
			foreach ($activities_array as $act) {
				$activity = new pikaActivity();
				$activity->setValues($act);
				$activity->case_id = $case->case_id;
				$activity->save();
			}
			foreach ($contacts_array as $con) {
				$contact = new pikaContact();
				$contact->setValues($con);
				$contact->save();
				$case->addContact($contact->contact_id,$con['role']);
				if(isset($con['primary']) && $con['primary'] == "true" && $con['role'] == '1') {
					$case->client_id = $contact->contact_id;
					$case->save();
				}
			}
			return $case->case_id;
		}
		$case_row['activities'] = $activities_array;
		$case_row['contacts'] = $contacts_array;
		return $case_row;
	}

	public function getXMLValue($xpath_query,$location_only = false)
	{
		if (!is_object($this->xpath)) {$this->xpath = new DOMXPath($this->xml_obj);}
		$xpath = $this->xpath;
		$tmp_val = "";
		$result_list = $xpath->query($xpath_query);
		if ($result_list->length) {
			if($result_list->length == 1) {
				if($location_only)
				{
					return array("{$xpath_query}[1]");
				}
				else
				{
					foreach ($result_list->item(0)->childNodes as $c) {
   						if ($c->nodeType == XML_ELEMENT_NODE) {
    						return array("{$xpath_query}[1]");
   						}
  					}
					return $result_list->item(0)->nodeValue;
				}
			}
			else {
				$count = 1;
				foreach ($result_list as $node) {
					$tmp_val[] = $xpath_query. "[{$count}]";
					$count++;
				}
			}
		}
		else {$this->log .= "Unable to find {$xpath_query} in LSXML file\n";}
		return $tmp_val;
	}

	public function setXMLValue ($xpath_parent_node = null, $values = null) {
		if (is_null($xpath_parent_node) || is_null($values)) {return false;}
		if (!is_object($this->xpath)) {$this->xpath = new DOMXPath($this->xml_obj);}
		if (is_array($values)) {
			foreach ($values as $element_name => $element_value) {
				if (!is_numeric($element_name)) {
					$parent_node_list = $this->xpath->query($xpath_parent_node);
					if ($parent_node_list->length == 1) {
						$parent_node = $parent_node_list->item(0);
						if (substr($element_name,0,1) == "@") {
							$child_node = $parent_node->setAttribute(substr($element_name,1),$element_value);
						}
						else {
							$child_node = $this->xml_obj->createElement($element_name,$element_value);
							$child_node = $parent_node->appendChild($child_node);
						}
					}
					elseif ($parent_node_list->length > 1) {
						$this->log .= "Parent Node query '{$xpath_parent_node}' returned too many results\n";
					}
					else {
						$this->log .= "Parent Node query '{$xpath_parent_node}' didn't return any results\n";
					}
				}
			}
		}
	}

	public function lookupXMLValue($field_name = null,$lookup_value = null) {
		// Set up the default menus first (these will likely not be correct)
		$a = array();
		$a['asset_type'] = array('1'=>'PersonalProperty',
								'2'=>'RealProperty',
								'3'=>'Checking',
								'4'=>'Savings',
								'5'=>'Automobile',
								'9'=>'Other');
		$a['income_type'] = array('1'=>'Employment',
								'2'=>'SocialSecurity',
								'3'=>'SSI',
								'4'=>'GeneralAssistance',
								'6'=>'ChildSupport',
								'9'=>'SpousalMaintenance',
								'11'=>'WorkersComp',
								'12'=>'Disability',
								'13'=>'Pension',
								'14'=>'Trust',
								'15'=>'Unemployment',
								'16'=>'VeteransBenefits',
								'17'=>'SeniorUnknown',
								'18'=>'Other');
		$a['expense_type'] = array(
								"18" => "Rent",
								"18" => "Mortgage",
								"18" => "Transportation",
								"18" => "Taxes",
								"18" => "Medical",
								"18" => "ChildCare",
								"6" => "ChildSupport",
								"9" => "SpousalMaintenance",
								"1" => "Employment",
								"18" => "Other"
		);
		$a['income_freq'] = array('1'=>'Annual','12'=>'Monthly','26'=>'BiWeekly','52'=>'Weekly');
		$a['intake_type'] = array('W'=>'WalkIn','T'=>'Telephone','C'=>'CircuitRider','L'=>'Letter','I'=>'Internet');
		$a['outcome'] = array('1'=>'HearingWon',
							'2'=>'HearingLost',
							'3'=>'SettledFavorably',
							'4'=>'SettledUnfavorably',
							'5'=>'OtherFavorable',
							'6'=>'OtherUnfavorable',
							'7'=>'NoEffect',
							'8'=>'Dismissed');
		$a['citizen'] = array('A'=>'Citizen','B'=>'LegalAlien','C'=>'UndocumentedAlien');
		$a['marital'] = array('S'=>'Single','M'=>'Married','W'=>'Widowed','D'=>'Divorced','P'=>'Separated','U'=>'Unknown');
		$a['ethnicity'] = array('10'=>'White','20'=>'Black','30'=>'Hispanic','40'=>'NativeAmerican','50'=>'Asian','99'=>'Other');
		$a['gender'] = array('F'=>'Female','M'=>'Male','U'=>'Unknown');
		$a['relation_codes'] = array('1'=>'Client','2'=>'OpposingParty','3'=>'OpposingCounsel','5'=>'Judge','50'=>'ReferralAgency','99'=>'Other');
		$a['language'] = array ('A' => 'sq', 'B' => 'km', 'D' => 'so',
											'E' => 'en', 'F' => 'fr', 'G' => 'de',
											'I' => 'it', 'J' => 'ja', 'K' => 'ko',
											'M' => 'zh', 'P' => 'pl', 'R' => 'ru',
											'S' => 'es', 'T' => 'tr', 'V' => 'vi',
											'W' => 'sr', 'X' => 'zh', 'Y' => 'yi');

		if(file_exists($this->lookupXMLValue_settings_file)) 
		{
			$b = include($this->lookupXMLValue_settings_file);
			foreach ($a as $key => $val)
			{
				if(isset($b[$key]))
				{
					$a[$key] = $b[$key];
				}
			}
		}
		
		if ($field_name != 'income_freq')
		{
			$a[$field_name] = pl_menu_get($field_name);
		}
		
		$match = '';
		if(isset($a[$field_name]) && is_array($a[$field_name]) && count($a[$field_name])) {
			$tmp_array = $a[$field_name];
			
			if(isset($tmp_array[$lookup_value])) {
				$match = $tmp_array[$lookup_value];
			} else {
				$tmp_array2 = array_flip($tmp_array);
				if(isset($tmp_array2[$lookup_value])) {
					$match = $tmp_array2[$lookup_value];
				}
			}
		}
		
		return $match;
	}

	

	public function fuzzyConflictCheck($lim = 10)
	{
		ini_set('display_errors','On');
		$case = $this->importXML(false);
		
		//print_r($case['contacts']);
		//exit;
		// Assemble Aliases Array
		$aliases_array = array();
		foreach ($case['contacts'] as $contact) {
			$lineitem = array();
			// Create primary alias
			$lineitem['relation_code'] = $contact['role'];
			$lineitem['mp_first'] = metaphone($contact['first_name']);
			$lineitem['mp_last'] = metaphone($contact['last_name']);
			$lineitem['birth_date'] = $contact['birth_date'];
			$lineitem['ssn'] = $contact['ssn'];
			$aliases_array[] = $lineitem;
			// Determine metaphone names
			foreach ($contact['aliases'] as $alias) {
				$lineitem = array();
				$lineitem['relation_code'] = $contact['role'];
				$lineitem['mp_first'] = metaphone($alias['first_name']);
				$lineitem['mp_last'] = metaphone($alias['last_name']);
				$lineitem['birth_date'] = $contact['birth_date'];
				$lineitem['ssn'] = $contact['ssn'];
				$aliases_array[] = $lineitem;
			}
		}
		//print_r($aliases_array);
		//exit;
		$conflict_array = array();
		foreach ($aliases_array as $row)
		{
			// Match by metaphone name/birth date
			if (strlen($row['mp_first']) > 0)
			{
				$mp_first = " AND aliases.mp_first='{$row['mp_first']}'";
			} else {
				$mp_first = '';
			}
			if ($row['birth_date'])
			{
				$safe_birth_date = mysql_real_escape_string(date('Y-m-d',strtotime($row['birth_date'])));
				$mp_first .= " AND (birth_date='{$safe_birth_date}' OR birth_date IS NULL)";
			}
			$sql = "SELECT conflict.*, contacts.*, number, cases.case_id, problem, cases.status, label AS role
					FROM aliases
					LEFT JOIN contacts ON aliases.contact_id=contacts.contact_id
					LEFT JOIN conflict ON aliases.contact_id=conflict.contact_id
					LEFT JOIN cases ON conflict.case_id=cases.case_id
					LEFT JOIN menu_relation_codes ON conflict.relation_code=menu_relation_codes.value
					WHERE relation_code != {$row['relation_code']} AND aliases.mp_last='{$row['mp_last']}'{$mp_first}
					LIMIT $lim";
			
			$sub_result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
			
			while($tmp_row = mysql_fetch_assoc($sub_result))
			{
				$tmp_row['match'] = 'NAME';
				$conflict_array[] = $tmp_row;
			}
			
			// Match by SSN
			if (strlen($row['ssn'] > 0))
			{
				$sql = "SELECT conflict.*, contacts.*, number, cases.case_id, problem, cases.status, label AS role
					FROM contacts
					LEFT JOIN conflict ON contacts.contact_id=conflict.contact_id
					LEFT JOIN cases ON conflict.case_id=cases.case_id
					LEFT JOIN menu_relation_codes ON conflict.relation_code=menu_relation_codes.value
					WHERE relation_code != {$row['relation_code']} AND ssn='{$row['ssn']}'
					AND mp_last!='{$row['mp_last']}'
					LIMIT $lim";
				$sql = "SELECT conflict.*, contacts.*, number, cases.case_id, problem, cases.status, label AS role
					FROM aliases
					LEFT JOIN contacts ON aliases.contact_id=contacts.contact_id
					LEFT JOIN conflict ON aliases.contact_id=conflict.contact_id
					LEFT JOIN cases ON conflict.case_id=cases.case_id
					LEFT JOIN menu_relation_codes ON conflict.relation_code=menu_relation_codes.value
					WHERE relation_code != {$row['relation_code']} AND aliases.ssn='{$row['ssn']}'
					AND aliases.mp_last!='{$row['mp_last']}'
					LIMIT $lim";
				$sub_result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());;
				//echo $sql;
				while($tmp_row = mysql_fetch_assoc($sub_result))
				{
					$tmp_row['match'] = 'SSN';
					$conflict_array[] = $tmp_row;
				}
			}
		}
		//echo $sql;
		//exit;
		return $conflict_array;
	}
	
	public static function getIncomeFields()
	{
		$income_fields_array = array();
		
		$sql = "SHOW COLUMNS FROM cases LIKE 'annual%';";
		$result = mysql_query($sql) or trigger_error('SQL: ' . $sql . ' Error: ' . mysql_error());
		
		while($row = mysql_fetch_assoc($result))
		{
			if(preg_match('/annual[0-9]+$/',$row['Field']) === 1)
			{
				$income_fields_array[] = $row['Field'];
			}
		}
		
		return $income_fields_array;
	}
	
	public static function getAssetFields()
	{
		$asset_fields_array = array();
		
		$sql = "SHOW COLUMNS FROM cases LIKE 'asset%';";
		$result = mysql_query($sql) or trigger_error('SQL: ' . $sql . ' Error: ' . mysql_error());
		
		while($row = mysql_fetch_assoc($result))
		{
			if(preg_match('/asset[0-9]+$/',$row['Field']) === 1)
			{
				$asset_fields_array[] = $row['Field'];
			}
		}
		
		return $asset_fields_array;
	}
	
}
?>