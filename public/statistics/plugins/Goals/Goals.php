<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: Goals.php 3565 2011-01-03 05:49:45Z matt $
 * 
 * @category Piwik_Plugins
 * @package Piwik_Goals
 */

/**
 *
 * @package Piwik_Goals
 */
class Piwik_Goals extends Piwik_Plugin
{	
	const ROUNDING_PRECISION = 2;
	
	public function getInformation()
	{
		$info = array(
			'description' => Piwik_Translate('Goals_PluginDescription'),
			'author' => 'Piwik',
			'author_homepage' => 'http://piwik.org/',
			'version' => Piwik_Version::VERSION,
			'TrackerPlugin' => true, // this plugin must be loaded during the stats logging
		);
		return $info;
	}
	
	function getListHooksRegistered()
	{
		$hooks = array(
			'AssetManager.getJsFiles' => 'getJsFiles',
			'AssetManager.getCssFiles' => 'getCssFiles',
			'Common.fetchWebsiteAttributes' => 'fetchGoalsFromDb',
			'ArchiveProcessing_Day.compute' => 'archiveDay',
			'ArchiveProcessing_Period.compute' => 'archivePeriod',
			'API.getReportMetadata.end' => 'getReportMetadata',
			'WidgetsList.add' => 'addWidgets',
			'Menu.add' => 'addMenus',
		);
		return $hooks;
	}

	/**
	 * Returns the Metadata for the Goals plugin API.
	 * The API returns general Goal metrics: conv, conv rate and revenue globally 
	 * and for each goal.
	 * 
	 * Also, this will update metadata of all other reports that have Goal segmentatation.
	 */
	public function getReportMetadata($notification) 
	{
		$idSites = $notification->getNotificationInfo();
		$reports = &$notification->getNotificationObject();
	
		// Processed in AddColumnsProcessedMetricsGoal
		// These metrics will also be available for some reports, for each goal
		// Example: Conversion rate for Goal 2 for the keyword 'piwik' 
		$goalProcessedMetrics = array(
    		'revenue_per_visit' => Piwik_Translate('General_ColumnValuePerVisit'),
    	);
		
		$goalMetrics = array(
			'nb_conversions' => Piwik_Translate('Goals_ColumnConversions'), 
			'conversion_rate' => Piwik_Translate('General_ColumnConversionRate'), 
			'revenue' => Piwik_Translate('Goals_ColumnRevenue')
		);

		// General Goal metrics: conversions, conv rate, revenue
		$reports[] = array(
			'category' => Piwik_Translate('Goals_Goals'),
			'name' => Piwik_Translate('Goals_Goals'),
			'module' => 'Goals',
			'action' => 'get',
			'metrics' => $goalMetrics,
			'processedMetrics' => array(),
		);
		
		/* 
		 * Add the metricsGoal and processedMetricsGoal entry
		 * to all reports that have Goal segmentation
		 */
		$reportsWithGoals = array();
		Piwik_PostEvent('Goals.getReportsWithGoalMetrics', $reportsWithGoals);
		foreach($reportsWithGoals as $reportWithGoals)
		{
			// Select this report from the API metadata array
			// and add the Goal metrics to it
			foreach($reports as &$apiReportToUpdate)
			{
				if($apiReportToUpdate['module'] == $reportWithGoals['module']
					&& $apiReportToUpdate['action'] == $reportWithGoals['action'])
				{
					$apiReportToUpdate['metricsGoal'] = $goalMetrics;
					$apiReportToUpdate['processedMetricsGoal'] = $goalProcessedMetrics;
					break;
				}
			}
		}
		
		// If only one website is selected, we add the Goal metrics
		if(count($idSites) == 1)
		{
			$goals = Piwik_Goals_API::getInstance()->getGoals(reset($idSites));
			foreach($goals as $goal) 
			{
				// Add the general Goal metrics: ie. total Goal conversions, 
				// Goal conv rate or Goal total revenue.
				// This API call requires a custom parameter
				$reports[] = array(
					'category' => Piwik_Translate('Goals_Goals'),
					'name' => Piwik_Translate('Goals_GoalX', $goal['name']),
					'module' => 'Goals',
					'action' => 'get',
					'parameters' => array('idGoal' => $goal['idgoal']),
					'metrics' => $goalMetrics,
					'processedMetrics' => false,
				);
			}
		}
	}
	
