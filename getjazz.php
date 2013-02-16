<?php

class GetJazz {
	/* song()
	 * get most recent song posted to [website] playlist
	 * @param 
	 * @return song or error message
	 *
	 */
	
	public function song() {
		/* playlist location */
		$playlist = 'http://www.kmhd.org/playlists.php';
		//$playlist = 'http://www.kmhd.org/playlist.php'; // test can't resolve url
		//$playlist = 'testdatawithsongs.php'; // test data with songs
		//$playlist = 'testdatawithoutsongs.php'; // test data without songs
		//$playlist = 'testfiledoesntexist.php'; // test file doesn't exist

		$dom = new DOMDocument;
		
		/* 
		 * surpress errors if loading the playlist fails
		 * returns can't find song message
		 */
		if (@$dom->loadHTMLFile($playlist) === false ) {
			return 'Sorry, I misplaced playlist. It must be around here somewhere....';
		} else {
			
			/* get last song played from html document */
			
			$html = array();
			$domxpath = new DOMXPath($dom);
			$newDom = new DOMDocument;
			/* keep output cleaner than not */
			//$newDom->formatOutput = false;
			//$newDom->preserveWhiteSpace = true;
			
			/* query page w/o know structure */
			$song = $domxpath->query("//div[@class='song']");
			$songTime = $domxpath->query("//div[@class ='time']");
			/* $filtered =  $domxpath->query('//div[@class="className"]');
			 * '//' when you don't know 'absolute' path
			 
			 * The above returns DomNodeList Object
			 * I use following routine to convert it to string(html); copied it 
       * from someone's post in this site. Thank you.
			 * Which i copied from that person -- 
       *   http://php.net/manual/en/domdocument.getelementsbytagname.php
       *
			 * We don't need to iterate though all the songs for this app as we only 
       * want the lastest song.
			 */
			
			/* load first song, item(0) if it exists */
			$songPulled = $song->item(0); 
			$songTimePulled = $songTime->item(0);
			/* check if songPulled is object
			 * if not the radio dj didn't update the playlist or station is after hours
			 * check if songTimePulled has value, just incase something weird happend 
       * and songPulled was good
			 */
			if (!is_object($songPulled) || !is_object($songTimePulled)) {
				return 'The DJ must be sleeping.  The playlist hasn\'t been updated...';			
			}

			$node = $newDom->importNode( $songTimePulled, true );    // import node time of last song
			$newDom->appendChild($node);  // append node
			$html[0] = $newDom->saveHTMl();  // save time to html array
			
			$node2 = $newDom->importNode( $songPulled, true );    // import node last song
			$newDom->replaceChild($node2, $node);  // relace child node with song
			$html[1] = $newDom->saveHTMl(); // save song to html array
			
			/* clean and remove html */
			$songTime = trim(strip_tags($html[0]));
			$htmlSong = explode("<br>", $html[1]);
			$songData = trim(strip_tags($htmlSong[0]));

			/* add extra space betwen time and song */			
			return $songTime.' '.$songData;
		}
	}
	
	/* show()
	 * get the name and url to the show currently in progress
	 * @param none
	 * @return show name and url to show
	 * 
	 */
	public function show() {
		/* schedule location */
		$schedule = 'http://www.kmhd.org/schedule.php';
		/* schedule initiation*/
		$dom = new DOMDocument;
		$dom->preserveWhiteSpace = false;
		
		/* 
		 * surpress errors if loading the schedule fails
		 * returns can't find song message
		 */
		if (@$dom->loadHTMLFile($schedule) === false ) {
			return 'Sorry, the schedule has been misplaced and the station is being ran by monkeys!';
		} else {
			
			/* build html list of shows and info */
			$html = array();
			$domxpath = new DOMXPath($dom);
			$newDom = new DOMDocument;
			$show = $domxpath->query("//td[@*]");
			$i = 0;
			while( $myItem = $show->item($i++) ){
				$node = $newDom->importNode( $myItem, true );    // import node
				$newDom->appendChild($node);                    // append node
			}
			$html = $newDom->saveHTML();
			/*
			 * Return the t value corresponding to current day, hour.
			 * If that value isn't found, find the most recent previous value and
			 * return that value.
			 */
			
			/* set the timezone for use with finding show time */
			date_default_timezone_set('America/Los_Angeles');
	
			/* Starts with current day and hour, iterating backwards from current hour
			 * untill the most recent show is found.  If show isn't found for the
			 * current day, decrement one day, and then iterate backwards from the 
			 * current hour again.  This will go on until the most recent show is 
			 * found.
			 */
	
			$today = (date('w') + 1);
			//$today = 3; //test
			$continue = true;
			$a = array();
			while ($continue) {
				$hour = date('H');
				//$hour = 23; //test
				/* special case, this show doesn't follow the rules */
				if ($today >= 2 && $today <= 5 && $hour == 23) {
					return "\"Something Different\" http://kmhd.org/programs/52";
				}else{
					$nowShow = "t_" . $today . "_" . $hour . ".0";
					$hourCont = true;
					while ($hourCont){
						$nowShow = "t_" . $today . "_" . $hour . ".0";
						if (preg_match("/$nowShow/", $html)) {
							/* found a match! */
							/* return the first found value - that is the current show */
							$newDom2 = new DOMDocument;
							$showLink = $domxpath->query("//td[@id='" . $nowShow . "']/a");
							$t = $showLink->item(0);
							$node2 = $newDom2->importNode( $t, true );    // import node
							$newDom2->appendChild($node2);
							$currentShow = $newDom2->saveHTML();
							$showName = trim(strip_tags($currentShow));
							preg_match("/\"(.*?)\"/", $currentShow, $showLink);
							/* show name and link to show */
							return "\"" . $showName . "\"" . ' http://kmhd.org' . $showLink[1];
						}
						$hour = --$hour;
						if ($hour == -1) $hourCont = false;
					}
					$today = --$today;
					if ($today == 0) $continue = false;				
				}
			}
		}
	}
}

