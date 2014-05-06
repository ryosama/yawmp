<?php
$start_time = microtime(true);
if (!session_id()) session_start();

include('../config/config.php');
include('../lib/useful.php');


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// LOGIN //////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (isset($_POST['what']) && $_POST['what']=='login' && isset($_POST['user']) && strlen($_POST['user'])>0 && isset($_POST['password']) && strlen($_POST['password'])>0) {
	login();
	return;
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// LOGOUT//////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (isset($_POST['what']) && $_POST['what']=='logout') {
	// logout the user (erase session in database)
	$sqlite = open_database();
	$sql = "DELETE FROM session WHERE session_username='".sql_escape_string($_SESSION['user'])."' AND session_id='".sql_escape_string($_SESSION['session_id'])."';";
  	$sqlite->exec($sql);
  	// destroy session on server
	session_destroy();
}



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// VERIFY LOGIN ///////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (!is_logon()) {
	display_auth();
	return;
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// LS /////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['what']) && $_GET['what']=='ls' && isset($_GET['dir'])) {
	$dirs = $files = array();
	if ($_GET['dir'] == '') {
		// add the roots directories declare in config.php
		foreach (unserialize(SONGS_PATHS) as $path => $values) {
			$dirs[] = 	'<tr class="dir" onclick="display_directory(\''.$values['label'].':/\');">'.
						'<td class="icon"><i class="fa fa-folder-o fa-lg"></i></td>'.
						'<td class="icon"></td>'.
						'<td class="icon"></td>'.
						'<td>'.$values['label'].'</td>'.
						'</tr>' ;
		}

	} else { // a directory is specified
		list($label,$path_from_label) = explode(':',utf8_decode($_GET['dir']));
		
		$directory = get_path_from_label($label).$path_from_label;
		$dir = opendir($directory) or die("Could not open song directory ".$directory);
		while (false !== ($file = readdir($dir))) {
			if ($file == '.') continue;

			if (is_mp3($file)) {
				$files[] = 	'<tr class="song" path="'.convert_local_path_to_virtual_path("$directory/$file").'" title="'.basename_without_extension($file).'" author="">'.						
								'<td class="icon"><i class="fa fa-play fa-lg" title="Play the song"></i></td>'.
								'<td class="icon"><i class="fa fa-plus fa-lg" title="Add to play list"></i></td>'.
								'<td class="icon"><a href="'.convert_local_path_to_virtual_path("$directory/$file").'"><i class="fa fa-download fa-lg" title="Download file"></i></a></td>'.
								'<td class="title">'.$file.'</td>'.
							'</tr>' ;

			} elseif (is_dir($directory."/$file") && $file == '..') {
				//echo "<tr><td colspan='10'>debug ".normalize_path(addslashes("$path_from_label"))."</td></tr>";
				if (normalize_path($path_from_label) == '/') { // at the root of the label
					$tmp = '';
				} else {
					$tmp = "$label:".normalize_path(addslashes("$path_from_label/.."));
				}
				$dirs[] = 	'<tr class="dir" onclick="display_directory(\''.$tmp.'\');">'.
							'<td class="icon"><i class="fa fa-level-up fa-lg"></i></td><td colspan="10"></td></tr>' ;

			} elseif (is_dir($directory."/$file")) {
				$dirs[] = 	'<tr class="dir" onclick="display_directory(\''.$label.':'.normalize_path(addslashes("$path_from_label/$file")).'\');">'.
							'<td class="icon"><i class="fa fa-folder-o fa-lg"></i></td>'.
							'<td class="icon"></td>'.
							'<td class="icon"></td>'.
							'<td>'.$file.'</td>'.
							'</tr>' ;

			}
		}
	}

	$html = "<tbody>";
	foreach ($dirs as $dir) 	$html .= $dir;
	foreach ($files as $file) 	$html .= $file;
	$html .= "</tbody><tfoot>".
				"<tr id='song_list_action'>".
					"<td colspan='10'><a id='result_to_playlist_btn' class='btn btn-success btn-sm' href='#'><i class='fa fa-align-justify fa-lg'></i> Add to play list</a> ".get_microdelay()."</td></tr>".
			"</tfoot>";

	echo utf8_encode($html) ;
}




