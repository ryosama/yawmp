<?php 

function get_total_rows_in_sql($sqlite,$sql) {
  $sql_count = preg_replace('/^\s*SELECT\s++.+?\s+FROM\s+/i',"SELECT count(*) AS nb_rows FROM ",$sql);
  if ($res = $sqlite->query($sql_count)) {
    $row = $res->fetch(PDO::FETCH_ASSOC);
    return $row['nb_rows'];
  } else {
    echo "Error in search request\n$sql_count\n".join('\n',$sqlite->errorInfo());
  }
}



function open_database(){
  $sqlite = null;
  $database = '../'.DATABASE;

  if (file_exists($database)) {
    try {
      $sqlite = new PDO('sqlite:'.$database); // success
      //$sqlite->sqliteCreateFunction('REGEXP', 'preg_match', 2); // on cree la fonction REGEXP dans sqlite.
    } catch (PDOException $exception) {
      echo "Unable to open database : ".$database.' ';
      die ($exception->getMessage());
    }
  }
  return $sqlite;
}


// extract a data from a SQL order
function get_value_from_sql($sqlite,$sql) {
  $res = $sqlite->query($sql);
  if (!$res)  echo "Error in request\n$sql\n".join('\n',$sqlite->errorInfo());
  $row = $res->fetch(PDO::FETCH_ASSOC);

  if (sizeof($row) == 1) { // if only one row --> return the value
    $keys = @array_keys($row);
    return $row[$keys[0]];

  } else { // if more than value, return hash table
    return $row;
  }
}


// return id of the insert (or selected) artist
function insert_artist($sqlite,$artist) {
  $sql = "INSERT OR IGNORE INTO artist (artist_name) VALUES ('".sql_escape_string(utf8_encode($artist))."');";
  $sqlite->exec($sql);
  return get_artist_id($sqlite,$artist);
}

function get_artist_id($sqlite,$artist) {
  return get_value_from_sql($sqlite,"SELECT rowid FROM artist WHERE artist_name='".sql_escape_string(utf8_encode($artist))."' LIMIT 0,1;");
}

function get_last_artist_id($sqlite) {
  return get_value_from_sql($sqlite,"SELECT rowid FROM artist ORDER BY rowid DESC LIMIT 0,1;");
}

// return id of the insert (or selected) album
function insert_album($sqlite,$album) {
  $sql = "INSERT OR IGNORE INTO album (album_title) VALUES ('".sql_escape_string(utf8_encode($album))."');";
  $sqlite->exec($sql);
  return get_album_id($sqlite,$album);
}

function get_album_id($sqlite,$album) {
  return get_value_from_sql($sqlite,"SELECT rowid FROM album WHERE album_title='".sql_escape_string(utf8_encode($album))."' LIMIT 0,1;");
}

function get_last_album_id($sqlite) {
  return get_value_from_sql($sqlite,"SELECT rowid FROM album ORDER BY rowid DESC LIMIT 0,1;");
}

function get_song_id($sqlite,$fullpath) {
  return get_value_from_sql($sqlite,"SELECT rowid FROM song WHERE song_fullpath='".sql_escape_string(utf8_encode($fullpath))."' LIMIT 0,1;");
}


// return id of the insert (or selected) song
function insert_song($sqlite,$fullpath,$tags=null,$artist_id=null,$album_id=null) {
  if ($tags == null) {
    $tags = get_tags_from_mp3($fullpath);
  }

  if ($artist_id == null)
    get_artist_id($sqlite,$tags['artist']);

  if ($album_id == null)
    get_album_id($sqlite,$tags['album']);

  $fullpath_converted = convert_windows_path_to_linux_path($fullpath);

  $sql = "INSERT OR IGNORE INTO song (song_title,song_fullpath,song_length,song_genre,song_year,song_track_number,song_disk_number,song_commentary,song_hz,song_kbps,song_stereo,song_id3v1,song_id3v2,song_artist_id,song_album_id,song_filesize,song_format) VALUES (".
              "'".sql_escape_string(utf8_encode($tags['title']))."',".
              "'".sql_escape_string(utf8_encode($fullpath_converted))."',".
              "'".sql_escape_string(utf8_encode($tags['playtime']))."',".
              "'".sql_escape_string(utf8_encode($tags['genre']))."',".
              "'".sql_escape_string(utf8_encode($tags['year']))."',".
              "'".sql_escape_string(utf8_encode($tags['track']))."',".
              "'".sql_escape_string(utf8_encode($tags['disk']))."',".
              "'".sql_escape_string(utf8_encode($tags['comment']))."',".
              "'".sql_escape_string($tags['hz'])."',".
              "'".sql_escape_string($tags['kbps'])."',".
              "'".sql_escape_string($tags['stereo'])."',".
              "'".sql_escape_string($tags['id3v1'])."',".
              "'".sql_escape_string($tags['id3v2'])."',".
              "'".sql_escape_string($artist_id)."',".
              "'".sql_escape_string($album_id)."',".
              "'".sql_escape_string(filesize($fullpath))."',".
              "'".sql_escape_string($tags['format'])."'".
          ");";
    $sqlite->exec($sql);
    return get_song_id($sqlite,$fullpath_converted);
}


