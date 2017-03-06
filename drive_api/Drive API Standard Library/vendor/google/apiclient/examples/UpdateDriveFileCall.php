<?php

require_once realpath(dirname(__FILE__) . '/../src/Google/autoload.php');
require_once dirname(__FILE__).'/Drive_API_std_library.php';
 
 $fileId = '1zuzMvVLXFLvWz6RdRZFmtPIQqdFVCFVTUKzsykLbvZc';
 $authenticationIniPath = 'DriveAuth.ini';
 $newFileName="C:\\Users\\Ellen\\Desktop\\drive_api_test_folder\\testupdate.csv";
 $client = MakeDriveClient($authenticationIniPath);
 
 updateFile($client, $fileId, $newFileName);