////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////// SEARCH ///////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_GET['what']) && $_GET['what']=='search' && isset($_GET['text']) && $_GET['text'] && isset($_GET['where']) && $_GET['where']) {
	$user_escape = sql_escape_string($_SESSION['user']);
	$texts = explode(' ',$_GET['text']);
	$texts = array_map('trim',$texts);
	$texts = array_map('sql_escape_string',$texts);
	$where = '';

	if 		($_GET['where'] == 'anything') {
		$tmp = array();
		foreach($texts as $text)
			$tmp[] = " (song_fullpath like '%$text%' or song_title like '%$text%' or artist_name like '%$text%' or album_title like '%$text%') ";
		$where = join(' AND ',$tmp);

	} else {
		$tmp = array();
		foreach($texts as $text)
			$tmp[] = " $_GET[where] like '%$text%' ";
		$where = join(' AND ',$tmp);
	}

	$sql = <<<EOT
SELECT *
FROM song
	left join artist
		on song_artist_id = artist.rowid
	left join album
		on song_album_id = album.rowid
	left join favorite
		on song_fullpath = favorite_fullpath and favorite_username='$user_escape'
WHERE
	($where)
EOT;

	$_SESSION['last_sql'] = $sql;
	$_SESSION['last_column'] = 'song_title';
	$_SESSION['last_order'] = 'ASC';
	$_SESSION['page'] = 1;

	display_search_result($sql);
}



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// LAST SONGS PLAYED //////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_GET['what']) && $_GET['what']=='display_last_played') {
	$user_escape = sql_escape_string($_SESSION['user']);
	$sql = <<<EOT
SELECT *
FROM song
	left join artist
		on song_artist_id = artist.rowid
	left join album
		on song_album_id = album.rowid
	left join last_played
		on 	last_played_fullpath = song_fullpath
	left join favorite
		on song_fullpath = favorite_fullpath and favorite_username='$user_escape'
WHERE
		last_played_datetime IS NOT NULL
	and last_played_username = '$user_escape'
EOT;

	$_SESSION['last_sql'] = $sql;
	$_SESSION['last_column'] = 'last_played_datetime';
	$_SESSION['last_order'] = 'DESC';
	$_SESSION['page'] = 1;

	display_search_result($sql);
}



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// TOP SONGS PLAYED //////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_GET['what']) && $_GET['what']=='display_most_played') {
	$user_escape = sql_escape_string($_SESSION['user']);
	$sql = <<<EOT
SELECT *
FROM song
	left join artist
		on song_artist_id = artist.rowid
	left join album
		on song_album_id = album.rowid
	left join last_played
		on 	last_played_fullpath = song_fullpath
	left join favorite
		on song_fullpath = favorite_fullpath and favorite_username='$user_escape'
WHERE
		last_played_datetime IS NOT NULL
	and last_played_username = '$user_escape'
EOT;

	$_SESSION['last_sql'] = $sql;
	$_SESSION['last_column'] = 'last_played_times DESC, last_played_datetime';
	$_SESSION['last_order'] = 'DESC';
	$_SESSION['page'] = 1;

	display_search_result($sql);
}



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// FAVORITE ///////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_GET['what']) && $_GET['what']=='display_favorite') {
	$user_escape = sql_escape_string($_SESSION['user']);
	$sql = <<<EOT
SELECT *
FROM song
	left join artist
		on song_artist_id = artist.rowid
	left join album
		on song_album_id = album.rowid
	left join last_played
		on 	last_played_fullpath = song_fullpath
		and last_played_username = '$user_escape'
	left join favorite
		on song_fullpath = favorite_fullpath
