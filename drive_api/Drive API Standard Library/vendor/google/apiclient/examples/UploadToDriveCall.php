<?php

require_once realpath(dirname(__FILE__) . '/../src/Google/autoload.php');
require_once dirname(__FILE__).'/Drive_API_std_library.php';

$authenticationIniPath = 'DriveAuth.ini';
$folderId = "0B9Sa1DTsUdhufjhfNjlOZlNLd1JUWmR4UGFkdldqV0d4ZllSQkhpcVcwTEw1OUNFUFJJblU";
$filePath = "C:\\Users\\Ellen\\Desktop\\drive_api_test_folder\\testupload.xlsx";
$makeGoogleDoc = true;
$mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

$client = MakeDriveClient($authenticationIniPath);							
UploadFileToDrive ($client, $folderId, $filePath,$makeGoogleDoc,$mimeType);