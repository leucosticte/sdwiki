<?php
/*
 * sdbot.php
 * This bot pulls data from deletion categories of Wikipedia
 *
*  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
/*

/* Set up my classes. */
include( './Chris-G-botclasses/botclasses.php' );
include( 'sd_config.php' );
$pullWiki      = new wikipedia;
$pullWiki->url = "https://en.wikipedia.org/w/api.php";
$pullWiki->setUserAgent( 'User-Agent: SDPullBot (http://mediawiki.org/wiki/User:KryptoKronic)' );
$pushWiki      = new wikipedia;
echo "Choose a push wiki\n";
echo "1: Local wiki\n";
echo "2: HemovanadinBot\n";
echo "2a: Hemovanadin CSD\n";
echo "2b: Hemovanadin AAPfD\n";
echo "2c: Hemovanadin MfD\n";
$failedImportsInARow = 0;
$pageSoFar = 0;
#$reImport = true;
$reImport = false;
$wikiName = '';

$choice = '';
while ( $choice != 1 && $choice != '2' && $choice != '2a' && $choice != '2b' && $choice != '2c' ) {
	$choice = readline ( 'pick one>' );
}
switch ( $choice ) {
	case 1:
		$wikiName = 'test1';
		$pushWiki->url = "http://localhost/test1/api.php";
		$pushUser = $pushUser1;
		$pushPass = $pushPass1;
		$wikiNumber = 1;
		continue;
	case '2':
		$wikiName = 'sdwiki';
		#$pushWiki->url = "http://hemovanadin.org/w/api.php";
		#$pushWiki->url = "http://fr.kingswiki.com/w/api.php";
		$pushWiki->url = "https://www.sdwiki.org/w/api.php";
		$pushUser = $pushUser2;
		$pushPass = $pushPass2;
		$wikiNumber = 3;
		#$wikiNumber = 4;
		continue;
	case '2a':
		$wikiName = 'sdwiki';
		#$pushWiki->url = "http://fr.kingswiki.com/w/api.php";
		$pushWiki->url = "https://www.sdwiki.org/w/api.php";
		$pushUser = $pushUser2a;
		$pushPass = $pushPass2a;
		$wikiNumber = 3;
		#$wikiNumber = 4;
		continue;
	case '2b':
		$wikiName = 'sdwiki';
		#$pushWiki->url = "http://fr.kingswiki.com/w/api.php";
		$pushWiki->url = "https://www.sdwiki.org/w/api.php";
		$pushUser = $pushUser2b;
		$pushPass = $pushPass2b;
		$wikiNumber = 3;
		#$wikiNumber = 4;
		continue;
	case '2c':
		$wikiName = 'sdwiki';
		#$pushWiki->url = "http://fr.kingswiki.com/w/api.php";
		$pushWiki->url = "https://www.sdwiki.org/w/api.php";
		$pushUser = $pushUser2c;
		$pushPass = $pushPass2c;
		$wikiNumber = 3;
		#$wikiNumber = 4;
		continue;
}
global $mode;
$mode = '';
echo "Choose a mode\n";
#echo "1: Get pages plus templates\n";
#echo "1a: Get pages plus templates, but with limited recursion\n";
echo "1b: Get pages plus templates, but with limited recursion, and only the latest revisions " .
	"of pages\n";
#echo "2: Get only pages\n";
#echo "3: Get only latest revisions of pages\n";
echo "4: Get only templates\n";
echo "5: Get only modules\n";
echo "6: Get only MediaWiki: pages\n";
echo "7: RPED creations\n";
echo "8: RPED deletions\n";
echo "9: Recent changes\n";

$allowedModes = [ '1b', '4', '5', '6', '7', '8', '9' ];
while ( !in_array( $mode, $allowedModes ) ) {
	$mode = readline ( 'pick one>' );
}
$everGetTemplates = true;
if ( $mode == '2' || $mode == '3' ) {
	$everGetTemplates = false;
}
$alwaysMostRecentOnly = false;
if ( $mode == '1b' || $mode == '3' ) {
	$alwaysMostRecentOnly = true;
}
$fullRecursion = true;
if ( $mode == '1a' || $mode == '1b' ) {
	$fullRecursion = false;
}

