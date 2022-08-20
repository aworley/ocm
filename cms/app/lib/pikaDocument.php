<?php

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

require_once('plBase.php');

/**
* Something.
*
* @author Aaron Worley <amworley@pikasoftware.com>;
* @version 1.0
* @package Danio
*/
class pikaDocument extends plBase 
{
	

	
	public function __construct($doc_id = null)
	{
		$this->db_table = 'doc_storage';
		parent::__construct($doc_id);
		return true;
	}
	
	
	/* Returns array of items in reverse order from current folder
	*  Used for pretty document tree
	*/
	public static function getParentFolders($folder = null) {
			$folders = array();
			$folder_ptr = 0;
			
			if(!is_null($folder) && $folder) {
				
				
				do {
					$sql = "SELECT doc_id, doc_name,
						description, created, doc_type,
						case_id, folder_ptr
						FROM doc_storage
						WHERE 1 
						AND doc_id = {$folder}
						AND folder = 1
						LIMIT 1";
					$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
					if (DBResult::numRows($result) != 0) {
						$row = DBResult::fetchRow($result);
						$folder_ptr = $folder = $row['folder_ptr'];
						$folders[] = $row;
					}
					
				
				} while ($folder_ptr);
			}
			return $folders;
	
	}
	
	
	
	public static function getFiles($folder, $doc_type, $id) {
		$file_array = array();
		$doc_type_mappings = array ('U' => 'user_id', // User files
									'F' => '', // Forms
									'C' => 'case_id', // Case files
									'R' => 'report_name' // Reports
									);
		$folder_sql = '';
		if (!is_null($folder) && $folder && is_numeric($folder)) {
			$safe_folder = DB::escapeString($folder);
			$folder_sql = "AND folder_ptr = '{$safe_folder}'";
		} else {
			$folder_sql = "AND (folder_ptr IS NULL OR folder_ptr = '0')";
		}
		if(isset($doc_type_mappings[$doc_type])) {
			$safe_doc_type = DB::escapeString($doc_type);
			$id_lookup = '';
			if(!is_null($id) && $doc_type_mappings[$doc_type]) {
				$safe_id = DB::escapeString($id);
				$id_lookup = "AND {$doc_type_mappings[$doc_type]} = '{$safe_id}'";
			}
			$sql = "SELECT doc_id, doc_name,
					mime_type, doc_type, doc_size, description, created,
					case_id, user_id, folder, folder_ptr
					FROM doc_storage 
					WHERE 1
					AND doc_type = '{$safe_doc_type}'
					{$id_lookup}
					{$folder_sql}
					ORDER BY folder DESC, doc_name ASC
					LIMIT 5000";
			//echo $sql;
			$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
			while ($row = DBResult::fetchRow($result)) {
				$file_array[] = $row;		
			}
		}
		return $file_array;
	}
	
	
	
	
	public static function getDocumentsByText($text_str)
	{
		$clean_text_str = DB::escapeString($text_str);
		$limit = 30;
		
		
		if (is_numeric($_SESSION['paging'])) 
		{
			$limit = $_SESSION['paging'];
		}
		
		$sql = "SELECT doc_id, doc_name, doc_storage.user_id,
				mime_type, doc_type, doc_size, description, doc_storage.created,
				doc_storage.case_id, folder, folder_ptr,
				cases.number
				FROM doc_storage
				LEFT JOIN cases ON cases.case_id = doc_storage.case_id
				WHERE 1
					AND (folder = 0 OR folder IS NULL)
					AND doc_type = 'C'
					AND (doc_name LIKE '%{$clean_text_str}%' 
					OR description LIKE '%{$clean_text_str}%' 
					OR doc_text LIKE '%{$clean_text_str}%')
				LIMIT {$limit}";
		$result = DB::query($sql) or trigger_error(DB::error());
		return $result;
	}
	
