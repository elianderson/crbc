<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: Controller.php 3558 2011-01-02 09:53:58Z matt $
 * 
 * @category Piwik_Plugins
 * @package Piwik_CoreAdminHome
 */

/**
 *
 * @package Piwik_CoreAdminHome
 */
class Piwik_CoreAdminHome_Controller extends Piwik_Controller
{
	public function index()
	{
		return $this->redirectToIndex('UsersManager', 'userSettings');
	}

	public function generalSettings()
	{
		Piwik::checkUserHasSomeAdminAccess();
		$view = Piwik_View::factory('generalSettings');
		
		if(Piwik::isUserIsSuperUser())
		{
    		$enableBrowserTriggerArchiving = Piwik_ArchiveProcessing::isBrowserTriggerArchivingEnabled();
    		$todayArchiveTimeToLive = Piwik_ArchiveProcessing::getTodayArchiveTimeToLive();
    		$showWarningCron = false;
    		if(!$enableBrowserTriggerArchiving
    			&& $todayArchiveTimeToLive < 3600)
    		{
    			$showWarningCron = true;
    		}
    		$view->showWarningCron = $showWarningCron;
    		$view->todayArchiveTimeToLive = $todayArchiveTimeToLive;
    		$view->enableBrowserTriggerArchiving = $enableBrowserTriggerArchiving;
    		
    	
    		if(!Zend_Registry::get('config')->isFileWritable())
    		{
    			$view->configFileNotWritable = true;
    		}
    		$view->mail = Zend_Registry::get('config')->mail->toArray();
		}
		
		$this->setBasicVariablesView($view);
		$view->topMenu = Piwik_GetTopMenu();
		$view->menu = Piwik_GetAdminMenu();
		echo $view->render();
	}
	
	public function setGeneralSettings()
	{
		Piwik::checkUserIsSuperUser();
		$response = new Piwik_API_ResponseBuilder(Piwik_Common::getRequestVar('format'));
		try {
    		$this->checkTokenInUrl();
    		$enableBrowserTriggerArchiving = Piwik_Common::getRequestVar('enableBrowserTriggerArchiving');
    		$todayArchiveTimeToLive = Piwik_Common::getRequestVar('todayArchiveTimeToLive');

    		Piwik_ArchiveProcessing::setBrowserTriggerArchiving((bool)$enableBrowserTriggerArchiving);
    		Piwik_ArchiveProcessing::setTodayArchiveTimeToLive($todayArchiveTimeToLive);
    		
    		// Update email settings
			$mail = Zend_Registry::get('config')->mail;
			$mail->transport = (Piwik_Common::getRequestVar('mailUseSmtp') == '1') ? 'smtp' : '';
			$mail->port = Piwik_Common::getRequestVar('mailPort', '');
			$mail->host = Piwik_Common::getRequestVar('mailHost', '');
			$mail->type = Piwik_Common::getRequestVar('mailType', '');
			$mail->username = Piwik_Common::getRequestVar('mailUsername', '');
			$mail->password = Piwik_Common::getRequestVar('mailPassword', '');
			$mail->encryption = Piwik_Common::getRequestVar('mailEncryption', '');
			Zend_Registry::get('config')->mail = $mail->toArray();
			
			$toReturn = $response->getResponse();
		} catch(Exception $e ) {
			$toReturn = $response->getResponseException( $e );
		}
		echo $toReturn;
	}
	
	/**
     * Shows the "Track Visits" checkbox.
     */
    public function optOut()
    {
		$trackVisits = !Piwik_Tracker_Cookie::isIgnoreCookieFound();

		$nonce = Piwik_Common::getRequestVar('nonce', false);
		if($nonce !== false && Piwik_Nonce::verifyNonce('Piwik_OptOut', $nonce))
		{
			Piwik_Tracker_Cookie::setIgnoreCookie();
			$trackVisits = !$trackVisits;
		}
 
		$view = Piwik_View::factory('optOut');
		$view->trackVisits = $trackVisits;
		$view->nonce = Piwik_Nonce::getNonce('Piwik_OptOut', 3600);
		echo $view->render();
	}
}