$pick = '';
if ( $mode == '1b' ) {
	echo "Pick a category\n";
	echo "1: Category:Candidates_for_speedy_deletion\n";
	echo "2: Category:All_articles_proposed_for_deletion\n";
	echo "3: Category:Miscellaneous_pages_for_deletion\n";
	echo "3a: Category:Miscellaneous_pages_for_deletion, " .
		"Articles_for_deletion, " .
		"Templates_for_deletion, " .
		"Categories_for_deletion\n";
	echo "4: Category:Articles_for_deletion\n";
	echo "5: Category:Templates_for_deletion\n";
	echo "6: Category:Categories_for_deletion\n";
	while ( $pick != 1 && $pick != 2 && $pick != 3 && $pick != '3a' && $pick != 4 && $pick != 5
		&& $pick != 6 ) {
		$pick = readline ( 'pick one>' );
	}
}
$processSecs = 30;
$sleepSecs = 15;
switch ( $pick ) {
	case 1:
		$categories = array( 'Category:Candidates_for_speedy_deletion' );
		continue;
	case 2:
		$categories = array( 'Category:All_articles_proposed_for_deletion' );
		continue;
	case '3':
		$categories = array( 'Category:Miscellaneous_pages_for_deletion' );
		continue;
	case '3a':
		$categories = array(
			'Category:Miscellaneous_pages_for_deletion',
			'Category:Articles_for_deletion',
			'Category:Templates_for_deletion',
			'Category:Categories_for_deletion'
		);
		$processSecs = 60;
		$sleepSecs = 60;
		continue;
	case 4:
		$categories = array( 'Category:Articles_for_deletion' );
		continue;
	case 5:
		$categories = array( 'Category:Templates_for_deletion' );
		continue;
	case 6:
		$categories = array( 'Category:Categories_for_deletion' );
}

$alreadyVisited = array();

# Database stuff
$db = new mysqli( $dbHost, $dbUser, $dbPassword, $dbName );
if ( !$db ) {
      die( 'Could not connect: ' . mysql_error() );
}
 
/* All the login stuff. */
$pullWiki->login( $pullUser, $pullPass );
$pushWiki->login( $pushUser, $pushPass );

if ( $mode == 4 ) {
	getOnlyTemplatesOrModules( $db, $pullWiki, $pushWiki, 10 );
} elseif ( $mode == 5 ) {
	getOnlyTemplatesOrModules( $db, $pullWiki, $pushWiki, 828 );
} elseif ( $mode == 6 ) {
	getOnlyTemplatesOrModules( $db, $pullWiki, $pushWiki, 8 );
} elseif ( $mode == 7 ) {
	getRpedCreations( $db, $pullWiki, $pushWiki, $wikiName );
} elseif ( $mode == 8 ) {
	$keepGoing = true;
	while ( $keepGoing ) {
		getRpedDeletions( $db, $pullWiki, $pushWiki, $wikiName );
		if ( isset( $time ) && time() - $time < $processSecs ) {
			echo "Finished; sleeping $sleepSecs seconds\n";
			sleep( $sleepSecs );
		}
		$time = time();
	}
} elseif ( $mode == 9 ) {
	getRecentChanges( $db, $pullWiki, $pushWiki, $wikiName );
} else {
	$keepGoing = true;
	while ( $keepGoing ) {
		checkAllCategories( $db, $pullWiki, $pushWiki, $categories, $alwaysMostRecentOnly,
			$everGetTemplates, $fullRecursion );
		if ( isset( $time ) && time() - $time < $processSecs ) {
			echo $categories[0] . " Mode $mode Choice $choice\n";
			echo "Finished with this category; sleeping $sleepSecs seconds\n";
			sleep( $sleepSecs );
		}
		$time = time();
	}
}

// Go through recent changes for new revisions
function getRecentChanges( $db, $pullWiki, $pushWiki, $wikiName ) {
	$rcStart = '20170701000000';
	$keepGoing = true;
	$dbKey = $wikiName . 'rcstart';
	$query = "SELECT sdc_value FROM sd_cursor WHERE sdc_key='$dbKey'";
	$ret = doDbQuery ( $db, $query );
	$row = $ret->fetch_assoc();
	if ( isset( $row['sdc_value'] ) ) {
		$rcStart = $row['sdc_value'];
	} else {
		$query = 'INSERT INTO sd_cursor ( sdc_key, sdc_value ) ' .
			"VALUES ( '$dbKey', '$rcStart' )";
		doDbQuery( $db, $query );
	}
	echo "Starting at $rcStart\n";
	while( $keepGoing ) {
		$query = '?action=query&' .
			"format=php&" .
			"list=recentchanges&" .
			"rcstart=$rcStart&" .
			"rcdir=newer&" .
			"rcprop=timestamp|ids&" .
			"rclimit=500&" .
			"rctype=edit|new|categorize";
		if ( isset( $continue ) ) {
			$query .= "&rccontinue=$continue";
		}
		$sleep = false;
		$ret = $pullWiki->query ( $query );
		if ( isset( $ret['continue'] ) ) {
			$continue = $ret['continue']['rccontinue'];
		} else {
			$sleep = true;
		}
		$attempts = 0;
		foreach( $ret['query']['recentchanges'] as $recentChange ) {
			var_dump( $recentChange );
			echo "$dbKey\n";
			$title = $recentChange['title'];
			$timestamp = $recentChange['timestamp'];
			$undesirables = array ( '-', ':', 'T', 'Z' );
			$timestamp = str_replace ( $undesirables, '', $timestamp );
			// TODO: Implement this
			#getRevisionFromRevId( ... )
			#http://localhost/test1/api.php?action=help&modules=query%2Brevisions
			#http://localhost/test1/api.php?action=help&modules=query%2Brecentchanges
			$ret = $pushWiki->rped( $title, $timestamp );
			var_dump ( $ret );
			if ( !isset( $ret['rped']['result'] ) || $ret['rped']['result'] != 'Success' ) {
				die ( "Lack of success!\n" );
			}
		}
		$query = "UPDATE sd_cursor SET sdc_value='$timestamp' WHERE sdc_key='$dbKey'";
		$ret = doDbQuery ( $db, $query );
		if ( $sleep ) {
			echo "All done. Sleeping 15 secs\n";
			sleep(15);
			$rcStart = $timestamp;
		}
	}
}

