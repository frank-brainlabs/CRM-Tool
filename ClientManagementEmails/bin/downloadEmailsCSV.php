<?php

require_once __DIR__ . "/../../drive_api/Drive API Standard Library/drive_library.php";

//$credentials = $argv[1];
//print_r(($credentials));

$authenticationIniPath = __DIR__ . "/../../drive_api/Drive API Standard Library/RDAuth.ini";

$client = MakeDriveClient($authenticationIniPath);
$fileId = "1zQttbXTlYvmxeAhxZrZ89R8QuAJJfYvfxoM2SXqWLKI";							
$isGoogleDoc = true;
$mimeType = 'text/csv';								
$fileExtension = '.csv';
$folderPath = __DIR__  . "/emailsToCheck";

print_r("Downloading latest client list to " . $folderPath . "\n");

DownloadFileFromDrive ($client, $fileId, $folderPath,$isGoogleDoc,$mimeType,$fileExtension);

print_r("Successfully downloaded latest client list to " . $folderPath . "\n");


