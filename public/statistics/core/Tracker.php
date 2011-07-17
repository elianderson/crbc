<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: Tracker.php 3565 2011-01-03 05:49:45Z matt $
 * 
 * @category Piwik
 * @package Piwik
 */

/**
 * Class used by the logging script piwik.php called by the javascript tag.
 * Handles the visitor & his/her actions on the website, saves the data in the DB, 
 * saves information in the cookie, etc.
 * 
 * We try to include as little files as possible (no dependency on 3rd party modules).
 * 
 * @package Piwik
 * @subpackage Piwik_Tracker
 */
class Piwik_Tracker
{	
	protected $stateValid = self::STATE_NOTHING_TO_NOTICE;
	
	/**
	 * @var Piwik_Tracker_Db
	 */
	protected static $db = null;
	
	const STATE_NOTHING_TO_NOTICE = 1;
	const STATE_LOGGING_DISABLE = 10;
	const STATE_EMPTY_REQUEST = 11;
	const STATE_NOSCRIPT_REQUEST = 13;
		
	const COOKIE_INDEX_IDVISITOR 				= 1;
	const COOKIE_INDEX_TIMESTAMP_LAST_ACTION 	= 2;
	const COOKIE_INDEX_TIMESTAMP_FIRST_ACTION 	= 3;
	const COOKIE_INDEX_ID_VISIT 				= 4;
	const COOKIE_INDEX_ID_LAST_ACTION 			= 5;
	const COOKIE_INDEX_REFERER_ID_VISIT			= 6;
	const COOKIE_INDEX_REFERER_TIMESTAMP		= 7;
	const COOKIE_INDEX_REFERER_TYPE				= 8;
	const COOKIE_INDEX_REFERER_NAME				= 9;
	const COOKIE_INDEX_REFERER_KEYWORD			= 10;
	const COOKIE_INDEX_VISITOR_RETURNING		= 11;
	
	static protected $forcedDateTime = null;
	static protected $forcedIpString = null;
	
	public function __construct($args = null)
	{
		$this->request = $args ? $args : $_GET + $_POST;
	}
	public static function setForceIp($ipString)
	{
		self::$forcedIpString = $ipString;
	}
	public static function setForceDateTime( $dateTime )
	{
		self::$forcedDateTime = $dateTime;
	}
	public function getCurrentTimestamp()
	{
		if(!is_null(self::$forcedDateTime))
		{
    		return strtotime(self::$forcedDateTime);
		}
		return time();
	}
	public function main()
	{
		$this->init();
		
		try {
			if( $this->isVisitValid() )
			{
				self::connectDatabase();
				
				$visit = $this->getNewVisitObject();
				$visit->setRequest($this->request);
				$visit->handle();
				unset($visit);
			}

			Piwik_Common::runScheduledTasks($now = $this->getCurrentTimestamp());
		} catch (Piwik_Tracker_Db_Exception $e) {
			printDebug($e->getMessage());
		} catch(Piwik_Tracker_Visit_Excluded $e) {
		} catch(Exception $e) {
			Piwik_Tracker_ExitWithException($e);
		}

		$this->end();
	}

	/**
	 * Returns the date in the "Y-m-d H:i:s" PHP format
	 * @return string
	 */
	public static function getDatetimeFromTimestamp($timestamp)
	{
		return date("Y-m-d H:i:s", $timestamp);
	}
	
	
	protected function init()
	{
		$this->loadTrackerPlugins();
		$this->handleDisabledTracker();
		$this->handleEmptyRequest();
	}

	protected function end()
	{
		switch($this->getState())
		{
			case self::STATE_LOGGING_DISABLE:
				printDebug("Logging disabled, display transparent logo");
				$this->outputTransparentGif();
			break;
			
			case self::STATE_EMPTY_REQUEST:
				printDebug("Empty request => Piwik page");
				echo "<a href='/'>Piwik</a> is a free open source <a href='http://piwik.org'>web analytics</a> alternative to Google analytics.";
			break;
			
			case self::STATE_NOSCRIPT_REQUEST:
			case self::STATE_NOTHING_TO_NOTICE:
			default:
				printDebug("Nothing to notice => default behaviour");
				$this->outputTransparentGif();
			break;
		}
		printDebug("End of the page.");
		
		if($GLOBALS['PIWIK_TRACKER_DEBUG'] === true)
		{
			if(isset(self::$db)) {
				self::$db->recordProfiling();
				Piwik::printSqlProfilingReportTracker(self::$db);
			}
		}
		
		self::disconnectDatabase();
	}

	/**
	 * Factory to create database objects
	 *
	 * @param array $configDb Database configuration
	 * @return Piwik_Tracker_Db_*
	 */
	public static function factory($configDb)
	{
		switch($configDb['adapter'])
		{
			case 'PDO_MYSQL':
				require_once PIWIK_INCLUDE_PATH .'/core/Tracker/Db/Pdo/Mysql.php';
				return new Piwik_Tracker_Db_Pdo_Mysql($configDb);

			case 'MYSQLI':
				require_once PIWIK_INCLUDE_PATH .'/core/Tracker/Db/Mysqli.php';
				return new Piwik_Tracker_Db_Mysqli($configDb);
		}

		throw new Exception('Unsupported database adapter '.$configDb['adapter']);
	}