WHERE
		favorite_fullpath IS NOT NULL
	and favorite_username='$user_escape'
EOT;

	$_SESSION['last_sql'] = $sql;
	$_SESSION['last_column'] = 'song_title';
	$_SESSION['last_order'] = 'ASC';
	$_SESSION['page'] = 1;

	display_search_result($sql);
}




////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// STATISTICS //////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_GET['what']) && $_GET['what']=='display_stats') {
	$user_escape = sql_escape_string($_SESSION['user']);
	$sqlite = open_database();

	$local_version 		= trim(join('',@file('../VERSION')));
	$uptodate_version 	= @join('',@file('https://raw.githubusercontent.com/ryosama/yawmp/master/VERSION'));
	$message_version	= $local_version < $uptodate_version ? '<i class="fa fa-exclamation-triangle"></i> A new version is available on https://github.com/ryosama/yawmp/' : '<i class="fa fa-check"></i> You\'re uptodate' ;

	$total_song 		= get_value_from_sql($sqlite,"SELECT count(*) FROM song");
	$total_time_song	= get_value_from_sql($sqlite,"SELECT sum(song_length) FROM song");
	$total_size_song	= get_value_from_sql($sqlite,"SELECT sum(song_filesize) FROM song");
	$total_artist 		= get_value_from_sql($sqlite,"SELECT count(*) FROM artist");
	$total_album 		= get_value_from_sql($sqlite,"SELECT count(*) FROM album");
	$total_song_listen 	= get_value_from_sql($sqlite,"SELECT count(*) FROM last_played WHERE last_played_username='$user_escape'");
	$total_playlist 	= get_value_from_sql($sqlite,"SELECT count(*) FROM playlist WHERE playlist_username='$user_escape'");
	$total_favorite 	= get_value_from_sql($sqlite,"SELECT count(*) FROM favorite WHERE favorite_username='$user_escape'");
	$most_listened_song = get_value_from_sql($sqlite,"SELECT song_title,artist_name FROM last_played LEFT JOIN song on last_played_fullpath=song_fullpath LEFT JOIN artist on song_artist_id=artist.rowid WHERE last_played_username='$user_escape' ORDER BY last_played_times DESC, last_played_datetime DESC LIMIT 0,1");
	$last_listened_song = get_value_from_sql($sqlite,"SELECT song_title,artist_name FROM last_played LEFT JOIN song on last_played_fullpath=song_fullpath LEFT JOIN artist on song_artist_id=artist.rowid WHERE last_played_username='$user_escape' ORDER BY last_played_datetime DESC LIMIT 0,1");

	echo 	"<tr class='stats'><td class='stats_label'>Application version</td><td class='stats_value'>r$local_version ($message_version)</td></tr>".
			"<tr class='stats'><td class='stats_label'>User</td><td class='stats_value'>$_SESSION[user]</td></tr>".
		 	"<tr class='stats'><td class='stats_label'>Number of songs</td><td class='stats_value'>$total_song</td></tr>".
			"<tr class='stats'><td class='stats_label'>Total time of listen</td><td class='stats_value'>".human_readable_time($total_time_song)."</td></tr>".
			"<tr class='stats'><td class='stats_label'>Total size of song</td><td class='stats_value'>".human_readable_size($total_size_song)."</td></tr>".
			"<tr class='stats'><td class='stats_label'>Number of artists</td><td class='stats_value'>$total_artist</td></tr>".
			"<tr class='stats'><td class='stats_label'>Number of album</td><td class='stats_value'>$total_album</td></tr>".
			"<tr class='stats'><td class='stats_label'>Number of song listen</td><td class='stats_value'>$total_song_listen</td></tr>".
			"<tr class='stats'><td class='stats_label'>Number of play list</td><td class='stats_value'>$total_playlist</td></tr>".
			"<tr class='stats'><td class='stats_label'>Number of favorite song</td><td class='stats_value'>$total_favorite</td></tr>".
			"<tr class='stats'><td class='stats_label'>Most listened song</td><td class='stats_value'>$most_listened_song[song_title] by $most_listened_song[artist_name]</td></tr>".
			"<tr class='stats'><td class='stats_label'>Last listened song</td><td class='stats_value'>$last_listened_song[song_title] by $last_listened_song[artist_name]</td></tr>".
			"<tr id='song_list_action'><td></td><td>".get_microdelay()."</td></tr>";
}



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// GET ALL TRACK FROM AN ALBUM ////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_GET['what']) && $_GET['what']=='display_album' && isset($_GET['id']) && $_GET['id']) {
	$id = sql_escape_string($_GET['id']);

	$sql = <<<EOT
