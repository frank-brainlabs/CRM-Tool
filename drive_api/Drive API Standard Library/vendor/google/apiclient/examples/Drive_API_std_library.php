<?php

/*

require_once realpath(dirname(__FILE__) . '/../src/Google/autoload.php');
require_once dirname(__FILE__).'/Drive_API_std_library.php';

$authenticationIniPath = 'DriveAuth.ini';
$fileId = "1iVY_9YQXnle-DH5Mu6pWi5DZdnCWK2Z-Xbps8v17bj4";
$folderPath = "C:\\drivetest\\";
$isGoogleDoc = true;
$mimeType = 'text/csv';								
$fileExtension = '.csv';

$client = MakeDriveClient($authenticationIniPath);							
DownloadFileFromDrive ($client, $fileId, $folderPath,$isGoogleDoc,$mimeType,$fileExtension);

$folderId = "0B9Sa1DTsUdhuflBrbTlUVWctcldBWmNxQWIzY0Z1QWE3VjJyck5WcTFXQkVvWTZQQWRiY3M";
$filePath = "C:\\drivetest\\Spreadtest.csv";
$mimeType = 'text/csv';																						
UploadFileToDrive($client,$folderId,$filePath,true,$mimeType);

*/

/**
 * Creates a client using oauth credentials in order to use drive services
 * @param string authenticationIniPath; path to the file DriveAuth.ini
 * @return Google_client client; a google client that can be used to set up api services (e.g. drive, youtube, books, maps)
 */
function MakeDriveClient($authenticationIniPath) {
	
	$driveCredentials = parse_ini_file($authenticationIniPath);
	
	$client = new Google_Client();
	$client->setClientId($driveCredentials["client_id"]);
	$client->setClientSecret($driveCredentials["client_secret"]);
	$client->setRedirectUri($driveCredentials["redirect_uri"]);
	$client->addScope("https://www.googleapis.com/auth/drive");
	$client->refreshToken($driveCredentials["refresh_token"]);
	
	return $client;
	
}


/**
 * Uploads a specified local file to specified folder within drive
 * @param Google_Client client; a google client set up using client Id, Client Secret, URL Redirect and a Refresh Token
 * @param string fileId; file Id of target file within Drive. Can be obtained by right clicking on file and pressing get link
 * @param string newFileName; file path for the file to upload the contents of. These contents will replace the current content of the file specified by $fileId
 * @return null
 */
function updateFile($client, $fileId, $newFileName) {
  try {
		$service = new Google_Service_Drive($client);
    // First retrieve the file from the API.
    $file = $service->files->get($fileId);

    // File's new content.
    $data = file_get_contents($newFileName);

    $additionalParams = array(
				'uploadType' => 'media',
        'data' => $data
    );

    // Send the request to the API.
    $updatedFile = $service->files->update($fileId, $file, $additionalParams);
    return $updatedFile;
  } catch (Exception $e) {
    print "An error occurred: " . $e->getMessage();
  }
}


/**
 * Uploads a specified local file to specified folder within drive
 * @param Google_Client client; a google client set up using client Id, Client Secret, URL Redirect and a Refresh Token
 * @param string fileId; file Id of target file within Drive. Can be obtained by right clicking on file and pressing get link
 * @param string folderPath; folder to which to download the file. Adds the Drive filename to give a file path
 * @param bool isGoogleDoc; set as false if file is already in its download format, and true if it is a google doc
 * @param string mimeType; optional parameter - if file is stored on drive as a google document, specify a MIME type to support a conversion to a specific file type. Defaults to plain text
 * @param string fileExtension; optional parameter - if file is stored on drive as a google document, specify a file extension matching the MIME type
 * @return null
 */
function DownloadFileFromDrive ($client, $fileID, $folderPath, $isGoogleDoc, $mimeType = 'text/plain', $fileExtension = ".txt") {
   
  $service = new Google_Service_Drive($client);
  $file = $service->files->get($fileID);
  $downloadUrl = $file->getDownloadUrl();
  $fileName =$file->title;
  $filePath = $folderPath.$fileName;
	
  if ($isGoogleDoc) {
	$downloadUrl = $file->getExportLinks()[$mimeType];
	$filePath = $folderPath.$fileName.$fileExtension;										//file name can be changed here if required
	}
  
  if ($downloadUrl) {
		$request = new Google_Http_Request($downloadUrl, 'GET', null, null);
		$httpRequest = $service->getClient()->getAuth()->authenticatedRequest($request);
		if ($httpRequest->getResponseHttpCode() == 200) {
			$fileContents = $httpRequest->getResponseBody();
			file_put_contents($filePath,$fileContents);	  
		}
  }
	
  return null;
	
}