// erase a unique song in database
function erase_song_by_fullpath($sqlite,$fullpath) {
  $sql = "DELETE FROM song WHERE song_fullpath='".sql_escape_string(utf8_encode(convert_windows_path_to_linux_path($fullpath)))."';";
  return $sqlite->exec($sql);
}


// erase table with no remanent information
function erase_data($sqlite) {
  // do NOT inverse order !!!
  $sqlite->query("DROP TABLE [artist];");
  $sqlite->query("DROP TABLE [album];");
  $sqlite->query("DROP TABLE [song];");
}

// create a session with a uniq id in database
function create_session($sqlite,$username) {
  $session_id = uniqid();
  $sql = "REPLACE INTO session (session_username,session_id,session_creation_datetime) VALUES ('".sql_escape_string($username)."','".sql_escape_string($session_id)."',datetime('now'));";
  if (!$sqlite->exec($sql)) { // if error --> false
    return false;
  }
  return $session_id;
}

// remove session older than one day
function remove_old_session($sqlite) {
  $sql = "DELETE FROM session WHERE session_creation_datetime < datetime('now','-1 day');";
  $sqlite->exec($sql);
}

// create new user in database
function create_user($sqlite,$username,$password) {
  $salt = uniqid();
  $md5 = md5($salt.$password);
  $sql = "INSERT OR IGNORE INTO user (user_name,user_salt,user_password) VALUES ('".sql_escape_string($username)."','".sql_escape_string($salt)."','".sql_escape_string($md5)."');";
  return $sqlite->exec($sql);
}

// change password in database
function change_password($sqlite,$username,$password) {
  $salt = uniqid();
  $md5 = md5($salt.$password);
  $sql = "UPDATE user SET user_salt='".sql_escape_string($salt)."',user_password='".sql_escape_string($md5)."' WHERE user_name='".sql_escape_string($username)."';";
  return $sqlite->exec($sql);
}

// check username and password
function authentificate_user($sqlite,$username,$password) {
  if (exist_user($sqlite,$username)) {
    $password_info = get_value_from_sql($sqlite,"SELECT user_salt,user_password FROM user WHERE user_name='".sql_escape_string($username)."'");
    if (md5($password_info['user_salt'].$password) == $password_info['user_password'])
      return true;
    else
      return false;

  } else {
    return false; // user don't exist
  }
}

// check if username exists in database
function exist_user($sqlite,$username) {
  return get_value_from_sql($sqlite,"SELECT count(*) FROM user WHERE user_name='".sql_escape_string($username)."'");
}

// erase the play list by name and user in active session
function delete_playlist($sqlite,$playlist_name) {
  // check if a play list with the same name exists
  $playlist_id = get_value_from_sql($sqlite,"SELECT rowid FROM playlist WHERE playlist_name='".sql_escape_string(utf8_encode($playlist_name))."' AND playlist_username='".sql_escape_string(utf8_encode($_SESSION['user']))."' LIMIT 0,1;");

  // erase old play list
  if ($playlist_id) {
    // erase song in play list
    $sql = "DELETE FROM playlist_song WHERE playlist_song_playlist_id='$playlist_id';";
     $sqlite->exec($sql);

    // erase play list
    $sql = "DELETE FROM playlist WHERE playlist_name='".sql_escape_string(utf8_encode($playlist_name))."' AND playlist_username='".sql_escape_string(utf8_encode($_SESSION['user']))."';";
    $sqlite->exec($sql);
  }
}


