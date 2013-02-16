<?php

/*
 * tweetsongCron.php
 * this version is intended to be run using cron.
 * If lock on file "lock.txt", exits, else continues.
 * output, if any, will be dumped to a log via cron
 * 
 */

date_default_timezone_set('America/Los_Angeles');

/* lock file for cron control */
$lock = $path . "lock.txt";

/* if 1 then ok*/
$ok = file_get_contents($lock);
if ( $ok == 0){
	echo date('Y-m-d H:i:s') . "told to die \n";
	exit;
}

/* if file isn't locked, run tweensong */
$fp = fopen($lock, 'r+');
if(!flock($fp, LOCK_EX | LOCK_NB)) {
		#echo date('H:i:s') . "Unable to obtain lock\n";
		exit(-1);
}

/* while file is open and locked iterate through < a minutes worth of tweetsong*/
$i = 1;

while ($i <= 5 ){
	
	$result = post_tweet($lock);
	/* echo the return code */
	echo $result;	
	if ($i < 5 ) {
		sleep (10);
	}
	$i++;
}

fclose($fp);
exit;



/* function to tweet current song or show */

function post_tweet($lock) {
	/* where our secret codes live */
	include( $path . 'config.php');
	
	/* the class that gets and cleans the song from the website */
	include_once( $path . 'getjazz.php');
	
	/* Matt Harris' OAuth library to make the connection
	 * This lives at: https://github.com/themattharris/tmhOAuth
	 */
	include_once("$incPath/tmhOAuth.php");
	
	/* set the time we will append to tweet */
	date_default_timezone_set('America/Los_Angeles');
	$currentTime = date('H:i:s');
	
	/* where we store the last tweet id we received */
	$file ='tweet_id.txt';
	$last_tweet_id = file_get_contents($file);

	/* new connection Set the authorization values */
	$connection = new tmhOAuth(array(
		'consumer_key' => $consumer_key,
		'consumer_secret' => $consumer_secret,
		'user_token' => $user_token,
		'user_secret' => $user_secret,
	)); 
	

	
	/**
	 * Make api call, get tweets to us since_id, we don't want tweets older 
	 * than the the most recent last tweet we stored
	 */
	$result = array(); 
	/* do this incase of error, like tweet_id.txt is empty or error finding file
	 * need a better solution than saving to file.  DB seems like too much extra for so simple. 
	 * making this a huge number so it will not tweet if this script dies strangely
	 */
	if (!$last_tweet_id){
		$last_tweet_id = "910512460661264384";
	}
	/*  */
	$connection->request('GET', $connection->url('1/statuses/mentions'), array('trim_user' => false, 'since_id' => $last_tweet_id)); //
	$result = $connection->response['response'];
	$error_time = date('Y-m-d H:i:s');
	/* error from api could live here */
	if (isset($connection->response['response']->error)){
		return $error_time." Response error: ".$connection->response['response']->error;
	}
	/* if we don't get a 200 response code, display code */
	if ($connection->response['code'] !='200'){
		return $error_time." Response error code: ".$connection->response['code']."\n";
	}
	
	/* display our api remaining hits */
	$connection->request('GET', $connection->url('1/account/rate_limit_status'));
	$rate = $connection->response;
	$remaining_hits = json_decode($rate['response']);
	#echo date('Y-m-d H:i:s') . ' remaing_hits/hourly_limit ' .$remaining_hits->remaining_hits.'/'.$remaining_hits->hourly_limit."\n";
	/* checking if any tweet were found */
	if (empty($result)){
		return $currentTime." sorry no tweets to us \n";
	}
	
	$list = json_decode($result);
	/* if list empty, then no tweets to us */
	if (empty($list)){
		return; //$currentTime." No tweets since the last one (json_decode(\$result) came back null) \n";
	}
	
	echo "array# tweet_id user_id screen_name tweet_to_me \n";
	foreach(array_keys($list) as $key) {
		echo date('Y-m-d H:i:s') ." new tweet! from @".$list[$key]->user->screen_name ."\n";
		/* new tweets!  
		* check if the new tweets match song request code*/
		if ($list[$key]->text == $tweetPhrase) 
		{
			/* display on cl */
			echo $key.'  '.$list[$key]->id.'  ' .$list[$key]->user->id.'   '.$list[$key]->user->screen_name.'   '. $list[$key]->text. "\n";
			/* format tweet*/
			$newSong = new GetJazz();
			$tweet_text = "@".$list[$key]->user->screen_name .' '. $newSong->song() . " #jazz #pdx " . $currentTime;
			/* restrict tweet length to 140 characters */
			$tweet_text = substr($tweet_text, 0, 139);
			echo $tweet_text ."\n";
			$connection->request('POST', $connection->url('1/statuses/update'), array('status' => $tweet_text));
			echo "tweeted....response code:" . $connection->response['code']."\n";
			/* should probably do something here (log error, store to retweet later) if the response code is not 200*/
		
			/* check if the new tweet matches out show request */
		} 
		elseif ($list[$key]->text == $tweetShow) 
		{
			echo "Request for show! \n";
			/* display on cl */
			echo $key.'  '.$list[$key]->id.'  ' .$list[$key]->user->id.'   '.$list[$key]->user->screen_name.'   '. $list[$key]->text. "\n";
			/* format tweet*/
			$newShow = new GetJazz();
			$tweet_text = "@".$list[$key]->user->screen_name .' '. $newShow->show() . " #jazz #pdx ". $currentTime;
			/* restrict tweet length to 140 characters */
			$tweet_text = substr($tweet_text, 0, 139);
			echo $tweet_text ."\n";
			$connection->request('POST', $connection->url('1/statuses/update'), array('status' => $tweet_text));
			echo "tweeted....response code:" . $connection->response['code']."\n";
			
		}
		/* special die code to kill remotely*/	
		elseif ($list[$key]->text == $diePhrase) {
			echo "told to DIE \n";
			echo "write tweet_id and lock 0 to file and exit... \n";
			$current_tweet_id = $list[0]->id;
			file_put_contents($file, $current_tweet_id);
			file_put_contents($lock, '0');
			exit;
		/* what'd you say? unknown tweet response */
		} elseif ($list[$key]->text != $tweetPhrase &&
							$list[$key]->text != $tweetShow &&
							$list[$key]->text != $diePhrase) {
							
			$tweet_text = "@".$list[$key]->user->screen_name . " Hey hip cat. What jive are you talkin'? Tweet to me 'song' or 'show', dig? #jazz #pdx ". $currentTime;
			/* restrict tweet length to 140 characters */
			$tweet_text = substr($tweet_text, 0, 139);
			echo $tweet_text ."\n";
			$connection->request('POST', $connection->url('1/statuses/update'), array('status' => $tweet_text));
			echo "tweeted....response code:" . $connection->response['code']."\n";
		} else {
				echo $currentTime." no request for songs \n";
		}

	}
	/* write latest(newest) recieved tweet id to file */
	$current_tweet_id = $list[0]->id;
	file_put_contents($file, $current_tweet_id);
}
?>
