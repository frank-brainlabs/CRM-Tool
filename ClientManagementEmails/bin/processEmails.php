<?php
require_once __DIR__ ."/../vendor/autoload.php";

use Brainlabs\Gmailer\Gmailer;

date_default_timezone_set("Europe/London");


function main($argv){

	$totalStartTime = microtime(true); 

	$emailsFilePath = __DIR__ . "/emailsToCheck.csv";
	$emails = getEmails($emailsFilePath);
	print_r("got emails");

	$credentialsDir = __DIR__ . "/../valid_credentials/";
	$credentials = $argv[1];
	$creds = $credentialsDir . $credentials;
	$credentialsName = join('_', explode('.', $credentials, -1));

	$gmailer = new Gmailer($creds);

	$dateRanges = array( "newer_than:5d", "older_than:5d newer_than:10d", "older_than:10d newer_than:15d", "older_than:15d newer_than:20d", 	"older_than:20d newer_than:25d", "older_than:25d newer_than:30d", "older_than:30d newer_than:35d", "older_than:35d newer_than:40d", "older_than:40d newer_than:45d", "older_than:45d newer_than:50d", "older_than:50d newer_than:55d", "older_than:55d newer_than:60d", "older_than:60d newer_than:65d", "older_than:65d newer_than:70d", "older_than:70d newer_than:75d", "older_than:75d newer_than:80d", "older_than:80d newer_than:85d", "older_than:85d newer_than:90d", );

	print_r("counting emails....");
	print_r("\n");
	$finalList = countEmails($emails, $gmailer, $dateRanges);
	print_r("counted emails");
	print_r("\n");

	$totalEndTime = microtime(true);
	$totalExecutionTime = ($totalEndTime - $totalStartTime)/60;
	print_r("Total Execution Time: ".$totalExecutionTime." Mins on " . $credentials . "\n");

	outputToCsv($credentialsName, $finalList, $dateRanges);
	print_r("outputed to csv");
}


function getEmails($filePath) {
	$emails = array();
	$handle = fopen($filePath, 'r');
	while(! feof($handle)) {
		$data = fgetcsv($handle);
		$email = $data[0];
		if ($email == '') continue;
		if (in_array($email, $emails)) continue;
		if ($email[0] == '@') {
			if (in_array('*'.$email, $emails)) continue;
			$emails[] = '*'.$email;
		} else {
		$emails[] = $email;
		}

	}

	return $emails;

}


function countEmails($emails, $gmailer, $dateRanges){

	$finalList = array();
	$numberOfTries = 5;

	foreach ($emails as $email) {
		print_r($email);
		print_r("\n");
	 	if (!isset($finalList[$email])) $finalList[$email] = array();
		foreach ($dateRanges as $dateRange) {
			print_r($dateRange);
			print_r("\n");
			$success = false;
			$i = 0;
			while ($success == false) {
				try {
					$messages = $gmailer->queryMessages($dateRange . " {cc:" . $email . " to:" . $email . " from:" . $email ."}");
					$messageIds = filterMessages($messages);
					print_r("successfully filetered messages");
					print_r("\n");
					// print_r('success'.PHP_EOL);
					$success = true;
				} catch (Exception $e) {
					print_r('Failure to query messages for the ' . $i . 'th time'.PHP_EOL);
					print_r($email . "\n");
					print_r('Current on ' . $credentials . "s credentials".PHP_EOL);
					print_r('And on ' . $dateRange . " date range".PHP_EOL);
					print_r('reason is ' . $e->getMessage() . PHP_EOL);
					$i++;
					sleep(mt_rand(10, 20) * $i);
				}
				if ($i == $numberOfTries) {
					print_r('Reached max number of tries, giving up'.PHP_EOL);
					break;
				}
			}
			sleep(0.5);
			if (!isset($finalList[$email][$dateRange])) $finalList[$email][$dateRange] = array();
			foreach ($messageIds as $id) {
				if (in_array($id, $finalList[$email][$dateRange])) continue;
				else $finalList[$email][$dateRange][] = $id;
			}
		}
	}

	return $finalList;

}


function filterMessages($messages) {
	$substringToReplace = array("'", '"', ",", " ");
	$filteredIds = [];
	foreach ($messages as $message) {
		$payload = $message->getPayload();
		$headers = $payload['headers'];
		foreach ($headers as $header) {
          $val = $header["value"];
          if (strpos($val, "h=mime-version:message-id:date:subject:from:to") !== false ||
          	  strpos($val, "@docs-share.google.com") !== false) continue 2;
        }
        $time = $message->getTimeSent();
        $snip = $message->getSnippet();
        $filteredIds[] = $time . '^' . str_replace($substringToReplace, "", substr($snip, 0, 3));
	}
	return $filteredIds;
}


function outputToCsv($credentialsName, $finalList, $dateRanges){

	$fileName = __DIR__ . '/../outputs/'.$credentialsName.'_emails.csv';
	//$fileName = __DIR__ . '/../outputs/test_emails.csv';
	$handle = fopen($fileName, 'w');
	foreach ($finalList as $email => $dateRanges) {
		$rowToInsert = array($email);
		foreach ($dateRanges as $ids) {
			$rowToInsert[] = json_encode($ids);
		}
		fputcsv($handle, $rowToInsert);
	}
}


try {
	main($argv);
} catch (Exception $e) {
  printf("An error has occurred: %s\n", $e->getMessage());
}