SELECT *
FROM song
	left join artist
		on song_artist_id = artist.rowid
	left join album
		on song_album_id = album.rowid
	left join favorite
		on song_fullpath = favorite_fullpath
WHERE
	song_album_id = '$id'
EOT;

	$_SESSION['last_sql'] = $sql;
	$_SESSION['last_column'] = 'song_track_number';
	$_SESSION['last_order'] = 'ASC';
	$_SESSION['page'] = 1;

	display_search_result($sql);
}



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// GET ALL TRACK FROM AN ARTIST ///////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_GET['what']) && $_GET['what']=='display_artist' && isset($_GET['id']) && $_GET['id']) {
	$id = sql_escape_string($_GET['id']);

	$sql = <<<EOT
SELECT *
FROM song
	left join artist
		on song_artist_id = artist.rowid
	left join album
		on song_album_id = album.rowid
	left join favorite
		on song_fullpath = favorite_fullpath
WHERE
	song_artist_id = '$id'
EOT;

	$_SESSION['last_sql'] = $sql;
	$_SESSION['last_column'] = 'song_title';
	$_SESSION['last_order'] = 'ASC';
	$_SESSION['page'] = 1;

	display_search_result($sql);
}



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// CHANGE PAGE /////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_GET['what']) && $_GET['what']=='change_page' && isset($_GET['page']) && $_GET['page']) {
	$_SESSION['page'] = $_GET['page'];
	display_search_result($_SESSION['last_sql']);
}



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// CHANGE PAGE SIZE /////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_GET['what']) && $_GET['what']=='change_page_size' && isset($_GET['size']) && $_GET['size']) {
	$_SESSION['page_size'] = $_GET['size'];
	display_search_result($_SESSION['last_sql']);
}




