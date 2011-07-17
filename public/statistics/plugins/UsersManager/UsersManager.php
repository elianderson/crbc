<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: UsersManager.php 2968 2010-08-20 15:26:33Z vipsoft $
 * 
 * @category Piwik_Plugins
 * @package Piwik_UsersManager
 */

/**
 * 
 * @package Piwik_UsersManager
 */
class Piwik_UsersManager extends Piwik_Plugin
{		
	public function getInformation()
	{
		$info = array(
			'description' => Piwik_Translate('UsersManager_PluginDescription'),
			'author' => 'Piwik',
			'author_homepage' => 'http://piwik.org/',
			'version' => Piwik_Version::VERSION,
		);
		
		return $info;
	}
	
	function getListHooksRegistered()
	{
		return array(
				'AdminMenu.add' => 'addMenu',
				'AssetManager.getJsFiles' => 'getJsFiles'
		);
	}
	
	function getJsFiles( $notification )
	{
		$jsFiles = &$notification->getNotificationObject();
		
		$jsFiles[] = "plugins/UsersManager/templates/UsersManager.js";	
		$jsFiles[] = "plugins/UsersManager/templates/userSettings.js";
	}
	
	function addMenu()
	{
		Piwik_AddAdminMenu('UsersManager_MenuUsers', 
							array('module' => 'UsersManager', 'action' => 'index'),
							Piwik::isUserHasSomeAdminAccess(),
							$order = 3);		
		Piwik_AddAdminMenu('UsersManager_MenuUserSettings', 
							array('module' => 'UsersManager', 'action' => 'userSettings'),
							Piwik::isUserHasSomeViewAccess(),
							$order = 1);		
	}
}

