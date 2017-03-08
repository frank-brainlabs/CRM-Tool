<?php

require_once __DIR__ ."/../vendor/autoload.php";
date_default_timezone_set("Europe/London");

use Brainlabs\Gmailer\Gmailer;

$allCredentialsDir = __DIR__ . "/../all_credentials/";
$credentials = array_diff(scandir($allCredentialsDir), array('..', '.'));

$validCredentialsDir = __DIR__ . "/../valid_credentials/";
array_map('unlink', glob($validCredentialsDir . "*"));

$borked_credentials = array();

$numberOfCredentialsTested = 0;

foreach ($credentials as $creds) {
	if ($numberOfCredentialsTested % 10 == 0) print_r('Tested ' . $numberOfCredentialsTested . ' credentials...'.PHP_EOL);
	$removeJson = join('_', explode('.', $creds, -1));
	$credentialName = join('.', explode('_', $removeJson));
	try {
		$gmailer = new Gmailer($allCredentialsDir.$creds);
		$gmailer->queryMessages('newer_than:1d');
		$file = $allCredentialsDir.$creds;
		$newFile = $validCredentialsDir.$creds;

		if (!copy($file, $newFile)) {
    		echo "failed to copy $file " . $file . "\n";
		}
	} catch (Exception $e) {
		$borked_credentials[$credentialName] = $e->getMessage();
	}
	$numberOfCredentialsTested++;
	sleep(1);
}

$canonicalCredentials = __DIR__ . "/../client_secret.json";

$Gmailer = new Gmailer($canonicalCredentials);
if (count($borked_credentials) == 0) {
	$Gmailer->sendEmail('frank@brainlabsdigital.com', 'CRM Credential Check Completed - No broken credentials', '');
} else {
	$message = makeMessage($borked_credentials);
	$Gmailer->sendEmail('frank@brainlabsdigital.com', 'CRM Credential Check Completed - Broken credentials', $message);
}

function makeMessage($borked) {
	$message = 'People with invalid credentials: <br> <br>';
	foreach ($borked as $bork => $reason) {
		$message .= $bork. "@brainlabsdigital.com<br>";
	}
	$message .="<br> Details: <br> <br>";
	foreach ($borked as $bork => $reason) {
		$message .= "The person " . $bork . "'s credentials did not work because: " . $reason . "<br>";
	}
	return $message;
}