// Go through recent changes for new pages
function getRpedCreations( $db, $pullWiki, $pushWiki, $wikiName ) {
	$rcStart = '20170701000000';
	$keepGoing = true;
	$dbKey = $wikiName . 'rpedrcstart';
	$query = "SELECT sdc_value FROM sd_cursor WHERE sdc_key='$dbKey'";
	$ret = doDbQuery ( $db, $query );
	$row = $ret->fetch_assoc();
	if ( isset( $row['sdc_value'] ) ) {
		$rcStart = $row['sdc_value'];
	} else {
		$query = 'INSERT INTO sd_cursor ( sdc_key, sdc_value ) ' .
			"VALUES ( '$dbKey', '$rcStart' )";
		doDbQuery( $db, $query );
	}
	echo "Starting at $rcStart\n";
	while( $keepGoing ) {
		$query = '?action=query&' .
			"format=php&" .
			"list=recentchanges&" .
			"rcstart=$rcStart&" .
			"rcdir=newer&" .
			"rcprop=timestamp|title&" .
			"rclimit=500&" .
			"rctype=new";
		if ( isset( $continue ) ) {
			$query .= "&rccontinue=$continue";
		}
		$sleep = false;
		$ret = $pullWiki->query ( $query );
		if ( isset( $ret['continue'] ) ) {
			$continue = $ret['continue']['rccontinue'];
		} else {
			$sleep = true;
		}
		$attempts = 0;
		foreach( $ret['query']['recentchanges'] as $recentChange ) {
			var_dump( $recentChange );
			echo "$dbKey\n";
			$title = $recentChange['title'];
			$timestamp = $recentChange['timestamp'];
			$undesirables = array ( '-', ':', 'T', 'Z' );
			$timestamp = str_replace ( $undesirables, '', $timestamp );		
			$ret = $pushWiki->rped( $title, $timestamp );
			var_dump ( $ret );
			if ( !isset( $ret['rped']['result'] ) || $ret['rped']['result'] != 'Success' ) {
				die ( "Lack of success!\n" );
			}
		}
		$query = "UPDATE sd_cursor SET sdc_value='$timestamp' WHERE sdc_key='$dbKey'";
		$ret = doDbQuery ( $db, $query );
		if ( $sleep ) {
			echo "All done. Sleeping 15 secs\n";
			sleep(15);
			$rcStart = $timestamp;
		}
	} 
}

// Go through recent changes for new pages
function getRpedDeletions( $db, $pullWiki, $pushWiki, $wikiName ) {
	$types = array(
		'delete',
		'move',
		'import',
		'upload'
	);
	foreach ( $types as $type ) {
		$leStart = '20170701000000';
		$continue = '';
		$dbKey = $wikiName. $type . "lestart";
		$keepGoing = true;
		$query = "SELECT sdc_value FROM sd_cursor WHERE sdc_key='$dbKey'";
		$ret = doDbQuery ( $db, $query );
		$row = $ret->fetch_assoc();
		if ( isset( $row['sdc_value'] ) ) {
			$leStart = $row['sdc_value'];
		} else {
			$query = 'INSERT INTO sd_cursor ( sdc_key, sdc_value ) ' .
				"VALUES ( '$dbKey', '$leStart' )";
			doDbQuery( $db, $query );
		}
		echo "Starting at $leStart\n";
		while( $keepGoing ) {
			$query = '?action=query&' .
				"format=php&" .
				"list=logevents&" .
				"lestart=$leStart&" .
				"ledir=newer&" .
				"letype=$type&" .
				"leprop=timestamp|title|type|details&" .
				"lelimit=500";
			if ( isset( $continue ) && $continue ) {
				$query .= "&lecontinue=$continue";
			}
			$sleep = false;
			$ret = $pullWiki->query ( $query );
			if ( isset( $ret['continue'] ) ) {
				$continue = $ret['continue']['lecontinue'];
			} else {
				$keepGoing = false;
			}
			$attempts = 0;
			foreach( $ret['query']['logevents'] as $logEvent ) {
				echo "$dbKey\n";
				var_dump( $logEvent );
				$logType = $logEvent['type'];
				$logAction = $logEvent['action'];
				$title = $logEvent['title'];
				$timestamp = $logEvent['timestamp'];
				$undesirables = array ( '-', ':', 'T', 'Z' );
				$timestamp = str_replace ( $undesirables, '', $timestamp );
				$attempted = false;
				if ( $logType == 'delete' &&
					( $logAction == 'delete' || $logAction == 'delete_redir' ) ) {
					$ret = $pushWiki->rped( $title, null, $timestamp );
					$attempted = true;
				}
				if ( $logType == 'delete' && $logAction == 'restore' ) {
					$ret = $pushWiki->rped( $title, $timestamp );
					$attempted = true;
				}
				if ( $logType == 'move' ) {
					if ( isset( $logEvent['params']['target_title'] ) ) {
						$targetTitle = $logEvent['params']['target_title'];
						$ret = $pushWiki->rped( $targetTitle, $timestamp );
						$attempted = true;
					} else {
						die ( "No target title!\n" );
					}
					if ( isset( $logEvent['params']['suppressredirect'] ) ) {
						$ret = $pushWiki->rped( $title, null, $timestamp );
						$attempted = true;
					}
				}
				if ( $logType == 'import' ) {
					$ret = $pushWiki->rped( $title, $timestamp );
					$attempted = true;
				}
				if ( $logType == 'upload' ) {
					$ret = $pushWiki->rped( $title, $timestamp );
					$attempted = true;
				}
				if ( $attempted &&
					( !isset( $ret['rped']['result'] ) || $ret['rped']['result'] != 'Success' ) ) {
					die ( "Lack of success!\n" );
				}
			}
			$query = "UPDATE sd_cursor SET sdc_value='$timestamp' WHERE sdc_key='$dbKey'";
			$ret = doDbQuery ( $db, $query );
		}
	}
}

