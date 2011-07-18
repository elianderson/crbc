<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: Controller.php 3565 2011-01-03 05:49:45Z matt $
 * 
 * @category Piwik_Plugins
 * @package Piwik_SEO
 */
 
/**
 * @package Piwik_SEO
 */
class Piwik_SEO_Controller extends Piwik_Controller
{	
	function getRank()
	{
		$idSite = Piwik_Common::getRequestVar('idSite'); 
		$site = new Piwik_Site($idSite);

		$url = urldecode(Piwik_Common::getRequestVar('url', '', 'string'));

		if(empty($url) || @parse_url($url) === false)
		{
			$url = $site->getMainUrl();
		}

		$dataTable = Piwik_SEO_API::getInstance()->getRank($url);
		
		$view = Piwik_View::factory('index');
		$view->urlToRank = $url;
		
		$renderer = Piwik_DataTable_Renderer::factory('php');
		$renderer->setSerialize(false);
		$view->ranks = $renderer->render($dataTable);
		echo $view->render();
	}
}
