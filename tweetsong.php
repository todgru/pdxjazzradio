<?php

# Tweet Song!

/* loop forever until it breaks */
while (1){	

	$result = post_tweet();
	/* echo the return code */
	echo $result;
		
	/* sleep for 11 seconds (327 hits/hr) to limit our api calls to less than 350/hour */
	echo "sleep for 11 seconds \n";
	sleep (11);
}

function post_tweet() {
	/* where our secret codes live */
	include('config.php');
	
	/* the class that gets and cleans the song from the website */
	include_once('getjazz.php');
	
	/* Matt Harris' OAuth library to make the connection
	 * This lives at: https://github.com/themattharris/tmhOAuth
	 */
	include_once("$incPath/tmhOAuth.php");
	
	/* set the time we will append to tweet */
	date_default_timezone_set('America/Los_Angeles');
	$currentTime = date('H:i:s');
	
	/* where we store the last tweet id we received */
	$file = $path . 'tweet_id.txt';
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
	/* error from api could live here */
	if (isset($connection->response['response']->error)){
		return $currentTime." Response error: ".$connection->response['response']->error;
	}
	/* if we don't get a 200 response code, display code */
	if ($connection->response['code'] !='200'){
		return $currentTime." Response error code: ".$connection->response['code']."\n";
	}
	
	/* display our api remaining hits */
	$connection->request('GET', $connection->url('1/account/rate_limit_status'));
	$rate = $connection->response;
	$remaining_hits = json_decode($rate['response']);
	echo 'remaing_hits/hourly_limit ' .$remaining_hits->remaining_hits.'/'.$remaining_hits->hourly_limit."\n";
	/* checking if any tweet were found */
	if (empty($result)){
		return $currentTime." sorry no tweets to us \n";
	}
	
	$list = json_decode($result);
	/* if list empty, then no tweets to us */
	if (empty($list)){
		return $currentTime." No tweets since the last one (json_decode(\$result) came back null) \n";
	}
	
	echo "array# tweet_id user_id screen_name tweet_to_me \n";
	foreach(array_keys($list) as $key) {
		echo $currentTime." new tweet! from @".$list[$key]->user->screen_name ."\n";
		/* new tweets!  
		* check if the new tweets match song request code*/
		if ($list[$key]->text == $tweetPhrase) {
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
		} elseif ($list[$key]->text == $tweetShow) {
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
			
		/* special die code to kill remotely*/	
		} elseif ($list[$key]->text == $diePhrase) {
			echo "told to DIE \n";
			echo "write to file and die... \n";
			$current_tweet_id = $list[0]->id;
			file_put_contents($file, $current_tweet_id);
			die();
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