// For modes 4 and 5; go through AllPages alphabetically and get all the templates or modules
function getOnlyTemplatesOrModules( $db, $pullWiki, $pushWiki, $namespace ) {
	$keepGoing = true;
	while ( $keepGoing ) {
		$query = "?action=query" .
		"&format=php" .
		"&generator=allpages" .
		"&gaplimit=500" .
		"&gapnamespace=$namespace" .
		"&prop=info";
		if ( isset( $continue ) ) {
			$query .= "&gapcontinue=$continue";
		}
		$ret = $pullWiki->query ( $query );
		if ( isset( $ret['continue'] ) ) {
			$continue = $ret['continue']['gapcontinue'];
		} else {
			$keepGoing = false;
		}
		$pages = array();
		foreach( $ret['query']['pages'] as $page ) {
			$pages[] = $page;
		}
		// It was unnecessary to break this up into chunks, but whatever
		$pageChunks = array_chunk( $pages, 500 );
		foreach ( $pageChunks as $pageChunk ) {
			checkLatestRevisionsThenGrabAndPushHistory( $db, $pullWiki, $pushWiki, $pageChunk,
				true, true, false, false );
		}
		echo "Finished\n";
	}
}

// Check all the deletion categories for revisions we need to grab from Wikipedia and then push
function checkAllCategories( $db, $pullWiki, $pushWiki, $categories,
	$alwaysMostRecentOnly, $everGetTemplates, $fullRecursion ) {
	$pages = array();
	foreach ( $categories as $category ) {
		$keepGoing = true;
		while ( $keepGoing ) {
			$query = "?action=query" .
				"&format=php" .
				"&generator=categorymembers" .
				"&gcmtitle=$category" .
				"&gcmtype=page" .
				"&gcmlimit=500" .
				"&prop=info";
			if ( isset( $continue ) ) {
				$query .= "&gcmcontinue=$continue";
			}
			$ret = $pullWiki->query ( $query );
			if ( isset( $ret['continue'] ) ) {
				$continue = $ret['continue']['gcmcontinue'];
			} else {
				$keepGoing = false;
			}
			if ( isset( $ret['query']['pages'] ) ) {
				$pages = array_merge( $pages, $ret['query']['pages'] );
			}
		}
		// Get the talk pages first, since if we were to grab the other page first, and
		// the bot were to subsequently crash, it would have already marked that page in
		// the queue as imported, and then not import the talk page, thinking that was
		// already taken care
		echo "Getting a list of talk pages...\n";
		$talkPages = getTalkPages( $db, $pullWiki, $pages );
		echo "Pushing " . count( $talkPages ) . " talk pages\n";
		checkLatestRevisionsThenGrabAndPushHistory( $db, $pullWiki, $pushWiki, $talkPages,
			$alwaysMostRecentOnly, $everGetTemplates, $fullRecursion );
		echo "Pushing " . count( $pages ) . " subject pages\n";
		checkLatestRevisionsThenGrabAndPushHistory( $db, $pullWiki, $pushWiki, $pages,
			$alwaysMostRecentOnly, $everGetTemplates, $fullRecursion );
	}
}

