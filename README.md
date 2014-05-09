YAWMP
=====

Yet Another Web Music Player

Play your music with a browser, anywhere.

Browser compatibility
=====================
- Firefox
- Chrome
- Android browser

Audio codec compatibility
=====================
- MP3
- OGG
- more to come...

Technology
==========
- HTML5
- Full ajax
- JQuery
- Bootstrap
- Fontawesome (no images in the application)
- PHP5
- PHP lib "getid3"
- SQLite

Install
=======
- Install a Web server
- Install a PHP environnement
- Enable openssl extension on PHP (only needed for version checking)
- Copy the project files to your "www/choose_a_name" web directory
- Create some alias in your web server for your songs.
	Your songs should be stream by your web browser.
	Example :
		I have songs in "D:/personal/music/"
		I create an Alias in Apache web server

		Alias /songs "D:/personal/music/"
		<Directory "D:/personal/music/">
	        	Options Indexes MultiViews
		        AllowOverride None
	        	Order allow,deny
		        Allow from all
		        Require all granted
		</Directory>

		In my browser now I can view my songs in http://myserver/songs/

- Launch your browser and go to http://myserver/yawmp/
- Follow the Wizard install instructions
- Go to "www/choose_a_name/bin" and launch "build_database.php"
- Your songs will be index (can take a while)
- Your ready to listen music at http://myserver/yawmp/