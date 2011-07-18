<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: API.php 3270 2010-10-28 18:21:55Z vipsoft $
 *
 * @category Piwik_Plugins
 * @package Piwik_SEO
 */

/**
 * @see plugins/Referers/functions.php
 */
require_once PIWIK_INCLUDE_PATH . '/plugins/Referers/functions.php';

/**
 * @package Piwik_SEO
 */
class Piwik_SEO_API 
{
	static private $instance = null;
	/**
	 * @return Piwik_SEO_API
	 */
	static public function getInstance()
	{
		if (self::$instance == null)
		{
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	/**
	 * @param $url URL to request Ranks for
	 * @return Piwik_DataTable
	 */
	public function getRank( $url )
	{
		Piwik::checkUserHasSomeViewAccess();
		$rank = new Piwik_SEO_RankChecker($url);
		
		$data = array(
			'Google Pagerank' 	=> array(
				'rank' => $rank->getPagerank(),
				'logo' => Piwik_getSearchEngineLogoFromUrl('http://www.google.com'),
				'id' => 'pagerank'
			),
			Piwik_Translate('SEO_YahooBacklinks')	=> array(
				'rank' => $rank->getBacklinksYahoo(),
				'logo' => Piwik_getSearchEngineLogoFromUrl('http://search.yahoo.com'),
				'id' => 'yahoo-bls'
			),
			Piwik_Translate('SEO_YahooIndexedPages') => array(
				'rank' => $rank->getIndexedYahoo(),
				'logo' => Piwik_getSearchEngineLogoFromUrl('http://search.yahoo.com'),
				'id' => 'yahoo-pages'
			),
			Piwik_Translate('SEO_AlexaRank') => array(
				'rank' => $rank->getAlexaRank(),
				'logo' => Piwik_getSearchEngineLogoFromUrl('http://alexa.com'),
				'id' => 'alexa',
			),
			Piwik_Translate('SEO_DomainAge') => array(
				'rank' => $rank->getAge(),
				'logo' => 'plugins/SEO/images/whois.png',
				'id'   => 'domain-age'
			),
		);
		$dataTable = new Piwik_DataTable();
		$dataTable->addRowsFromArrayWithIndexLabel($data);
		return $dataTable;
	}
	
}
