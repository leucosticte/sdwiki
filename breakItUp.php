<?php
$inputFilename = 'enwiki-20170701-all-titles';
echo "Using inputFilename $inputFilename\n";
$handleIn = fopen( $inputFilename, "r");
$keepGoing = true;
$fileCount = 0;
$lineCount = 1;
$line = fgets($handleIn); // Get rid of header line
while ( $keepGoing ) {
	$fileCount++;
	$lineCount++;
	$outputFilename = 'file' . $fileCount . '.txt';
	echo "$outputFilename\n";
	$handleOut = fopen( $outputFilename, 'w' );
	while ( $lineCount % 1000000 != 0 ) {
		$line = fgets($handleIn);
		if ( $line === false ) {
			fclose( $handleOut );
			die( "Done\n" );
		}
		fwrite( $handleOut, $line );
		$lineCount++;
	}
	fclose ( $handleOut );
}