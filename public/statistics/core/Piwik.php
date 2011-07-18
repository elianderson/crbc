<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: Piwik.php 3565 2011-01-03 05:49:45Z matt $
 *
 * @category Piwik
 * @package Piwik
 */

/**
 * @see core/Translate.php
 */
require_once PIWIK_INCLUDE_PATH . '/core/Translate.php';

/**
 * Main piwik helper class.
 * Contains static functions you can call from the plugins.
 *
 * @package Piwik
 */
class Piwik
{
	const CLASSES_PREFIX = 'Piwik_';
	const COMPRESSED_FILE_LOCATION = '/tmp/assets/';

	public static $idPeriods =  array(
			'day'	=> 1,
			'week'	=> 2,
			'month'	=> 3,
			'year'	=> 4,
		);

/*
 * Prefix/unprefix class name
 */

	/**
	 * Prefix class name (if needed)
	 *
	 * @param string $class
	 * @return string
	 */
	static public function prefixClass( $class )
	{
		if(!strncmp($class, Piwik::CLASSES_PREFIX, strlen(Piwik::CLASSES_PREFIX)))
		{
			return $class;
		}
		return Piwik::CLASSES_PREFIX.$class;
	}

	/**
	 * Unprefix class name (if needed)
	 *
	 * @param string $class
	 * @return string
	 */
	static public function unprefixClass( $class )
	{
		$lenPrefix = strlen(Piwik::CLASSES_PREFIX);
		if(!strncmp($class, Piwik::CLASSES_PREFIX, $lenPrefix))
		{
			return substr($class, $lenPrefix);
		}
		return $class;
	}

/*
 * Installation / Uninstallation
 */

	/**
	 * Installation helper
	 */
	static public function install()
	{
		Piwik_Common::mkdir(Zend_Registry::get('config')->smarty->compile_dir);
	}

	/**
	 * Uninstallation helper
	 */
	static public function uninstall()
	{
		Piwik_Db_Schema::getInstance()->dropTables();
	}

	/**
	 * Returns true if Piwik is installed
	 *
	 * @since 0.6.3
	 *
	 * @return bool True if installed; false otherwise
	 */
	static public function isInstalled()
	{
		return Piwik_Db_Schema::getInstance()->hasTables();
	}

/*
 * HTTP headers
 */
	/**
	 * Returns true if this appears to be a secure HTTPS connection
	 *
	 * @return bool
	 */
	static public function isHttps()
	{
		return Piwik_Url::getCurrentScheme() === 'https' || Zend_Registry::get('config')->General->reverse_proxy;
	}

	/**
	 * Set response header, e.g., HTTP/1.0 200 Ok
	 *
	 * @return bool
	 */
	static public function setHttpStatus($status)
	{
		if(substr_compare(PHP_SAPI, '-fcgi', -5))
		{
			@header($_SERVER['SERVER_PROTOCOL'] . ' ' . $status);
		}
		else
		{
			// FastCGI
			@header('Status: ' . $status);
		}
	}

	/**
	 * Workaround IE bug when downloading certain document types over SSL and
	 * cache control headers are present, e.g.,
	 *
	 *    Cache-Control: no-cache
	 *    Cache-Control: no-store,max-age=0,must-revalidate
	 *    Pragma: no-cache
	 *
	 * @see http://support.microsoft.com/kb/316431/
	 * @see RFC2616
	 *
	 * @param string $override One of "public", "private", "no-cache", or "no-store". (optional)
	 */
	static public function overrideCacheControlHeaders($override = null)
	{
		if($override || self::isHttps())
		{
			@header('Pragma: ');
			@header('Expires: ');
			if(in_array($override, array('public', 'private', 'no-cache', 'no-store')))
			{
				@header("Cache-Control: $override, must-revalidate");
			}
			else
			{
				@header('Cache-Control: must-revalidate');
			}
		}
	}

/*
 * File and directory operations
 */

	/**
	 * Copy recursively from $source to $target.
	 *
	 * @param string $source eg. './tmp/latest'
	 * @param string $target eg. '.'
	 * @param bool   $excludePhp
	 */
	static public function copyRecursive($source, $target, $excludePhp=false )
	{
		if ( is_dir( $source ) )
		{
			@mkdir( $target );
			$d = dir( $source );
			while ( false !== ( $entry = $d->read() ) )
			{
				if ( $entry == '.' || $entry == '..' )
				{
					continue;
				}

				$sourcePath = $source . '/' . $entry;
				if ( is_dir( $sourcePath ) )
				{
					self::copyRecursive( $sourcePath, $target . '/' . $entry, $excludePhp );
					continue;
				}
				$destPath = $target . '/' . $entry;
				self::copy($sourcePath, $destPath, $excludePhp);
			}
			$d->close();
		}
		else
		{
			self::copy($source, $target, $excludePhp);
		}
	}

	/**
	 * Copy individual file from $source to $target.
	 *
	 * @param string $source eg. './tmp/latest/index.php'
	 * @param string $target eg. './index.php'
	 * @param bool   $excludePhp
	 * @return bool
	 */
	static public function copy($source, $dest, $excludePhp=false)
	{
		static $phpExtensions = array('php', 'tpl');

		if($excludePhp)
		{
			$path_parts = pathinfo($source);
			if(in_array($path_parts['extension'], $phpExtensions))
			{
				return true;
			}
		}

		if(!@copy( $source, $dest ))
		{
			@chmod($dest, 0755);
	   		if(!@copy( $source, $dest ))
	   		{
				$message = "Error while copying file to <code>$dest</code>. <br />"
				         . "Please check that the web server has enough permission to overwrite this file. <br />";

				if(Piwik_Common::isWindows())
				{
					$message .= "On Windows, you can try to execute:<br />"
					          . "<code>cacls ".Piwik_Common::getPathToPiwikRoot()." /t /g ".get_current_user().":f</code><br />";
				}
				else
				{
					$message = "For example, on a Linux server, if your Apache httpd user is www-data you can try to execute:<br />"
					         . "<code>chown -R www-data:www-data ".Piwik_Common::getPathToPiwikRoot()."</code><br />"
					         . "<code>chmod -R 0755 ".Piwik_Common::getPathToPiwikRoot()."</code><br />";
				}
				throw new Exception($message);
	   		}
		}
		return true;
	}

	/**
	 * Recursively delete a directory
	 *
	 * @param string $dir Directory name
	 * @param boolean $deleteRootToo Delete specified top-level directory as well
	 */
	static public function unlinkRecursive($dir, $deleteRootToo)
	{
		if(!$dh = @opendir($dir))
		{
			return;
		}
		while (false !== ($obj = readdir($dh)))
		{
			if($obj == '.' || $obj == '..')
			{
				continue;
			}

			if (!@unlink($dir . '/' . $obj))
			{
				self::unlinkRecursive($dir.'/'.$obj, true);
			}
		}
		closedir($dh);
		if ($deleteRootToo)
		{
			@rmdir($dir);
		}
		return;
	}