	static public function getReportsWithGoalMetrics()
	{
		$segments = array();
		Piwik_PostEvent('Goals.getReportsWithGoalMetrics', $segments);
		$segmentsByGroup = array();
		foreach($segments as $segment)
		{
			$group = $segment['category'];
			unset($segment['category']);
			$segmentsByGroup[$group][] = $segment;
		}
		return $segmentsByGroup;
	}
	
	function getJsFiles( $notification )
	{
		$jsFiles = &$notification->getNotificationObject();
		$jsFiles[] = "plugins/Goals/templates/GoalForm.js";
		$jsFiles[] = "plugins/CoreHome/templates/sparkline.js";
	}

	function getCssFiles( $notification )
	{
		$cssFiles = &$notification->getNotificationObject();
		$cssFiles[] = "plugins/Goals/templates/goals.css";
	}	
	
	function fetchGoalsFromDb($notification)
	{
		$idsite = $notification->getNotificationInfo();
		
		// add the 'goal' entry in the website array
		$array =& $notification->getNotificationObject();
		$array['goals'] = Piwik_Goals_API::getInstance()->getGoals($idsite);
	}
	
	function addWidgets()
	{
		Piwik_AddWidget('Goals_Goals', 'Goals_GoalsOverview', 'Goals', 'widgetGoalsOverview');
		$goals = Piwik_Tracker_GoalManager::getGoalDefinitions(Piwik_Common::getRequestVar('idSite', null, 'int'));
		if(count($goals) > 0)
		{
			foreach($goals as $goal) 
			{
        		Piwik_AddWidget('Goals_Goals', $goal['name'], 'Goals', 'widgetGoalReport', array('idGoal' => $goal['idgoal']));
			}
		}
	}
	
	function addMenus()
	{
		$goals = Piwik_Tracker_GoalManager::getGoalDefinitions(Piwik_Common::getRequestVar('idSite', null, 'int'));
		if(count($goals)==0)
		{
			Piwik_AddMenu('Goals_Goals', '', array('module' => 'Goals', 'action' => 'addNewGoal'), true, 25);
			Piwik_AddMenu('Goals_Goals', 'Goals_AddNewGoal', array('module' => 'Goals', 'action' => 'addNewGoal'));
		}
		else
		{
			Piwik_AddMenu('Goals_Goals', '', array('module' => 'Goals', 'action' => 'index'), true, 25);
			Piwik_AddMenu('Goals_Goals', 'Goals_Overview', array('module' => 'Goals', 'action' => 'index'), true, 1);
			foreach($goals as $goal) 
			{
				Piwik_AddMenu('Goals_Goals', str_replace('%', '%%', $goal['name']), array('module' => 'Goals', 'action' => 'goalReport', 'idGoal' => $goal['idgoal']));
			}
		}
	}
	
	/**
	 * @param string $recordName 'nb_conversions'
	 * @param int $idGoal idGoal to return the metrics for, or false to return overall 
	 * @param int $visitorReturning 0 for new visitors, 1 for returning visitors, false for all
	 * @return unknown
	 */
	static public function getRecordName($recordName, $idGoal = false, $visitorReturning = false)
	{
		$idGoalStr = $returningStr = '';
		if(!empty($idGoal))
		{
			$idGoalStr = $idGoal . "_";
		}
		if($visitorReturning !== false)
		{
			$returningStr = 'visitor_returning_' . $visitorReturning . '_';
		}
		return 'Goal_' . $returningStr . $idGoalStr . $recordName;
	}
	