/**
 * Uploads a specified local file to specified folder within drive
 * @param Google_Client client; a google client set up using client Id, Client Secret, URL Redirect and a Refresh Token
 * @param string folderId; folder Id of target file within Drive. Can be obtained by right clicking on folder and pressing get link
 * @param string filePath; file path to file for upload
 * @param bool makeGoogleDoc; set as false if file is to be uploaded as normal, and true if it is to be stored as a google doc
 * @param string mimeType; optional parameter - if file is to be stored on drive as a google document, specify a MIME type to support a conversion to a specific google doc type. Defaults to plain text
 * @return null
 */
function UploadFileToDrive ($client, $folderId, $filePath,$makeGoogleDoc,$mimeType = 'text/plain') {
	
  $service = new Google_Service_Drive($client);
  $file = new Google_Service_Drive_DriveFile();
  $file->title = basename($filePath);
	if ($makeGoogleDoc) {
		$fullFileName = basename($filePath);
		$googleDocName = explode(".",$fullFileName);
		$file->title = $googleDocName[0];
	}
  $parent = new Google_Service_Drive_ParentReference();
  $parent->setId($folderId);
  $file->setParents(array($parent));
  $chunkSizeBytes = 1 * 1024 * 1024;

  // Call the API with the media upload, defer so it doesn't immediately return.
  $client->setDefer(true);
  $request = $service->files->insert($file, array('convert' => $makeGoogleDoc));

  // Create a media file upload to represent our upload process.
  $media = new Google_Http_MediaFileUpload(
		$client,
		$request,
		$mimeType,
		null,
		true,
		$chunkSizeBytes
  );
  $media->setFileSize(filesize($filePath));

  // Upload the various chunks. $status will be false until the process is
  // complete.
  $status = false;
  $handle = fopen($filePath, "rb");
  while (!$status && !feof($handle)) {
    $chunk = readVideoChunk($handle, $chunkSizeBytes);
    $status = $media->nextChunk($chunk);
  }

  fclose($handle);
  return null;
}


/**
 * Uploads a specified local file to specified folder within drive
 * @param pointer handle; the pointer of the current position within a binary file
 * @param int chunkSize; specifies the number of bytes to take from the binary file for each chunk
 * @return string giantChunk, a chunk of the binary data to be uploaded
 */
function readVideoChunk ($handle, $chunkSize)
{
    $byteCount = 0;
    $giantChunk = "";
    while (!feof($handle)) {
        // fread will never return more than 8192 bytes if the stream is read buffered and it does not represent a plain file
        $chunk = fread($handle, 8192);
        $byteCount += strlen($chunk);
        $giantChunk .= $chunk;
        if ($byteCount >= $chunkSize)
        {
            return $giantChunk;
        }
    }
    return $giantChunk;
}


/*mime types supported by drive download/upload and their file extensions
	Google doc => various MIME types : https://developers.google.com/drive/web/manage-downloads (see Downloading Google Documents section)
	"xls" =>'application/vnd.ms-excel',
    "xlsx" =>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    "xml" =>'text/xml',
    "ods"=>'application/vnd.oasis.opendocument.spreadsheet',
    "csv"=>'text/csv',
    "tmpl"=>'text/plain',
    "pdf"=> 'application/pdf',
    "php"=>'application/x-httpd-php',
    "jpg"=>'image/jpeg',
    "png"=>'image/png',
    "gif"=>'image/gif',
    "bmp"=>'image/bmp',
    "txt"=>'text/plain',
    "doc"=>'application/msword',
    "js"=>'text/js',
    "swf"=>'application/x-shockwave-flash',
    "mp3"=>'audio/mpeg',
    "zip"=>'application/zip',
    "rar"=>'application/rar',
    "tar"=>'application/tar',
    "arj"=>'application/arj',
    "cab"=>'application/cab',
    "html"=>'text/html',
    "htm"=>'text/html',
    "default"=>'application/octet-stream',
    "folder"=>'application/vnd.google-apps.folder'	
*/