////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// CHANGE SORT /////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_GET['what']) && $_GET['what']=='change_sort' && isset($_GET['column']) && $_GET['column']) {
	$_GET['column'] = strtolower(sql_escape_string(trim($_GET['column'])));
	
	switch ($_GET['column']) {
		case 'artist':
			$_SESSION['last_order'] = ($_SESSION['last_column'] == 'artist_name' ? get_invert_last_order() : 'asc');
			$_SESSION['last_column'] = 'artist_name';
			break;

		case 'album': 
			$_SESSION['last_order'] = ($_SESSION['last_column'] == 'album_title' ? get_invert_last_order() : 'asc');
			$_SESSION['last_column'] = 'album_title';
			break;

		case 'track':
			$_SESSION['last_order'] = ($_SESSION['last_column'] == 'song_track_number' ? get_invert_last_order() : 'asc');
			$_SESSION['last_column'] = 'song_track_number';
			break;

		case 'genre': 
			$_SESSION['last_order'] = ($_SESSION['last_column'] == 'song_genre' ? get_invert_last_order() : 'asc');
			$_SESSION['last_column'] = 'song_genre';
			break;

		case 'year': 
			$_SESSION['last_order'] = ($_SESSION['last_column'] == 'song_year' ? get_invert_last_order() : 'asc');
			$_SESSION['last_column'] = 'song_year';
			break;

		case 'title':
			$_SESSION['last_order'] = ($_SESSION['last_column'] == 'song_title' ? get_invert_last_order() : 'asc');
			$_SESSION['last_column'] = 'song_title';
			break;

		case 'length':
			$_SESSION['last_order'] = ($_SESSION['last_column'] == 'song_length' ? get_invert_last_order() : 'asc');
			$_SESSION['last_column'] = 'song_length';
			break;
	}

	display_search_result($_SESSION['last_sql']);
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// MARK SONG AS PLAYED ////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_GET['what']) && $_GET['what']=='mark_song_as_played' && isset($_GET['song']) && $_GET['song']) {
	$fullpath = null;
	$user_escape = sql_escape_string($_SESSION['user']);
	$sqlite = open_database();

	if (is_numeric($_GET['song'])) {	// rowid of the song
		$fullpath = get_value_from_sql($sqlite,"SELECT song_fullpath FROM song WHERE rowid='".sql_escape_string($_GET['song'])."'");
	} else {	// fullpath of the song
		$fullpath = convert_virtual_path_to_local_path($_GET['song']);
	}

	// mark the song as read at NOW()
	$sql = "INSERT  INTO last_played (last_played_fullpath,last_played_datetime,last_played_times,last_played_username) VALUES ('".sql_escape_string($fullpath)."',datetime('now'),0,'$user_escape')";
	if (!$sqlite->query($sql))	echo "Error in request\n$sql\n".join('\n',$sqlite->errorInfo());

	$sql = "UPDATE last_played SET last_played_times=last_played_times+1, last_played_datetime=datetime('now') WHERE last_played_fullpath='".sql_escape_string($fullpath)."' and last_played_username='$user_escape'";
	if (!$sqlite->query($sql))	echo "Error in request\n$sql\n".join('\n',$sqlite->errorInfo());
}




////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// TOOGLE FAVORITE ////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_GET['what']) && $_GET['what']=='toogle_favorite' && isset($_GET['song']) && $_GET['song']) {
	$fullpath = null;
	$user_escape = sql_escape_string($_SESSION['user']);
	$sqlite = open_database();

	if (is_numeric($_GET['song'])) {	// rowid of the song
		$fullpath = get_value_from_sql($sqlite,"SELECT song_fullpath FROM song WHERE rowid='".sql_escape_string($_GET['song'])."'");
	} else {	// fullpath of the song
		$fullpath = convert_virtual_path_to_local_path($_GET['song']);
	}

	$is_favorite = get_value_from_sql($sqlite,"SELECT count(*) FROM favorite WHERE favorite_fullpath='".sql_escape_string($fullpath)."' AND favorite_username='$user_escape'");
	if ($is_favorite) {
		$sql = "DELETE FROM favorite WHERE favorite_fullpath='".sql_escape_string($fullpath)."' and favorite_username='$user_escape'";
		if (!$sqlite->query($sql))	echo "Error in request\n$sql\n".join('\n',$sqlite->errorInfo());
		echo 'remove';

	} else {
		$sql = "INSERT OR IGNORE INTO favorite (favorite_fullpath,favorite_username) VALUES ('".sql_escape_string($fullpath)."','$user_escape')";
		if (!$sqlite->query($sql))	echo "Error in request\n$sql\n".join('\n',$sqlite->errorInfo());
		echo 'add';
	}
}




////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// DISPLAY ALBUM COVER ////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_GET['what']) && $_GET['what']=='display_info_cover_lyrics' && isset($_GET['path']) && $_GET['path']) {
	$sqlite = open_database();
	$song_info = array('covers'=>array(), 'cover'=>'', 'infos'=>'', 'lyrics'=>'', 'from'=>'');

	$path = sql_escape_string(normalize_path(convert_virtual_path_to_local_path($_GET['path'])));
	$sql = <<<EOT
