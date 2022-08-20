<?php

define('PL_DISABLE_SECURITY',true);
chdir("..");
require_once ('pika-danio.php');
pika_init();


$user_id = pl_grab_get('user_id');
$clean_user_id = DB::escapeString($user_id);
$base_url = pl_settings_get('base_url');
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == TRUE) {
	$cal_url= "https://".$_SERVER['HTTP_HOST'].$base_url;
}else { $cal_url= "http://".$_SERVER['HTTP_HOST'].$base_url; }


$sql = "SELECT user_id FROM users WHERE 1 AND enabled = 1 AND user_id = '{$safe_user_id}' LIMIT 1;";
$result = DB::query($sql);
if(DBResult::numRows($result) == 1)
{
	require_once ('pikaDefPrefs.php');
	pikaDefPrefs::getInstance()->initPrefs($user_id);
}

//header("Content-Type: application/xml; charset=ISO-8859-1"); 
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<rss version=\"2.0\">\n";
echo "\t<channel>\n";
echo "\t\t<title>Pika CMS Calendar</title>\n";
echo "\t\t<link>{$cal_url}/cal_day.php</link>\n";
echo "\t\t<description>My calendar</description>\n";

$date = date('Y-m-d');
$interval = 7;
if(isset($_SESSION['def_rss_interval']) && is_numeric($_SESSION['def_rss_interval'])) {
	$interval = $_SESSION['def_rss_interval'];
}
$end_date = date('Y-m-d',time() + ($interval * 24 * 60 * 60));

$sql = "SELECT act_id, act_date, act_time, summary, notes
		FROM activities
		WHERE user_id='{$clean_user_id}' AND completed=0 AND act_date>='{$date}' AND act_date<'{$end_date}' LIMIT 200";

		
$result = DB::query($sql) or die("query failed");

while ($row = DBResult::fetchRow($result))
{
	$act_date = pl_date_unmogrify($row['act_date']);
	$act_time = pl_time_unmogrify($row['act_time']);
	echo "\t\t<item>\n";
	echo "\t\t\t<title>{$act_date} {$act_time} - {$row['summary']}</title>\n";
	echo "\t\t\t<link>{$cal_url}/activity.php?act_id={$row['act_id']}</link>\n";
	echo "\t\t\t<description>{$row['notes']}</description>\n";
	echo "\t\t</item>\n";
}

echo "\t</channel>\n";
echo "</rss>";

