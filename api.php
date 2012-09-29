<?php

	require_once 'rdio-consumer-credentials.php' ;
	require_once 'om.php';
	require_once 'rdio.php';
	
	$playback_token = "GBNQZzz3ACojA3J0ZHg0ZDZmcnBjY2syZWo4cXE2dTVjamxhYnMuZGllbWV0YWxsZS5jb22IqHrPSpIrOKtc7w7odJpI";
	$rdio = authenticate();
	
	if(isset($_POST['command']))
	{
		router();
	}
	
	function authenticate()
	{
		session_start();
		# create an instance of the Rdio object with our consumer credentials
		$rdio = new Rdio(array(RDIO_CONSUMER_KEY, RDIO_CONSUMER_SECRET));
		
		# work out what our current URL is
		$current_url = "http" . ((!empty($_SERVER['HTTPS'])) ? "s" : "") .
		  "://" . $_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];
		
		if ($_SESSION['oauth_token'] && $_SESSION['oauth_token_secret']) {
		  # we have a token in our session, let's use it
		  $rdio->token = array($_SESSION['oauth_token'],
		    $_SESSION['oauth_token_secret']);
		  if ($_GET['oauth_verifier']) {
		    # we've been passed a verifier, that means that we're in the middle of
		    # authentication.
		    $rdio->complete_authentication($_GET['oauth_verifier']);
		    # save the new token in our session
		    $_SESSION['oauth_token'] = $rdio->token[0];
		    $_SESSION['oauth_token_secret'] = $rdio->token[1];
		  }
		  # make sure that we can in fact make an authenticated call
		  $currentUser = $rdio->call('currentUser');
		  if ($currentUser) {
		  
		  /*
		  
		    ?><h1><?=$currentUser->result->firstName?>'s Playlists</h1>
		      <ul><?
		    $myPlaylists = $rdio->call('getPlaylists')->result->owned;
		    
		    # list them
		    foreach ($myPlaylists as $playlist) {
		      ?><li><a href="<?= $playlist->shortUrl?>"><?=$playlist->name?></a></li><?
		    }
		    ?></ul><a href="?logout=1">Log out.</a><?
		    
		    */
		    
		    
		  } else {
		    # auth failure, clear session
		    session_destroy();
		    # and start again
		    //return json_encode(new array(success => false));
		  }
		} else {
		  # we have no authentication tokens.
		  # ask the user to approve this app
		  $authorize_url = $rdio->begin_authentication($current_url);
		  # save the new token in our session
		  $_SESSION['oauth_token'] = $rdio->token[0];
		  $_SESSION['oauth_token_secret'] = $rdio->token[1];
			
		  header('Location: '.$authorize_url);
		}
		return $rdio;
		
	}

	function router()
	{
		$command = $_POST['command'];
		switch($command){
			case ('getPlaybackToken'):
				return getPlaybackToken(false);
				break;
				
			default:
				break;
		}
	
	}
	
	function getPlaybackToken($new)
	{
		
		global $rdio, $playback_token;
		if ($new){
			$key = $rdio->call('getPlaybackToken', array(domain => "labs.diemetalle.com"));
			$playback_token = $key->result;
			return $playback_token;
		} else {
			return $playback_token;
		}	
	}
	
	function getPlaylist()
	{
		global $rdio;
		$playlist = $rdio->call('getPlaylists')->result->owned;
		
	
	}

?>