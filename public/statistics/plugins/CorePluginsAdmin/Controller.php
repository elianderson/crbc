<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: Controller.php 3470 2010-12-20 19:03:26Z matt $
 * 
 * @category Piwik_Plugins
 * @package Piwik_CorePluginsAdmin
 */

/**
 *
 * @package Piwik_CorePluginsAdmin
 */
class Piwik_CorePluginsAdmin_Controller extends Piwik_Controller
{	
	function index()
	{
		Piwik::checkUserIsSuperUser();
		
		$plugins = array();
	
		$listPlugins = Piwik_PluginsManager::getInstance()->readPluginsDirectory();
		foreach($listPlugins as $pluginName)
		{
			$oPlugin = Piwik_PluginsManager::getInstance()->loadPlugin($pluginName);
			$plugins[$pluginName] = array(
			 	'activated' => Piwik_PluginsManager::getInstance()->isPluginActivated($pluginName),
				'alwaysActivated' => Piwik_PluginsManager::getInstance()->isPluginAlwaysActivated($pluginName),
			);
		}
		Piwik_PluginsManager::getInstance()->loadPluginTranslations();

		$loadedPlugins = Piwik_PluginsManager::getInstance()->getLoadedPlugins();
		foreach($loadedPlugins as $oPlugin)
		{
			$pluginName = $oPlugin->getClassName();
			$plugins[$pluginName]['info'] = $oPlugin->getInformation();
		}

		$view = Piwik_View::factory('manage');
		$view->pluginsName = $plugins;
		$this->setBasicVariablesView($view);
		$view->menu = Piwik_GetAdminMenu();
		if(!Zend_Registry::get('config')->isFileWritable())
		{
			$view->configFileNotWritable = true;
		}
		echo $view->render();
	}

	public function deactivate()
	{
		Piwik::checkUserIsSuperUser();
		$this->checkTokenInUrl();
		$pluginName = Piwik_Common::getRequestVar('pluginName', null, 'string');
		Piwik_PluginsManager::getInstance()->deactivatePlugin($pluginName);
		Piwik_Url::redirectToReferer();
	}
	
	public function activate()
	{
		Piwik::checkUserIsSuperUser();
		$this->checkTokenInUrl();
		$pluginName = Piwik_Common::getRequestVar('pluginName', null, 'string');
		Piwik_PluginsManager::getInstance()->activatePlugin($pluginName);
		Piwik_Url::redirectToReferer();
	}
}
