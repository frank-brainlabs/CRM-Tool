<?php
require_once __DIR__ ."/../vendor/autoload.php";

// print_r($authenticationIniPath);

$totalStartTime = microtime(true); 

$emailsFilePath = __DIR__ . "/emailsToCheck.csv";

$emails = getEmails($emailsFilePath);
// $emails = ["petra.studholme@simplybusiness.co.uk"];

// print_r("There are " . count($emails) . " to go through \n");

use Brainlabs\Gmailer\Gmailer;

$numberOfTries = 5;

date_default_timezone_set("Europe/London");

$credentialsDir = __DIR__ . "/../credentials/";
$credentials = $argv[1];

$dateRanges = array(
// "newer_than:1d",
// "newer_than:2d",
// "newer_than:3d",
	"newer_than:5d",
	"older_than:5d newer_than:10d",
	"older_than:10d newer_than:15d",
	"older_than:15d newer_than:20d",
	"older_than:20d newer_than:25d",
	"older_than:25d newer_than:30d",
	"older_than:30d newer_than:35d",
	"older_than:35d newer_than:40d",
	"older_than:40d newer_than:45d",
	"older_than:45d newer_than:50d",
	"older_than:50d newer_than:55d",
	"older_than:55d newer_than:60d",
	"older_than:60d newer_than:65d",
	"older_than:65d newer_than:70d",
	"older_than:70d newer_than:75d",
	"older_than:75d newer_than:80d",
	"older_than:80d newer_than:85d",
	"older_than:85d newer_than:90d",
	);

$finalList = array();

$creds = $credentialsDir . $credentials;
// print_r($creds);


foreach ($emails as $email) {
	// print_r($email . "\n");
	if (!isset($finalList[$email])) $finalList[$email] = array();
	foreach ($dateRanges as $dateRange) {
		$gmailer = new Gmailer($creds);
		// print_r($dateRange . "\n");
		$success = false;
		$i = 0;
		while ($success == false) {
			try {
				$messages = $gmailer->queryMessages($dateRange . " {cc:" . $email . " to:" . $email . " from:" . $email ."}");
				$messageIds = filterMessages($messages);
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

$totalEndTime = microtime(true);
$totalExecutionTime = ($totalEndTime - $totalStartTime)/60;
print_r("Total Execution Time: ".$totalExecutionTime." Mins on " . $credentials . "\n");

// print_r($finalList);
$credentialName = join('_', explode('.', $credentials, -1));

$fileName = __DIR__ . '/../outputs/'.$credentialName.'_emails.csv';
$handle = fopen($fileName, 'w');
foreach ($finalList as $email => $dateRanges) {
	$rowToInsert = array($email);
	foreach ($dateRanges as $ids) {
		$rowToInsert[] = json_encode($ids);
	}
	fputcsv($handle, $rowToInsert);
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

?>