	/**
	 * Recursively find pathnames that match a pattern
	 * @see glob()
	 *
	 * @param string $sDir directory
	 * @param string $sPattern pattern
	 * @param int $nFlags glob() flags
	 * @return array
	 */
	public static function globr($sDir, $sPattern, $nFlags = NULL)
	{
		if(($aFiles = _glob("$sDir/$sPattern", $nFlags)) == false)
		{
			$aFiles = array();
		}
		if(($aDirs = _glob("$sDir/*", GLOB_ONLYDIR)) != false)
		{
			foreach ($aDirs as $sSubDir)
			{
				$aSubFiles = self::globr($sSubDir, $sPattern, $nFlags);
				$aFiles = array_merge($aFiles, $aSubFiles);
			}
		}
		return $aFiles;
	}

	/**
	 * Checks that the directories Piwik needs write access are actually writable
	 * Displays a nice error page if permissions are missing on some directories
	 *
	 * @param array $directoriesToCheck Array of directory names to check
	 */
	static public function checkDirectoriesWritableOrDie( $directoriesToCheck = null )
	{
		$resultCheck = Piwik::checkDirectoriesWritable( $directoriesToCheck );
		if( array_search(false, $resultCheck) === false )
		{
			return;
		}
		$directoryList = '';
		foreach($resultCheck as $dir => $bool)
		{
			$realpath = Piwik_Common::realpath($dir);
			if(!empty($realpath) && $bool === false)
			{
				if(Piwik_Common::isWindows())
				{
					$directoryList .= "<code>cacls $realpath /t /g ".get_current_user().":f</code><br />";
				}
				else
				{
					$directoryList .= "<code>chmod 0777 $realpath</code><br />";
				}
			}
		}
		$directoryMessage = "<p><b>Piwik couldn't write to some directories</b>.</p> <p>Try to Execute the following commands on your server:</p>"
		                  . "<blockquote>$directoryList</blockquote>"
		                  . "<p>If this doesn't work, you can try to create the directories with your FTP software, and set the CHMOD to 0777 (with your FTP software, right click on the directories, permissions).</p>"
		                  . "<p>After applying the modifications, you can <a href='index.php'>refresh the page</a>.</p>"
		                  . "<p>If you need more help, try <a href='?module=Proxy&action=redirect&url=http://piwik.org'>Piwik.org</a>.</p>";

		Piwik_ExitWithMessage($directoryMessage, false, true);
	}

	/**
	 * Checks if directories are writable and create them if they do not exist.
	 *
	 * @param array $directoriesToCheck array of directories to check - if not given default Piwik directories that needs write permission are checked
	 * @return array directory name => true|false (is writable)
	 */
	static public function checkDirectoriesWritable($directoriesToCheck = null)
	{
		if( $directoriesToCheck == null )
		{
			$directoriesToCheck = array(
				'/config/',
				'/tmp/',
				'/tmp/templates_c/',
				'/tmp/cache/',
				'/tmp/latest/',
				'/tmp/assets/',
			);
		}

		$resultCheck = array();
		foreach($directoriesToCheck as $directoryToCheck)
		{
			if( !preg_match('/^'.preg_quote(PIWIK_USER_PATH, '/').'/', $directoryToCheck) )
			{
				$directoryToCheck = PIWIK_USER_PATH . $directoryToCheck;
			}

			if(!file_exists($directoryToCheck))
			{
				// the mode in mkdir is modified by the current umask
				Piwik_Common::mkdir($directoryToCheck, 0755, false);
			}

			$directory = Piwik_Common::realpath($directoryToCheck);
			$resultCheck[$directory] = false;
			if($directory !== false // realpath() returns FALSE on failure
				&& is_writable($directoryToCheck))
			{
				$resultCheck[$directory] = true;
			}
		}
		return $resultCheck;
	}

	/**
	 * Check if this installation can be auto-updated.
	 *
	 * For performance, we look for clues rather than an exhaustive test.
	 */
	static public function canAutoUpdate()
	{
		if(!is_writable(PIWIK_INCLUDE_PATH . '/') ||
			!is_writable(PIWIK_DOCUMENT_ROOT . '/index.php') ||
			!is_writable(PIWIK_INCLUDE_PATH . '/core') ||
			!is_writable(PIWIK_USER_PATH . '/config/global.ini.php'))
		{
			return false;
		}
		return true;
	}

	/**
	 * Generate default robots.txt, favicon.ico, etc to suppress
	 * 404 (Not Found) errors in the web server logs, if Piwik
	 * is installed in the web root (or top level of subdomain).
	 *
	 * @see misc/crossdomain.xml
	 */
	static public function createWebRootFiles()
	{
		$filesToCreate = array(
			'/robots.txt',
			'/favicon.ico',
		);
		foreach($filesToCreate as $file)
		{
			@file_put_contents(PIWIK_DOCUMENT_ROOT . $file, '');
		}
	}

	/**
	 * Generate Apache .htaccess files to restrict access
	 */
	static public function createHtAccessFiles()
	{
		// deny access to these folders
		$directoriesToProtect = array(
			'/config',
			'/core',
			'/lang',
			'/tmp',
		);
		foreach($directoriesToProtect as $directoryToProtect)
		{
			Piwik_Common::createHtAccess(PIWIK_INCLUDE_PATH . $directoryToProtect);
		}

		// more selective allow/deny filters
		$allowAny = "<Files \"*\">\nAllow from all\nSatisfy any\n</Files>\n";
		$allowStaticAssets = "<Files ~ \"\\.(test\.php|gif|ico|jpg|png|js|css|swf)$\">\nSatisfy any\nAllow from all\n</Files>\n";
		$denyDirectPhp = "<Files ~ \"\\.(php|php4|php5|inc|tpl|in)$\">\nDeny from all\n</Files>\n";
		$directoriesToProtect = array(
			'/js' => $allowAny,
			'/libs' => $denyDirectPhp . $allowStaticAssets,
			'/plugins' => $denyDirectPhp . $allowStaticAssets,
			'/themes' => $denyDirectPhp . $allowStaticAssets,
		);
		foreach($directoriesToProtect as $directoryToProtect => $content)
		{
			Piwik_Common::createHtAccess(PIWIK_INCLUDE_PATH . $directoryToProtect, $content);
		}
	}

