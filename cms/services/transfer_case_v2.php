<?php

/***************************/
/* Pika CMS (C) 2015       */
/* Pika Software, LLC.     */
/* http://pikasoftware.com */
/***************************/

chdir('../');

define('PL_HTTP_SECURITY',true);

require_once ('pika-danio.php');
pika_init();
require_once('pikaTransfer.php');

function json_clean_sub($j)
{
  foreach ($j as $key => $value) 
  {
    if (is_array($value))
    {
      $j[$key] = json_clean_sub($value);
    }
    
    else 
    {
      if ($key == 'ssn' && strlen($value) == 9 && is_numeric($value))
      {
        $j[$key] = substr($value, 0, 3) . '-' . substr($value, 3, 2) . '-' 
          . substr($value, 5, 4);
        $j[$key] = $value;
      }
      
      else 
      {
        $j[$key] = pl_clean_form_input($value);
      }
    }
  }
  
  return($j);
}

$auth_row = pikaAuthHttp::getInstance()->getAuthRow();
$transfer = new pikaTransfer;
$transfer->user_id = $auth_row['user_id'];
$j = file_get_contents('php://input');
$d = json_decode($j, true);
$d = json_clean_sub($d);
$d = json_encode($d);
$transfer->json_data = $d;
$transfer->accepted = 2;  // Pending review.
$transfer->save();
echo $transfer->getValue('transfer_id');
exit();