	public static function moveFiles($location = null, $files = array()) {
		if (is_array($files)) {
			if (is_null($location) || !$location) {  // Location left blank or 0 (root) location
				foreach ($files as $val) {
					$tmp_doc = new pikaDocument($val['doc_id']);
					$tmp_doc->folder_ptr = 0;
					$tmp_doc->save();
				}
				return true;
			}
			elseif (is_numeric($location)) { // Location needs to be verified as valid
				$tmp_doc = new pikaDocument($location);
				if ($tmp_doc->folder) {
					foreach ($files as $val) {
						$tmp_doc = new pikaDocument($val['doc_id']);
						$tmp_doc->folder_ptr = 0;
						$tmp_doc->save();
					}
					return true;
				}
			}
		}
		return false;
	}
	
	public function createFolder($folder_name = null,$parent_folder = null, $doc_type = null, $id = null) {
			global $auth_row;
			$doc_type_mappings = array ('U' => 'user_id', // User files
									'F' => '', // Forms
									'C' => 'case_id', // Case files
									'R' => 'report_name' // Reports
									);
			$this->folder = 1;
			if(is_null($folder_name) || !$folder_name) {
				$this->doc_name = "New Folder";
			}else {
				$this->doc_name = $folder_name;	
			}
			if(isset($doc_type_mappings[$doc_type])) {
				if(!is_null($id) && $doc_type_mappings[$doc_type]) {
					$id_field = $doc_type_mappings[$doc_type];
					$this->$id_field = $id;
				}
			}
			
			$this->folder_ptr = $parent_folder;
			
			$this->user_id = $auth_row['user_id'];
			$this->created = date('Y-m-d');
			if(!is_null($doc_type)) {
				$this->doc_type = $doc_type;
				$this->save();
			}
	}
	
	
	/* Creates gzcompressed document in doc_storage database
	*/
	public function uploadDoc($file_array = null, $description = null, $parent_folder = null, $doc_type = null, $case_id = null)
	{
		if (isset($file_array['tmp_name']) && isset($file_array['name']) 
		&& file_exists($file_array['tmp_name']) && (!$parent_folder || $this->isFolder($parent_folder))
		&& !is_null($doc_type)) 
		{
			global $auth_row;
			$content = file_get_contents($file_array['tmp_name']);
			
			$this->doc_data = addslashes(gzcompress($content,9));
			$this->description = $description;
			$this->case_id = $case_id;
			$this->doc_name = $file_array['name'];
			if ($file_array['type'] != 'text/plain') {
				$this->mime_type = $file_array['type'];
			}
			$this->doc_type = $doc_type;
			$this->doc_size = $file_array['size'];
			$this->folder_ptr = $parent_folder;
			$this->user_id = $auth_row['user_id'];
			$this->created = date('Y-m-d');
			
			$extension = strrchr($this->doc_name, '.');
			$safe_full_path = escapeshellarg($file_array['tmp_name']);
			switch ($extension)
			{
				case '.pdf':
					exec("ps2ascii {$safe_full_path}", $string_array);
					//exec("pdftotext {$safe_full_path} -", $string_array);
					$contents_text = implode($string_array, "\n"); 
					
				break;
						
				case '.txt':
					exec("cat {$safe_full_path}", $string_array);
					$contents_text = implode($string_array, "\n");
				break;
			
				default:
					exec("strings {$safe_full_path}", $string_array);
					$contents_text = implode($string_array, "\n");
			
				break;
			}
			$this->doc_text = $contents_text;
			$this->save();
		
		}
		return true;
		
	}
	
	
	public function importCaseDoc ($file_name = null, $file_path = null, $description = null, $case_id = null, $user_id = null, $doc_text = null) {
		
		$full_path = $file_path . '/' . $file_name;
		
		if (file_exists($full_path) && $file_name && !is_dir($full_path) && is_numeric($case_id))	{
			
			$content = file_get_contents($full_path);
			$this->doc_data = addslashes(gzcompress($content,9));
			$this->description = $description;
			$this->case_id = $case_id;
			$this->doc_name = $file_name;
			$this->doc_type = 'C';
			$this->doc_size = filesize($full_path);
			$this->user_id = $user_id;
			$last_modified = filemtime($full_path);
			if($last_modified) {
				$this->created = date('Y-m-d', $last_modified);	
			} else {
				$this->created = date('Y-m-d');
			}
			
			$extension = strrchr($file_name, '.');
			
			// Determine mime type from list
			switch ($extension)
			{
				case '.pdf':
					$mime_type = 'application/pdf';
				break;		
				case '.rtf':
					$mime_type = 'application/rtf';
				break;
				case '.wpd':
					$mime_type = 'application/wpd';
				break;
				case '.doc':
				case '.docx':
					$mime_type = 'application/doc';
				break;
				case '.xls':
				case '.xlsx':
					$mime_type = 'application/xls';
				break;
				case '.anx':
					$mime_type = 'application/x-hotdocs-auto';
				break;
				case '.gif':
					$mime_type = 'image/gif';
				break;
				case '.jpg':
					$mime_type = 'image/jpg';
				break;
				case '.png':
					$mime_type = 'image/png';
				break;
				case '.tiff':
					$mime_type = 'image/tiff';
				break;
				case '.xfdf':
					$mime_type = 'application/vnd.adobe.xfdf';
				break;
				default:
					$mime_type = 'application/octet-stream'; // assume binary file
				break;
			}
			$this->mime_type = $mime_type;
			
			if(is_null($doc_text)) {
				$safe_full_path = escapeshellarg($full_path);
				switch ($extension)
				{
					case '.pdf':
						exec("ps2ascii {$safe_full_path}", $string_array);
						$contents_text = implode($string_array, "\n");
						/*
						exec("pdftotext {$safe_full_path}", $string_array);
						$contents_text = implode($string_array, "\n"); 
						*/
					break;		
					case '.txt':
						exec("cat {$safe_full_path}", $string_array);
						$contents_text = implode($string_array, "\n");
					break;
					default:
						exec("strings {$safe_full_path}", $string_array);
						$contents_text = implode($string_array, "\n");
					break;
				}
				$this->doc_text = $contents_text;
			} else {$this->doc_text = $doc_text;}
			
			$this->save();
			return $this->doc_id;
			
		} else { return false; }
		
	}
	
	
	public function isFolder($folder_ptr = null) {
		if (!is_null($folder_ptr) && is_numeric($folder_ptr)) {
			$folder_ptr = DB::escapeString($folder_ptr);
			$sql = "SELECT folder 
					FROM doc_storage 
					WHERE 1 
					AND folder = 1 
					AND doc_id = {$folder_ptr}
					LIMIT 1";
			$result = DB::query($sql) or trigger_error();
			
			if (DBResult::numRows($result) == 1) { return true;}
			else { return false; }
		}
		else { return false; }
	}
	
