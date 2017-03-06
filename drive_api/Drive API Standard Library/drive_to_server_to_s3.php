<?php

require '/home/rasool/projects/drive_api/Drive API Standard Library/drive_library.php';
require '/home/rasool/projects/vendor/autoload.php';

$authenticationIniPath = 'RDAuth.ini';
$client = MakeDriveClient($authenticationIniPath);
$fileId = "0B2Gq-FRFcvNic3V4UktkNnA4dUk";
$folderPath = "/home/rasool/projects/drive_api/Drive API Standard Library/";

DownloadFileFromDrive($client, $fileId, $folderPath, False, 'text/plain', '.json');

$s3 = new Aws\S3\S3Client([
	'version' => 'latest',
	'region' => 'eu-west-1',
	'credentials' => [
		'key' => getenv('S3_KEY'),
		'secret' => getenv('S3_SECRET')
		],
	// 'debug' => true
]);

$s3->putObject(array(
	'Bucket' => 'rasooltestingbucket',
    'Key' => 'export/weekly-ppc-roundup.json',
	'SourceFile' => '/home/rasool/projects/drive_api/Drive API Standard Library/JustGivingAllRows.json'
));

echo $s3->listObjects([
	'Bucket' => 'rasooltestingbucket',
]);

unlink ('JustGivingAllRows.json');
