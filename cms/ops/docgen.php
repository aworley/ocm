<?php 

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

/* docgen.php is used to generate form letters and other documents.  It fills in case and
other information into a specified template file.  Template files are usually in RTF, XML
or any plain text format.
*/

define('PIKA_NO_COMPRESSION', 1);

/* unlike some scripts, date input variables aren't converted with pl_mog
right away in this script.
*/
chdir('../');
require_once ('pika-danio.php');
pika_init();

require_once('pikaCase.php');
require_once('pikaContact.php');
require_once('pikaUser.php');
require_once('pikaDocument.php');
require_once('pikaTempLib.php');


if ( !function_exists('sys_get_temp_dir')) {
  function sys_get_temp_dir() {
      if( $temp=getenv('TMP') )        return $temp;
      if( $temp=getenv('TEMP') )        return $temp;
      if( $temp=getenv('TMPDIR') )    return $temp;
      $temp=tempnam(__FILE__,'');
      if (file_exists($temp)) {
          unlink($temp);
          return dirname($temp);
      }
      return null;
  }
}


$case_id = pl_grab_post('case_id');
$recipient = pl_grab_post('recipient');
$opposing = pl_grab_post('opposing');
$opp_counsel = pl_grab_post('opp_counsel');
$form_id = pl_grab_post('form_id');
$autosave = pl_grab_post('autosave');
$debug = pl_grab_post('debug');
$base_url = pl_settings_get('base_url');


$doc = new pikaDocument($form_id);
$doc_data = gzuncompress(stripslashes($doc->doc_data));


// Determine the file extension
$z = explode('.', $doc->doc_name);
$y = array_reverse($z);
$extension = $y[0];

$output_format = 'text';
if ($extension == 'rtf') {
	$output_format = 'rtf';
}
elseif ($extension == 'docx') {
	$output_format = 'docx';
}

// needs security enforcement
$case1 = new pikaCase($case_id);
$a = $case1->getValues();

if (is_numeric($a['client_id']))
{
	$client = new pikaContact($a['client_id']);
	$b = $client->getValues();
	$a = array_merge($a, $b);
}

else
{
	$b = array();
}

// Commented for causing problems - DTK
// Unmogrifying VLP review date
if (isset($a['vlp_review_date'])) 
{
	$a['vlp_review_date'] = pikaTempLib::plugin('text_date','',$a['vlp_review_date']);
}

$counsel_row = array();
if (is_numeric($a['user_id']))
{
	$atty = new pikaUser($a['user_id']);
	$counsel_row = $atty->getValues();
}

$cocounsel1_row = array();
if (is_numeric($a['cocounsel1']))
{
	$atty2 = new pikaUser($a['cocounsel1']);
	$cocounsel1_row = $atty2->getValues();
}

$cocounsel2_row = array();
if (is_numeric($a['cocounsel2']))
{
	$atty3 = new pikaUser($a['cocounsel2']);
	$cocounsel2_row = $atty3->getValues();
}

$pba_row = array();
if (is_numeric($a['pba_id1']))
{
	require_once('pikaPbAttorney.php');
	$pba1 = new pikaPbAttorney($a['pba_id1']);
	$pba_row = $pba1->getValues();
}

$recipient_row = array();
if (is_numeric($recipient))
{
	$recipient_obj = new pikaContact($recipient);
	$recipient_row = $recipient_obj->getValues();
}

// If the recipient is a PB attorney
elseif (substr($recipient,0,3) == 'pba')
{
	// import the class
	require_once('pikaPbAttorney.php');
	// fetch the PB attorney (stripping the 'pba' from the passed value)
	$pbaX = new pikaPbAttorney(substr($recipient,3));
	$recipient_row = $pbaX->getValues();
}



$client_row = $b;

$opposing_row = array();
if (is_numeric($opposing))
{
	$opposing_obj = new pikaContact($opposing);
	$opposing_row = $opposing_obj->getValues();

	$a['opposing_name'] = $a['opposing'] = pikaTempLib::plugin('text_name','',$opposing_row);
	$a['opposing_full_address'] = pikaTempLib::plugin('text_address','',$opposing_row,'',array("output={$output_format}"));
}

$opp_counsel_row = array();
if (is_numeric($opp_counsel))
{
	$opp_counsel_obj = new pikaContact($opp_counsel);
	$opp_counsel_row = $opp_counsel_obj->getValues();

	$a['opp_counsel_name'] = $a['opp_counsel'] = pikaTempLib::plugin('text_name','',$opp_counsel_row);
	$a['opp_counsel_full_address'] = pikaTempLib::plugin('text_address','',$opp_counsel_row,'',array("output={$output_format}"));
}


$a['counsel_name'] = $a['counsel'] = pikaTempLib::plugin('text_name','',$counsel_row);
$a['cocounsel1_name'] = $a['cocounsel1'] = pikaTempLib::plugin('text_name','',$cocounsel1_row);
$a['cocounsel2_name'] = $a['cocounsel2'] = pikaTempLib::plugin('text_name','',$cocounsel2_row);



if (sizeof($pba_row) > 0) 
{
	$a['vol_attorney_name'] = $a['vol_attorney'] = pikaTempLib::plugin('text_name','',$pba_row);
	$a['vol_attorney_firm'] = $pba_row['firm'];
	$a['vol_attorney_address'] = pikaTempLib::plugin('text_address','',$pba_row,'',array("output={$output_format}"));
	// AMW - 2014-07-23 - Added for ILCM.
        $a['vol_attorney_phone'] = $pba_row['phone_notes'];
        $a['vol_attorney_email'] = $pba_row['email'];
}

