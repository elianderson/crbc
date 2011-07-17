<?php

/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: PDFReports.php 3617 2011-01-04 18:38:37Z vipsoft $
 * 
 * @category Piwik_Plugins
 * @package Piwik_PDFReports
 */

class Piwik_PDFReports extends Piwik_Plugin
{
	public function getInformation()
	{
		return array(
			'name' => 'Pdf Export Plugin',
			'description' => Piwik_Translate('PDFReports_PluginDescription'),
			'author' => 'Piwik',
			'author_homepage' => 'http://piwik.org/',
			'version' => Piwik_Version::VERSION,
		);
	}
	public function getListHooksRegistered()
	{
		return array( 
				'TopMenu.add' => 'addTopMenu',
				'TaskScheduler.getScheduledTasks' => 'getScheduledTasks',
				'AssetManager.getJsFiles' => 'getJsFiles',
		);
	}

	function getJsFiles( $notification )
	{
		$jsFiles = &$notification->getNotificationObject();
		$jsFiles[] = "plugins/PDFReports/templates/pdf.js";
	}
	
	function getScheduledTasks ( $notification )
	{
		// Reports have to be sent when the period ends for all websites
		$maxHourOffset = 0;
		$sites = Piwik_SitesManager_API::getInstance()->getSitesWithAtLeastViewAccess();
		$baseDate = Piwik_Date::factory("1971-01-01");
		foreach($sites as &$site)
		{
			$offsetDate = Piwik_Date::factory($baseDate->toString(),  $site['timezone']);

			// Earlier means a negative timezone
			if ( $offsetDate->isEarlier($baseDate) )
			{
				// Gets the timezone offset
				$hourOffset = (24 - date ('H', $offsetDate->getTimestamp()));

				if ( $hourOffset > $maxHourOffset )
				{
					$maxHourOffset = $hourOffset;
				}
			}
		}

		$tasks = &$notification->getNotificationObject();

		$dailySchedule = new Piwik_ScheduledTime_Daily();
		$dailySchedule->setHour($maxHourOffset);
		$tasks[] = new Piwik_ScheduledTask ( $this, 'dailySchedule', $dailySchedule );

		$weeklySchedule = new Piwik_ScheduledTime_Weekly();
		$weeklySchedule->setHour($maxHourOffset);
		$tasks[] = new Piwik_ScheduledTask ( $this, 'weeklySchedule', $weeklySchedule );

		$monthlySchedule = new Piwik_ScheduledTime_Monthly();
		$monthlySchedule->setHour($maxHourOffset);
		$tasks[] = new Piwik_ScheduledTask ( $this, 'monthlySchedule', $monthlySchedule );
	}
	
	function dailySchedule()
	{
		$this->generateAndSendScheduledReports('day');
	}
	
	function weeklySchedule()
	{
		$this->generateAndSendScheduledReports('week');
	}
	
	function monthlySchedule()
	{
		$this->generateAndSendScheduledReports('month');
	}
	
	function generateAndSendScheduledReports($period)
	{
		// Select all reports to generate
		$reportsToGenerate = Piwik_PDFReports_API::getInstance()->getReports($idSite = false, $period);
		
		// For each, generate the file and send the message with the attached report
		foreach($reportsToGenerate as $report)
		{
			Piwik_PDFReports_API::getInstance()->sendEmailReport(	$report['idreport'], 
																	$report['idsite']);
		}
	}
		
    function addTopMenu()
    {
    	Piwik_AddTopMenu( 'PDFReports_EmailReports', array('module' => 'PDFReports', 'action' => 'index'), true, 13);
    }
	
    function install()
	{
		$queries[] = "
                CREATE TABLE ".Piwik_Common::prefixTable('pdf')." (
					idreport INT(11) NOT NULL AUTO_INCREMENT,
					idsite INTEGER(11) NOT NULL,
					login VARCHAR(100) NOT NULL,
					description VARCHAR(255) NOT NULL,
					period VARCHAR(10) NULL,
					email_me TINYINT NULL,
					additional_emails TEXT NULL,
					reports TEXT NOT NULL,
					ts_created TIMESTAMP NULL,
					ts_last_sent TIMESTAMP NULL,
					deleted tinyint(4) NOT NULL default '0',
					PRIMARY KEY (idreport)
				) DEFAULT CHARSET=utf8";
        try {
        	foreach($queries as $query)
        	{
        		Piwik_Exec($query);
        	}
		}
		catch(Exception $e) {
    		if(!Zend_Registry::get('db')->isErrNo($e, '1050'))
			{
				throw $e;
			}
		}
	}
}