// Get info (latest rev ids, etc.) concerning the talk pages corresponding to a set of pages
function getTalkPages( $db, $pullWiki, $pages ) {
	$talkPages = array();
	$talkPageIds = array();
	$pageChunks = array_chunk( $pages, 50 ); // Limited to 50
	$pageIds = '';
	#var_dump( $pages );
	#die();
	foreach ( $pageChunks as $pageChunk ) {
		$firstOne = true;
		$query = "?action=query" .
				"&format=php" .
				"&prop=info" .
				"&inprop=talkid" .
				"&pageids=";
		foreach( $pageChunk as $page ) {	
			if ( $firstOne ) {
				$firstOne = false;
			} else {
				$query .= '|';
			}
			$query .= $page['pageid'];
		}
		$ret = $pullWiki->query ( $query );
		if ( isset( $ret['query']['pages'] ) ) {
			foreach( $ret['query']['pages'] as $page ) {
				if( isset( $page['talkid'] ) ) {
					$talkPageIds[] = $page['talkid'];
				}
			}
		} else {
			echo "The query came up empty!\n";
		}
	}
	$talkPageIdChunks = array_chunk( $talkPageIds, 50 );
	foreach ( $talkPageIdChunks as $talkPageIdChunk ) {
		$firstOne = true;
		$query = "?action=query" .
				"&format=php" .
				"&prop=info" .
				"&pageids=";
		foreach( $talkPageIdChunk as $talkPageId ) {	
			if ( $firstOne ) {
				$firstOne = false;
			} else {
				$query .= '|';
			}
			$query .= $talkPageId;
		}
		$ret = $pullWiki->query ( $query );
		if ( isset( $ret['query']['pages'] ) ) {
			$talkPages = array_merge( $talkPages, $ret['query']['pages'] );
		} else {
			echo "Couldn't retrieve talk pages\n";
		}
	}
	return $talkPages;
}
	
// See if we have the latest revision of a page; if not, get the necessary revisions
function checkLatestRevisionsThenGrabAndPushHistory( $db, $pullWiki, $pushWiki, $pages,
	$mostRecentOnly = false, $getTemplates = true, $fullRecursion = true, $postHistory = true ) {
	global $wikiNumber, $reImport;
	foreach ( $pages as $page ) {
		// Do we have the latest revision for the page? If not, grab any new revisions	
		$query = "SELECT sdq_rev_id FROM sd_queue "
			. "WHERE sdq_wiki='" . $wikiNumber . "' AND sdq_page_id='" . $page['pageid'] . "' "
			. "ORDER BY sdq_rev_id DESC LIMIT 1";
		$ret = doDbQuery ( $db, $query, true );
		$row = $ret->fetch_assoc();
		// If we don't have any database rows at all related to that page, or if we don't have
		// the latest, then grab that history. Get the templates first so that they'll be
		// there when the page arrives.
		if ( !$row || $row['sdq_rev_id'] < $page['lastrevid'] || $reImport ) {
			echo "Processing page " . $page['title'] . ". . .";
			if ( $getTemplates ) {
				getTemplates( $db, $pullWiki, $pushWiki, $page, $fullRecursion );
			}
			if ( !$row ) {
				grabAndPushHistory( $db, $pullWiki, $pushWiki, $page, null, $mostRecentOnly, $postHistory );
			} elseif ( $row['sdq_rev_id'] < $page['lastrevid'] ) {
				grabAndPushHistory( $db, $pullWiki, $pushWiki, $page, $row['sdq_rev_id'],
					$mostRecentOnly, $postHistory );
			}
		} else {
			echo "Page " . $page['title'] . " present and accounted for!\n";
		}
	}
	return;
}

// See if we have the latest revisions of a page's templates; if not, get them
function getTemplates( $db, $pullWiki, $pushWiki, $page, $fullRecursion ) {
	global $alreadyVisited;
	$keepGoing = true;
	while ( $keepGoing) {
		$query = "?action=query" .
			"&format=php" .
			"&generator=templates" .
			"&pageids=" . $page['pageid'] .
			"&prop=info" .
			"&gtlnamespace=10|828" . // Template and Module namespaces
			"&gtllimit=500";
		if ( isset( $continue ) ) {
				$query .= "&gtlcontinue=$continue";
		}
		$ret = $pullWiki->query ( $query );
		if ( isset ( $ret['query']['pages'] ) ) {
			echo "Making sure templates are up to date...\n";
			foreach ( $ret['query']['pages'] as $templatePage ) {
				// Do this recursively, but don't go in circles
				$recursion = $fullRecursion;
				if ( in_array( $templatePage['pageid'], $alreadyVisited ) ) {
					$recursion = false;
				} else {
					$alreadyVisited[] = $templatePage['pageid'];
				}
				checkLatestRevisionsThenGrabAndPushHistory( $db, $pullWiki, $pushWiki,
					array( $templatePage ), true, $recursion, $fullRecursion, false );
			}
		}
		if ( isset( $ret['continue'] ) ) {
			$continue = $ret['continue']['gtlcontinue'];
		} else {
			$keepGoing = false;
		}
	}
	return;
}

