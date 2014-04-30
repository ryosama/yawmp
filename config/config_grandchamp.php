<?php
# define constant, serialize array
define ('SONGS_PATHS',serialize(array(
	'E:/_sons' 				=> array('label'=>'music',		'virtual'=>'/sons'),
	'E:/_livre audio' 		=> array('label'=>'audio book',	'virtual'=>'/audio_books'),
	'E:/_podcast' 			=> array('label'=>'podcast',	'virtual'=>'/_podcast')
)));

define('TITLE','MP3 Player'); // title of the application
define('DATABASE','../../databases/songs.sqlite');
define('PAGE_SIZE',10); // default page size of search results
?>