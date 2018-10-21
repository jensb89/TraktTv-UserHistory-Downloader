<?php
/* Script to download Trakt v2 User History 
   Copyright Jens Brauer, 2018
*/ 

require_once("fanartDownloader.php");

/* ToDo: Use cache file to reduce API calls */

$DEBUG = false;
$URL_HISTORY = "https://api.trakt.tv/users/jensb/history/episodes";
$URL_AUTHORIZATION = "https://api.trakt.tv/oauth/token";
$fanart_api_key = 'abcde'; //ToDo: Put this into SQL database


/* Open database */
$db_file = 'dummy_database.db';
$db = new SQLite3($db_file, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);

/* Load app credentials */
$trakt_app_credentials = $db->query("SELECT client_id, client_secret, redirect_uri  FROM oauth_clients");
$res = $trakt_app_credentials->fetchArray();
$clientId = $res[0];
$clientSecret = $res[1];
$redirectUri = $res[2];

if ($DEBUG){
	echo $clientId;
	echo "Client ID" . $clientId . "\n";
	echo "Client Secret" . $clientSecret . "\n";
	echo "Redirect URI" . $redirectUri . "\n";
}

/* Load Access Tokens */
$tokens = $db->query("SELECT access_token, refresh_token, expires, created_at FROM oauth_access_tokens");
$res = $tokens->fetchArray();
$accessToken = $res[0];
$refreshToken = $res[1];
$expires = $res[2];
$createdAt = $res[3];

if ($DEBUG){
	echo "AToken" . $accessToken . "\n";
	echo "Refresh Token" . $refreshToken . "\n";
	echo "Redirect URI" . $redirectUri . "\n";
}


/* cURL requests */
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $URL_HISTORY);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);

curl_setopt($ch, CURLOPT_HTTPHEADER, array(
  "Content-Type: application/json",
  "trakt-api-version: 2",
  "trakt-api-key: ".$clientId,
  'Authorization: Bearer '.$accessToken
));

$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

/* Check Request Status: Update Access Token if neccessary */
if ( $status != 200 ){
	/* Something went wrong */
    if ($status == 403){
		/* Error 403 = Wrong Authorization due to expired AccessToken */
		if($DEBUG){
			echo "failed with status".$status.", response ".$response;
			echo "Try refreshing access token";
		}
		/* Prepare Refresh Token Data */
		$data = array();
		$data['client_secret'] = $clientSecret;
		$data['redirect_uri'] = $redirectUri;
		$data['grant_type'] = 'refresh_token';
		$data['refresh_token'] = $refreshToken;
		$data['client_id']  = $clientId;
		$content = json_encode($data);

		if($DEBUG){
			print_r($content);
		}

		/* Request new Access Token with the Refresh Token */
		$curl = curl_init($URL_AUTHORIZATION);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $content);

		$jsonResponse = curl_exec($curl);
		$status2 = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if ($status2 != 200){
			die("Error: call to URL $URL_AUTHORIZATION failed with status $status2, response $jsonResponse, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
		}
		curl_close($curl);

		/* Get new Tokens from Data */
		$response = json_decode($jsonResponse, true);
		if($DEBUG){
			print_r($response);
		}
		$accessTokenNew = $response["access_token"];
		$refreshTokenNew = $response["refresh_token"];
		$createdAtNew = $response["created_at"];
		$expiresNew = $response["expires_in"];

		/* Save new Tokens into database */
		$sql = "UPDATE oauth_access_tokens SET access_token=:atoken, refresh_token=:rtoken, created_at=:created, expires=:expiresin";
		$stmt = $db->prepare($sql);
		// passing values to the parameters
		$stmt->bindValue(':atoken', $accessTokenNew);
		$stmt->bindValue(':rtoken', $refreshTokenNew);
		$stmt->bindValue(':created', $createdAtNew);
		$stmt->bindValue(':expiresin', $expiresNew);
		$stmt->execute();

		exit(0);
		/* TODO: Make the cURL requests in a function and call the function here again. Currently, the data will be shown in the next reload... */
	}
	else{
		die("Error: call to URL" .$URL_HISTORY." failed with status".$status.", response ".$response.", curl_error " . curl_error($ch) . ", curl_errno " . curl_errno($ch));
	}
}

curl_close($ch);

/* SUCCESS: RETURN DATA */
header('Content-Type: text/javascript');
echo $_GET['callback'] . '('.$response.')';


/* UPDATE FANARTS */
$fanart = new fanArtDownloader($db_file);
$lastRequest = json_decode($response, true); //latest request
$fanart->checkTvShowChange($lastRequest[0]['show']['ids']['tvdb']);
$fanart->downloadImage();

?>