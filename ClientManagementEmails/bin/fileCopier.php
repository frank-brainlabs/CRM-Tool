<?php

$allCredentialsDir = __DIR__ . "/../all_credentials/";
$credentials = array_diff(scandir($allCredentialsDir), array('..', '.'));
print_r($credentials);

foreach ($credentials as $creds) {
	for($i = 0; $i<50; $i++){
		//print_r($creds. "\n");
		$newName = explode('.', $creds, -1)[0] . $i . ".json";
		print_r($newName);
		$file = $allCredentialsDir.$creds;
		$newFile = $allCredentialsDir.$newName;

		if (!copy($file, $newFile)) {
			echo "failed to copy $file " . $file . "\n";
		}
	}
}