function create_database($sqlite = null) {
  if (!$sqlite) {
    $database = '../'.DATABASE;
    
    if (!is_dir(dirname($database)))
      mkdir(dirname($database), 0777, true) or die("Could not create path to the database '".dirname($database)."'");

    $sqlite = new PDO('sqlite:'.$database); // success
    if (!$sqlite)
      die("Could not create database '$database'");
  }

	//////////////////////////////////////////// TABLE artist //////////////////////////////////////////////////////////////
	$sql = <<<EOT
CREATE TABLE IF NOT EXISTS [artist] (
  [artist_name] CHAR NOT NULL, 
  CONSTRAINT [sqlite_autoindex_artist_1] PRIMARY KEY ([artist_name]));
EOT;
	$sqlite->query($sql) or die("Unable to create table artist in database ".DATABASE.'. '.join('\n',$sqlite->errorInfo()));


	//////////////////////////////////////////// TABLE album //////////////////////////////////////////////////////////////
	$sql = <<<EOT
CREATE TABLE IF NOT EXISTS [album] (
  [album_title] CHAR NOT NULL);
EOT;
	$sqlite->query($sql) or die("Unable to create table album in database ".DATABASE.'. '.join('\n',$sqlite->errorInfo()));


	//////////////////////////////////////////// TABLE song //////////////////////////////////////////////////////////////
	$sql = <<<EOT
CREATE TABLE IF NOT EXISTS [song] (
  [song_title] CHAR NOT NULL, 
  [song_fullpath] TEXT NOT NULL, 
  [song_album_id] INT REFERENCES [album]([rowid]), 
  [song_artist_id] INT REFERENCES [artist]([rowid]), 
  [song_length] INT, 
  [song_genre] CHAR, 
  [song_year] INT, 
  [song_track_number] INT, 
  [song_disk_number] INT, 
  [song_commentary] TEXT, 
  [song_hz] INT, 
  [song_kbps] INT, 
  [song_stereo] BOOL, 
  [song_id3v1] BOOL,
  [song_id3v2] BOOL,
  [song_filesize] INT NOT NULL,
  [song_format] CHAR NOT NULL,
  CONSTRAINT [sqlite_autoindex_song_1] PRIMARY KEY ([song_fullpath]));
EOT;
	$sqlite->query($sql) or die("Unable to create table song in database ".DATABASE.'. '.join('\n',$sqlite->errorInfo()));


//////////////////////////////////////////// TABLE user //////////////////////////////////////////////////////////////
$sql = <<<EOT
CREATE TABLE IF NOT EXISTS [user] (
  [user_name] CHAR NOT NULL, 
  [user_salt] CHAR NOT NULL, 
  [user_password] CHAR NOT NULL,
  CONSTRAINT [] PRIMARY KEY ([user_name]));
EOT;
  $sqlite->query($sql) or die("Unable to create table user in database ".DATABASE.'. '.join('\n',$sqlite->errorInfo()));


	//////////////////////////////////////////// TABLE last_played //////////////////////////////////////////////////////////////
$sql = <<<EOT
CREATE TABLE IF NOT EXISTS [last_played] (
  [last_played_fullpath] CHAR NOT NULL REFERENCES [song]([song_fullpath]), 
  [last_played_datetime] DATETIME NOT NULL, 
  [last_played_times] INT DEFAULT (0), 
  [last_played_username] CHAR NOT NULL CONSTRAINT [username] REFERENCES [user]([user_name]), 
  CONSTRAINT [] PRIMARY KEY ([last_played_fullpath], [last_played_username]));
EOT;
	$sqlite->query($sql) or die("Unable to create table last_played in database ".DATABASE.'. '.join('\n',$sqlite->errorInfo()));
	$sqlite->query("CREATE INDEX IF NOT EXISTS [last_play] ON [last_played] ([last_played_datetime]);") or die("Unable to create index last_play in database ".DATABASE.'. '.join('\n',$sqlite->errorInfo()));
  


  //////////////////////////////////////////// TABLE favortite //////////////////////////////////////////////////////////////
$sql = <<<EOT
CREATE TABLE IF NOT EXISTS [favorite] (
  [favorite_fullpath] CHAR NOT NULL REFERENCES [song]([song_fullpath]), 
  [favorite_username] CHAR NOT NULL CONSTRAINT [username] REFERENCES [user]([user_name]), 
  CONSTRAINT [sqlite_autoindex_favorite_1] PRIMARY KEY ([favorite_fullpath], [favorite_username]));
EOT;
  $sqlite->query($sql) or die("Unable to create table favorite in database ".DATABASE.'. '.join('\n',$sqlite->errorInfo()));


//////////////////////////////////////////// TABLE session //////////////////////////////////////////////////////////////
$sql = <<<EOT
CREATE TABLE IF NOT EXISTS [session] (
  [session_username] CHAR NOT NULL CONSTRAINT [username] REFERENCES [user]([user_name]), 
  [session_id] CHAR NOT NULL, 
  [session_creation_datetime] DATETIME NOT NULL);
EOT;
  $sqlite->query($sql) or die("Unable to create table session in database ".DATABASE.'. '.join('\n',$sqlite->errorInfo()));


//////////////////////////////////////////// TABLE playlist //////////////////////////////////////////////////////////////
$sql = <<<EOT
CREATE TABLE IF NOT EXISTS [playlist] (
  [playlist_name] CHAR NOT NULL, 
  [playlist_username] CHAR NOT NULL CONSTRAINT [username] REFERENCES [user]([user_name]), 
  CONSTRAINT [] PRIMARY KEY ([playlist_name], [playlist_username]));
EOT;
  $sqlite->query($sql) or die("Unable to create table playlist in database ".DATABASE.'. '.join('\n',$sqlite->errorInfo()));


//////////////////////////////////////////// TABLE playlist_song //////////////////////////////////////////////////////////////
$sql = <<<EOT
CREATE TABLE IF NOT EXISTS [playlist_song] (
  [playlist_song_playlist_id] INT NOT NULL, 
  [playlist_song_song_fullpath] CHAR NOT NULL REFERENCES [song]([song_fullpath]));
EOT;
  $sqlite->query($sql) or die("Unable to create table playlist_song in database ".DATABASE.'. '.join('\n',$sqlite->errorInfo()));


  return $sqlite;
}
?>