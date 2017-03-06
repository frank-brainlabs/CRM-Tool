<?php

require_once realpath(dirname(__FILE__) . '/../src/Google/autoload.php');
require_once dirname(__FILE__).'/Drive_API_std_library.php';

$authenticationIniPath = 'DriveAuth.ini';
$fileId = "17QHzvFucr7u5UOvwx4DitWQLRb4fRuHcUiVEPyF7DH0";
$folderPath = "C:\\Users\\Ellen\\Desktop\\drive_api_test_folder\\";
$isGoogleDoc = true;
$mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';								
$fileExtension = '.xlsx';

$client = MakeDriveClient($authenticationIniPath);							
DownloadFileFromDrive ($client, $fileId, $folderPath,$isGoogleDoc,$mimeType,$fileExtension);