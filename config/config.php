<?php

# define constant, serialize array
define ('SONGS_PATHS',serialize(array(
	'E:/perso/OST' 			=> array('label'=>'music',		'virtual'=>'/songs'),
	'E:/perso/Livres audio' => array('label'=>'audio book',	'virtual'=>'/books')
)));

define('TITLE','MP3 Player'); // title of the application
define('DATABASE','../../databases/songs.sqlite');
define('PAGE_SIZE',10); // default page size of search results
?>