$a['recipient_salutation'] = pikaTempLib::plugin('text_name','',array('last_name' => $recipient_row['last_name'],'gender'=>$recipient_row['gender']),'',array('salutation'));
$a['recipient'] = $a['recipient_name']= pikaTempLib::plugin('text_name','',$recipient_row);
$a['client'] = $a['client_name'] = pikaTempLib::plugin('text_name','',$client_row);



// Display dates in American style human readable format
$a['open_date'] = pl_date_unmogrify($a['open_date']);
$a['close_date'] = pl_date_unmogrify($a['close_date']);

$a['current_date'] = date("F j, Y");

// Text fields
$a['extra_text1'] = pl_grab_post('text1');
$a['extra_text2'] = pl_grab_post('text2');
$a['extra_text3'] = pl_grab_post('text3');


$a['full_address'] = pikaTempLib::plugin('text_address','',$recipient_row,'',array("output={$output_format}"));



// MDF - Will not work with pl_template wrapper for DB Doc Storage
// TODO - modify pl_template to work with subheadings

$result = $case1->getContactsDb();
$referral_agencies = '';
while ($row = DBResult::fetchRow($result))
{
	if($row['relation_code'] == 50) {
		$row['full_address'] = pikaTempLib::plugin('text_address','',$row,'',array('nobreak',"output={$output_format}"));
		$row['full_phone'] = pl_text_phone($row);
		$referral_template = new pikaTempLib($doc_data,$row,'referral_agencies');
		$referral_agencies .= $referral_template->draw();
	}
}
$a['referral_agencies'] = $referral_agencies;


if($debug) {
	
	$plTemplate['content'] = '';
	
	foreach ($a as $key => $val) {
		//if(!is_array($val)) {
			$plTemplate['content'] .= "Field Name: {$key} => {$val}<br/>\n";
		/*} else {
			$plTemplate['content'] .= "Field Collection: {$key}<br/>\n";
			$plTemplate['content'] .= "<blockquote>\n";
			foreach ($val as $subkey => $subval) {
				$plTemplate['content'] .= "Field Name: {$subkey} => {$subval}<br/>\n";
			}
			$plTemplate['content'] .= "</blockquote>\n";
		}*/
	}
	
	$plTemplate["page_title"] = 'Document Assembly Debug';
	$plTemplate['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
							<a href=\"{$base_url}/case.php?case_id={$case_id}&screen=docs\">{$a['number']}</a> &gt;
							Document Assembly Debug";
	$template = new pikaTempLib('templates/default.html',$plTemplate);
	$buffer = $template->draw();
	//$buffer = pl_template('templates/default.html',$plTemplate);
	pika_exit($buffer);
}


if($extension == 'docx')
{
	$temp_document = tempnam(sys_get_temp_dir(),'');
	file_put_contents($temp_document,$doc_data);

	$docx = new ZipArchive();

	if($docx->open($temp_document) === true)
	{
		$file_string = $docx->getFromName('word/document.xml');
		$template = new pikaTempLib($file_string,$a);
		$templated_file_string = $template->draw();
		$docx->addFromString('word/document.xml',$templated_file_string);
		$docx->close();
		$doc_size = filesize($temp_document);
		$contents = file_get_contents($temp_document);
		unlink($temp_document);
	}
	else 
	{
		trigger_error('There was an error opening the document template.  Please verify that the selected document assembly form is a valid Open Office XML document.');
	}
}

/*
This is commented out because 1) pdftk does not install on CentOS 7 and 2) the
code base is moving away from calling system processes as a security precaution.
The code took a fair amount of time to develop so I'm going to hold onto it here,
however.  AMW
*/
/*
else if ($extension == 'xfdf')
{
        $pdf_path = "/tmp/cc375.pdf";
        $pdf_filename = "cc375.pdf";
        $pipes = array();
        $z = array(array("pipe", "r"), array("pipe", "w"));

        $process = proc_open("pdftk {$pdf_path} fill_form - output -", $z, $pipes);

        if (is_resource($process))
        {
            fwrite($pipes[0], file_get_contents($pdf_path));
            fclose($pipes[0]);
            $pdf_output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $return_value = proc_close($process);

            header("Content-type: application/pdf");
            header("Content-Disposition: attachment; filename=\"{$pdf_filename}\"");
            echo $pdf_output;
        }

        exit();
}
*/

else
{
	$template = new pikaTempLib($doc_data,$a);
	$contents = $template->draw();
	//$contents = pl_template_string($doc_data,$a);
	if(function_exists('mb_strlen')) {
		$doc_size = mb_strlen($contents);	
	} else {
		$doc_size = strlen($contents);
	}
}



if ($autosave) 
{
	
	$new_doc = new pikaDocument();
	$new_doc->doc_data = addslashes(gzcompress($contents,9));
	$new_doc->case_id = $case_id;
	$new_doc->doc_name = $doc->doc_name;
	$new_doc->mime_type = $doc->mime_type;
	$new_doc->doc_type = 'C';
	$new_doc->doc_size = $doc_size;
	$new_doc->user_id = $auth_row['user_id'];
	$new_doc->created = date('Y-m-d');
	$new_doc->save();
	
}


header("Pragma: public");
header("Cache-Control: cache, must-revalidate");
header("Content-type: application/force-download");
header("Content-Type: {$doc->mime_type}");
header("Content-Disposition: inline; filename=\"{$doc->doc_name}\"");

echo $contents;

exit();
