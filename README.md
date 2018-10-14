# TraktTv-UserHistory-Downloader
Simple PHP script to get the latest watched epsiodes from Trakt.tv and download a fanart image.
For trakt.tv api v2.
The code is still in a very early development state...

## Usage
- Create a Trakt.tv API app (https://trakt.tv/oauth/applications)
- Redirect URI should point to the redirect.php file
- Click "Authorize" on the Trakt website
- Put the code into the getTokenFromCode.php file
- Obtain a first Access Token by calling getTokenFromCode.php
- Fill in all information into the SQLITE database file (or create a new one)
- Fill in the FanArt API key into getTraktUserHistory.php
- Call getTraktUserHistory.php to fetch new history data and download fanart images

## Note
This is in a very early state and was mainly used for testing purposes. A lot of things can be improved that includes using a cache file,
using the database file for everything, get rid off the getTokenFromCode.php, code style, and more. I may do so in the future :)
