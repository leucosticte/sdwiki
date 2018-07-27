<?php
#$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';
#$basePath = '/var/www/html/test1/';
$basePath = '/home/thedonald/sdwiki.org/w/';

require_once $basePath . '/maintenance/Maintenance.php';


class ImportTitles extends Maintenance {

	public function execute() {

		$namespaces = array(
			0 => '',
			1 => 'Talk:',
			2 => 'User:',
			3 => 'User talk:',
			4 => 'Wikipedia:',
			5 => 'Wikipedia talk:',
			6 => 'File:',
			7 => 'File talk:',
			8 => 'MediaWiki:',
			9 => 'MediaWiki talk:',
			10 => 'Template:',
			11 => 'Template talk:',
			12 => 'Help:',
			13 => 'Help talk:',
			14 => 'Category:',
			15 => 'Category talk:',
			100 => 'Portal:',
			101 => 'Portal talk:',
			108 => 'Book:',
			109 => 'Book talk:',
			118 => 'Draft:',
			119 => 'Draft talk:',
			446 => 'Education Program:',
			447 => 'Education Program talk:',
			710 => 'TimedText:',
			711 => 'TimedText talk:',
			828 => 'Module:',
			829 => 'Module talk:',
			2300 => 'Gadget:',
			2301 => 'Gadget talk:',
			2302 => 'Gadget definition:',
			2303 => 'Gadget definition talk:'
		);
		$time = time();
		$startTime = time();
		$tabChar = '	';
		$dbw = wfGetDB( DB_MASTER );
		
		#$fileCount = 43;
		$fileCount = 1;
		$maxFileCount = 44;
		$inputPath = '/home/nathan/bots/data/file';
		#$inputPath = '/home/thedonald/sdwiki.org/rped_data/file';
		$inputTimestamp = '20170701000000';
		echo "Using maxFileCount $maxFileCount\n";
		echo "Using inputPath $inputPath\n";
		echo "Using inputTimestamp $inputTimestamp\n";
		#$firstLine = true;
		while ( $fileCount < $maxFileCount ) {
			$inputFilename = $inputPath . $fileCount . '.txt';
			echo "$inputFilename\n";
			$handle = fopen( $inputFilename, "r");
			if (!$handle) {
				die ( "File wouldn't open\n" );
			}
			$lineCount = 0;
			$dbw->begin();
			while (($line = fgets($handle)) !== false) {
				#echo $line;
				/*if ( $firstLine ) {
					$tabChar = strpos( $line, 1 );
					$firstLine = false;
				}*/
				$spacePos = strpos( $line, $tabChar );
				$namespaceNumber = trim( substr( $line, 0, $spacePos ) );
				if ( !isset( $namespaces[$namespaceNumber] ) ) {
					die ( "Invalid namespace number $namespaceNumber ");
				}
				$line = trim( $namespaces[$namespaceNumber] .
					substr( $line, $spacePos + 1, strlen ( $line ) - $spacePos - 2 ) );
				#echo "$line\n";
				$line = str_replace( ' ', '_', $line );
				$dbw->insert(
					'rped',
					array(
						'rped_prefixed_dbkey' => $line,
						'rped_exists' => 1,
						'rped_created' => $inputTimestamp
					)
				);
				if ( $lineCount % 100000 == 0 ) {
					$shortTime = time() - $time;
					$longTime = time() - $startTime;
					echo "$lineCount $shortTime $longTime $line\n";
					$time = time();
					$dbw->commit();
					$dbw->begin();
				}
				$lineCount++;
			}
			$shortTime = time() - $time;
			$longTime = time() - $startTime;
			echo "$lineCount $shortTime $longTime $line\n";
			$dbw->commit();
			fclose($handle);
			$fileCount++;
		}
		echo "Done\n";
	}
}

$maintClass = 'ImportTitles';

require_once RUN_MAINTENANCE_IF_MAIN;