	public static function getFolderList($filter = array()) {
		
		$folder_array = array();
		
		if (isset($filter['doc_type']) && $filter['doc_type']) {
			$safe_doc_type = DB::escapeString($filter['doc_type']);
			$selection_sql = " AND doc_type = '{$safe_doc_type}' ";
		} else {
			return $folder_array;
		}
		
		if ($filter['doc_type'] == 'C' && isset($filter['case_id']) && is_numeric($filter['case_id'])) {
			$safe_case_id = DB::escapeString($filter['case_id']);
			$selection_sql = " AND case_id = '{$safe_case_id}'";
		}
		if ($filter['doc_type'] == 'R' && isset($filter['report_name']) && strlen($filter['report_name'])) {
			$safe_report_name = DB::escapeString($filter['report_name']);
			$selection_sql = " AND report_name = '{$safe_report_name}'";
		}
		
		$sql = "SELECT doc_id, doc_name,
						description, created,
						case_id, folder_ptr
						FROM doc_storage
						WHERE 1
						{$selection_sql} 
						AND folder = 1";
		//echo $sql;
		$result = DB::query($sql) or trigger_error('SQL: ' . $sql . ' Error: ' . DB::error());
		while ($row = DBResult::fetchRow($result)) {
			$folder_array[] = $row;
		}
		return $folder_array;
	}

	public static function format_bytes($size) {
    	$units = array(' B', ' KB', ' MB');
    	for ($i = 0; $size >= 1024 && $i < 2; $i++)
    	{
    		$size /= 1024;
    	}
    	return round($size, 2).$units[$i];
	}
	
}

?>