	public static function connectPiwikTrackerDb()
	{
		$db = null;
		$configDb = Piwik_Tracker_Config::getInstance()->database;
		
		if(!isset($configDb['port']))
		{
			// before 0.2.4 there is no port specified in config file
			$configDb['port'] = '3306';  
		}

		$db = self::factory( $configDb );
		$db->connect();
		
		return $db;
	}
	
	public static function connectDatabase()
	{
		if( !is_null(self::$db))
		{
			return;
		}
		
		$db = null;
		Piwik_PostEvent('Tracker.createDatabase', $db);
		if(is_null($db))
		{
			$db = self::connectPiwikTrackerDb();
		}
		self::$db = $db;
	}
	
	/**
	 * @return Piwik_Tracker_Db
	 */
	public static function getDatabase()
	{
		return self::$db;
	}

	public static function disconnectDatabase()
	{
		if(isset(self::$db))
		{
			self::$db->disconnect();
			self::$db = null;
		}
	}

	/**
	 * Returns the Tracker_Visit object.
	 * This method can be overwritten to use a different Tracker_Visit object
	 *
	 * @return Piwik_Tracker_Visit
	 */
	protected function getNewVisitObject()
	{
		$visit = null;
		Piwik_PostEvent('Tracker.getNewVisitObject', $visit);
	
		if(is_null($visit))
		{
			$visit = new Piwik_Tracker_Visit( self::$forcedIpString, self::$forcedDateTime );
		}
		elseif(!($visit instanceof Piwik_Tracker_Visit_Interface ))
		{
			throw new Exception("The Visit object set in the plugin must implement Piwik_Tracker_Visit_Interface");
		}
		return $visit;
	}
	
	protected function outputTransparentGif()
	{
		if( !isset($GLOBALS['PIWIK_TRACKER_DEBUG']) || !$GLOBALS['PIWIK_TRACKER_DEBUG'] ) 
		{
			$trans_gif_64 = "R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";
			header("Content-Type: image/gif");
			print(base64_decode($trans_gif_64));
		}
	}
	
	protected function sendHeader($header)
	{
		header($header);
	}
	
	protected function isVisitValid()
	{
		return $this->stateValid !== self::STATE_LOGGING_DISABLE
				&&  $this->stateValid !== self::STATE_EMPTY_REQUEST;
	}
	
	protected function getState()
	{
		return $this->stateValid;
	}
	
	protected function setState( $value )
	{
		$this->stateValid = $value;
	}

	protected function loadTrackerPlugins()
	{
		try{
			$pluginsTracker = Piwik_Tracker_Config::getInstance()->Plugins_Tracker;
			if(is_array($pluginsTracker)
				&& count($pluginsTracker) != 0)
			{
				Piwik_PluginsManager::getInstance()->doNotLoadAlwaysActivatedPlugins();
				Piwik_PluginsManager::getInstance()->loadPlugins( $pluginsTracker['Plugins_Tracker'] );
				
				printDebug("Loading plugins: { ". implode(",", $pluginsTracker['Plugins_Tracker']) . "}");
			}
		} catch(Exception $e) {
			printDebug("ERROR: ".$e->getMessage());
		}
	}
	
	protected function handleEmptyRequest()
	{
		$countParameters = count($this->request);
		if($countParameters == 0) 
		{
			$this->setState(self::STATE_EMPTY_REQUEST);
		}
		if($countParameters == 1 )
		{
			$this->setState(self::STATE_NOSCRIPT_REQUEST);
		}
	}
	
	protected function handleDisabledTracker()
	{
		$saveStats = Piwik_Tracker_Config::getInstance()->Tracker['record_statistics'];
		if($saveStats == 0)
		{
			$this->setState(self::STATE_LOGGING_DISABLE);
		}
	}
}

if(!function_exists('printDebug')) 
{
    function printDebug( $info = '' )
    {
    	if(isset($GLOBALS['PIWIK_TRACKER_DEBUG']) && $GLOBALS['PIWIK_TRACKER_DEBUG'])
    	{
    		if(is_array($info))
    		{
    			print("<pre>");
    			print(var_export($info,true));
    			print("</pre>");
    		}
    		else
    		{
    			print($info . "<br />\n");
    		}
    	}
    }
}

/**
 * Displays exception in a friendly UI and exits.
 *
 * @param Exception $e
 */
function Piwik_Tracker_ExitWithException($e)
{
	header('Content-Type: text/html; charset=utf-8');
	if(isset($GLOBALS['PIWIK_TRACKER_DEBUG']) && $GLOBALS['PIWIK_TRACKER_DEBUG'])
	{
		$trailer = '<font color="#888888">Backtrace:<br /><pre>'.$e->getTraceAsString().'</pre></font>';
	}
	else
	{
		$trailer = '<p>Edit the following line in piwik.php to enable tracker debugging and display a backtrace:</p>
					<blockquote><pre>$GLOBALS[\'PIWIK_TRACKER_DEBUG\'] = true;</pre></blockquote>';
	}

	$headerPage = file_get_contents(PIWIK_INCLUDE_PATH . '/themes/default/simple_structure_header.tpl');
	$footerPage = file_get_contents(PIWIK_INCLUDE_PATH . '/themes/default/simple_structure_footer.tpl');
	$headerPage = str_replace('{$HTML_TITLE}', 'Piwik &rsaquo; Error', $headerPage);
	
	echo $headerPage . '<p>' . $e->getMessage() . '</p>' . $trailer . $footerPage;

	exit;
}
