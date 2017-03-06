<?php

require_once __DIR__ . "/../../drive_api/Drive API Standard Library/drive_library.php";

$authenticationIniPath = __DIR__ . "/../../drive_api/Drive API Standard Library/RDAuth.ini";

print_r('Updating file on drive' . "\n");
$client = MakeDriveClient($authenticationIniPath);
$driveFileId = "0B2Gq-FRFcvNiMFM5QjRUSHhHcEU";
//$driveFileId = "0B_VG24-72cBOR21ReldoRmswdUE";
//$driveFileId = "0B14sHPPvWMm9dUJ3TnIyREVscW8";
$fileName = __DIR__ . "/combo_emails.csv";
updateFile($client, $driveFileId, $fileName);
print_r('Updated file on drive' . "\n");

