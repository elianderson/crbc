<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: modifier.stripeol.php 2968 2010-08-20 15:26:33Z vipsoft $
 * 
 * @category Piwik
 * @package SmartyPlugins
 */

/**
 * Smarty stripeol modifier plugin
 *
 * Type:     modifier<br>
 * Name:     stripeol<br>
 * Purpose:  Replace all end-of-line characters with platform specific string.<br>
 * Example:  {$var|stripeol}
 * Date:     March 10th, 2010
 * @author   anthon (at) piwik.org
 * @version  1.0
 * @param string
 * @param string
 * @return string
 */
function smarty_modifier_stripeol($text)
{
    return preg_replace('!(\r\n|\r|\n)!', PHP_EOL, $text);
}

/* vim: set expandtab: */