	/**
	 * Hooks on Period archiving. 
	 * Sums up Goal conversions stats, and processes overall conversion rate
	 * 
	 * @param $notification
	 * @return void
	 */
	function archivePeriod($notification )
	{
		$archiveProcessing = $notification->getNotificationObject();
		
		$metricsToSum = array( 'nb_conversions', 'revenue');
		$goalIdsToSum = Piwik_Tracker_GoalManager::getGoalIds($archiveProcessing->idsite);
		
		$fieldsToSum = array();
		foreach($metricsToSum as $metricName)
		{
			foreach($goalIdsToSum as $goalId)
			{
				$fieldsToSum[] = self::getRecordName($metricName, $goalId);
				$fieldsToSum[] = self::getRecordName($metricName, $goalId, 0);
				$fieldsToSum[] = self::getRecordName($metricName, $goalId, 1);
			}
			$fieldsToSum[] = self::getRecordName($metricName);
		}
		$records = $archiveProcessing->archiveNumericValuesSum($fieldsToSum);
		
		// also recording conversion_rate for each goal
		foreach($goalIdsToSum as $goalId)
		{
			$nb_conversions = $records[self::getRecordName('nb_conversions', $goalId)]->value;
			$conversion_rate = $this->getConversionRate($nb_conversions, $archiveProcessing);
			$archiveProcessing->insertNumericRecord(self::getRecordName('conversion_rate', $goalId), $conversion_rate);
		}
		
		// global conversion rate
		$nb_conversions = $records[self::getRecordName('nb_conversions')]->value;
		$conversion_rate = $this->getConversionRate($nb_conversions, $archiveProcessing);
		$archiveProcessing->insertNumericRecord(self::getRecordName('conversion_rate'), $conversion_rate);
	}
	
	/**
	 * Hooks on the Daily archiving.
	 * Will process Goal stats overall and for each Goal. 
	 * Also processes the New VS Returning visitors conversion stats.
	 * @param $notification
	 * @return void
	 */
	function archiveDay( $notification )
	{
		/**
		 * @var Piwik_ArchiveProcessing_Day 
		 */
		$archiveProcessing = $notification->getNotificationObject();
		
		// by processing visitor_returning segment, we can also simply sum and get stats for all goals.
		$query = $archiveProcessing->queryConversionsBySegment('visitor_returning');

		$nb_conversions = $revenue = 0;
		$goals = $goalsByVisitorReturning = array();
		while($row = $query->fetch() )
		{
			$goalsByVisitorReturning[$row['idgoal']][$row['visitor_returning']] = $archiveProcessing->getGoalRowFromQueryRow($row);
			
			if(!isset($goals[$row['idgoal']])) $goals[$row['idgoal']] = $archiveProcessing->getNewGoalRow();
			$archiveProcessing->updateGoalStats($row, $goals[$row['idgoal']]);

			$revenue += $row['revenue'];
			$nb_conversions += $row['nb_conversions'];
		}
		
		// Stats by goal, for all visitors
		foreach($goals as $idgoal => $values)
		{
			foreach($values as $metricId => $value)
			{
				$metricName = Piwik_Archive::$mappingFromIdToNameGoal[$metricId];
				$recordName = self::getRecordName($metricName, $idgoal);
				$archiveProcessing->insertNumericRecord($recordName, $value);
			}
			$conversion_rate = $this->getConversionRate($values[Piwik_Archive::INDEX_GOAL_NB_CONVERSIONS], $archiveProcessing);
			$recordName = self::getRecordName('conversion_rate', $idgoal);
			$archiveProcessing->insertNumericRecord($recordName, $conversion_rate);
		}
		
		// Stats by goal, for visitor returning / non returning
		foreach($goalsByVisitorReturning as $idgoal => $values)
		{
			foreach($values as $visitor_returning => $goalValues)
			{
				foreach($goalValues as $metricId => $value)
				{
					$metricName = Piwik_Archive::$mappingFromIdToNameGoal[$metricId];
					$recordName = self::getRecordName($metricName, $idgoal, $visitor_returning);
					$archiveProcessing->insertNumericRecord($recordName, $value);
//					echo $record . "<br />";
				}
			}
		}
	
		// Stats for all goals
		$totalAllGoals = array(
			self::getRecordName('conversion_rate')	=> $this->getConversionRate($archiveProcessing->getNumberOfVisitsConverted(), $archiveProcessing),
			self::getRecordName('nb_conversions')	=> $nb_conversions,
			self::getRecordName('revenue') 			=> $revenue,
		);
		foreach($totalAllGoals as $recordName => $value)
		{
			$archiveProcessing->insertNumericRecord($recordName, $value);
		}
	}
	
	function getConversionRate($count, $archiveProcessing)
	{
		return round(100 * $count / $archiveProcessing->getNumberOfVisits(), self::ROUNDING_PRECISION);
	}

}
