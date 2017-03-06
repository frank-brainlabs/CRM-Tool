<?php

require_once realpath(dirname(__FILE__) . '/vendor/autoload.php');

/*

require_once realpath(dirname(__FILE__) . '/../src/Google/autoload.php');
require_once dirname(__FILE__).'/Drive_API_std_library.php';

$authenticationIniPath = 'DriveAuth.ini';
$fileId = "1iVY_9YQXnle-DH5Mu6pWi5DZdnCWK2Z-Xbps8v17bj4";
$folderPath = "C:/drivetest/";
$isGoogleDoc = true;
$mimeType = 'text/csv';								
$fileExtension = '.csv';

$client = MakeDriveClient($authenticationIniPath);							
DownloadFileFromDrive ($client, $fileId, $folderPath,$isGoogleDoc,$mimeType,$fileExtension);

$folderId = "0B9Sa1DTsUdhuflBrbTlUVWctcldBWmNxQWIzY0Z1QWE3VjJyck5WcTFXQkVvWTZQQWRiY3M";
$filePath = "C:/drivetest/Spreadtest.csv";
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
	$client->addScope("https:/www.googleapis.com/auth/drive");
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
    sleep(1);
    // File's new content.
    $data = file_get_contents($newFileName);

    $additionalParams = array(
        'uploadType' => 'media',
        'data' => $data
    );

    // Send the request to the API.
    $updatedFile = $service->files->update($fileId, $file, $additionalParams);
    // print_r("The following file Successfully uploaded : $newFileName".PHP_EOL);
    return $updatedFile;
  } catch (Exception $e) {
    throw new Exception("An error occurred: " . $e->getMessage(), 1);
  }
}


/**
* Function to check the given folders exist in drive and creates them if not
* @param Google_Client client; a google client set up using client Id, Client Secret, URL Redirect and a Refresh Token
* @param String $parentId - the Id of the base folder
* @param String $locationPath - the location path of the folders eg Which/Trusted_Traders
* @return Bool $exists - whether the folder exists / has been created
*/
function checkLocation($client, $parentId, $locationPath) {
  $service = new Google_Service_Drive($client);

  $folders = explode("/", $locationPath);

  foreach ($folders as $folder) {
    $existingFolders = $service->children->listChildren($parentId);
    // print_r(($existingFolders));
    foreach ($existingFolders->getItems() as $item) {
      $id = $item->getId();
      // $fileService = new Google_Service_Drive($client);
      $itemTitle = $service->files->get($id)->title;

      if($itemTitle == $folder) {
        $parentId = $id;
        continue 2;
      }
    }

    $parentId = createFolder($client, $parentId, $folder);
  }

  return $parentId;
}

//Checks if the given filename exists in the given folder
function fileExistsInFolder($client, $parentId, $fileName) {
  $service = new Google_Service_Drive($client);
  $children = $service->children->listChildren($parentId);
  $items = $children->getItems();
  foreach ($items as $item) {
    $id = $item['id'];
    $title = $service->files->get($id)->title;
    if($title == $fileName) return $id;
  }
  return false;
}


/**
* Creates subfolder with the given name
* @param Google_Client client; a google client set up using client Id, Client Secret, URL Redirect and a Refresh Token
* @param String parentId - google id of the parent folder
* @param String folder - the name of the folder to create
* @return String id - the id of the new folder
*/
function createFolder($client, $parentId, $folder) {
    $newFolder = new Google_Service_Drive_DriveFile();
    $newFolder->setTitle($folder);
    $newFolder->setMimeType('application/vnd.google-apps.folder');

    $parent = new Google_Service_Drive_ParentReference();
    $parent->setId($parentId);
    $newFolder->setParents(array($parent));

    try {
      $service = new Google_Service_Drive($client);
      $createdFolder = $service->files->insert($newFolder, array('mimeType'=>'application/vnd.google-apps.folder'));
      // print_r($createdFolder->id);
      return $createdFolder->id;
    } catch(Exception $e) {
      // print_r($e->getMessage());
    }
}

function metadata($client, $fileId) {
  try {
    $service = new Google_Service_Drive($client);
    $file = $service->files->get($fileId);
    // print_r($file);
  } catch(Exception $e) {
    throw new Exception("An error occerrud: ".$e->getMessage(), 1);
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
  // print_r($file);
  $downloadUrl = $file->getDownloadUrl();
  $fileName =$file->title;
  $filePath = $folderPath.$fileName;
	
  if ($isGoogleDoc) {
	$downloadUrl = $file->getExportLinks()[$mimeType];
	$filePath = $folderPath.$fileExtension;										//file name can be changed here if required
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
    sleep(0.5);
  }

  fclose($handle);
  $client->setDefer(false);
  // print_r("The following file Successfully uploaded : $filePath".PHP_EOL);
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


/**
* Renames the file contained in a ZIP archive. Will only work if there is one file in the ZIP.
* @param String $ZipArchivePath - path of zipped file
* @param Number $index - the rename index of the file
* @param String $newName - new name of the file
* @return 0
*/
function zipRename($ZipArchivePath, $index, $newName) {
  $zip = new ZipArchive();
  $res = $zip->open($ZipArchivePath);
  if ($res === TRUE) {
    $zip->renameIndex($index, $newName);
    $zip->close();
  } else {
    echo 'failed, code:' . $res;
  }
  
  return 0;
}

/**
* Decompresses a ZIP Archive and writes the contents to the specified file path.
* @param String $fromZipArchive - the filepath of the file to unzip
* @param String $toExtractedFile - the filepath to extract the file to
*/
function DecompressFile($fromZipArchive, $toExtractedFile){
  $archive = new ZipArchive;

  if ($archive->open($fromZipArchive) === TRUE) {
    $archive->extractTo(dirname($toExtractedFile));
    $archive->close();
  }
  else {
    throw new Exception ("Decompress operation from ZIP file failed.");
  }
}


/**
 * Uploads a specified local file to specified folder within drive
 * @param Google_Client client; a google client set up using client Id, Client Secret, URL Redirect and a Refresh Token
 * @param string fileId; file Id of target file within Drive. Can be obtained by right clicking on file and pressing get link
 * @param string folderPath; folder to which to download the file. Adds the Drive filename to give a file path
 * @return null
 */
function DownloadSpreadSheetFromDrive ($client, $fileID, $filePath) {
  $service = new Google_Service_Drive($client);
  $file = $service->files->get($fileID);
  // print_r($file);
  $downloadUrl = $file->getExportLinks()['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
  
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
