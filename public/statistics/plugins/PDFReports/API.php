<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: API.php 3576 2011-01-03 12:38:40Z matt $
 * 
 * @category Piwik_Plugins
 * @package Piwik_PDFReports
 */

class Piwik_PDFReports_API
{
	protected $reportsMetadata = array();
	static private $instance = null;

	const OUTPUT_PDF_INLINE_IN_BROWSER = 0; 
	const OUTPUT_PDF_DOWNLOAD = 1; 
	const OUTPUT_PDF_SAVE_ON_DISK = 2;

	/**
	 * @return Piwik_PDFReports_API
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
	 * Creates a new PDF report and schedules it. 
	 * 
	 * @param $idSite 
	 * @param $description Report description
	 * @param $period Schedule frequency: day, week or month 
	 * @param $sendToMe bool
	 * @param $additionalEmails Comma separated list of emails
	 * @param $reports Comma separated list of reports
	 * @return idReport generated
	 */
	public function addReport( $idSite, $description, $period, $reports, $emailMe = true, $additionalEmails = false)
	{
		Piwik::checkUserIsNotAnonymous();
		Piwik::checkUserHasViewAccess($idSite);
		$this->checkPeriod($period);
		$description = $this->checkDescription($description);
		$emailMe = (bool)$emailMe;
		$additionalEmails = $this->checkAdditionalEmails($additionalEmails);
		$reports = $this->checkAvailableReports($idSite, $reports);
		
		$db = Zend_Registry::get('db');
		$idReport = $db->fetchOne("SELECT max(idreport) + 1 
								FROM ".Piwik_Common::prefixTable('pdf'));
		if($idReport == false)
		{
			$idReport = 1;
		}
		$db->insert(Piwik_Common::prefixTable('pdf'),
					array( 
						'idreport' => $idReport,
						'idsite' => $idSite,
						'login' => Piwik::getCurrentUserLogin(),
						'description' => $description,
						'period' => $period,
						'email_me' => $emailMe,
						'additional_emails' => $additionalEmails,
						'reports' => $reports,
						'ts_created' => Piwik_Date::now()->getDatetime(),
						'deleted' => 0,
					));
		return $idReport;
	} 
	
	/**
	 * Updates an existing PDF report
	 * @see addReport()
	 */
	public function updateReport( $idReport, $idSite, $description, $period, $reports, $emailMe = true, $additionalEmails = false)
	{
		Piwik::checkUserHasViewAccess($idSite);
		$pdfReports = $this->getReports($idSite, $periodSearch = false, $idReport);
		$report = reset($pdfReports);
		$idReport = $report['idreport'];
		
		$this->checkPeriod($period);
		$description = $this->checkDescription($description);
		$emailMe = (bool)$emailMe;
		$additionalEmails = $this->checkAdditionalEmails($additionalEmails);
		$reports = $this->checkAvailableReports($idSite, $reports);
		
		Zend_Registry::get('db')->update( Piwik_Common::prefixTable('pdf'), 
					array(
						'description' => $description,
						'period' => $period,
						'email_me' => $emailMe,
						'additional_emails' => $additionalEmails,
						'reports' => $reports,
						),
					"idreport = '$idReport'"
		);	
		self::$cache = array();
	}
	
	/**
	 * Deletes a specific report
	 * 
	 * @param $idReport
	 */
	public function deleteReport($idReport)
	{
		$pdfReports = $this->getReports($idSite = false, $periodSearch = false, $idReport);
		$report = reset($pdfReports);
		Piwik::checkUserIsSuperUserOrTheUser($report['login']);
		
		Zend_Registry::get('db')->update( Piwik_Common::prefixTable('pdf'), 
					array(
						'deleted' => 1,
						),
						"idreport = '$idReport'"
		);	
		self::$cache = array();
	}
	
	// static cache storing reports
	public static $cache = array();
	
	/**
	 * Returns the list of PDF reports matching the passed parameters
	 * 
	 * @param $idSite If specified, will filter reports that belong to a specific idsite
	 * @param $period If specified, will filter reports that are scheduled for this period (day,week,month)
	 * @param $idReport If specified, will filter the report that has the given idReport 
	 * 
	 * @throws Exception if $idReport was specified but the report wasn't found
	 */
	public function getReports($idSite = false, $period = false, $idReport = false, $ifSuperUserReturnOnlySuperUserReports = false)
	{
		$cacheKey = (int)$idSite .'.'. (string)$period .'.'. (int)$idReport .'.'. (int)$ifSuperUserReturnOnlySuperUserReports;
		if(isset(self::$cache[$cacheKey]))
		{
			return self::$cache[$cacheKey];
		}

		$sqlWhere = '';
		$bind = array();
		
		// Super user gets all reports back, other users only their own
		if(!Piwik::isUserIsSuperUser()
			|| $ifSuperUserReturnOnlySuperUserReports)
		{
			$sqlWhere .= "AND login = ?";
			$bind[] = Piwik::getCurrentUserLogin();
		}
		
		if(!empty($period))
		{
			$this->checkPeriod($period);
			$sqlWhere .= " AND period = ? ";
			$bind[] = $period;
		}
		if(!empty($idSite))
		{
			Piwik::checkUserHasViewAccess($idSite);
			$sqlWhere .= " AND idsite = ?";
			$bind[] = $idSite;
		}
		if(!empty($idReport))
		{
			$sqlWhere .= " AND idreport = ?";
			$bind[] = $idReport;
		}
		
		$reports = Piwik_FetchAll("SELECT * 
    							FROM ".Piwik_Common::prefixTable('pdf')." 
    							WHERE deleted = 0
    								$sqlWhere", $bind);
    								
    	// When a specific report was requested and not found, throw an error
    	if($idReport !== false
    		&& empty($reports))
		{
			throw new Exception("Requested PDF report couldn't be found.");
		}
		// static cache
		self::$cache[$cacheKey] = $reports;
		
		return $reports;
	}
	
    /**
	 * Generates a PDF file in the browser output.
	 * 
	 * @param int $idSite 
	 * @param string $period 
	 * @param string $date
	 * @param string|false $language
	 * @param int $idReport If not passed, will generate a PDF containing all reports.
	 * @param bool $outputType 
	 * @param string $periodUsedFullReport If idReport == 0, will generate the report for the specified period
	 */
	public function generateReport($idReport, $date, $idSite = false, $language = false, $outputType = false, $periodUsedFullReport = 'day')
	{
		// Load specified language
		if(empty($language))
		{
			$language = Piwik_Translate::getInstance()->getLanguageDefault();
		}
    	Piwik_Translate::getInstance()->reloadLanguage($language);
		
		// Available reports
		static $reportMetadata = null;
		if(is_null($reportMetadata))
		{
			$reportMetadata = Piwik_API_API::getInstance()->getReportMetadata($idSite);
		}
		
		// Test template: include all reports
		if($idReport == 0)
		{
			$reports = $reportMetadata;
			$period = $periodUsedFullReport;
			$description = Piwik_Translate('PDFReports_DefaultPDFContainingAllReports');
		}
		// Template is a custom template
		else
		{
			$pdfReports = $this->getReports($idSite, $period = false, $idReport);
			$pdfReport = reset($pdfReports);
			$reportUniqueIds = explode(',', $pdfReport['reports']);
			
    		$description = $pdfReport['description'];
			$period = $pdfReport['period'];
    		
    		// We need to lookup which reports metadata are registered in this PDF
    		$reports = array();
    		foreach($reportMetadata as $metadata)
    		{
    			if(in_array($metadata['uniqueId'], $reportUniqueIds))
    			{
    				$reports[] = $metadata;
    			}
    		}
		}
		
		// PDF will display the first 30 rows, then aggregate other rows in a summary row 
    	$filterTruncateGET = Piwik_Common::getRequestVar('filter_truncate', false);
    	$_GET['filter_truncate'] = 30;
    	
    	$date = Piwik_Date::factory($date)->toString('Y-m-d');
        
    	$websiteName = $prettyDate = false;
        $processedReports = array();
        foreach ($reports as $action)
        {
        	$apiModule = $action['module'];
        	$apiAction = $action['action'];
        	$apiParameters = array();
        	if(isset($action['parameters']))
        	{
        		$apiParameters = $action['parameters'];
        	}
        	$report = Piwik_API_API::getInstance()->getProcessedReport($idSite, $date, $period, $apiModule, $apiAction, $apiParameters, $language);
        	$websiteName = $report['website'];
        	$prettyDate = $report['prettyDate'];
        	$processedReports[] = $report;
        }
        
        // Restore values
        if($filterTruncateGET !== false)
        {
        	$_GET['filter_truncate'] = $filterTruncateGET;
        }
	
        // Generates the PDF Report
		$pdf = new Piwik_PDFReports_PDFRenderer($websiteName, $prettyDate, $description, $language);
        $pdf->paintFirstPage();
        foreach($processedReports as $report)
        {
    		$pdf->setReport($report['metadata'], $report['reportData'], $report['columns'], $report['reportMetadata']);
    		$pdf->paintReport();
        }
        $outputFilename = 'PDF Report - '.$idReport.'.'.$date.'.'.$idSite.'.'.$language.'.pdf';	
        
        switch($outputType)
        { 
        	case self::OUTPUT_PDF_SAVE_ON_DISK:
        		$flagOutput = 'F';
        		$outputFilename = PIWIK_INCLUDE_PATH . '/tmp/' . $outputFilename;
        		@unlink($outputFilename);
    		break;
        	case self::OUTPUT_PDF_DOWNLOAD:
        		$outputFilename = "$websiteName - $prettyDate - $description.pdf";
        		$flagOutput = 'D';
    		break;
        	default:
        	case self::OUTPUT_PDF_INLINE_IN_BROWSER:
        		$flagOutput = 'I';
        	break;
        }
    	$pdf->Output($outputFilename, $flagOutput);
    	
    	if($outputType == self::OUTPUT_PDF_SAVE_ON_DISK)
    	{
    		return array(	$outputFilename,
    						$prettyDate,
    						$websiteName
			);
    	}
	}

	public function sendEmailReport($idReport, $idSite)
	{
		$reports = $this->getReports($idSite, $period = false, $idReport);
		$report = reset($reports);
		
		// Get user emails and languages 
		$emails = self::getEmailsFromString($report['additional_emails']);
		if($report['email_me'] == 1)
		{	
			if(Piwik::getCurrentUserLogin() == $report['login']
				|| Piwik::isUserIsSuperUser())
			{
				$emails[] = Piwik::getCurrentUserEmail();
			}
			else
			{
				$user = Piwik_UsersManager_API::getInstance()->getUser($report['login']);
				$emails[] = $user['email'];
			}
		}
		$language = Piwik_LanguagesManager_API::getInstance()->getLanguageForUser($report['login']);
		list($outputFilename, $prettyDate, $websiteName) = 
			$this->generateReport(
					$idReport, 
					Piwik_Date::now()->subPeriod(1, $report['period'])->toString(),
					$idSite,
					$language,
					$outputType = Piwik_PDFReports_API::OUTPUT_PDF_SAVE_ON_DISK
					);

		$this->sendReportEmailPdfAttached($emails, $outputFilename, $prettyDate, $websiteName, $report);
	}
	
	protected function sendReportEmailPdfAttached($emails, $outputFilename, $prettyDate, $websiteName, $report)
	{
		$periods = self::getPeriodToFrequency();
		$message  = Piwik_Translate('PDFReports_EmailHello');
		$message .= "\n" . Piwik_Translate('PDFReports_PleaseFindAttachedFile', array($periods[$report['period']], $websiteName));
		$subject = Piwik_Translate('General_Report') . ' '. $websiteName . " - ".$prettyDate;

		if(!file_exists($outputFilename))
		{
			throw new Exception("The PDF file wasn't found in $outputFilename");
		}
		$filename = basename($outputFilename);
		$handle = fopen($outputFilename, "r");
		$contents = fread($handle, filesize($outputFilename));
		fclose($handle);
		
		$mail = new Piwik_Mail();
		$mail->setSubject($subject);
		$mail->setBodyText($message);
		$fromEmailName = Piwik_Translate('PDFReports_PiwikReports');
		$fromEmailAddress = Zend_Registry::get('config')->General->noreply_email_address;
		$attachmentName = $subject;
		$mail->setFrom($fromEmailAddress, $fromEmailName);
		$mail->createAttachment(	$contents, 
									'application/pdf', 
									Zend_Mime::DISPOSITION_INLINE, 
									Zend_Mime::ENCODING_BASE64, 
									$attachmentName.'.pdf'
		);
		
		foreach ($emails as $email)
		{
			$mail->addTo($email);
    
    		try {
    			$mail->send();
    		} catch(Exception $e) {
    			
    			// If running from piwik.php with debug, we ignore the 'email not sent' error 
    			if(!isset($GLOBALS['PIWIK_TRACKER_DEBUG']) || !$GLOBALS['PIWIK_TRACKER_DEBUG'])
				{
					throw new Exception("An error occured while sending '$filename' ".
    								" to ". implode(', ',$mail->getRecipients()). ". 
    								Error was '". $e->getMessage()."'");
				}
    		}
    		$mail->clearRecipients();
		}
		// Update flag in DB
		Zend_Registry::get('db')->update( Piwik_Common::prefixTable('pdf'), 
					array( 'ts_last_sent' => Piwik_Date::now()->getDatetime() ),
					"idreport = " . $report['idreport']
		);	
		
		// If running from piwik.php with debug, do not delete the PDF after sending the email  
		if(!isset($GLOBALS['PIWIK_TRACKER_DEBUG']) || !$GLOBALS['PIWIK_TRACKER_DEBUG'])
		{
			unlink($outputFilename);
		}
	}
	
	private function checkAdditionalEmails($additionalEmails)
	{
		if(empty($additionalEmails))
		{
			return '';
		}
		$additionalEmails = self::getEmailsFromString($additionalEmails);
		foreach($additionalEmails as &$email)
		{
			$email = trim($email);
			if(!Piwik::isValidEmailString($email))
			{
				throw new Exception(Piwik_TranslateException('UsersManager_ExceptionInvalidEmail') . ' ('.$email.')');
			}
		}
		$additionalEmails = implode(',',$additionalEmails);
		return $additionalEmails;
	}

	static protected function getEmailsFromString($additionalEmails)
	{
		if(empty($additionalEmails))
		{
			return array();
		}
		$additionalEmails = explode(',', trim($additionalEmails));
		$additionalEmails = array_filter($additionalEmails, 'strlen');
		return $additionalEmails;
	}
	
	private function checkDescription($description)
	{
		return substr($description, 0, 250);
	}
	
	private function checkAvailableReports($idSite, $reports)
	{
		$availableReports = Piwik_API_API::getInstance()->getReportMetadata($idSite);
		$availableReportIds = array();
		foreach($availableReports as $report)
		{
			$availableReportIds[] = $report['uniqueId'];
		}
		$reports = explode(',', $reports);
		$reports = array_filter($reports, 'strlen');
		foreach($reports as $report)
		{
			if(!in_array($report, $availableReportIds))
			{
				throw new Exception("Report $report is unknown.");
			}
		}
		$reports = implode(',', $reports);
		return $reports;
	}
	
	private function checkPeriod($period)
	{
		$availablePeriods = array('day', 'week', 'month');
		if(!in_array($period, $availablePeriods))
		{
			throw new Exception(Piwik_Translate("Period schedule must be one of the following: " . implode(', ', $availablePeriods)));
		}
	}

	static public function getPeriodToFrequency()
	{
		$periods = array(
			'day' => Piwik_Translate('General_Daily'),
			'week' => Piwik_Translate('General_Weekly'),
			'month' => Piwik_Translate('General_Monthly'),
		);
		return $periods;
	}
}
