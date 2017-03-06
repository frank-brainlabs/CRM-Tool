<?php

$outputsDir = __DIR__ . "/../outputs/";
$outputs = array_diff(scandir($outputsDir), array('..', '.'));

$finalOutput = array();

// print_r($outputs);

foreach ($outputs as $output) {
	$filePath = $outputsDir . $output;
	$handle = fopen($filePath, 'r');
	while(! feof($handle)) {
		$data = fgetcsv($handle);
		$numberOfCols = count($data);
		$client = $data[0];
		if (array_key_exists($client, $finalOutput)) {
			for ($i = 1; $i <$numberOfCols; $i++) {
				$idsArray = json_decode($data[$i]);
				$existingArray = $finalOutput[$client][$i-1];
				// print_r(count($existingArray));
				// print_r(PHP_EOL);
				if (is_null($idsArray)) continue;
				$newArray = array_unique(array_merge($idsArray, $existingArray));
				$finalOutput[$client][$i-1] = $newArray;
			} 
		} else {
			for ($i = 1; $i <$numberOfCols; $i++) {
				$idsArray = json_decode($data[$i]);
				$finalOutput[$client][$i-1] = $idsArray;
			}
		}
	}
	
}

// print_r($finalOutput);

$fileName = __DIR__ . '/combo_emails.csv';
$handle = fopen($fileName, 'w');
foreach ($finalOutput as $client => $dateRanges) {
	$rowToInsert = array($client);
	foreach ($dateRanges as $ids) {
		$rowToInsert[] = count($ids);
	}
	fputcsv($handle, $rowToInsert);
}