// Grab either the full history or the most recent revision
function grabAndPushHistory( $db, $pullWiki, $pushWiki, $page, $lastRevIdFromOurDatabase = null,
	$mostRecentOnly = false, $postHistory = true ) {
	// If we're only grabbing the most recent, that's pretty easy
	if ( $mostRecentOnly ) {
		if ( $postHistory ) {
			grabAndPushHistoryToHistoryNamespace( $db, $pullWiki, $pushWiki, $page );
		}
		grabAndPushMostRecentRevision ( $db, $pullWiki, $pushWiki, $page );
		return;
	}
	// Grab the page history, starting with the newest revision in our database
	$keepGoing = true;
	$revisions = array();
	$skipFirst = false;
	if ( isset( $lastRevIdFromOurDatabase ) ) {
		$skipFirst = true; // Skip the first entry if we're picking up where we left off
	}
	while ( $keepGoing ) {
		$query = "?action=query" .
			"&format=php" .
			"&prop=revisions" .
			"&rvprop=ids|flags|timestamp|user|contentmodel|comment|content|tags" .
			"&rvdir=newer" .
			"&pageids=" . $page['pageid'] .
			"&rvlimit=50";
		if ( isset( $lastRevIdFromOurDatabase ) ) {
			$query .= "&rvstart=$lastRevIdFromOurDatabase";
		}
		if ( isset( $continue ) ) {
			$query .= "&rvcontinue=$continue";
		}
		$ret = $pullWiki->query ( $query );
		// If we can't retrieve it, the revisions must've been deleted, so abort
		if ( isset( $ret['error'] ) ) {
			echo "grabAndPushHistory: WP API returned error; aborting\n";
			return;
		}
		// If we can't retrieve it, the page must've been deleted, so abort
		if ( isset( $ret['query']['pages']['-1'] ) ) {
			echo "grabAndPushHistory: page not found; aborting\n";
			return true;
		}
		if ( isset( $ret['continue'] ) ) {
			$continue = $ret['continue']['rvcontinue'];
		} else {
			$keepGoing = false;
		}
		// Now push the revisions to our wiki
		pushAndPutInDatabase( $db, $pullWiki, $pushWiki, $page, $ret, $skipFirst );
	}
	return;
}

// Save page history to the History: namespace
function grabAndPushHistoryToHistoryNamespace( $db, $pullWiki, $pushWiki, $page ) {
	$keepGoing = true;
	$revisions = array();
	while ( $keepGoing ) {
		$query = "?action=query" .
			"&format=php" .
			"&prop=revisions" .
			"&rvprop=ids|timestamp|user|userid|size|contentmodel|comment|tags" .
			"&pageids=" . $page['pageid'] .
			"&rvlimit=500";
		if ( isset( $continue ) ) {
			$query .= "&rvcontinue=$continue";
		}
		$ret = $pullWiki->query ( $query );
		// If we can't retrieve it, the revisions must've been deleted, so abort
		if ( isset( $ret['error'] ) ) {
			echo "WP API returned error; aborting\n";
			return;
		}
		// If we can't retrieve it, the page must've been deleted, so abort
		if ( isset( $ret['query']['pages']['-1'] ) ) {
			echo "Page not found; aborting\n";
			return;
		}
		if ( isset( $ret['continue'] ) ) {
			$continue = $ret['continue']['rvcontinue'];
		} else {
			$keepGoing = false;
		}
		foreach ( $ret['query']['pages'] as $page ) {
			$revisions = array_merge ( $revisions, $page['revisions'] );
		}
	}
	// Now push the revision to our wiki
	$contentToPush = "{{Sdwikihistory begin}}\n";
	$pageTitle = "History:" . $page['title'];
	echo "Saving history to $pageTitle\n";
	foreach ( $revisions as $revisionKey => $revision ) {
		$revisions[$revisionKey]['sizechange'] =
			createSizeString( $revision['size'] );
		if ( isset( $previousRevisionKey ) ) {
			$sizeDifference = $previousSize - $revision['size'];
			$sizeString = createSizeString( $sizeDifference );
			$revisions[$previousRevisionKey]['sizechange'] = $sizeString;
		}
		$previousRevisionKey = $revisionKey;
		$previousSize = $revision['size'];
	}
	foreach ( $revisions as $revision ) {
		$tags = '';
		if ( isset ( $revision['tags'] ) && $revision['tags'] ) {
			$firstOne = true;
			foreach( $revision['tags'] as $tag ) {
				if ( $firstOne ) {
					$firstOne = false;
				} else {
					$tags .= ', ';
				}
				$tags .= $tag;
			}
		}
		if ( !isset( $revision['timestamp'] ) ) {
			var_dump( $revision );
			die();
		}
		$year = substr( $revision['timestamp'], 0, 4 );
		$month = substr( $revision['timestamp'], 5, 2 );
		$day = substr($revision['timestamp'], 8, 2 );
		$hour = substr( $revision['timestamp'], 11, 2 );
		$minute = substr( $revision['timestamp'], 14, 2 );
		$second = substr( $revision['timestamp'], 17, 2 );
		$dateObj   = DateTime::createFromFormat('!m', $month);
		$monthName = $dateObj->format('F');
		$timestamp = $year. '.' . $month . '.' . $day . '&nbsp;' .
			$hour . ':' . $minute . ':' . $second;
		$contentToPush .= '{{Sdwikihistory row' .
			'|revid=' . $revision['revid'] .
			'|user=' . $revision['user'] .
			'|userid=' . $revision['userid'] .
			'|timestamp=' . $timestamp .
			'|size=' . number_format( $revision['size'] ) .
			'|sizechange=' . $revision['sizechange'] .
			'|contentmodel=' . $revision['contentmodel'];
		if ( isset( $revision['comment'] ) ) {
			$contentToPush .= '|comment=<nowiki>' . $revision['comment'] . '</nowiki>';
		}
		if ( $tags ) {
			$contentToPush .= '|tags=' . $tags;
		}
		$contentToPush .= "}}\n";
	}
	$contentToPush .= '{{Sdwikihistory end}}';
	$ret = $pushWiki->edit ( $pageTitle, $contentToPush, $contentToPush );
	if ( !array( $ret ) ) {
		die( "Ret was not an array. Details: $ret \n" );
	}
	if ( isset( $ret['error'] ) || isset( $ret['http error'] ) ) {
		var_dump( $ret );
		if ( isset( $ret['error'] ) && $ret['error']['code'] = 'unknownerror' ) {
			echo "Known bug; not sure how to fix, so just skip this\n";
			return;
		}
		if ( isset( $ret['http error'] ) ) {
			if ( $ret['http error'] === 413 )
				echo "The history was too big to save\n";
				return;
		}
		die( "Error saving the history\n" );
	}
	return;
}