SELECT 	*
FROM 	album
	LEFT JOIN song
		ON song_album_id=album.rowid
	LEFT JOIN artist
		ON song_artist_id=artist.rowid
WHERE
	song_fullpath='$path'
EOT;
	//echo $sql;

	if ($res = $sqlite->query($sql)) {
		$row = $res->fetch(PDO::FETCH_ASSOC);

		// try to look for pictures in the directory
		$dirname_iso8859_1 	= dirname(convert_virtual_path_to_local_path(utf8_decode($_GET['path'])));

		if (is_dir($dirname_iso8859_1) && $dir = opendir($dirname_iso8859_1)) { // is dir
			while($file = readdir($dir))
				if (is_image($file))
					$song_info['covers'][] = '<img src="'.convert_local_path_to_virtual_path(utf8_encode($dirname_iso8859_1.'/'.$file)).'"/>';
			closedir($dir);
		}
		
		$url = "http://api.chartlyrics.com/apiv1.asmx/SearchLyricDirect?artist=".urlencode(utf8_decode($row['artist_name']))."&song=".urlencode(utf8_decode($row['song_title']));
		$response = @join('',@file($url));
		if ($response) {
			$song_info['from'] = 'chartlyrics';
			
			// try a ressource for covers and lyrics
			preg_match('/<LyricCovertArtUrl>(.+?)<\/LyricCovertArtUrl>/i',$response,$regs);
			if (isset($regs[1]))
				$song_info['covers'][] = '<img src="'.$regs[1].'"/>';

			preg_match("/<Lyric>([^<]+?)<\/Lyric>/ix",$response,$regs);
			$song_info['lyrics'] = isset($regs[1]) ? $regs[1] : '';
			$song_info['lyrics'] = preg_replace("/\n\n\n+/", "\n\n", $song_info['lyrics']);

		} else {

			// no response -> try another ressource for covers
			$url = "http://itunes.apple.com/search?term=".urlencode(utf8_decode($row['album_title'])).'+'.urlencode(utf8_decode($row['artist_name']))."&entity=album";
			$response = @join('',@file($url));
			if ($response) {
				$song_info['from'] = 'itunes';
				
				$json = json_decode($response);
				if ($json->{'resultCount'} > 0)
					$song_info['covers'][] = "<img src='".$json->results[0]->artworkUrl100."'/>";
			}
		}

		// get infos for sql
		$song_info['infos'] = 	"<div class='title'>$row[song_title]</div>".
								($row['artist_name'] ? "<div class='artist'>by ".$row['artist_name']."</div>" : '').
								($row['album_title'] ? "<div class='album'>Album : ".$row['album_title'].($row['song_year'] ? ' ('.$row['song_year'].')':'')."</div>" : '').
								($row['song_track_number'] ?
									"<div class='track'>".
									($row['song_disk_number'] ? "Disk $row[song_disk_number] - ":'').
									"Track ".$row['song_track_number'].
									"</div>" : '').
								($row['song_genre'] ? "<div class='genre'>Genre : ".$row['song_genre']."</div>" : '').
								"<div class='quality'>".($row['song_stereo'] ? 'Stereo':'Mono')." $row[song_hz]hz $row[song_kbps]kbps</div>".
								"<div class='size'>".floor($row['song_length'] / 60).'min '.($row['song_length'] % 60)."sec (".round($row['song_filesize'] / 1024 / 1024,2)." Mb)</div>";


		// at least one picture
		if (sizeof($song_info['covers'])>0) {
			// create Fotorama object
			$song_info['cover'] .= '<div id="fotorama" class="fotorama" data-width="150" data-ratio="150/150" data-max-width="100%" data-autoplay="true" data-autoplay="3000" data-loop="true" data-arrows="false" data-click="true" data-auto="false">';
			$song_info['cover'] .= join('',$song_info['covers']);
			$song_info['cover'] .= '</div>';
		}


	} else {
		echo "Error in request\n$sql\n".join('\n',$sqlite->errorInfo());
	}

	// encode results
	header('Content-type: text/json');
	header('Content-type: application/json');
	echo json_encode($song_info);
}



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// SAVE THE PLAY LIST ////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_POST['what']) && $_POST['what']=='save_playlist') {
	$_SESSION['playlist'] = array(); // empty the previous play list
	if (isset($_POST['playlist']) && is_array($_POST['playlist'])) {
		foreach($_POST['playlist'] as $hash_data) {
			array_push($_SESSION['playlist'],$hash_data);
		}
	}
}



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// SAVE THE PLAY LIST IN DATABASE /////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_POST['what']) && $_POST['what']=='save_playlist_in_database' && isset($_POST['name']) && strlen($_POST['what'])>0) {
	$sqlite = open_database();

	// delete the old play list with the same name
  	delete_playlist($sqlite,$_POST['name']);

  	// save the new play list name
  	$sql = "INSERT INTO playlist (playlist_name,playlist_username) VALUES ('".sql_escape_string($_POST['name'])."','".sql_escape_string($_SESSION['user'])."');";
  	$sqlite->exec($sql);

  	// get id of the new play list
  	$playlist_id = get_value_from_sql($sqlite,"SELECT rowid FROM playlist WHERE playlist_name='".sql_escape_string($_POST['name'])."' AND playlist_username='".sql_escape_string($_SESSION['user'])."' LIMIT 0,1;");

  	// insert song in the play list
  	if (isset($_POST['playlist']) && is_array($_POST['playlist'])) {
		foreach($_POST['playlist'] as $hash_data) {
  			$sqlite->exec("INSERT INTO playlist_song (playlist_song_playlist_id,playlist_song_song_fullpath) VALUES ('$playlist_id','".sql_escape_string(convert_virtual_path_to_local_path($hash_data['path']))."');");
  		}
  	}
	echo 1;
}




