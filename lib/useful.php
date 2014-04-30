<?php

define('VERSION','1.0');

if (file_exists('../config/config.php'))
	include_once('../config/config.php');

include_once('database.php');

// check if the script is called from command line
function is_command_line_interface() {
    return php_sapi_name() === 'cli';
}

// check if file has an ".mp3" extension
function is_mp3($filename) {
	return preg_match('/\.mp3$/i', $filename) ;
}


// check if file has an .jpeg, .jpg, .png, .gif extension
function is_image($filename) {
	return preg_match('/\.(?:jpe?g|gif|png)$/i',$filename) ;
}


// check if the user is logon (check session_id in database)
function is_logon() {
	$nb_session = 0;
	if (isset($_SESSION['user']) && isset($_SESSION['session_id'])) {
		$nb_session = get_value_from_sql(open_database(),"SELECT count(*) FROM session WHERE session_username='".sql_escape_string($_SESSION['user'])."' AND session_id='".sql_escape_string($_SESSION['session_id'])."'");
	}
	return (1 == $nb_session ? true : false);
}


// login the user
function login() {
	$sqlite = open_database();
	$exist_user = exist_user($sqlite,$_POST['user']);
	if ($exist_user) { // user exists, try to authentify
		if (authentificate_user($sqlite,$_POST['user'],$_POST['password'])) { // success
			$_SESSION['user'] = $_POST['user'];
			$_SESSION['session_id'] = create_session($sqlite,$_POST['user']);
			echo 2;
		} else { // fail to authentificate --> wrong password
			echo 0;
		}

	} else { // user don't exist --> create
		if (create_user($sqlite,$_POST['user'],$_POST['password'])) {
			$_SESSION['user'] = $_POST['user'];
			$_SESSION['session_id'] = create_session($sqlite,$_POST['user']);
			echo 1;
		} else { // fail to create user
			echo 0;
		}
	}

	remove_old_session($sqlite); // remove the old session store in database
}


// erase useless '.' or '..' in a file path
function normalize_path($path) {
    $out=array();
    foreach(explode('/', $path) as $i=>$fold){
        if ($fold=='' || $fold=='.') continue;
        if ($fold=='..' && $i>0 && end($out)!='..') array_pop($out);
    else $out[]= $fold;
    } return ($path{0}=='/'?'/':'').join('/', $out);
} 

// invert ASC and DESC
function get_invert_last_order() {
	return (strtoupper($_SESSION['last_order']) == 'ASC' ? 'DESC':'ASC');
}


// screen for authentification
function display_auth() {
	$html = "<caption>Authentification</caption>";
	$html .= "<tr class='authentification'><td>".
				"<div id='auth_user' class='input-group margin-bottom-sm'><span class='input-group-addon'><i class='fa fa-user fa-fw'></i></span><input id='input_user' class='form-control' type='text' placeholder='User'>
				</div>".
				"<div id='auth_password' class='input-group'><span class='input-group-addon'><i class='fa fa-key fa-fw'></i></span><input id='input_password' class='form-control' type='password' placeholder='Password'></div>".
				"<div>You can create a new user if the username don't exists</div>".
			"</td></tr>";
	$html .= "<tr><td><a id='auth_btn' class='btn btn-success btn-sm' href='#'><i class='fa fa-sign-in fa-lg'></i> Login or create</a></td></tr>";
	echo $html;
}


