<?php
/* Download FanArt Poster */

class FanArtDownloader
{
	var $API_URL = "http://webservice.fanart.tv/v3/tv/";
	var $outputFile = '../../tv-movie/tvposter.jpg';

	var $APIKEY;
	var $lastShow;
	var $lastShowID;
	var $posterUrl;

	var $db;

	var $isTvShowChanged = false;

	// load entries from database
	function loadDatabase($db_file){
		$this->db = new SQLite3($db_file, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);

		$fanArtInfos = $this->db->query("SELECT apiKey, lastShowName, lastShowID, TvPosterUrl  FROM fanart");
		$res = $fanArtInfos->fetchArray();

		$this->APIKEY = $res[0];
		$this->lastShow = $res[1];
		$this->lastShowID = $res[2];
		$this->posterUrl = $res[3];
	}

	// constructor
	function __construct($db_file){
		$this->loadDatabase($db_file);
	}

	// destructor
	function __destruct() {
        $this->db->close();
    }

    //check if tv show is changed or file is empty
    function checkTvShowChange($tvdbID)
    {
    	if ( (0 == filesize($this->outputFile)) || !($this->lastShowID == $tvdbID) ){
    		$this->isTvShowChanged = true;

			/* Update Poster */
			$ch = curl_init();
			$url = $this->API_URL.$tvdbID.'?api_key='.$this->APIKEY;
			echo $url;
			curl_setopt($ch, CURLOPT_URL, $this->API_URL.$tvdbID.'?api_key='.$this->APIKEY);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			$response = curl_exec($ch);
			curl_close($ch);

			$json_tmp = json_decode($response,true);
			$this->posterUrl = $json_tmp['tvposter'][0]['url'];
			$this->updateDatabase($json_tmp['name'],$json_tmp['thetvdb_id'],$json_tmp['tvposter'][0]['url']);
		}
    }

    // update database entries
    function updateDatabase($name, $id, $url)
    {
    	$sql = "UPDATE fanart SET lastShowName=:name, lastShowID=:id, TvPosterUrl=:url";
		$stmt = $this->db->prepare($sql);
		// passing values to the parameters
		$stmt->bindValue(':name', $name );
		$stmt->bindValue(':id', $id);
		$stmt->bindValue(':url', $url);
		$stmt->execute();
    }

	// download new poster images if tv show has changed
	function downloadImage()
	{
		if ($this->isTvShowChanged==true){
		    $fp = fopen ($this->outputFile, 'w+');              // open file handle
		    $ch = curl_init($this->posterUrl);
		    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // enable if you want
		    curl_setopt($ch, CURLOPT_FILE, $fp);          // output to file
		    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		    curl_setopt($ch, CURLOPT_TIMEOUT, 1000);      // some large value to allow curl to run for a long time
		    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
		    // curl_setopt($ch, CURLOPT_VERBOSE, true);   // Enable this line to see debug prints
		    curl_exec($ch);
		    curl_close($ch);                              // closing curl handle
		    fclose($fp);                                  // closing file handle
		}
	}
}

?>