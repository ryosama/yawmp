<?php
// this script build or update the database
include_once('../lib/useful.php');

if (!is_command_line_interface()) 
	die("This script can only be called from command line");


// include getID3() library (can be in a different directory if full path is specified)
require_once('../lib/getid3/getid3.php');

$sqlite = open_database();

$arguments = getopt('f::',array('filter::'));
if (isset($arguments['filter'])) {
	// a filter is specifie erase only the found songs
	echo "Applying filter : $arguments[filter]\n";
} else {
	// no filter specifie --> erase all the data
	echo "Erase database\n";
	erase_data($sqlite);
}

create_database('',$sqlite);

foreach (unserialize(SONGS_PATHS) as $path => $values) {
	$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($path)),RecursiveIteratorIterator::SELF_FIRST);

	foreach($objects as $name => $object){
	    if(is_mp3($object->getFilename())) {

	    	// test if Pathname is not in the filter --> skip
	    	if (isset($arguments['filter']) &&  stripos($object->getPathname(),$arguments['filter']) === false)
	    		continue;

	    	// song is in the filter (or no filter specified)
    		//echo "ANALYSE ".$object->getPathname()."\n";

    		// get tags from song
    		$tags = get_tags_from_mp3($object->getPathname());

    		// insert artist and album
    		$artist_id = get_artist_id($sqlite,$tags['artist']);
    		if (!$artist_id)
    			$artist_id = insert_artist($sqlite,$tags['artist']);

    		$album_id = get_album_id($sqlite,$tags['album']);
    		if (!$album_id)
    			$album_id = insert_album($sqlite,$tags['album']);

    		// if filter is specified, delete the song first and insert new tags
    		if (isset($arguments['filter'])) {
    			if (erase_song_by_fullpath($sqlite,$object->getPathname()))
    				echo "UPDATE ".$object->getPathname()."\n";
    			else
    				echo "ADD ".$object->getPathname()."\n";
    		} else {
                echo "ADD ".$object->getPathname()."\n";
            }

    		// insert the song
			insert_song($sqlite,$object->getPathname(),$tags,$artist_id,$album_id);
	    } // if is_mp3
	} // foreach file
} // foreach each path
?>