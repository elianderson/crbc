<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: Null.php 2968 2010-08-20 15:26:33Z vipsoft $
 * 
 * @category Piwik
 * @package Piwik
 */

/**
 * Filter template.
 * You can use it if you want to create a new filter.
 * 
 * @package Piwik
 * @subpackage Piwik_DataTable
 */
class Piwik_DataTable_Filter_Null extends Piwik_DataTable_Filter
{
	
	public function __construct( $table )
	{
		parent::__construct($table);
		$this->filter();
	}
	
	protected function filter()
	{
		foreach($this->table->getRows() as $key => $row)
		{
		}
	}
}
