<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: Unzip.php 3620 2011-01-04 21:22:36Z vipsoft $
 *
 * @category Piwik
 * @package Piwik
 */

/**
 * Unzip wrapper around ZipArchive and PclZip
 *
 * @package Piwik
 */
class Piwik_Unzip
{
	/**
	 * Returns an unarchiver
	 *
	 * @param string $filename Name of .zip archive
	 * @return Piwik_Unzip
	 */
	static public function getDefaultUnzip($filename)
	{
		if(class_exists('ZipArchive', false))
			return new Piwik_Unzip_ZipArchive($filename);

		return new Piwik_Unzip_PclZip($filename);
	}
}