////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// LOAD THE PLAY LIST ////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_GET['what']) && $_GET['what']=='load_playlist') {
	if (isset($_SESSION['playlist']) && is_array($_SESSION['playlist'])) {
		// do nothing
	} else {
		$_SESSION['playlist'] = array(); // create the array
	}
	echo json_encode($_SESSION['playlist']);
}



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// LOAD THE PLAY LIST FROM DATABASE ///////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_GET['what']) && $_GET['what']=='load_playlist_from_database' && isset($_GET['playlist_id']) && strlen($_GET['playlist_id'])>0) {
	$user_escape = sql_escape_string($_SESSION['user']);
	$playlist_id_escape = sql_escape_string($_GET['playlist_id']);
	$sqlite = open_database();

	$sql = <<<EOT
SELECT
	song_fullpath,song_title,artist_name
FROM	
	playlist_song
	LEFT JOIN playlist
		ON playlist_song_playlist_id=playlist.rowid
	LEFT JOIN song
		ON playlist_song_song_fullpath=song_fullpath
	LEFT JOIN artist
		ON song_artist_id=artist.rowid
WHERE
		playlist_username='$user_escape'
	and playlist.rowid='$playlist_id_escape'
ORDER BY
	playlist_song.rowid ASC;
EOT;

	$songs = array();
  	$res = $sqlite->query($sql);
  	while($row = $res->fetch(PDO::FETCH_ASSOC)) {
		$songs[] = array('path'=>convert_local_path_to_virtual_path($row['song_fullpath']), 'title'=>$row['song_title'], 'artist'=>$row['artist_name']);
	}
	echo json_encode($songs);
}