// Create a parameter for the template
function createSizeString ( $sizeDifference ) {
	$sizeDifferenceGrouped = number_format( $sizeDifference );
	if  ( $sizeDifference < -500 ) {
		$sizeString = "{{sdBigDecrease|$sizeDifferenceGrouped}}";
	}
	if  ( $sizeDifference < 0 && $sizeDifference >= -500 ) {
		$sizeString = "{{sdSmallDecrease|$sizeDifferenceGrouped}}";
	}
	if  ( $sizeDifference == 0 ) {
		$sizeString = "{{sdNoChange}}";
	}
	if  ( $sizeDifference > 0 && $sizeDifference <= 500 ) {
		$sizeString = "{{sdSmallIncrease|$sizeDifferenceGrouped}}";
	}
	if  ( $sizeDifference > 500 ) {
		$sizeString = "{{sdBigIncrease|$sizeDifferenceGrouped}}";
	}
	return $sizeString;
}

// Get and push only the most recent revision
function grabAndPushMostRecentRevision ( $db, $pullWiki, $pushWiki, $page ) {
	$query = "?action=query" .
		"&format=php" .
		"&prop=revisions" .
		"&rvprop=ids|flags|timestamp|user|contentmodel|comment|content|tags" .
		"&pageids=" . $page['pageid'] .
		"&rvlimit=1";
	$ret = $pullWiki->query ( $query );
	pushAndPutInDatabase( $db, $pullWiki, $pushWiki, $page, $ret );
	return;
}

// Make the edit, and record in the database that we made it
function pushAndPutInDatabase( $db, $pullWiki, $pushWiki, $page, $ret, $skipFirst = false ) {
	global $alreadyVisited, $wikiNumber;
	$firstOne = true; // Discard the first one; we already have it
	// See if we even have it; it could have been deleted
	if ( !isset ( $page['pageid'] ) ) {
		echo "No page ID\n";
		return;
	}
	if ( isset( $ret['query']['pages'][$page['pageid']]['revisions'] ) ) {
		foreach ( $ret['query']['pages'][$page['pageid']]['revisions'] as $revision ) {
			// Push the revision
			if ( $firstOne && $skipFirst ) {
				$firstOne = false;
				continue;
			}
			pushRevision ( $db, $pullWiki, $pushWiki, $page, $revision );
			// After pushing each revision, save to our database
			$escapedTitle = $db->escape_string ( $page['title'] );
			$query = "INSERT INTO sd_queue ( sdq_wiki, sdq_page_id, sdq_page_title, sdq_rev_id )" .
				" VALUES ( " . $wikiNumber . ", " . $page['pageid'] . ",'" .
				$escapedTitle . "'," . $revision['revid'] . " );";
			doDbQuery ( $db, $query );
			$alreadyVisited = array(); // We can clear the array now that we made some progress
		}
	}
	return;
}

