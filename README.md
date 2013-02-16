#PDXJazzRadio
 _Twitter Auto-Responder Robot_

This is the initial commit of a tweetsong bot.

Users who tweet '@YOURSPECIALUSERNAME song' will receive a reply containing the time and song last updated on the KMHD playlist.  

Using The Matt Harris' OAuth library.  https://github.com/themattharris/tmhOAuth

Create a config.php file to hold your API tokens.  
In this file you should add: 

 * `$tweetPhrase = '@YOURSPECIALUSERNAME song'` -- this is matched and returns current playing song 
 * `$tweetShow   = '@YOURSPECIALUSERNAME show'` -- this is matched and returns the name of the current show
 * `$diePhrase   = '@YOURSPECIALUSERNAME SPECIALDIEPHRASE'` -- In case something bad happens, kill the app remotely 

**@todo** allow users to direct message bot 'song', 'show', whatever and receive a direct message with requested information

**@todo** This should run on cron or better yet as a daemon - get rid of while().

This is for fun.  I have no affiliation with KMHD 89.1 FM, Portland, OR.

Comments and critiques welcome.

http://todgru.com/pdxjazzradio
