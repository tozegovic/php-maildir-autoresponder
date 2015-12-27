<?php
/*
	php-maildir-autoresponder - the PHP auto responder for Maildir

    Copyright © 2015 - Author:
    (c) 2015 Todor Ozegovic

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
    
    Version 1.1
*/
	
    ini_set('display_errors', true);
    error_reporting( E_ALL );
    
    ######################################
    # Check PHP version #
    ######################################
    
	if ( version_compare( PHP_VERSION, "5.0.0" ) == - 1 )
	{
		echo "Error, you are currently not running PHP 5 or later. Exiting.\n";
		exit;
	}
    
    ######################################
    # Configuration #
    ######################################
    /* General */

	$conf['dir_with_users'] = "/home";
	$conf['default_domain'] = "mydomainname.com";

    /* Logging */
    $conf['log_file_path'] = "/var/log/php-maildir-autoresponder";
    $conf['write_log'] = true;

	$conf['create_user_settings_from_default'] = 0;
	$defaultUserSettings = [
			'enabled' => 0,
			'email' => "jdoe@mydomainname.com",
			'descname' => "John Doe",
			'from' => "12/20/2015",
			'to' => "01/03/2016",
			'subject' => "Out of Office: %s",
			'message' => <<<EOT
I will be out of office from 12/20/2015 until 1/3/2016.
I will get back to you as soon as I can.

Thanks

EOT
	];


    
    class Logger
    {
		var $logfile;
		var $str;
		function addLine($str)
		{
		    $str = date("Y-m-d h:i:s")." ".$str;
		    $this->str .= "\n$str";
		    echo $str."\n";
		}
		
		function writeLog(&$conf)
		{
		    if (! $conf['write_log'] ) return;
		    
		    if (is_writable($conf['log_file_path']))
		    {
		    	$this->addLine("--------- End execution ------------");
	   	    	if (!$handle = fopen($conf['log_file_path'], 'a'))
	   	    	{
	                echo "Cannot open file ({$conf['log_file_path']})";
	                exit;
	            }
	
	            if (fwrite($handle, $this->str) === FALSE)
	            {
	                echo "Cannot write to file)";
	                exit;
	            }
	            else
	            {
					echo "Wrote log successfully.";
		    	}
	
	            fclose($handle);
	
		  }
		  else
		  {
			echo "Error: The log file is not writeable.\n";
			echo "The log has not been written.\n";
		  }
		}
    }
    
    ######################################
    # Create log object #
    ######################################
    $log = new Logger();
    
    ######################################
    # function endup() #
    ######################################
    function endup(&$log, &$conf)
    {
		$log->writeLog($conf);
		exit;
    }


	// function extract_email
	// thanks to http://stackoverflow.com/users/316941/wom
	// source http://stackoverflow.com/questions/4621660/regex-get-email-handle-from-email-address
	function extract_email($email_string) {
		preg_match("/<?([^<]+?)@([^>]+?)>?$/", $email_string, $matches);
		return $matches[1] . "@" . $matches[2];
	}

	// scan directory that contains all user directories
	foreach(scandir($conf['dir_with_users']) as $oneUserDir) {
		$oneUserDirPath = $conf['dir_with_users'] . '/' . $oneUserDir;

		// process only directories that have Maildir subdirectory
		if ( is_dir($oneUserDirPath) && $oneUserDir != '.' && $oneUserDir != '..' && is_dir($oneUserDirPath."/Maildir")) {
			$log->addLine("Checking settings for user : $oneUserDir");

			// load settings if exist and create a template setting file if none exist
			$userAutoresponseSettingsFile = $oneUserDirPath."/php-maildir-autoresponder.json";
			if( !file_exists($userAutoresponseSettingsFile)) {

				if(	$conf['create_user_settings_from_default'] === 1){
					// create default setting file that can be used as a template
					$userSettings = $defaultUserSettings;
					$userSettings['email'] = $oneUserDir."@".$conf['default_domain'];
					$userSettings['descname']  = $userSettings['email'];

					file_put_contents($userAutoresponseSettingsFile,
							json_encode($userSettings,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
					$log->addLine("Created default settings file for user : $userAutoresponseSettingsFile");
				}
			}else{

				$jsonStringSettings = file_get_contents($userAutoresponseSettingsFile);
				$userSettings = json_decode($jsonStringSettings, true);

				// skip this directory if autoresponder is disabled
				if($userSettings['enabled'] !== 1){
					$log->addLine("Autoresponder disabled for : $oneUserDir");
					continue;
				}

				// skip this directory if active window is not now
				$fromTime = strtotime($userSettings['from']);
				$toTime = strtotime($userSettings['to']. ' +1 day');
				if( (time() < $fromTime) || (time() > $toTime) ){
					$log->addLine("Outside active window from: ".date("Y-m-d",$fromTime)."  to: ".date("Y-m-d",$toTime));
					continue;
				}

				$log->addLine("Checking mail for user : $oneUserDir");

				// process all emails in Maildir/new directory
				foreach(scandir($oneUserDirPath."/Maildir/new/") as $oneEmail) {
					$oneEmailPath = $oneUserDirPath."/Maildir/new/".$oneEmail;
					if (is_file($oneEmailPath)) {

						// Reading return address
						$emailFile = file($oneEmailPath);

						unset($address);
						unset($returnpath);
						foreach ($emailFile as $line)
						{
							$line = trim($line);

							if (( !isset($returnpath)) && (substr($line, 0, 12) == 'Return-Path:'))
							{
								$returnpath = extract_email($line);
							}

							if (( !isset($address)) && (substr($line, 0, 5) == 'From:') && strstr($line,"@"))
							{
								$address = extract_email($line);
							}

							if (( !isset($subject)) && (substr($line, 0, 8) == 'Subject:'))
							{
								$subject = substr($line, strpos($line, ':') + 1);
							}
						}
						if ( !filter_var($address, FILTER_VALIDATE_EMAIL) ) {
	//						$log->addLine("Invalid From address: $address");

							if (filter_var($returnpath, FILTER_VALIDATE_EMAIL)) {
								$address = $returnpath;
							}else{
								$log->addLine("Invalid Reply-To address: $returnpath");
							}
						}

						// skip email if there is no properly formatted return address
						if ( !filter_var($address, FILTER_VALIDATE_EMAIL) ) {
							$log->addLine("Unable to extract return address");
							continue;
						}
						if(
								(strpos($address, $userSettings['email']) !== false)
								|| (strpos($address, "root@") !== false)
								|| (strpos($address, "admin@") !== false)
								|| (strpos($address, "webadmin@") !== false)
						){

							$log->addLine("System generated email that need no reply: $address");
							continue;
						}

						$log->addLine("Return address : $address");
						$log->addLine("Subject : $subject");

						$responseSubject = sprintf($userSettings['subject'], $subject );
						$responseMessage = $userSettings['message'];
						$responseHeaders = "From: ".$userSettings['descname']."<".$userSettings['email'].">";

						$sent = mail($address, $responseSubject, $responseMessage, $responseHeaders);

						if( !$sent) {
							$log->addLine("Autoresponse was not sent. Something went wrong");
							continue;
						}

						sleep(1); // necessary for windows to allow rename()
						$oneEmailPath = $oneUserDirPath."/Maildir/new/".$oneEmail;

						$ret = rename($oneUserDirPath."/Maildir/new/".$oneEmail, $oneUserDirPath."/Maildir/cur/".$oneEmail.":2,");
						if( $ret === false ){
							$log->addLine("ERROR: file could not be moved to cur: ".$oneUserDirPath."/Maildir/new/".$oneEmail);

						}

					}
				}


			}
		}
	}
	echo "End execution."; 
?>