<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: Smarty.php 2968 2010-08-20 15:26:33Z vipsoft $
 * 
 * @category Piwik
 * @package Piwik
 */

/**
 * @see libs/Smarty/Smarty.class.php
 * @link http://smarty.net
 */
require_once PIWIK_INCLUDE_PATH . '/libs/Smarty/Smarty.class.php';

/**
 * Smarty class
 *
 * @package Piwik
 * @subpackage Piwik_Smarty
 * @see Smarty, libs/Smarty/Smarty.class.php
 * @link http://smarty.net/manual/en/
 */
class Piwik_Smarty extends Smarty 
{
	function trigger_error($error_msg, $error_type = E_USER_WARNING)
	{
		throw new SmartyException($error_msg);
	}
}

/**
 * @package Piwik
 * @subpackage Piwik_Smarty
 */
class SmartyException extends Exception {}
