YAWMP
=====

Yet Another Web Music Player

Play your music with a browser, anywhere.

Browser compatibility
=====================
- Firefox
- Chrome
- Android browser

Technology
==========
- Full ajax
- Bootstrap
- Fontawesome (no images in the application)
- PHP
- PHP lib "getid3"
- SQLite

Install
=======
- Install a Web server
- Install a PHP environnement
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
		</Directory>

		In my browser now I can view my songs in http://myserver/songs/

- Launch your browser and go to http://myserver/choose_a_name/
- Follow the Wizard install instructions
- Go to "www/choose_a_name/bin" and launch "build_database.php"
- Your songs will be index (can take a while)
- Your ready to listen music at http://myserver/choose_a_name/