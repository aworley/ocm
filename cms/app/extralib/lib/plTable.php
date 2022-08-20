<?php
/* PlTable - display a sortable, filterable table of information

Input is an array listing the column labels, and a 2-d array of rows and
data.  If the data exceeds the max. list length, a pager is provided.
Options include row_offset, max. list length, colors, a base url for
all navigation links.

Sort and filter settings should be stored in session variables.

Integrating DB access by passing a SQL query to the object won't work because
it would bypass the abstraction layer.  And integration with pkCms DB access
would still require special coding for each particular case (some tables have
hyperlinked cells, etc.)
*/
class plTable
{
	var $cols;  // array
	var $rows = array();  // 2D array
	var $table_name;  // what exactly does this do?
	var $nav_url;
	var $summary = '';  // the table's HTML summary attribute
	
	var $sortable = FALSE;
	var $order_field;
	var $order = 'ASC';
	
	var $show_pager = TRUE;
	var $page_size = 20;  // in rows
	var $dataset_size = -1;  // in rows
	var $pager_offset = 0;  // row # (relative to entire dataset) of first row
	var $show_header = TRUE;
	
	var $col_bg = 'rowh';
	var $rowa_bg = 'row1';
	var $rowb_bg = 'row2';
	var $td_style = '';
	var $table_class = '';
	var $width = '100%';
	var $min_row_height = '';
	
	
	function __construct($table_name='default')
	{
		$this->table_name = $table_name;
	}
	
	function assignLabels($cols)
	{
		$this->cols = $cols;
	}
	
	function addRow($rows)
	{
		$this->rows[] = $rows;
	}
	
