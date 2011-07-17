<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: API.php 3270 2010-10-28 18:21:55Z vipsoft $
 * 
 * @category Piwik_Plugins
 * @package Piwik_VisitTime
 */

/**
 *
 * @package Piwik_VisitTime
 */
class Piwik_VisitTime_API
{
	static private $instance = null;
	static public function getInstance()
	{
		if (self::$instance == null)
		{
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	protected function getDataTable($name, $idSite, $period, $date )
	{
		Piwik::checkUserHasViewAccess( $idSite );
		$archive = Piwik_Archive::build($idSite, $period, $date );
		$dataTable = $archive->getDataTable($name);
		$dataTable->filter('Sort', array('label', 'asc', true));
		$dataTable->queueFilter('ColumnCallbackReplace', array('label', 'Piwik_getTimeLabel'));
		$dataTable->queueFilter('ReplaceColumnNames');
		return $dataTable;
	}
	
	public function getVisitInformationPerLocalTime( $idSite, $period, $date )
	{
		return $this->getDataTable('VisitTime_localTime', $idSite, $period, $date );
	}
	
	public function getVisitInformationPerServerTime( $idSite, $period, $date )
	{
		return $this->getDataTable('VisitTime_serverTime', $idSite, $period, $date );
	}
}

function Piwik_getTimeLabel($label)
{
	return sprintf(Piwik_Translate('VisitTime_NHour'), $label);
}