// table of song result
function display_search_result($sql) {
	$sqlite = open_database();
	$nb_rows = get_total_rows_in_sql($sqlite,$sql);

	// order of result
	$sql .= " ORDER BY $_SESSION[last_column] $_SESSION[last_order] ";

	// pagination
	$page 		= isset($_SESSION['page']) 		? (int)$_SESSION['page'] : 1;
	$page_size 	= isset($_SESSION['page_size']) ? (int)$_SESSION['page_size'] : PAGE_SIZE;

	$sql .= " LIMIT ".($page - 1) * $page_size.",$page_size ";

	if ($res = $sqlite->query($sql)) {
		$html = '';

		$first_result_id 	= ($page-1) * $page_size + 1;
		$last_result_id 	= ($page) * $page_size;
		if ($last_result_id > $nb_rows) $last_result_id = $nb_rows ;
		if ($nb_rows <= 0) 	$first_result_id = 0 ;
		$last_page_id  		= ceil($nb_rows / $page_size);

		$html .= "<caption id='pagination'><span id='result_range'>Result(s) from $first_result_id to $last_result_id</span>";
		if ($nb_rows > 0) {
			$html .= "<span id='number_of_results'>$nb_rows</span> song(s) ".
					"<select id='current_page'>";
			for($i=1 ; $i<=$last_page_id ; $i++)
				$html .= "<option value='$i'".($i==$page?' selected':'').">Page $i</option>";
			$html .= "</select> of <span id='number_of_pages'>$last_page_id page(s)</span>";
		}

		$html .= "<span id='result_per_page'><select id='page_size'>".
					"<option value='10' 	".(isset($_SESSION['page_size']) && $_SESSION['page_size']=='10' 	? " selected='selected'":'').">10</option>".
					"<option value='25' 	".(isset($_SESSION['page_size']) && $_SESSION['page_size']=='25' 	? " selected='selected'":'').">25</option>".
					"<option value='50' 	".(isset($_SESSION['page_size']) && $_SESSION['page_size']=='50' 	? " selected='selected'":'').">50</option>".
					"<option value='100' 	".(isset($_SESSION['page_size']) && $_SESSION['page_size']=='100' 	? " selected='selected'":'').">100</option>".
					"<option value='200' 	".(isset($_SESSION['page_size']) && $_SESSION['page_size']=='200' 	? " selected='selected'":'').">200</option>".
					"<option value='0' 		".(isset($_SESSION['page_size']) && $_SESSION['page_size']=='0' 	? " selected='selected'":'').">All</option>".
				"</select> results per page</span>";

		$html .= "</caption><thead><tr>".
				"<th class='icon'></th>".
				"<th class='icon'></th>".
				"<th class='icon'></th>".
				"<th class='icon'></th>".
				"<th class='title sortable'><i class='fa fa-sort-alpha-asc'></i> Title</th>".
				"<th class='artist sortable'><i class='fa fa-sort-alpha-asc'></i> Artist</th>".
				"<th class='album sortable'><i class='fa fa-sort-alpha-asc'></i> Album</th>".
				"<th class='track sortable'><i class='fa fa-sort-numeric-asc'></i> Track</th>".
				"<th class='year sortable'><i class='fa fa-sort-numeric-asc'></i> Year</th>".
				"<th class='genre sortable'><i class='fa fa-sort-alpha-asc'></i> Genre</th>".
				"<th class='length sortable'><i class='fa fa-sort-numeric-asc'></i> Length</th>".
				"</tr></thead><tbody>";
		while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
			// on parcours les résultats
			$html .= '<tr class="song" path="'.convert_local_path_to_virtual_path($row['song_fullpath']).'" title="'.htmlentities($row['song_title'],ENT_COMPAT,'UTF-8').'" artist="'.htmlentities($row['artist_name'],ENT_COMPAT,'UTF-8').'">'.
						'<td class="icon"><i class="fa fa-play fa-lg" title="Play the song"></i></td>'.
						'<td class="icon"><i class="fa fa-plus fa-lg" title="Add to play list"></i></td>'.
						'<td class="icon"><i class="fa fa-star'.($row['favorite_fullpath'] ? '':'-o').' fa-lg" title="Toogle favorite"></i></td>'.
						'<td class="icon"><a href="'.convert_local_path_to_virtual_path($row['song_fullpath']).'"><i class="fa fa-download fa-lg" title="Download file"></i></a></td>'.
						'<td class="title">'.$row['song_title'].'</td>';
			$html .= 	"<td class='artist' onclick='display_artist($row[song_artist_id]);'>$row[artist_name]</td>";
			$html .= 	"<td class='album' onclick='display_album($row[song_album_id]);'>$row[album_title]</td>";
			$html .= 	"<td class='track'>$row[song_track_number]</td>";
			$html .= 	"<td class='year'>$row[song_year]</td>";
			$html .= 	"<td class='genre'>$row[song_genre]</td>";
			$html .= 	"<td class='length'>".sec2minute($row['song_length'])."</td>";
			$html .= "</tr>";
		}
		$html .= "</tbody>";
		$html .= "<tfoot>".
					"<tr id='song_list_action'><td colspan='11'><a id='result_to_playlist_btn' class='btn btn-success btn-sm' href='#'><i class='fa fa-align-justify fa-lg'></i> Add to play list</a> ".get_microdelay()."</td></tr>".
				"</tfoot>";
		echo $html;

	} else {
		echo "Error in search request\n$sql\n".join('\n',$sqlite->errorInfo());
	}
}

// convert "xxx sec" into "xx:xx"
function sec2minute($second) {
	return sprintf('%d:%02d',floor($second / 60),($second % 60));
}

// escape string for sql statement
function sql_escape_string($inp) {
	if(is_array($inp))
		return array_map(__METHOD__, $inp);

	if(!empty($inp) && is_string($inp)) {
		return str_replace(array('\\', "\0", "\n", "\r",  "\x1a","'"), array('\\\\', '\\0', '\\n', '\\r', '\\Z',"''"), $inp);
	}
	return $inp;
}

