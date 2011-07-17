<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: Controller.php 3565 2011-01-03 05:49:45Z matt $
 *
 * @category Piwik_Plugins
 * @package Piwik_Live
 */

/**
 * @package Piwik_Live
 */
class Piwik_Live_Controller extends Piwik_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->minIdVisit = Piwik_Common::getRequestVar('minIdVisit', 0, 'int');
	}

	function index()
	{
		$this->widget(true);
	}

	public function widget($fetch = false)
	{
		$view = Piwik_View::factory('index');
		$view->idSite = $this->idSite;
		$view->visitorsCountHalfHour = $this->getUsersInLastXMin(30);
		$view->visitorsCountToday = $this->getUsersInLastXDays(1);
		$view->pisHalfhour = $this->getPageImpressionsInLastXMin(30);
		$view->pisToday = $this->getPageImpressionsInLastXDays(1);
		$view->visitors = $this->getLastVisitsStart($fetch = true);

		echo $view->render();
	}

	public function getVisitorLog($fetch = false)
	{
		$limit = 20;
		$_GET['limit'] = $limit;
		$view = Piwik_ViewDataTable::factory();
		$view->init( $this->pluginName,
							__FUNCTION__,
						'Live.getLastVisitsDetails'
						);

		// All colomns in DB which could be shown
		//'ip', 'idVisit', 'countActions', 'isVisitorReturning', 'country', 'countryFlag', 'continent', 'provider', 'providerUrl', 'idSite',
		//'serverDate', 'visitLength', 'visitLengthPretty', 'firstActionTimestamp', 'lastActionTimestamp', 'refererType', 'refererName',
		//'keywords', 'refererUrl', 'searchEngineUrl', 'searchEngineIcon', 'operatingSystem', 'operatingSystemShortName', 'operatingSystemIcon',
		//'browserFamily', 'browserFamilyDescription', 'browser', 'browserIcon', 'screen', 'resolution', 'screenIcon', 'plugins', 'lastActionDateTime',
		//'serverDatePretty', 'serverTimePretty', 'actionDetails'
		$view->disableGenericFilters();
		$view->disableSort();
		$view->setLimit($limit);
		$view->setTemplate("Live/templates/visitorLog.tpl");
		$view->setSortedColumn('idVisit', 'ASC');
		$view->disableSearchBox();
		$view->disableOffsetInformation();
		// "Include low population" link won't be displayed under this table
		$view->disableExcludeLowPopulation();
		// disable the tag cloud,  pie charts, bar chart icons
		$view->disableShowAllViewsIcons();
		// disable the button "show more datas"
		$view->disableShowAllColumns();
		// disable the RSS feed
		$view->disableShowExportAsRssFeed();
		return $this->renderView($view, $fetch);
	}

	public function getLastVisitsStart($fetch = false)
	{
		$view = Piwik_View::factory('lastVisits');
		$view->idSite = $this->idSite;

		$view->visitors = $this->getLastVisits(10);

		$rendered = $view->render($fetch);

		if($fetch)
		{
			return $rendered;
		}
		echo $rendered;
	}

	public function getLastVisits($limit = 10)
	{
		$api = new Piwik_API_Request("method=Live.getLastVisits&idSite=$this->idSite&limit=$limit&format=php&serialize=0&disable_generic_filters=1");
		$visitors = $api->process();

		return $visitors;
	}

	public function getUsersInLastXMin($minutes = 30) {
		$api = new Piwik_API_Request("method=Live.getUsersInLastXMin&idSite=".$this->idSite."&minutes=".$minutes."&format=php&serialize=0&disable_generic_filters=1");
		$visitors_halfhour = $api->process();

		return count($visitors_halfhour);
	}

	public function getUsersInLastXDays($days = 1) {
		$api = new Piwik_API_Request("method=Live.getUsersInLastXDays&idSite=$this->idSite&days=$days&format=php&serialize=0&disable_generic_filters=1");
		$visitors_today = $api->process();

		return count($visitors_today);
	}

	public function getPageImpressionsInLastXMin($minutes = 30) {
		$api = new Piwik_API_Request("method=Live.getPageImpressionsInLastXMin&idSite=$this->idSite&minutes=$minutes&format=php&serialize=0&disable_generic_filters=1");
		$pis_halfhour = $api->process();

		return count($pis_halfhour);
	}

	public function getPageImpressionsInLastXDays($days = 1) {
		$api = new Piwik_API_Request("method=Live.getPageImpressionsInLastXDays&idSite=$this->idSite&days=$days&format=php&serialize=0&disable_generic_filters=1");
		$pis_today = $api->process();

		return count($pis_today);
	}

	public function ajaxTotalVisitors($fetch = false)
	{
		$view = Piwik_View::factory('totalVisits');
		$view->idSite = $this->idSite;
		$view->visitorsCountHalfHour = $this->getUsersInLastXMin(30);
		$view->visitorsCountToday = $this->getUsersInLastXDays(1);
		$view->pisHalfhour = $this->getPageImpressionsInLastXMin(30);
		$view->pisToday = $this->getPageImpressionsInLastXDays(1);

		$rendered = $view->render($fetch);

		if($fetch)
		{
			return $rendered;
		}
		echo $rendered;
	}
}
