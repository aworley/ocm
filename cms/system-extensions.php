<?php

/**********************************/
/* Pika CMS (C) 2012 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

require_once ('pika-danio.php');
pika_init();

function scan_extension_folder($subdir, $base_path)
{
	$path = $base_path . '/' . $subdir;
	$a = array();
	$h = opendir($path);
	
	while ($h != false && false !== ($entry = readdir($h))) 
	{
	    if ($entry != "." && $entry != ".." && is_dir($path . "/" . $entry))
	    {
        	if (file_exists($path . "/" . $entry . "/title.txt"))
        	{
        		$title = file_get_contents($path . "/" . $entry . "/title.txt");
        		$a[$title] = array($title, "$subdir/$entry", "");
			}
			
			else if(file_exists($path . "/" . $entry . "/manifest.txt"))
			{
				$ini = parse_ini_file($path . "/" . $entry . "/manifest.txt");
				$a[$ini['site_map_title']] = array($ini['site_map_title'], "$subdir/$entry", 
					$ini['description']);
			}
	    }
    }
    
    closedir($h);
    return $a;
}

// This old one was recursive.  I think that's going to be too confusing in practice.
function old_scan_extension_folder($subdir, $base_path)
{
	$path = $base_path . '/' . $subdir;
	$a = array();
	$h = opendir($path);
	
	while (false !== ($entry = readdir($h))) 
	{
        if ($entry != "." && $entry != ".." && is_dir($path . "/" . $entry))
        {
        	if (file_exists($path . "/" . $entry . "/title.txt"))
        	{
        		$title = file_get_contents($path . "/" . $entry . "/title.txt");
        		$a[$title] = array($title, "$subdir/$entry", "");
			}
			
			else if(file_exists($path . "/" . $entry . "/manifest.txt"))
			{
				$ini = parse_ini_file($path . "/" . $entry . "/manifest.txt");
				$a[$ini['site_map_title']] = array($ini['site_map_title'], "$subdir/$entry", 
					$ini['description']);
			}
			
			else
			{
				$a = array_merge($a, scan_extension_folder($subdir . "/" . $entry, $base_path));
			}
        }
    }
    
    closedir($h);
    return $a;
}

$extension_whitelist = explode(':', pl_settings_get('extensions'));

$x = scan_extension_folder ("", pl_custom_directory() . "/extensions/");
sort($x);

$h ="";
$h .= "<form action=\"ops/update_extensions.php\" method=\"POST\">\n";

foreach($x as $val)
{
	//var_dump($val);
	
	if (array_search($val[1], $extension_whitelist) === false)
	{
		$checked = "";
	}
	
	else
	{
		$checked = " checked";
	}
	
	$h .= "<div class=\"extension\">";
	$h .= "<label class=\"ext\"><input type=\"checkbox\" name=\"{$val[1]}\" tabindex=\"1\"{$checked}>" . $val[0] . "</label>";
	$h .= "<p class=\"file_path\">{$val[1]}</p><p>{$val[2]}</p></div>";
	
}

$h .= "<div class=\"x\"><input type=\"submit\"></div></form>\n";

$base_url = pl_settings_get('base_url');
$main_html = array();
$main_html["page_title"] = "Extensions";
$main_html['content'] = $h;
$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a> &gt; 
				<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; Extensions";




$buffer = pl_template($main_html, 'templates/default.html');

pika_exit($buffer);
?>
