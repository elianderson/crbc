<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: iUnzip.php 3312 2010-11-13 04:26:27Z vipsoft $
 *
 * @category Piwik
 * @package Piwik
 */

/**
 * Unzip interface
 *
 * @package Piwik
 */
interface Piwik_iUnzip
{
	/**
	 * Constructor
	 *
	 * @param string $filename Name of the .zip archive
	 */
	function __construct($filename);

	/**
	 * Extract files from archive to target directory
	 *
	 * @param string $pathExtracted Absolute path of target directory
	 * @return mixed Array of filenames if successful; or 0 if an error occurred
	 */
	public function extract($pathExtracted);

	/**
	 * Get error status string for the latest error
	 *
	 * @return string
	 */
	public function errorInfo();
}
