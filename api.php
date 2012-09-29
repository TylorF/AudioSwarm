<?php

	require_once 'rdio-consumer-credentials.php' ;
	require_once 'om.php';
	require_once 'rdio.php';
	
	$oatoken = null;
	$oatokensec = null;
	//$playback_token = "GBNQZzz3ACojA3J0ZHg0ZDZmcnBjY2syZWo4cXE2dTVjamxhYnMuZGllbWV0YWxsZS5jb22IqHrPSpIrOKtc7w7odJpI";
	$rdio = authenticate();
	//setUp();

	//print getPlaybackToken(true);
	//print json_encode(get_object_vars(search("Psy", "gangnam style")), true);
	//print getSongQueue();
	
	if(isset($_GET['command']))
	{
		getRouter();
	}
	
	if(isset($_POST['command']))
	{
		postRouter();
	}
	
	function setUp()
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
		    $json = json_encode(array("oauth_token"=>$rdio->token[0], "oauth_token_secret"=>$rdio->token[1]));
		    file_put_contents('tokens.json', $json);
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
		  # we have no authentication tokens
		  # ask the user to approve this app
		  $authorize_url = $rdio->begin_authentication($current_url);
		  # save the new token in our session
		  $_SESSION['oauth_token'] = $rdio->token[0];
		  $_SESSION['oauth_token_secret'] = $rdio->token[1];
		  $json = json_encode(array("oauth_token"=>$rdio->token[0], "oauth_token_secret"=>$rdio->token[1]));
		  file_put_contents('tokens.json', $$json);
			
		  header('Location: '.$authorize_url);
		}
		return $rdio;
		
	}
	
	function authenticate()
	{
		global $oatoken, $oatokensec;
		$rdio = new Rdio(array(RDIO_CONSUMER_KEY, RDIO_CONSUMER_SECRET));
		$file = file_get_contents('tokens.json');
		if ($file != false)
		{
			$arr = json_decode($file, true);
			$oatoken = $arr['oauth_token'];
			$oatokensec = $arr['oauth_token_secret'];
			$rdio->token = array($oatoken,
			  $oatokensec);
			return $rdio;
		}		
		else 
		{
			return setUp();
		}
		
		
	}

	function postRouter()
	{
		$command = $_POST['command'];
		switch($command){
			case ('getPlaybackToken'):
				return getPlaybackToken(false);
				break;
			case ('search'):
				return search($_POST['keyword']);
				break;
			case ('songQueue'):
				print getSongQueue();
				break;
			case ('getCurrentlyPlayingSong'):
				print getNowPlayingInterface();
				break;
				
			default:
				break;
		}
	
	}
	
	function getRouter()
	{
		$command = $_GET['command'];
		switch($command){
			case ('getPlaybackToken'):
				return getPlaybackToken(false);
				break;
			case ('search'):
				return search($_GET['keyword']);
				break;
			case ('songQueue'):
				print getSongQueue();
				break;
			case ('getCurrentlyPlayingSong'):
				print getNowPlayingInterface();
				break;
				
			default:
				break;
		}
	
	}
	
	function getPlaybackToken($new)
	{
		
		global $rdio, $playback_token;
		if ($new){
			$key = $rdio->call('getPlaybackToken', array("domain" => "labs.diemetalle.com"));
			$playback_token = $key->result;
			return $playback_token;
		} else {
			return $playback_token;
		}	
	}
	
	function getSongQueue()
	{
		$playlist = getPlaylist("");
		$playback_token = getPlaybackToken(true);
		$array = array("token"=>$playback_token, "playlist"=>$playlist->key);
		$json = json_encode($array, true);
		return $json;
	}
	
	function getPlaylist($extras)
	{
		global $rdio;
		$playlist = reset($rdio->call('getPlaylists', array("extras"=>$extras))->result->owned);
		if($playlist != null){
			return $playlist;
		} else {
			return createPlaylist();
		}
	
	}
	
	function createPlaylist()
	{
		global $rdio;
		$playlist = $rdio->call('createPlaylist', array(
			"name"=>"default",
			"description" => "default playlist",
			"tracks" => ""));
		return $playlist->result;
	
	}
	
	function removePlayedSong()
	{
		
	
	}
	
	function getNowPlayingInterface()
	{
		return json_encode(get_object_vars(getNowPlaying()), true);
	}

	function getNowPlaying()
	{
		$playlist = getPlaylist("tracks");
		$song = reset($playlist->tracks);
		return $song;
		
	}

	function search($key)
	{
		global $rdio;
		if($key)
		{
			$results = $rdio->call('search', array("query" => $key, "types" => "Artist, Track"))->result;
			//$arr = json_decode($results, true);

			echo json_encode(array("key"=> $results->results['1']->key, 
				"artist"=> $results->results['1']->artist,
				"song"=> $results->results['1']->name
				));
		}
		else
		{
			echo json_encode(array("returnValue"=>"Not Found"));
		}


	}

?>