	/**
	 * Generate IIS web.config files to restrict access
	 *
	 * Note: for IIS 7 and above
	 */
	static public function createWebConfigFiles()
	{
		@file_put_contents(PIWIK_INCLUDE_PATH . '/web.config',
'<?xml version="1.0" encoding="UTF-8"?>
<configuration>
  <system.webServer>
    <security>
      <requestFiltering>
        <hiddenSegments>
          <add segment="config" />
          <add segment="core" />
          <add segment="lang" />
          <add segment="tmp" />
        </hiddenSegments>
        <fileExtensions>
          <add fileExtension=".tpl" allowed="false" />
          <add fileExtension=".php4" allowed="false" />
          <add fileExtension=".php5" allowed="false" />
          <add fileExtension=".inc" allowed="false" />
          <add fileExtension=".in" allowed="false" />
        </fileExtensions>
      </requestFiltering>
    </security>
    <directoryBrowse enabled="false" />
    <defaultDocument>
      <files>
        <remove value="index.php" />
        <add value="index.php" />
      </files>
    </defaultDocument>
  </system.webServer>
</configuration>');

		// deny direct access to .php files
		$directoriesToProtect = array(
			'/libs',
			'/plugins',
		);
		foreach($directoriesToProtect as $directoryToProtect)
		{
			@file_put_contents(PIWIK_INCLUDE_PATH . $directoryToProtect . '/web.config',
'<?xml version="1.0" encoding="UTF-8"?>
<configuration>
  <system.webServer>
    <security>
      <requestFiltering>
        <denyUrlSequences>
          <add sequence=".php" />
        </denyUrlSequences>
      </requestFiltering>
    </security>
  </system.webServer>
</configuration>');
		}
	}

	/**
	 * Get file integrity information (in PIWIK_INCLUDE_PATH).
	 *
	 * @return array(bool, string, ...) Return code (true/false), followed by zero or more error messages
	 */
	static public function getFileIntegrityInformation()
	{
		$messages = array();
		$messages[] = true;

		// ignore dev environments
		if(file_exists(PIWIK_INCLUDE_PATH . '/.svn'))
		{
			$messages[] = Piwik_Translate('General_WarningFileIntegritySkipped');
			return $messages;
		}

		$manifest = PIWIK_INCLUDE_PATH . '/config/manifest.inc.php';
		if(!file_exists($manifest))
		{
			$messages[] = Piwik_Translate('General_WarningFileIntegrityNoManifest');
			return $messages;
		}

		require_once $manifest;

		$files = Manifest::$files;

		$hasMd5file = function_exists('md5_file');
		$hasMd5 = function_exists('md5');
		foreach($files as $path => $props)
		{
			$file = PIWIK_INCLUDE_PATH . '/' . $path;

			if(!file_exists($file))
			{
				$messages[] = Piwik_Translate('General_ExceptionMissingFile', $file);
			}
			else if(filesize($file) != $props[0])
			{
				if(!$hasMd5 || in_array(substr($path, -4), array('.gif', '.ico', '.jpg', '.png', '.swf')))
				{
					// files that contain binary data (e.g., images) must match the file size
					$messages[] = Piwik_Translate('General_ExceptionFilesizeMismatch', array($file, $props[0], filesize($file)));
				}
				else
				{
					// convert end-of-line characters and re-test text files
					$content = @file_get_contents($file);
					$content = str_replace("\r\n", "\n", $content);
					if((strlen($content) != $props[0])
						|| (@md5($content) !== $props[1]))
					{
						$messages[] = Piwik_Translate('General_ExceptionFilesizeMismatch', array($file, $props[0], filesize($file)));
					}
				}
			}
			else if($hasMd5file && (@md5_file($file) !== $props[1]))
			{
				$messages[] = Piwik_Translate('General_ExceptionFileIntegrity', $file);
			}
		}

		if(count($messages) > 1)
		{
			$messages[0] = false;
		}

		if(!$hasMd5file)
		{
			$messages[] = Piwik_Translate('General_WarningFileIntegrityNoMd5file');
		}

		return $messages;
	}

	/**
	 * Serve static files through php proxy.
	 *
	 * It performs the following actions:
	 * 	- Checks the file is readable or returns "HTTP/1.0 404 Not Found"
	 *  - Returns "HTTP/1.1 304 Not Modified" after comparing the HTTP_IF_MODIFIED_SINCE
	 *	  with the modification date of the static file
	 *	- Will try to compress the static file according to HTTP_ACCEPT_ENCODING. Compressed files are store in
	 *	  the /tmp directory. If compressing extensions are not available, a manually gzip compressed file
	 *	  can be provided in the /tmp directory. It has to bear the same name with an added .gz extension.
	 *	  Using manually compressed static files requires you to manually update the compressed file when
	 *	  the static file is updated.
	 *	- Overrides server cache control config to allow caching
	 *	- Sends Very Accept-Encoding to tell proxies to store different version of the static file according
	 *	  to users encoding capacities.
	 *
	 * Warning:
	 * 		Compressed filed are stored in the /tmp directory.
	 * 		If this method is used with two files bearing the same name but located in different locations,
	 * 		there is a risk of conflict. One file could be served with the content of the other.
	 * 		A future upgrade of this method would be to recreate the directory structure of the static file
	 * 		within a /tmp/compressed-static-files directory.
	 *
	 * @param string $file The location of the static file to serve
	 * @param string $contentType The content type of the static file.
	 */
	static public function serveStaticFile($file, $contentType)
	{
		if (file_exists($file) && function_exists('readfile'))
		{
			// conditional GET
			$modifiedSince = '';
			if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
			{
				$modifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
			}

			// strip any trailing data appended to header
			if (false !== ($semicolon = strpos($modifiedSince, ';')))
			{
				$modifiedSince = substr($modifiedSince, 0, $semicolon);
			}

			$fileModifiedTime = @filemtime($file);
			$lastModified = gmdate('D, d M Y H:i:s', $fileModifiedTime) . ' GMT';

			// set HTTP response headers
			self::overrideCacheControlHeaders('public');
			@header('Vary: Accept-Encoding');
			@header('Content-Disposition: inline; filename='.basename($file));

			// Returns 304 if not modified since
			if ($modifiedSince == $lastModified)
			{
				self::setHttpStatus('304 Not Modified');
			}
			else
			{
				// optional compression
				$compressed = false;
				$encoding = '';
				$compressedFileLocation = PIWIK_USER_PATH . self::COMPRESSED_FILE_LOCATION . basename($file);

				// Off = ''; On = '1'; otherwise, it's a buffer size
				$zlibOutputCompression = ini_get('zlib.output_compression');
				$phpOutputCompressionEnabled = !empty($zlibOutputCompression);
				if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && !$phpOutputCompressionEnabled)
				{
					$acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'];

					if (extension_loaded('zlib') && function_exists('file_get_contents') && function_exists('file_put_contents'))
					{
						if (preg_match('/(?:^|, ?)(deflate)(?:,|$)/', $acceptEncoding, $matches))
						{
							$encoding = 'deflate';
							$filegz = $compressedFileLocation .'.deflate';
						}
						else if (preg_match('/(?:^|, ?)((x-)?gzip)(?:,|$)/', $acceptEncoding, $matches))
						{
							$encoding = $matches[1];
							$filegz = $compressedFileLocation .'.gz';
						}

						if (!empty($encoding))
						{
							// compress-on-demand and use cache
							if(!file_exists($filegz) || ($fileModifiedTime > @filemtime($filegz)))
							{
								$data = file_get_contents($file);

								if ($encoding == 'deflate')
								{
									$data = gzdeflate($data, 9);
								}
								else if ($encoding == 'gzip' || $encoding == 'x-gzip')
								{
									$data = gzencode($data, 9);
								}

								file_put_contents($filegz, $data);
								$file = $filegz;
							}

							$compressed = true;
							$file = $filegz;
						}
					}
					else
					{
						// manually compressed
						$filegz = $compressedFileLocation .'.gz';
						if (preg_match('/(?:^|, ?)((x-)?gzip)(?:,|$)/', $acceptEncoding, $matches) && file_exists($filegz) && ($fileModifiedTime < @filemtime($filegz)))
						{
							$encoding = $matches[1];
							$compressed = true;
							$file = $filegz;
						}
					}
				}

				@header('Last-Modified: ' . $lastModified);

				if (!$phpOutputCompressionEnabled)
				{
					@header('Content-Length: ' . filesize($file));
				}

				if(!empty($contentType))
				{
					@header('Content-Type: '.$contentType);
				}

				if ($compressed)
				{
					@header('Content-Encoding: ' . $encoding);
				}

				if (!@readfile($file))
				{
					self::setHttpStatus('505 Internal server error');
				}
			}
		}
		else
		{
			self::setHttpStatus('404 Not Found');
		}
	}

/*
 * PHP environment settings
 */

	/**
	 * Set maximum script execution time.
	 *
	 * @param int max execution time in seconds (0 = no limit)
	 */
	static public function setMaxExecutionTime($executionTime)
	{
		// in the event one or the other is disabled...
		@ini_set('max_execution_time', $executionTime);
		@set_time_limit($executionTime);
	}

	/**
	 * Get php memory_limit (in Megabytes)
	 *
	 * Prior to PHP 5.2.1, or on Windows, --enable-memory-limit is not a
	 * compile-time default, so ini_get('memory_limit') may return false.
	 *
	 * @see http://www.php.net/manual/en/faq.using.php#faq.using.shorthandbytes
	 * @return int memory limit in megabytes
	 */
	static public function getMemoryLimitValue()
	{
		if($memory = ini_get('memory_limit'))
		{
			// handle shorthand byte options (case-insensitive)
			$shorthandByteOption = substr($memory, -1);
			switch($shorthandByteOption)
			{
				case 'G':
				case 'g':
					return substr($memory, 0, -1) * 1024;
				case 'M':
				case 'm':
					return substr($memory, 0, -1);
				case 'K':
				case 'k':
					return substr($memory, 0, -1) / 1024;
			}
			return $memory / 1048576;
		}
		return false;
	}

	/**
	 * Set PHP memory limit
	 *
	 * Note: system settings may prevent scripts from overriding the master value
	 *
	 * @param int $minimumMemoryLimit
	 * @return bool true if set; false otherwise
	 */
	static public function setMemoryLimit($minimumMemoryLimit)
	{
		$currentValue = self::getMemoryLimitValue();
		if( ($currentValue === false
			|| $currentValue < $minimumMemoryLimit )
			&& @ini_set('memory_limit', $minimumMemoryLimit.'M'))
		{
			return true;
		}
		return false;
	}

	/**
	 * Raise PHP memory limit if below the minimum required
	 *
	 * @return bool true if set; false otherwise
	 */
	static public function raiseMemoryLimitIfNecessary()
	{
		$minimumMemoryLimit = Zend_Registry::get('config')->General->minimum_memory_limit;
		$memoryLimit = self::getMemoryLimitValue();
		if($memoryLimit === false
			|| $memoryLimit < $minimumMemoryLimit)
		{
			return self::setMemoryLimit($minimumMemoryLimit);
		}

		return false;
	}

/*
 * Logging and error handling
 */

	static public function log($message = '')
	{
		Zend_Registry::get('logger_message')->logEvent($message);
	}

	static public function error($message = '')
	{
		trigger_error($message, E_USER_ERROR);
	}

	/**
	 * Display the message in a nice red font with a nice icon
	 * ... and dies
	 */
	static public function exitWithErrorMessage( $message )
	{
		$output = "<style>a{color:red;}</style>\n".
			"<div style='color:red;font-family:Georgia;font-size:120%'>".
			"<p><img src='themes/default/images/error_medium.png' style='vertical-align:middle; float:left;padding:20 20 20 20' />".
			$message.
			"</p></div>";
		print(Piwik_Log_Formatter_ScreenFormatter::getFormattedString($output));
		exit;
	}

/*
 * Profiling
 */

	/**
	 * Get total number of queries
	 *
	 * @return int number of queries
	 */
	static public function getQueryCount()
	{
		$profiler = Zend_Registry::get('db')->getProfiler();
		return $profiler->getTotalNumQueries();
	}

	/**
	 * Get total elapsed time (in seconds)
	 *
	 * @return int elapsed time
	 */
	static public function getDbElapsedSecs()
	{
		$profiler = Zend_Registry::get('db')->getProfiler();
		return $profiler->getTotalElapsedSecs();
	}

	/**
	 * Print number of queries and elapsed time
	 */
	static public function printQueryCount()
	{
		$totalTime = self::getDbElapsedSecs();
		$queryCount = self::getQueryCount();
		Piwik::log("Total queries = $queryCount (total sql time = ".round($totalTime,2)."s)");
	}

	/**
	 * Print profiling report for the tracker
	 *
	 * @param Piwik_Tracker_Db $db Tracker database object (or null)
	 */
	static public function printSqlProfilingReportTracker( $db = null )
	{
		if(!function_exists('maxSumMsFirst'))
		{
			function maxSumMsFirst($a,$b)
			{
				return $a['sum_time_ms'] < $b['sum_time_ms'];
			}
		}

		if(is_null($db))
		{
			$db = Piwik_Tracker::getDatabase();
		}
		$tableName = Piwik_Common::prefixTable('log_profiling');

		$all = $db->fetchAll('SELECT * FROM '.$tableName );
		if($all === false)
		{
			return;
		}
		uasort($all, 'maxSumMsFirst');

		$infoIndexedByQuery = array();
		foreach($all as $infoQuery)
		{
			$query = $infoQuery['query'];
			$count = $infoQuery['count'];
			$sum_time_ms = $infoQuery['sum_time_ms'];
			$infoIndexedByQuery[$query] = array('count' => $count, 'sumTimeMs' => $sum_time_ms);
		}
		Piwik::getSqlProfilingQueryBreakdownOutput($infoIndexedByQuery);
	}

	/**
	 * Outputs SQL Profiling reports
	 * It is automatically called when enabling the SQL profiling in the config file enable_sql_profiler
	 */
	static function printSqlProfilingReportZend()
	{
		$profiler = Zend_Registry::get('db')->getProfiler();

		if(!$profiler->getEnabled())
		{
			throw new Exception("To display the profiler you should enable enable_sql_profiler on your config/config.ini.php file");
		}

		$infoIndexedByQuery = array();
		foreach($profiler->getQueryProfiles() as $query)
		{
			if(isset($infoIndexedByQuery[$query->getQuery()]))
			{
				$existing =  $infoIndexedByQuery[$query->getQuery()];
			}
			else
			{
				$existing = array( 'count' => 0, 'sumTimeMs' => 0);
			}
			$new = array( 'count' => $existing['count'] + 1,
							'sumTimeMs' =>  $existing['count'] + $query->getElapsedSecs() * 1000);
			$infoIndexedByQuery[$query->getQuery()] = $new;
		}

		if(!function_exists('sortTimeDesc'))
		{
			function sortTimeDesc($a,$b)
			{
				return $a['sumTimeMs'] < $b['sumTimeMs'];
			}
		}
		uasort( $infoIndexedByQuery, 'sortTimeDesc');

		Piwik::log('<hr /><b>SQL Profiler</b>');
		Piwik::log('<hr /><b>Summary</b>');
		$totalTime	= $profiler->getTotalElapsedSecs();
		$queryCount   = $profiler->getTotalNumQueries();
		$longestTime  = 0;
		$longestQuery = null;
		foreach ($profiler->getQueryProfiles() as $query) {
			if ($query->getElapsedSecs() > $longestTime) {
				$longestTime  = $query->getElapsedSecs();
				$longestQuery = $query->getQuery();
			}
		}
		$str = 'Executed ' . $queryCount . ' queries in ' . round($totalTime,3) . ' seconds' . "\n";
		$str .= '(Average query length: ' . round($totalTime / $queryCount,3) . ' seconds)' . "\n";
		$str .= '<br />Queries per second: ' . round($queryCount / $totalTime,1) . "\n";
		$str .= '<br />Longest query length: ' . round($longestTime,3) . " seconds (<code>$longestQuery</code>) \n";
		Piwik::log($str);
		Piwik::getSqlProfilingQueryBreakdownOutput($infoIndexedByQuery);
	}

	/**
	 * Log a breakdown by query
	 *
	 * @param array $infoIndexedByQuery
	 */
	static private function getSqlProfilingQueryBreakdownOutput( $infoIndexedByQuery )
	{
		Piwik::log('<hr /><b>Breakdown by query</b>');
		$output = '';
		foreach($infoIndexedByQuery as $query => $queryInfo)
		{
			$timeMs = round($queryInfo['sumTimeMs'],1);
			$count = $queryInfo['count'];
			$avgTimeString = '';
			if($count > 1)
			{
				$avgTimeMs = $timeMs / $count;
				$avgTimeString = " (average = <b>". round($avgTimeMs,1) . "ms</b>)";
			}
			$query = preg_replace('/([\t\n\r ]+)/', ' ', $query);
			$output .= "Executed <b>$count</b> time". ($count==1?'':'s') ." in <b>".$timeMs."ms</b> $avgTimeString <pre>\t$query</pre>";
		}
		Piwik::log($output);
	}

	/**
	 * Print timer
	 */
	static public function printTimer()
	{
		echo Zend_Registry::get('timer');
	}

	/**
	 * Print memory leak
	 *
	 * @param string $prefix
	 * @param string $suffix
	 */
	static public function printMemoryLeak($prefix = '', $suffix = '<br />')
	{
		echo $prefix;
		echo Zend_Registry::get('timer')->getMemoryLeak();
		echo $suffix;
	}

	/**
	 * Print memory usage
	 *
	 * @param string $prefixString
	 */
	static public function printMemoryUsage( $prefixString = null )
	{
		$memory = false;
		if(function_exists('xdebug_memory_usage'))
		{
			$memory = xdebug_memory_usage();
		}
		elseif(function_exists('memory_get_usage'))
		{
			$memory = memory_get_usage();
		}

		if($memory !== false)
		{
			$usage = round( $memory / 1024 / 1024, 2);
			if(!is_null($prefixString))
			{
				Piwik::log($prefixString);
			}
			Piwik::log("Memory usage = $usage Mb");
		}
		else
		{
			Piwik::log("Memory usage function not found.");
		}
	}

/*
 * Amounts, Percentages, Currency, Time, Math Operations, and Pretty Printing
 */

	/**
	 * Returns a list of currency symbols
	 *
	 * @return array array( currencyCode => symbol, ... )
	 */
	static public function getCurrencyList()
	{
		static $currenciesList = null;
		if(is_null($currenciesList))
		{
			require_once PIWIK_INCLUDE_PATH . '/core/DataFiles/Currencies.php';
			$currenciesList = $GLOBALS['Piwik_CurrencyList'];
		}
		return $currenciesList;
	}

	/**
	 * Computes the division of i1 by i2. If either i1 or i2 are not number, or if i2 has a value of zero
	 * we return 0 to avoid the division by zero.
	 *
	 * @param numeric $i1
	 * @param numeric $i2
	 * @return numeric The result of the division or zero
	 */
	static public function secureDiv( $i1, $i2 )
	{
		if ( is_numeric($i1) && is_numeric($i2) && floatval($i2) != 0)
		{
			return $i1 / $i2;
		}
		return 0;
	}

	/**
	 * Safely compute a percentage.  Return 0 to avoid division by zero.
	 *
	 * @param numeric $dividend
	 * @param numeric $divisor
	 * @param int $precision
	 * @return numeric
	 */
	static public function getPercentageSafe($dividend, $divisor, $precision = 0)
	{
		if($divisor == 0)
		{
			return 0;
		}
		return round(100 * $dividend / $divisor, $precision);
	}

	/**
	 * Get currency symbol for a site
	 *
	 * @param int $idSite
	 * @return string
	 */
	static public function getCurrency($idSite)
	{
		$symbols = self::getCurrencyList();
		$site = new Piwik_Site($idSite);
		$currency = $site->getCurrency();
		if(isset($symbols[$currency]))
		{
			return $symbols[$currency][0];
		}

		return '';
	}

	/**
	 * For the given value, based on the column name, will apply: pretty time, pretty money
	 * @param $idSite
	 * @param $columnName
	 * @param $value
	 * @param $htmlAllowed
	 * @param $timeAsSentence
	 * @return string
	 */
	static public function getPrettyValue($idSite, $columnName, $value, $htmlAllowed, $timeAsSentence)
	{
		// Display time in human readable
		if(strpos($columnName, 'time') !== false)
		{
			return Piwik::getPrettyTimeFromSeconds($value, $timeAsSentence);
		}
		// Add revenue symbol to revenues
		if(strpos($columnName, 'revenue') !== false)
		{
			return Piwik::getPrettyMoney($value, $idSite, $htmlAllowed);
		}
		return $value;
	}

	/**
	 * Pretty format monetary value for a site
	 *
	 * @param numeric|string $value
	 * @param int $idSite
	 * @return string
	 */
	static public function getPrettyMoney($value, $idSite, $htmlAllowed = true)
	{
		$currencyBefore = self::getCurrency($idSite);

		$space = ' ';
		if($htmlAllowed)
		{
			$space = '&nbsp;';
		}

		$currencyAfter = '';
		// manually put the currency symbol after the amount for euro
		// (maybe more currencies prefer this notation?)
		if(in_array($currencyBefore,array('€')))
		{
			$currencyAfter = $space.$currencyBefore;
			$currencyBefore = '';
		}

		// if the input is a number (it could be a string or INPUT form),
		// and if this number is not an int, we round to precision 2
		if(is_numeric($value))
		{
			if($value == round($value))
			{
				// 0.0 => 0
				$value = round($value);
			}
			else
			{
				$precision = 2;
				$value = sprintf( "%01.".$precision."f", $value);
			}
		}
		$prettyMoney = $currencyBefore . $space . $value . $currencyAfter;
		return $prettyMoney;
	}

	/**
	 * Pretty format a memory size value
	 *
	 * @param numeric $size in bytes
	 * @return string
	 */
	static public function getPrettySizeFromBytes($size)
	{
		$bytes = array('','K','M','G','T');
		foreach($bytes as $val)
		{
			if($size > 1024)
			{
				$size = $size / 1024;
			}
			else
			{
				break;
			}
		}
		return round($size, 1)." ".$val;
	}

	/**
	 * Pretty format a time
	 *
	 * @param numeric $numberOfSeconds
	 * @param bool If set to true, will output "5min 17s", if false "00:05:17"
	 * @return string
	 */
	static public function getPrettyTimeFromSeconds($numberOfSeconds, $displayTimeAsSentence = true)
	{
		$numberOfSeconds = (int)$numberOfSeconds;

		// Display 01:45:17 time format
		if($displayTimeAsSentence === false)
		{
			$hours = floor( $numberOfSeconds / 3600);
			$minutes = floor( ($reminder = ($numberOfSeconds - $hours * 3600)) / 60 );
			$seconds = $reminder - $minutes * 60;
			return sprintf("%02s", $hours) . ':' . sprintf("%02s", $minutes) .':'. sprintf("%02s", $seconds);
		}
		$secondsInYear = 86400 * 365.25;
		$years = floor($numberOfSeconds / $secondsInYear);
		$minusYears = $numberOfSeconds - $years * $secondsInYear;
		$days = floor($minusYears / 86400);

		$minusDays = $numberOfSeconds - $days * 86400;
		$hours = floor($minusDays / 3600);

		$minusDaysAndHours = $minusDays - $hours * 3600;
		$minutes = floor($minusDaysAndHours / 60 );

		$seconds = $minusDaysAndHours - $minutes * 60;

		if($years > 0)
		{
			$return = sprintf(Piwik_Translate('General_YearsDays'), $years, $days);
		}
		elseif($days > 0)
		{
			$return = sprintf(Piwik_Translate('General_DaysHours'), $days, $hours);
		}
		elseif($hours > 0)
		{
			$return = sprintf(Piwik_Translate('General_HoursMinutes'), $hours, $minutes);
		}
		elseif($minutes > 0)
		{
			$return = sprintf(Piwik_Translate('General_MinutesSeconds'), $minutes, $seconds);
		}
		else
		{
			$return = sprintf(Piwik_Translate('General_Seconds'), $seconds);
		}
		return str_replace(' ', '&nbsp;', $return);
	}

	/**
	 * Returns relative path to the App logo
	 *
	 * @return string
	 */
	public function getLogoPath()
	{
		return Piwik_Common::getPathToPiwikRoot() . '/themes/default/images/logo.png';
	}

	/**
	 * Returns the Javascript code to be inserted on every page to track
	 *
	 * @param int $idSite
	 * @param string $piwikUrl http://path/to/piwik/directory/
	 * @return string
	 */
	static public function getJavascriptCode($idSite, $piwikUrl)
	{
		$jsTag = file_get_contents( PIWIK_INCLUDE_PATH . "/core/Tracker/javascriptTag.tpl");
		$jsTag = nl2br(htmlentities($jsTag));
		$piwikUrl = preg_match('~^(http|https)://(.*)$~', $piwikUrl, $matches);
		$piwikUrl = @$matches[2];
		$jsTag = str_replace('{$idSite}', $idSite, $jsTag);
		$jsTag = str_replace('{$piwikUrl}', Piwik_Common::sanitizeInputValue($piwikUrl), $jsTag);
		$jsTag = str_replace('{$hrefTitle}', Piwik::getRandomTitle(), $jsTag);
		return $jsTag;
	}

	/**
	 * Generate a title for image tags
	 *
	 * @return string
	 */
	static public function getRandomTitle()
	{
		static $titles = array(
			'Web analytics',
			'Analytics',
			'Real time web analytics',
			'Real time analytics',
			'Open source analytics',
			'Open source web analytics',
			'Google Analytics alternative',
			'Open source Google Analytics',
			'Free analytics',
			'Analytics software',
			'Free web analytics',
			'Free web statistics',
		);
		$id = abs(intval(md5(Piwik_Url::getCurrentHost())));
		$title = $titles[ $id % count($titles)];
		return $title;
	}
	
	/**
	 * Number of websites to show in the Website selector
	 * 
	 * @return int
	 */
	static public function getWebsitesCountToDisplay()
	{
		$count = max(Zend_Registry::get('config')->General->site_selector_max_sites,
					Zend_Registry::get('config')->General->autocomplete_min_sites);
		return (int)$count;
	}

/*
 * Access
 */

	static public function getCurrentUserEmail()
	{
		if(!Piwik::isUserIsSuperUser())
		{
			$user = Piwik_UsersManager_API::getInstance()->getUser(Piwik::getCurrentUserLogin());
			return $user['email'];
		}
		$superuser = Zend_Registry::get('config')->superuser;
		return $superuser->email;
	}
	/**
	 * Get current user login
	 *
	 * @return string
	 */
	static public function getCurrentUserLogin()
	{
		return Zend_Registry::get('access')->getLogin();
	}

	/**
	 * Get current user's token auth
	 *
	 * @return string
	 */
	static public function getCurrentUserTokenAuth()
	{
		return Zend_Registry::get('access')->getTokenAuth();
	}

	/**
	 * Returns true if the current user is either the super user, or the user $theUser
	 * Used when modifying user preference: this usually requires super user or being the user itself.
	 *
	 * @param string $theUser
	 * @return bool
	 */
	static public function isUserIsSuperUserOrTheUser( $theUser )
	{
		try{
			self::checkUserIsSuperUserOrTheUser( $theUser );
			return true;
		} catch( Exception $e){
			return false;
		}
	}

	/**
	 * Check that current user is either the specified user or the superuser
	 *
	 * @param string $theUser
	 * @throws exception if the user is neither the super user nor the user $theUser
	 */
	static public function checkUserIsSuperUserOrTheUser( $theUser )
	{
		try{
			if( Piwik::getCurrentUserLogin() !== $theUser)
			{
				// or to the super user
				Piwik::checkUserIsSuperUser();
			}
		} catch( Piwik_Access_NoAccessException $e){
			throw new Piwik_Access_NoAccessException("The user has to be either the Super User or the user '$theUser' itself.");
		}
	}

	/**
	 * Returns true if the current user is the Super User
	 *
	 * @return bool
	 */
	static public function isUserIsSuperUser()
	{
		try{
			self::checkUserIsSuperUser();
			return true;
		} catch( Exception $e){
			return false;
		}
	}
	
	static public function isUserIsAnonymous()
	{
		return Piwik::getCurrentUserLogin() == 'anonymous';
	}

	static public function checkUserIsNotAnonymous()
	{
		if(self::isUserIsAnonymous())
		{
			throw new Exception(Piwik_Translate('General_YouMustBeLoggedIn'));
		}
	}

	/**
	 * Helper method user to set the current as Super User.
	 * This should be used with great care as this gives the user all permissions.
	 */
	static public function setUserIsSuperUser( $bool = true )
	{
		Zend_Registry::get('access')->setSuperUser($bool);
	}

	/**
	 * Check that user is the superuser
	 *
	 * @throws Exception if not the superuser
	 */
	static public function checkUserIsSuperUser()
	{
		Zend_Registry::get('access')->checkUserIsSuperUser();
	}

	/**
	 * Returns true if the user has admin access to the sites
	 *
	 * @param mixed $idSites
	 * @return bool
	 */
	static public function isUserHasAdminAccess( $idSites )
	{
		try{
			self::checkUserHasAdminAccess( $idSites );
			return true;
		} catch( Exception $e){
			return false;
		}
	}

	/**
	 * Check user has admin access to the sites
	 *
	 * @param mixed $idSites
	 * @throws Exception if user doesn't have admin access to the sites
	 */
	static public function checkUserHasAdminAccess( $idSites )
	{
		Zend_Registry::get('access')->checkUserHasAdminAccess( $idSites );
	}

	/**
	 * Returns true if the user has admin access to any sites
	 *
	 * @return bool
	 */
	static public function isUserHasSomeAdminAccess()
	{
		try{
			self::checkUserHasSomeAdminAccess();
			return true;
		} catch( Exception $e){
			return false;
		}
	}

	/**
	 * Check user has admin access to any sites
	 *
	 * @throws Exception if user doesn't have admin access to any sites
	 */
	static public function checkUserHasSomeAdminAccess()
	{
		Zend_Registry::get('access')->checkUserHasSomeAdminAccess();
	}

	/**
	 * Returns true if the user has view access to the sites
	 *
	 * @param mixed $idSites
	 * @return bool
	 */
	static public function isUserHasViewAccess( $idSites )
	{
		try{
			self::checkUserHasViewAccess( $idSites );
			return true;
		} catch( Exception $e){
			return false;
		}
	}

	/**
	 * Check user has view access to the sites
	 *
	 * @param mixed $idSites
	 * @throws Exception if user doesn't have view access to sites
	 */
	static public function checkUserHasViewAccess( $idSites )
	{
		Zend_Registry::get('access')->checkUserHasViewAccess( $idSites );
	}

	/**
	 * Returns true if the user has view access to any sites
	 *
	 * @return bool
	 */
	static public function isUserHasSomeViewAccess()
	{
		try{
			self::checkUserHasSomeViewAccess();
			return true;
		} catch( Exception $e){
			return false;
		}
	}

	/**
	 * Check user has view access to any sites
	 *
	 * @throws Exception if user doesn't have view access to any sites
	 */
	static public function checkUserHasSomeViewAccess()
	{
		Zend_Registry::get('access')->checkUserHasSomeViewAccess();
	}

/*
 * Current module, action, plugin
 */

	/**
	 * Returns the name of the Login plugin currently being used.
	 * Must be used since it is not allowed to hardcode 'Login' in URLs
	 * in case another Login plugin is being used.
	 *
	 * @return string
	 */
	static public function getLoginPluginName()
	{
		return Zend_Registry::get('auth')->getName();
	}

	/**
	 * Returns the plugin currently being used to display the page
	 *
	 * @return Piwik_Plugin
	 */
	static public function getCurrentPlugin()
	{
		return Piwik_PluginsManager::getInstance()->getLoadedPlugin(Piwik::getModule());
	}

	/**
	 * Returns the current module read from the URL (eg. 'API', 'UserSettings', etc.)
	 *
	 * @return string
	 */
	static public function getModule()
	{
		return Piwik_Common::getRequestVar('module', '', 'string');
	}

	/**
	 * Returns the current action read from the URL
	 *
	 * @return string
	 */
	static public function getAction()
	{
		return Piwik_Common::getRequestVar('action', '', 'string');
	}

	/**
	 * Helper method used in API function to introduce array elements in API parameters.
	 * Array elements can be passed by comma separated values, or using the notation
	 * array[]=value1&array[]=value2 in the URL.
	 * This function will handle both cases and return the array.
	 *
	 * @param $columns String or array
	 * @return array
	 */
	static public function getArrayFromApiParameter($columns)
	{
		return $columns === false
				? array()
				: (is_array($columns)
					? $columns
					: explode(',', $columns)
					);
	}

	/**
	 * Redirect to module (and action)
	 *
	 * @param string $newModule
	 * @param string $newAction
	 * @return bool false if the URL to redirect to is already this URL
	 */
	static public function redirectToModule( $newModule, $newAction = '', $parameters = array() )
	{
		$newUrl = 'index.php' . Piwik_Url::getCurrentQueryStringWithParametersModified(
					array('module' => $newModule, 'action' => $newAction)
					+ $parameters
			);
		Piwik_Url::redirectToUrl($newUrl);
	}

/*
 * Global database object
 */

	/**
	 * Create database object and connect to database
	 */
	static public function createDatabaseObject( $dbInfos = null )
	{
		$config = Zend_Registry::get('config');

		if(is_null($dbInfos))
		{
			$dbInfos = $config->database->toArray();
		}

		$dbInfos['profiler'] = $config->Debug->enable_sql_profiler;

		$db = null;
		Piwik_PostEvent('Reporting.createDatabase', $db);
		if(is_null($db))
		{
			$adapter = $dbInfos['adapter'];
			$db = Piwik_Db_Adapter::factory($adapter, $dbInfos);
		}
		Zend_Registry::set('db', $db);
	}

	/**
	 * Disconnect from database
	 */
	static public function disconnectDatabase()
	{
		Zend_Registry::get('db')->closeConnection();
	}

	/**
	 * Checks the database server version against the required minimum
	 * version.
	 *
	 * @see config/global.ini.php
	 * @since 0.4.4
	 * @throws Exception if server version is less than the required version
	 */
	static public function checkDatabaseVersion()
	{
		Zend_Registry::get('db')->checkServerVersion();
	}

	/**
	 * Check database connection character set is utf8.
	 *
	 * @return bool True if it is (or doesn't matter); false otherwise
	 */
	static public function isDatabaseConnectionUTF8()
	{
		return Zend_Registry::get('db')->isConnectionUTF8();
	}

/*
 * Global log object
 */

	/**
	 * Create log object
	 */
	static public function createLogObject()
	{
		$configAPI = Zend_Registry::get('config')->log;

		$aLoggers = array(
				'logger_api_call' => new Piwik_Log_APICall,
				'logger_exception' => new Piwik_Log_Exception,
				'logger_error' => new Piwik_Log_Error,
				'logger_message' => new Piwik_Log_Message,
			);

		foreach($configAPI as $loggerType => $aRecordTo)
		{
			if(isset($aLoggers[$loggerType]))
			{
				$logger = $aLoggers[$loggerType];

				foreach($aRecordTo as $recordTo)
				{
					switch($recordTo)
					{
						case 'screen':
							$logger->addWriteToScreen();
						break;

						case 'database':
							$logger->addWriteToDatabase();
						break;

						case 'file':
							$logger->addWriteToFile();
						break;

						default:
							throw new Exception("'$recordTo' is not a valid Log type. Valid logger types are: screen, database, file.");
						break;
					}
				}
			}
		}

		foreach($aLoggers as $loggerType =>$logger)
		{
			if($logger->getWritersCount() == 0)
			{
				$logger->addWriteToNull();
			}
			Zend_Registry::set($loggerType, $logger);
		}
	}

/*
 * Global config object
 */

	/**
	 * Create configuration object
	 *
	 * @param string $pathConfigFile
	 */
	static public function createConfigObject( $pathConfigFile = null )
	{
		$config = new Piwik_Config($pathConfigFile);
		Zend_Registry::set('config', $config);
		$config->init();
	}

/*
 * Global access object
 */

	/**
	 * Create access object
	 */
	static public function createAccessObject()
	{
		Zend_Registry::set('access', new Piwik_Access());
	}

/*
 * User input validation
 */

	/**
	 * Returns true if the email is a valid email
	 *
	 * @param string email
	 * @return bool
	 */
	static public function isValidEmailString( $email )
	{
		return (preg_match('/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9_.-]+\.[a-zA-Z]{2,4}$/', $email) > 0);
	}

	/**
	 * Returns true if the login is valid.
	 * Warning: does not check if the login already exists! You must use UsersManager_API->userExists as well.
	 *
	 * @param string $login
	 * @return bool or throws exception
	 */
	static public function checkValidLoginString( $userLogin )
	{
		$loginMinimumLength = 3;
		$loginMaximumLength = 100;
		$l = strlen($userLogin);
		if(!($l >= $loginMinimumLength
				&& $l <= $loginMaximumLength
				&& (preg_match('/^[A-Za-z0-9_.-@]*$/', $userLogin) > 0))
		)
		{
			throw new Exception(Piwik_TranslateException('UsersManager_ExceptionInvalidLoginFormat', array($loginMinimumLength, $loginMaximumLength)));
		}
	}

/*
 * Date / Timezone
 */

	/**
	 * Returns true if the current php version supports timezone manipulation
	 * (most likely if php >= 5.2)
	 *
	 * @return bool
	 */
	static public function isTimezoneSupportEnabled()
	{
		return
			function_exists( 'date_create' ) &&
			function_exists( 'date_default_timezone_set' ) &&
			function_exists( 'timezone_identifiers_list' ) &&
			function_exists( 'timezone_open' ) &&
			function_exists( 'timezone_offset_get' );
	}

/*
 * Database and table definition methods
 */

	/**
	 * Is the schema available?
	 *
	 * @return bool True if schema is available; false otherwise
	 */
	static public function isAvailable()
	{
		return Piwik_Db_Schema::getInstance()->isAvailable();
	}

	/**
	 * Get the SQL to create a specific Piwik table
	 *
	 * @param string $tableName
	 * @return string SQL
	 */
	static public function getTableCreateSql( $tableName )
	{
		return Piwik_Db_Schema::getInstance()->getTableCreateSql($tableName);
	}

	/**
	 * Get the SQL to create Piwik tables
	 *
	 * @return array of strings containing SQL
	 */
	static public function getTablesCreateSql()
	{
		return Piwik_Db_Schema::getInstance()->getTablesCreateSql();
	}

	/**
	 * Create database
	 *
	 * @param string $dbName
	 */
	static public function createDatabase( $dbName = null )
	{
		Piwik_Db_Schema::getInstance()->createDatabase($dbName);
	}

	/**
	 * Drop database
	 */
	static public function dropDatabase()
	{
		Piwik_Db_Schema::getInstance()->dropDatabase();
	}

	/**
	 * Create all tables
	 */
	static public function createTables()
	{
		Piwik_Db_Schema::getInstance()->createTables();
	}

	/**
	 * Creates an entry in the User table for the "anonymous" user.
	 */
	static public function createAnonymousUser()
	{
		Piwik_Db_Schema::getInstance()->createAnonymousUser();
	}

	/**
	 * Truncate all tables
	 */
	static public function truncateAllTables()
	{
		Piwik_Db_Schema::getInstance()->truncateAllTables();
	}

	/**
	 * Drop specific tables
	 *
	 * @param array $doNotDelete Names of tables to not delete
	 */
	static public function dropTables( $doNotDelete = array() )
	{
		Piwik_Db_Schema::getInstance()->dropTables($doNotDelete);
	}

	/**
	 * Names of all the prefixed tables in piwik
	 * Doesn't use the DB
	 *
	 * @return array Table names
	 */
	static public function getTablesNames()
	{
		return Piwik_Db_Schema::getInstance()->getTablesNames();
	}

	/**
	 * Get list of tables installed
	 *
	 * @param bool $forceReload Invalidate cache
	 * @param string $idSite
	 * @return array Tables installed
	 */
	static public function getTablesInstalled($forceReload = true,  $idSite = null)
	{
		return Piwik_Db_Schema::getInstance()->getTablesInstalled($forceReload, $idSite);
	}
}