	function draw()
	{
		$class_str = '';
		$C = '';
		$style = '';
		$order_url = '';
		
		// If there are no rows provided, create an empty array to avoid errors
		if (!isset($this->rows[0]) || !is_array($this->rows[0]))
		{
			$this->rows[0] = array('');
		}
		
		// This grabs the the db column names from the data in $rows
		while (list($key, $val) = each($this->rows[0]))
		{
			$col_vals[] = $key;
		}
		
		if (!is_array($this->cols))
		{
			/*	if $this->cols hasn't been specified, try grabbing
			the field names from the first row's array keys
			*/
			$this->cols = $col_vals;
		}
		
		$col_count = count($this->cols);  // the number of columns
		
		if ($this->table_class)
		{
			$class_str = " class=\"{$this->table_class}\"";
		}
		
		$C .= "<table cellpadding=3 cellspacing=0 border=0 width=\"{$this->width}\" summary=\"{$this->summary}\"$class_str>\n";
		
		
		if ($this->show_header)
		{
			// Column headers
			$C .= "<tr>\n";
			
			for ($i = 0; $i < $col_count; $i++)
			{
				$C .= '<th nowrap>';
				
				if ($this->cols[$i] == '')  // this column has no label
				{
					$C .= '&nbsp;';
				}
				
				else if (!$this->sortable)  // this column/table is not sortable
				{
					// Draw the column label
					$C .= "{$this->cols[$i]}";
				}
				
				else if (strcmp($col_vals[$i], $this->order_field) == 0)
				// this table is sorted by this column
				{
					if ($this->order == 'ASC')
					{
						$opp_order = 'DESC';
					}
					
					else
					{
						$opp_order = 'ASC';
					}
					
					// Draw the column label
					$C .= "<a href=\"{$this->nav_url}order_field={$col_vals[$i]}&order=$opp_order\">";
					$C .= "{$this->cols[$i]}</a>";
					
					// Draw sort arrow
					if ($this->order == 'ASC')
					{
						$C .= '<img src="images/asc.gif" alt="a to z">';
					}
					
					else
					{
						$C .= '<img src="images/desc.gif" alt="z to a">';
					}
				}
				
				else
				// the table can be sorted by this column, but isn't
				{
					// Draw the column label
					$C .= "<a href=\"{$this->nav_url}order_field={$col_vals[$i]}\">";
					$C .= "{$this->cols[$i]}</a>";
				}
				
				$C .= "</th>\n";
			}
			
			$C .= "</tr>\n";
		}
		
		// main body of the table
		
		if ($this->td_style)
		{
			$style = " class='$this->td_style'";
		}
		
		if (!is_array($this->rows))
		{
			/* no data to show, just draw one big empty row and let the use know
			* that there's no data
			*/
			$C .= "<tr class=\"$this->rowa_bg\">\n";
			$C .= '<td colspan=' . count($this->cols) . '><p>&nbsp;&nbsp;<i>nothing to display</i></p></td>' . "\n";
			$C .= '</tr>';
			$C .= "\n";
		}
		
		else
		{
			// data rows
			reset($this->rows);
			$z = 0;
			
			while ($dummy = each($this->rows))
			{
				if($z % 2 == 0)
				{
					$C .= "<tr valign=\"top\" class=\"$this->rowa_bg\">";
				}
				
				else
				{
					$C .= "<tr valign=\"top\" class=\"$this->rowb_bg\">";
				}
				
				$C .= "\n";
				
				reset($this->rows[$z]);
				
				$q = 0;
				while (list($key, $val) = each($this->rows[$z]))
				{
					if ($q == 0 && $this->min_row_height)
					{
						$height = " height={$this->min_row_height}";
					}
					
					else
					{
						$height = '';
					}
					
					if ($val == '') // this test lets the value '0' through
					$C .= "<td$style$height>&nbsp;</td>\n";
					
					else
					$C .= "<td$style$height>$val</td>\n";
					
					$q++;
				}
				
				$C .= "</tr>\n";
				
				$z++;
			}
		}
		
		$C .= '</table>';
		$C .= "\n";
		
		
		// on-screen list navigation (the "pager")
		
		if (($this->dataset_size >= $this->page_size) && (TRUE == $this->show_pager))
		{
			// we're going to need to show a pager
			
			if ($this->sortable)
			{
				$order_url = "&order_field={$this->order_field}&order={$this->order}";
			}
			
			$C .= "<table width=\"100%\" cellspacing=0 summary='Pager'><tr>\n<td align=left width=100>";
			
			$current_page = (int) ($this->pager_offset / $this->page_size);
			$last_page = (int) (($this->dataset_size - 1) / $this->page_size);
			
			if ($current_page > 0)
			{
				$t = ($current_page - 1) * $this->page_size;
				$C .= "<a href={$this->nav_url}offset=$t$order_url>Previous {$this->page_size}</a>";
			}
			
			$C .= "</td>\n<td align=center>";
			
			if ($last_page < 10)
			{
				// don't have to worry about shortening the pager
				
				for($x = 0; $x <= $last_page; $x++)
				{
					$y = $x + 1;
					
					if ($x == $current_page)
					{
						$C .= '<b> ' . $y . ' </b>&nbsp;';
					}
					
					else
					{
						$w = $x * $this->page_size;
						$C .= "<a href={$this->nav_url}offset=$w$order_url> $y </a>&nbsp;";
					}
				}
			}
			
			// this code keeps the size of the pager at or under 10 pages, if needed
			
			// show (up to) the first 10 pages
			else if ($current_page < 8)
			{
				for($x = 0; $x < 10; $x++)
				{
					$y = $x + 1;
					
					if ($x == $current_page)
					{
						$C .= '<b> ' . $y . ' </b>&nbsp;';
					}
					
					else
					{
						$w = $x * $this->page_size;
						$C .= "<a href={$this->nav_url}offset=$w$order_url> $y </a>&nbsp;";
					}
				}
				
				$w = $last_page * $this->page_size;
				$y = $last_page + 1;
				$C .= " ... <a href={$this->nav_url}offset=$w$order_url> $y </a>&nbsp;";
			}
			
			// show the last 10 pages
			else if ($current_page > ($last_page - 11))
			{
				$C .= "<a href={$this->nav_url}offset=0> 1 </a> ... ";
				
				for($x = $last_page - 9; $x <= $last_page; $x++)
				{
					$y = $x + 1;
					
					if ($x == $current_page)
					{
						$C .= '<b> ' . $y . ' </b>&nbsp;';
					}
					
					else
					{
						$w = $x * $this->page_size;
						$C .= "<a href={$this->nav_url}offset=$w$order_url> $y </a>&nbsp;";
					}
				}
			}
			
			else // stuck in the middle...
			{
				$C .= "<a href={$this->nav_url}offset=0> 1 </a> ... ";
				
				for($x = $current_page; $x < $current_page + 10; $x++)
				{
					$y = $x + 1;
					
					if ($x == $current_page)
					{
						$C .= '<b> ' . $y . ' </b>&nbsp;';
					}
					
					else
					{
						$w = $x * $this->page_size;
						$C .= "<a href={$this->nav_url}offset=$w$order_url> $y </a>&nbsp;";
					}
				}
				
				$w = $last_page * $this->page_size;
				$y = $last_page + 1;
				$C .= " ... <a href={$this->nav_url}offset=$w> $y </a>&nbsp;";
			}
			
			
			$C .= "</td>\n<td align=right width=100>";
			
			if ($current_page < $last_page)
			{
				$t = ($current_page + 1) * $this->page_size;
				$C .= "<a href={$this->nav_url}offset=$t$order_url>Next {$this->page_size}</a>";
			}
			
			$C .= "</td>\n</tr></table>\n";
		}	
		
		// Always show the record count if the pager is enabled, even if no paging is done
		if (TRUE == $this->show_pager && $this->dataset_size >= 0)
		{			
			$C .= "<p align=center><b>{$this->dataset_size}</b> records
				 found</p>";			
		}
		// end of pager
		
		return $C;
	}
}
?>