// Actually make the edit
function pushRevision( $db, $pullWiki, $pushWiki, $page, $revision ) {
	global $failedImportsInARow, $pagesSoFar, $categories, $mode, $choice;
	$undesirables = array ( '-', ':', 'T', 'Z' );
    $timestamp = str_replace ( $undesirables, '', $revision['timestamp'] );
	$minor = false;
	if ( isset ( $revision['minor'] ) ) {
		$minor = true;
	}
	if ( isset ( $revision['contentmodel'] ) ) {
		$contentmodel = $revision['contentmodel'];
	}
	$tags = '';
	if ( isset ( $revision['tags'] ) && $revision['tags'] ) {
		$firstOne = true;
		foreach( $revision['tags'] as $tag ) {
			if ( $firstOne ) {
				$firstOne = false;
			} else {
				$tags .= '|';
			}
			$tags .= $tag;
		}
	}
	if ( isset( $revision['*'] ) ) {
		echo $categories[0] . " Mode $mode Choice $choice\n";
		echo "Pushing title " . $page['title'] . " comment " . $revision['comment']
			. ' timestamp ' . $timestamp . ' user ' . $revision['user'] . ' revision '
			. $revision['revid'] . "\n";
		// First try to import
		$pagesSoFar++;
		echo "Pages so far: $pagesSoFar \n";
		echo "Attempting import\n";
		$importErrorCount = 0;
		while ( $importErrorCount < 1 ) { // Make one attempt to import
			$ret = $pushWiki->import ( 'enwikipedia', $page['title'], $revision['revid'] );
			if ( !array( $ret ) ) {
				die( "Ret was not an array. Details: $ret \n" );
			}
			if ( !isset( $ret['error'] ) && !isset( $ret['http error'] ) &&
				!isset( $ret['errors'] ) ) {
				$failedImportsInARow = 0;
				return;
			}
			if ( isset( $ret['error'] ) && $ret['error']['code'] === 'edit-already-saved' ) {
				echo "Already saved/imported\n";
				$failedImportsInARow = 0;
				return;
			}
			$importErrorCount++;
		}
		// Import failed once; now try to edit
		echo "Import error; details:\n";
		var_dump ( $ret );
		echo( "Falling back to an edit\n" );
		$keepGoing = true;
		$attemptsSoFar = 0;
		while( $keepGoing ) {
			$ret = $pushWiki->edit ( $page['title'], $revision['*'], $revision['comment'],
				$minor,false,null,false,'', $timestamp, $revision['user'],$contentmodel,null,$tags,
				$revision['revid'], true );
			if ( !array( $ret ) ) {
				die( "Ret was not an array. Details: $ret \n" );
			}
			if ( !isset( $ret['error'] ) && !isset( $ret['http error'] ) &&
				!isset( $ret['errors'] ) ) {
				$failedImportsInARow = 0;
				return;
			}
			if ( isset( $ret['error'] ) && $ret['error']['code'] === 'edit-already-saved' ) {
				echo "Already saved/imported\n";
				$failedImportsInARow = 0;
				return;
			}
			var_dump ( $ret );
			if ( $attemptsSoFar < 2 ) {
				$attemptsSoFar++;
				echo "Edit failed; sleeping 5 seconds and trying again. Attempt $attemptsSoFar\n)";
				sleep(5);
				continue;
			} else {
				$keepGoing = false;
			}
			if ( $failedImportsInARow > 5 ) {
				die( "5 failed imports in a row\n" );
			}
		}
		echo "Error; aborting and saving a failed import template. Details:\n";
		var_dump ( $ret );
		$date = new DateTime();
		$templateToPost = '{{failed import|seconds=' . time() . '|len=' . strlen( $revision['*'] )
			. '|date=' . $date->format('j F Y') . '|time=' . $date->format('H:i:s') . '}}';
		$attemptsSoFar = 0;
		$keepGoing = true;
		while ( $keepGoing ) {
			$ret = $pushWiki->edit ( $page['title'], $templateToPost, $templateToPost,
				false,false,null,false,'', null, $revision['user'],null,null,$tags,
				$revision['revid'], false, true );
			if ( !array( $ret ) ) {
				die( "Ret was not an array. Details: $ret \n" );
			}
			if ( isset( $ret['error'] ) || isset( $ret['http error'] ) ) {
				if ( isset( $ret['error']['code'] ) &&
					$ret['error']['code'] === 'edit-already-saved' ||
						$ret['error']['code'] === 'edit-already-exists' ) {
					echo "Already saved/imported; never mind, all's well!\n";
					$failedImportsInARow = 0;
					return;
				} else{
					$attemptsSoFar++;
					if ( $attemptsSoFar < 3 ) {
						echo "Failed; sleeping 5 seconds and trying again...\n";
						sleep(5);
						continue;
					} else {
						die ( "Couldn't save the failed import template; dying\n" );
					}
				}
			}
			break;
		}
		$failedImportsInARow++;
	}
	return;
}

function doDbQuery ( $db, $query, $suppress = false ) {
	if ( !$suppress ) {
		echo $query . "\n";
	}
	$status = $db->query( $query );
	if ( !$status ) {
		echo "Database write error\n";
		var_dump( $db->error_list );
	}
	return $status;
}

function attemptPushQuery( $pushWiki, $query, $attempts, $requireSuccess = null ) {
	$keepGoing = true;
	while( $keepGoing ) {	
		$ret = $pushWiki->query( $query );
		if ( !array( $ret ) ) {
			die( "Ret was not an array. Details: $ret \n" );
		}
		if ( isset( $ret['error'] ) || isset( $ret['http error'] ) ) {
			var_dump ( $ret );
			$attempts++;
			if ( $attempts > $attempts ) {
				die( "$attempts in a row failed; dying\n" );
			}
		}
	}
	return $ret;
}