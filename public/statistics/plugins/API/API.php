<?php

/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: API.php 3578 2011-01-03 12:59:24Z matt $
 * 
 * @category Piwik_Plugins
 * @package Piwik_API
 */

/**
 * 
 * @package Piwik_API
 */
class Piwik_API extends Piwik_Plugin {

	public function getInformation() {
		return array(
			'description' => Piwik_Translate('API_PluginDescription'),
			'author' => 'Piwik',
			'author_homepage' => 'http://piwik.org/',
			'version' => Piwik_Version::VERSION,
		);
	}
	
	public function getListHooksRegistered() {
		return array(
			'AssetManager.getCssFiles' => 'getCssFiles',
			'TopMenu.add' => 'addTopMenu',
		);
	}
	
	public function addTopMenu() {
		Piwik_AddTopMenu('General_API', array('module' => 'API', 'action' => 'listAllAPI'), true, 7);
	}

	public function getCssFiles($notification) {
		$cssFiles = &$notification->getNotificationObject();
		
		$cssFiles[] = "plugins/API/css/styles.css";
	}
}


/**
 * 
 * @package Piwik_API
 */
class Piwik_API_API 
{
	static private $instance = null;

	/**
	 * @return Piwik_API_API
	 */
	static public function getInstance()
	{
		if (self::$instance == null)
		{
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function getDefaultMetrics() 
	{
		$translations = array(
			// Standard metrics
    		'nb_visits' => 'General_ColumnNbVisits',
    		'nb_uniq_visitors' => 'General_ColumnNbUniqVisitors',
    		'nb_actions' => 'General_ColumnNbActions',
// Do not display these in reports, as they are not so relevant
// They are used to process metrics below
//			'nb_visits_converted' => 'General_ColumnVisitsWithConversions',
//    		'max_actions' => 'General_ColumnMaxActions',
//    		'sum_visit_length' => 'General_ColumnSumVisitLength',
//			'bounce_count'
		);
		$translations = array_map('Piwik_Translate', $translations);
		return $translations;
	}

	public function getDefaultProcessedMetrics()
	{
		$translations = array(
			// Processed in AddColumnsProcessedMetrics
			'nb_actions_per_visit' => 'General_ColumnActionsPerVisit',
    		'avg_time_on_site' => 'General_ColumnAvgTimeOnSite',
    		'bounce_rate' => 'General_ColumnBounceRate',
    		'conversion_rate' => 'General_ColumnConversionRate',
		);
		return array_map('Piwik_Translate', $translations);
	}
	
    /*
     * Loads reports metadata, then return the requested one, 
     * matching optional API parameters.
     */
	public function getMetadata($idSite, $apiModule, $apiAction, $apiParameters = array(), $language = false)
    {
    	Piwik_Translate::getInstance()->reloadLanguage($language);
    	static $reportsMetadata = array();
    	$cacheKey = $idSite.$language;
    	if(!isset($reportsMetadata[$cacheKey]))
    	{
    		$reportsMetadata[$cacheKey] = $this->getReportMetadata($idSite);
    	}
    	
    	foreach($reportsMetadata[$cacheKey] as $report)
    	{
    		if($report['module'] == $apiModule
    			&& $report['action'] == $apiAction)
			{
				if(empty($apiParameters))
				{
        			return array($report);
				}
				if(empty($report['parameters']))
				{
					continue;
				}
				$diff = array_diff($report['parameters'], $apiParameters);
				if(empty($diff))
				{
					return array($report);
				}
			}
    	}
    	return false;
    }
    
	/**
	 * Triggers a hook to ask plugins for available Reports.
	 * Returns metadata information about each report (category, name, dimension, metrics, etc.) 
	 *
	 * @param string $idSites Comma separated list of website Ids
	 * @return array
	 */
	public function getReportMetadata($idSites = array()) 
	{
		if (!is_array($idSites)) 
		{ 
            $idSites = array($idSites); 
		}
		 
		$idSites = Piwik_Site::getIdSitesFromIdSitesString($idSites);
		
		$availableReports = array();
		Piwik_PostEvent('API.getReportMetadata', $availableReports, $idSites);
		foreach ($availableReports as &$availableReport) {
			if (!isset($availableReport['metrics'])) {
				$availableReport['metrics'] = $this->getDefaultMetrics();
			}
			if (!isset($availableReport['processedMetrics'])) {
				$availableReport['processedMetrics'] = $this->getDefaultProcessedMetrics();
			}
		}
		
		// Some plugins need to add custom metrics after all plugins hooked in
		Piwik_PostEvent('API.getReportMetadata.end', $availableReports, $idSites);
		
		$knownMetrics = array_merge( $this->getDefaultMetrics(), $this->getDefaultProcessedMetrics() );
		foreach($availableReports as &$availableReport)
		{
			// Ensure all metrics have a translation
			$metrics = $availableReport['metrics'];
			$cleanedMetrics = array();
			foreach($metrics as $metricId => $metricTranslation)
			{
				// When simply the column name was given, ie 'metric' => array( 'nb_visits' )
				// $metricTranslation is in this case nb_visits. We look for a known translation.
				if(is_numeric($metricId)
					&& isset($knownMetrics[$metricTranslation]))
				{
					$metricId = $metricTranslation;
					$metricTranslation = $knownMetrics[$metricTranslation];
				}
				$cleanedMetrics[$metricId] = $metricTranslation;
			}
			$availableReport['metrics'] = $cleanedMetrics;
			
			// Remove array elements that are false (to clean up API output)
			foreach($availableReport as $attributeName => $attributeValue)
			{
				if(empty($attributeValue))
				{
					unset($availableReport[$attributeName]);
				}
			}
			
			// Processing a uniqueId for each report, 
			// can be used by UIs as a key to match a given report
			$uniqueId = $availableReport['module'] . '_' . $availableReport['action'];
			if(!empty($availableReport['parameters']))
			{
				foreach($availableReport['parameters'] as $key => $value)
				{
					$uniqueId .= '_' . $key . '--' . $value;
				}
			}
			$availableReport['uniqueId'] = $uniqueId;
		}
		// Sort results to ensure consistent order
		usort($availableReports, array($this, 'sort'));
		return $availableReports;
	}

	public function getProcessedReport($idSite, $date, $period, $apiModule, $apiAction, $apiParameters = false, $language = false)
    {
    	if($apiParameters === false)
    	{
    		$apiParameters = array();
    	}
        // Is this report found in the Metadata available reports?
        $reportMetadata = $this->getMetadata($idSite, $apiModule, $apiAction, $apiParameters, $language);
        if(empty($reportMetadata))
        {
        	throw new Exception("Requested report $apiModule.$apiAction for Website id=$idSite not found in the list of available reports. \n");
        }
        $reportMetadata = reset($reportMetadata);
        
		// Generate Api call URL passing custom parameters
		$parameters = array_merge( $apiParameters, array(
			'method' => $apiModule.'.'.$apiAction,
			'idSite' => $idSite,
			'period' => $period,
			'date' => $date,
			'format' => 'original',
			'serialize' => '0',
			'language' => $language,
		));
		$url = Piwik_Url::getQueryStringFromParameters($parameters);
        $request = new Piwik_API_Request($url);
        try {
        	/** @var Piwik_DataTable */
        	$dataTable = $request->process();
        } catch(Exception $e) {
        	throw new Exception("API returned an error: ".$e->getMessage()."\n");
        }
        // Table with a Dimension (Keywords, Pages, Browsers, etc.)
        if(isset($reportMetadata['dimension']))
        {
        	$callback = 'handleTableReport';
        }
        // Table without a dimension, simple list of general metrics (eg. VisitsSummary.get)
        else
        {
        	$callback = 'handleTableSimple';
        }
    	list($newReport, $columns, $rowsMetadata) = $this->$callback($idSite, $period, $dataTable, $reportMetadata);
    	foreach($columns as $columnId => &$name)
    	{
    		$name = ucfirst($name);
    	}
    	$website = new Piwik_Site($idSite);
    	return array(
				'website' => $website->getName(),
				'prettyDate' => Piwik_Period::factory($period, Piwik_Date::factory($date))->getLocalizedLongString(),
				'metadata' => $reportMetadata, 
				'columns' => $columns, 
				'reportData' =>	$newReport, 
				'reportMetadata' => $rowsMetadata,
		);
    }
    
    private function handleTableSimple($idSite, $period, $dataTable, $reportMetadata)
    {
        $renderer = new Piwik_DataTable_Renderer_Php();
        $renderer->setTable($dataTable);
        $renderer->setSerialize(false);
        $reportTable = $renderer->render();

        $newReport = array();
        foreach($reportTable as $metric => $value)
        {
        	// Use translated metric from metadata
        	// If translation not found, do not display the returned data
        	if(isset($reportMetadata['metrics'][$metric]))
        	{
        		$value = Piwik::getPrettyValue($idSite, $metric, $value, $htmlAllowed = false, $timeAsSentence = false);
    		
        		$metric = $reportMetadata['metrics'][$metric];
            	$newReport[] = array(
            		'label' => $metric,
            		'value' => $value
            	);
        	}
        }
        
        $columns = array(
        	'label' => Piwik_Translate('General_Name'),
        	'value' => Piwik_Translate('General_Value'),
        );
    	return array(
    		$newReport, 
    		$columns,
    		$rowsMetadata = array()
    	);
    }
    
    private function handleTableReport($idSite, $period, $dataTable, &$reportMetadata)
    {
    	// displayed columns
    	$columns = array_merge(
    		array('label' => $reportMetadata['dimension'] ),
    		$reportMetadata['metrics']
    	);
    	
		// See ArchiveProcessing/Period.php - unique visitors are not processed for year period
    	if($period == 'year')
    	{
    		unset($columns['nb_uniq_visitors']);
    		unset($reportMetadata['metrics']['nb_uniq_visitors']);
    	}
    	
        if(isset($reportMetadata['processedMetrics']))
        {
        	$processedMetricsAdded = $this->getDefaultProcessedMetrics();
        	foreach($processedMetricsAdded as $processedMetricId => $processedMetricTranslation)
        	{
        		// this processed metric can be displayed for this report
        		if(isset($reportMetadata['processedMetrics'][$processedMetricId]))
        		{
        			$columns[$processedMetricId] = $processedMetricTranslation;
        		}
        	}
        }
    	// Display the global Goal metrics 
        if(isset($reportMetadata['metricsGoal']))
        {
        	$metricsGoalDisplay = array('conversion_rate', 'revenue');
        	
    		// Add processed metrics to be displayed for this report
        	foreach($metricsGoalDisplay as $goalMetricId)
        	{
        		if(isset($reportMetadata['metricsGoal'][$goalMetricId]))
        		{
        			$columns[$goalMetricId] = $reportMetadata['metricsGoal'][$goalMetricId];
        		}
        	}
        }
        if(isset($reportMetadata['metricsGoal']))
        {
        	// To process conversion_rate, we need to apply the Goal processed filter
        	// only requesting to process the basic metrics
        	// This adds goal metrics as well as standard metrics
        	$dataTable->filter('AddColumnsProcessedMetricsGoal', array($enable=true, Piwik_DataTable_Filter_AddColumnsProcessedMetricsGoal::GOALS_MINIMAL_REPORT));
        }
        elseif(isset($reportMetadata['processedMetrics']))
        {
        	// Add processed metrics
        	$dataTable->filter('AddColumnsProcessedMetrics');
        }
        $renderer = new Piwik_DataTable_Renderer_Php();
        $renderer->setTable($dataTable);
        $renderer->setSerialize(false);
        $reportTable = $renderer->render();
    	$rowsMetadata = array();
    	
    	$newReport = array();
    	foreach($reportTable as $rowId => $row)
    	{
    		// ensure all displayed columns have 0 values
    		foreach($columns as $id => $name)
    		{
    			if(!isset($row[$id]))
    			{
    				$row[$id] = 0;
    			}
    		}
    		$newRow = array();
    		foreach($row as $columnId => $value)
    		{
    			// Keep displayed columns
    			if(isset($columns[$columnId]))
    			{
        			$newRow[$columnId] = Piwik::getPrettyValue($idSite, $columnId, $value, $htmlAllowed = false, $timeAsSentence = false);
    			}
        		// We try and only keep metadata 
        		// - if the column value is not an array (eg. metrics per goal)
        		// - if the column name doesn't contain _ (which is by standard, a metric column)
    			elseif(!is_array($value)
    				&& strpos($columnId, '_') === false
    				)
    			{
    				$rowsMetadata[$rowId][$columnId] = $value;
    			}
    		}
    		$newReport[] = $newRow;
    	}
    	return array(
    		$newReport, 
    		$columns, 
    		$rowsMetadata
    	);
    }

	/**
	 * API metadata are sorted by category/name
	 * @param $a
	 * @param $b
	 * @return int
	 */
	private function sort($a, $b)
	{
		return ($category = strcmp($a['category'], $b['category'])) != 0 	
				? $category
				: strcmp($a['action'], $b['action']);
	}
}