////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// DELETE THE PLAY LIST IN DATABASE ///////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_GET['what']) && $_GET['what']=='delete_playlist_in_database' && isset($_GET['playlist_id']) && strlen($_GET['playlist_id'])>0) {
	$sqlite = open_database();
	$user_escape = sql_escape_string($_SESSION['user']);
	$playlist_id_escape = sql_escape_string($_GET['playlist_id']);
	$playlist_name = get_value_from_sql($sqlite,"SELECT playlist_name FROM playlist WHERE playlist_username='$user_escape' AND playlist.rowid='$playlist_id_escape'");
	delete_playlist($sqlite,$playlist_name);
}




////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// DISPLAY PLAY LIST //////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_GET['what']) && $_GET['what']=='display_playlist') {
	$user_escape = sql_escape_string($_SESSION['user']);
	$sqlite = open_database();

	$sql = <<<EOT
SELECT
	playlist.rowid as playlist_id,playlist_name,count(playlist_song_song_fullpath) as nb_songs
FROM
	playlist_song
	LEFT JOIN playlist
		ON playlist_song_playlist_id=playlist.rowid
WHERE
	playlist_username='$user_escape'
GROUP BY
	playlist_song_playlist_id
ORDER BY
	playlist_name ASC;
EOT;

  	$res = $sqlite->query($sql);
  	while($row = $res->fetch(PDO::FETCH_ASSOC)) {
  		//
		echo 	"<tr class='playlist'><td class='playlist_name'>".
					"<i class='fa fa-play fa-lg' title='Load the play list' onclick='load_playlist_from_database($row[playlist_id]);'></i> ".
					"<i class='fa fa-eraser fa-lg' title='Delete the play list' onclick='delete_playlist_in_database($row[playlist_id],this);'></i> ".
					"<span onclick='load_playlist_from_database($row[playlist_id]);'>$row[playlist_name] ($row[nb_songs] song".($row['nb_songs']>1 ?'s':'').")</span>".
				"</td></tr>";
	}
	echo 	"<tr id='song_list_action'><td>".get_microdelay()."</td></tr>";
}



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// SEARCH HELP ////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_POST['what']) && $_POST['what']=='search_help' && isset($_POST['text']) && strlen($_POST['text'])>=2) {
	$sqlite = open_database();
	$text_escape = sql_escape_string($_POST['text']);
	$results = array(	'artist'=>array(),
						'title'=>array(),
						'album'=>array(),
						'text'=>$_POST['text']
					);

	// search in artist name
	$sql = "SELECT DISTINCT(artist_name) FROM artist WHERE artist_name LIKE '%$text_escape%' ORDER BY artist_name ASC LIMIT 0,10";
	$res = $sqlite->query($sql);
  	while($row = $res->fetch(PDO::FETCH_ASSOC))
  		array_push($results['artist'],$row['artist_name']);

  	// search in album name
  	$sql = "SELECT DISTINCT(album_title) FROM album WHERE album_title LIKE '%$text_escape%' ORDER BY album_title ASC LIMIT 0,10";
	$res = $sqlite->query($sql);
  	while($row = $res->fetch(PDO::FETCH_ASSOC))
  		array_push($results['album'],$row['album_title']);

  	// search in song title
  	$sql = "SELECT DISTINCT(song_title) FROM song WHERE song_title LIKE '%$text_escape%' ORDER BY song_title ASC LIMIT 0,10";
	$res = $sqlite->query($sql);
  	while($row = $res->fetch(PDO::FETCH_ASSOC))
  		array_push($results['title'],$row['song_title']);

	echo json_encode($results);
}




////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////// SAVE NEW PASSWORD //////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
elseif (isset($_POST['what']) && $_POST['what']=='save_new_password' && isset($_POST['current_password']) && strlen($_POST['current_password'])>0 && isset($_POST['new_password']) && strlen($_POST['new_password'])>0) {
	
	$sqlite = open_database();
	if (authentificate_user($sqlite,$_SESSION['user'],$_POST['current_password'])) {
		echo change_password($sqlite,$_SESSION['user'],$_POST['new_password']);
	} else {
		echo '0';
	}
}





else {
	echo "Unknown ajax request";
}
?>