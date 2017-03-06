<?php
require_once dirname(__FILE__) . "/drive_library.php";

// Example call
$inputFilePath = "http://www.ist-apps.com/RetrieveExportFeed.csv?Id=80003a8a-5311-4107-a681-9761ac338d52";
$todayDate = date("Y-m-d");
$outputFilePath = dirname(__FILE__) . "/MilletsShoppingFeed" . $todayDate . ".csv";
convertFile($inputFilePath, $outputFilePath);
$authenticationIniPath = dirname(__FILE__) . '/RDAuth.ini';
$client = MakeDriveClient($authenticationIniPath);
$folderId = "0B2Gq-FRFcvNiNFZHTHJ0THdVVW8";
// $mimeType = "application/vnd.google-apps.spreadsheet";
$mimeType = "text/csv";
UploadFileToDrive($client, $folderId, $outputFilePath, true, $mimeType);
unlink($outputFilePath);

/*
 * Converts a file downloaded from the AdWords interface (a tab separated
 * file encoded with UCS-2LE) to a UTF-8 CSV file.
 * Throws an exception if either file cannot be opened.
 * @param string $inputFilePath the file from AdWords
 * @param string $outputFilePath the file to be written
 * @param boolean $includeUTF8Bom If true then the output file will be
 * given a UTF-8 BOM. This may get in the way of PHP reading the first cell
 * but makes the file easier to open in Excel.
 */
function convertFile($inputFilePath, $outputFilePath, $includeUTF8Bom = false) {
	
	for ($i=0; $i<5; $i++) {
		$inputHandle = fopen($inputFilePath, "r");
		if ($inputHandle !== false) {
			break;
		}
		echo "Attempt " . $i . " at getting the file failed!";
		sleep(5);
	}

	if ($inputHandle === false) {
		throw new Exception("Input file " . $inputFilePath . " could not be opened.");
	}
	
	$outputHandle = fopen($outputFilePath, "w");
	if ($outputHandle === false) {
		throw new Exception("Output file " . $outputFilePath . " could not be opened.");
	}
	if ($includeUTF8Bom) {
		fwrite($outputHandle, "\xEF\xBB\xBF"); // UTF-8 BOM
	}
	
	$firstLine = fgets($inputHandle);
	$bom = substr($firstLine,0,3); // For some reason lines can't be converted without appending this.
	
	$encoding = detectEncoding($firstLine);
	if (array_search($encoding, array('UTF-32BE','UTF-32LE','UTF-16BE','UTF-16LE')) !== false) {
		$bom = substr($firstLine,0,3); // For some reason lines can't be converted without appending this.
		$trimLength = 4;
		$firstLine = mb_convert_encoding($firstLine, "UTF-8", $encoding); // Converts the line to UTF-8
		$firstLine = substr($firstLine, 3); // Gets rid of the BOM
	} else {
		$bom = "";
		$trimLength = 0;
		$firstLine = mb_convert_encoding($firstLine, "UTF-8", $encoding);
	}
	
	// Check for the delimiter
	if (strpos($firstLine, "\t") !== false) {
		$delimiter = "\t";
	} elseif (strpos($firstLine, ",") !== false) {
		$delimiter = ",";
	} else {
		// First line may just be a title, so we check the next line for a delimiter
		fputcsv($outputHandle, array($firstLine));
		$firstLine = mb_convert_encoding($bom.fgets($inputHandle), "UTF-8", $encoding);
		$firstLine = substr($firstLine, $trimLength);
		if (strpos($firstLine, "\t") !== false) {
			$delimiter = "\t";
		} else {
			$delimiter = ",";
		}
	}
	
	$firstLine = str_getcsv($firstLine, $delimiter, '"', '"'); // Converts from tab separated text to array
	fputcsv($outputHandle, $firstLine); //Writes the array to the file
	
	while (!feof($inputHandle)) {
		$row = mb_convert_encoding($bom.fgets($inputHandle), "UTF-8", $encoding); // Converts the line to UTF-8
		$row = str_getcsv(substr($row, $trimLength), $delimiter, '"', '"'); // Gets rid of the BOM and converts to an array
		fputcsv($outputHandle, $row); // Writes to the output file
	}
	
	fclose($inputHandle);
	fclose($outputHandle);
}


function detectEncoding($string) {
	// Doesn't recognise UTF-8 without a BOM
	
	// From http://php.net/manual/en/function.mb-detect-encoding.php#91051
	// Unicode BOM is U+FEFF, but after encoded, it will look like this.
	define ('UTF32_BIG_ENDIAN_BOM'   , chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF));
	define ('UTF32_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00));
	define ('UTF16_BIG_ENDIAN_BOM'   , chr(0xFE) . chr(0xFF));
	define ('UTF16_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE));
	define ('UTF8_BOM'               , chr(0xEF) . chr(0xBB) . chr(0xBF));
	$first2 = substr($string, 0, 2);
	$first3 = substr($string, 0, 3);
	$first4 = substr($string, 0, 3);
	
	if ($first3 == UTF8_BOM) return 'UTF-8';
	elseif ($first4 == UTF32_BIG_ENDIAN_BOM) return 'UTF-32BE';
	elseif ($first4 == UTF32_LITTLE_ENDIAN_BOM) return 'UTF-32LE';
	elseif ($first2 == UTF16_BIG_ENDIAN_BOM) return 'UTF-16BE';
	elseif ($first2 == UTF16_LITTLE_ENDIAN_BOM) return 'UTF-16LE';
	
	$encoding = mb_detect_encoding($string);
	if ($encoding !==  false) {
		return $encoding;
	}
}

// Don't run the example if the file is being included.
if (__FILE__ != realpath($_SERVER['PHP_SELF'])) {
  return;
}

?>