// extratc tags fom an mp3 file
function get_tags_from_mp3($fullpath) {
	// Initialize getID3 engine
	$getID3 = new getID3;
	// Analyze file and store returned data in $ThisFileInfo
	$ThisFileInfo = $getID3->analyze($fullpath);
	/*
	 Optional: copies data from all subarrays of [tags] into [comments] so
	 metadata is all available in one location for all tag formats
	 metainformation is always available under [tags] even if this is not called
	*/
	getid3_lib::CopyTagsToComments($ThisFileInfo);

	$tags = array('format'=>null,'details'=>null,'tag'=>null,'tags'=>null,'title'=>null,'artist'=>null,'album'=>null,'year'=>null,'track'=>null,'total_track'=>null,'genre'=>null,'comment'=>null, 'disk'=>null, 'hz'=>null,'kbps'=>null,'stereo'=>null,'id3v1'=>null,'id3v2'=>null,'playtime'=>null);

	$tags['format'] = $ThisFileInfo['fileformat'];
	if (isset($ThisFileInfo['comments']['title'][0]))
		$tags['title'] 	= $ThisFileInfo['comments']['title'][0];
	if (isset($ThisFileInfo['comments']['artist'][0]))
		$tags['artist'] = $ThisFileInfo['comments']['artist'][0];
	if (isset($ThisFileInfo['comments']['album'][0]))
		$tags['album'] 	= $ThisFileInfo['comments']['album'][0];
	if (isset($ThisFileInfo['comments']['year'][0]))
		$tags['year'] 	= $ThisFileInfo['comments']['year'][0];
	if (isset($ThisFileInfo['comments']['track'][0]))
		$tags['track'] 	= $ThisFileInfo['comments']['track'][0];
	if (isset($ThisFileInfo['comments']['genre'][0]))
		$tags['genre'] 	= $ThisFileInfo['comments']['genre'][0];
	if (isset($ThisFileInfo['comments']['text'][0]))
		$tags['comment']= join("\n",$ThisFileInfo['comments']['text']);

	// encode if needed
	if ($ThisFileInfo['encoding'] == 'UTF-8')
		$tags = array_map('utf8_decode', $tags);

	$tags['disk'] 	= isset($ThisFileInfo['comments']['part_of_a_set'][0]) ? $ThisFileInfo['comments']['part_of_a_set'][0]:null;
	$tags['hz'] 	= (int)$ThisFileInfo['audio']['sample_rate'];
	$tags['kbps'] 	= (int)($ThisFileInfo['audio']['bitrate']/1000);
	$tags['stereo'] = $ThisFileInfo['audio']['channels'] == 2 ? true:false;
	$tags['id3v1'] 	= isset($ThisFileInfo['id3v1']) ? true:false;
	$tags['id3v2'] 	= isset($ThisFileInfo['id3v2']) ? true:false;
	$tags['playtime']= round($ThisFileInfo['playtime_seconds']);

	if (!$tags['title']) {
		$tags['title'] = basename_without_extension($fullpath); //remove extention
	}

	//print_r($ThisFileInfo);
	return $tags;
}


// remove extension at the end of a basename
function basename_without_extension($path) {
	$tmp = explode('.',basename($path));
	array_pop($tmp);
	return join('.',$tmp);
}

// convert path from web server to OS
function convert_virtual_path_to_local_path($virtual_path) {
	foreach (unserialize(SONGS_PATHS) as $path => $values) {
		if (substr($virtual_path,0,strlen($values['virtual'])) == $values['virtual']) { // if it's the good path
			return $path.substr($virtual_path,strlen($values['virtual']),strlen($virtual_path));
		}
	}
}

// convert path from OS to web server
function convert_local_path_to_virtual_path($local_path) {
	foreach (unserialize(SONGS_PATHS) as $path => $values) {
		if (substr($local_path,0,strlen($path)) == $path) { // if it's the good path
			return $values['virtual'].substr($local_path,strlen($path),strlen($local_path));
		}
	}
}

// replace \ with /
function convert_windows_path_to_linux_path($path) {
	return str_replace('\\','/',$path);
}

function get_path_from_label($label) {
	foreach (unserialize(SONGS_PATHS) as $path => $values) {
		if ($label == $values['label']) {
			return $path;
		}
	}
	return null;
}

function get_microdelay() {
	global $start_time;
	return sprintf('Request took %0.4f sec', microtime(true) - $start_time);
}


// convert sec in week, day, hour, minute, second
function human_readable_time($secs) {
    $units = array(
    	'year'		=>	365*24*3600,
        'week'		=>	7*24*3600,
        'day'		=>	24*3600,
        'hour'		=>	3600,
        'minute'	=>	60,
        'second'	=>	1,
    );

	// specifically handle zero
    if ($secs == 0) return '0 seconds';
    $s = '';
    foreach ( $units as $name => $divisor ) {
        if ( $quot = intval($secs / $divisor) ) {
            $s .= "$quot $name";
            $s .= (abs($quot) > 1 ? 's' : '') . ', ';
            $secs -= $quot * $divisor;
        }
    }
    return substr($s, 0, -2);
}


function human_readable_size($size) {
    $mod = 1024;
    $units = explode(' ','B KB MB GB TB PB');
    for ($i = 0; $size > $mod; $i++) {
        $size /= $mod;
    } 
    return round($size, 2) . ' ' . $units[$i];
}

function quote2html($str) {
	return str_replace('"', '', $str);
}

?>