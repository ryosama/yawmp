<?php
if (!file_exists('config/config.php'))
	include_once('lib/install.php'); // load wizard to create new configuration file

include_once('config/config.php'); // load application configuration
include_once('lib/useful.php');
session_start();
?>
<html>
	<head>
		<title><?=TITLE?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

		<!-- CSS -->
		<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
		<link rel="stylesheet" type="text/css" href="css/font-awesome.min.css">
		<link rel="stylesheet" type="text/css" href="css/sortable.css">
		<link rel="stylesheet" type="text/css" href="css/dropdown-menu.css">
		<link rel="stylesheet" type="text/css" href="css/style.css">
		<link rel="stylesheet" type="text/css" href="css/visualization.css">

		<!-- JQuery and plugins -->
		<script type="text/javascript" src="js/jquery.js"></script>
		<script type="text/javascript" src="js/jquery-sortable-min.js"></script>
		<script type="text/javascript" src="js/useful.js"></script>
		<script type="text/javascript" src="js/events.js"></script>

		<!-- Fotorama -->
		<link rel="stylesheet" type="text/css" href="css/fotorama.css" >
		<script type="text/javascript" src="js/fotorama.js"></script>

		<!-- Sound visualization -->
		<script type="text/javascript" src="js/simple_canvas.js"></script>
		<script type="text/javascript" src="js/simple_audio.js"></script>
		<script type="text/javascript" src="js/visualization.js"></script>

<?php
		$effects = array();
		$dir = opendir('js/visualization');
		while (false !== ($file = readdir($dir))) {
			if (is_javascript($file)) { // load the javascritp visualization effect
				$effects[] = str_replace('.js','',$file);
				echo '<script type="text/javascript" src="js/visualization/'.$file.'"></script>'."\n";
			}
		}
		closedir($dir);
?>

<script type="text/javascript">

<?php
$tmp = array();
foreach ($effects as $effect)
	$tmp[] = "'$effect'";
echo "var visualization_effects = [".join(',',$tmp)."];";
?>

$(document).ready(function(){
	// display last played songs by default
	display_last_played();

	// load play list in memory
	load_playlist();
});
</script>
</head>
<body>
<div id="header">
	<div id="title"><?=TITLE?></div>
	<div id="search">
		<input type="text" id="search_text" name="search_text" value="" placeholder="search..." autocomplete="off"/>
		<select id="search_where" name="search_where">
			<option value="anything">anything</option>
			<option value="song_title">titles</option>
			<option value="artist_name">artists</option>
			<option value="album_title">albums</option>
		</select>
		<a id="search_btn" 		class="btn btn-success btn-sm" href="#"><i class="fa fa-search fa-lg"></i> Search</a>
		<a id="browse_file_btn" class="btn btn-success btn-sm" href="#"><i class="fa fa-code-fork fa-lg"></i> Browse files</a>

		<div class="btn-group open">
			<a id="actions_btn" class="btn btn-success btn-sm" href="#"><i class="fa fa-caret-down fa-lg"></i> More actions</a>
			<ul class="dropdown-menu">
				<li><a id="playlist_btn" 	class="btn btn-success btn-sm" href="#"><i class="fa fa-bars fa-lg"></i> Play lists</a></li>
				<li><a id="last_played_btn" class="btn btn-success btn-sm" href="#"><i class="fa fa-clock-o fa-lg"></i> Last played</a></li>
				<li><a id="most_played_btn" class="btn btn-success btn-sm" href="#"><i class="fa fa-thumbs-o-up fa-lg"></i> Most played</a></li>
				<li><a id="favorite_btn" 	class="btn btn-success btn-sm" href="#"><i class="fa fa-star fa-lg"></i> Favorite</a></li>
				<li><a id="stats_btn" 		class="btn btn-success btn-sm" href="#"><i class="fa fa-bar-chart-o fa-lg"></i> Stats</a></li>
				<li><a id="password_btn" 	class="btn btn-success btn-sm" href="#"><i class="fa fa-key fa-lg"></i> Password</a></li>
				<li><a id="logout_btn" 		class="btn btn-danger btn-sm" href="#"><i class="fa fa-sign-out fa-lg"></i> Logout</a></li>
			</ul>
		</div>
	</div>
</div>

<div id="player_and_playlist">
	<div id="playlist_options">
		<span id="playlist_title">Play list</span>
		<label for="repeat" title="Repeat play list"><i class="fa fa-repeat fa-lg"></i> Repeat</label> <input type="checkbox" name="repeat" id="repeat"/>
		<i id="save_playlist_in_database" class="fa fa-save fa-lg" title="Save play list"></i>
		<i id="erase_playlist" class="fa fa-eraser fa-lg" title="Erase play list"></i>

		<!-- visualization -->
		<canvas id="visualization" width="200" height="32">You're browser doesn't support canvas</canvas>
	</div>
	
	<!-- play list -->
	<ol id="playlist" class="sortable"></ol>

	<!-- visualization buttons -->
	<div id="visualization_btn">
		<i id="change_visualization" class="fa fa-bar-chart-o fa-lg" title="Change visualization"></i>
		<i id="toogle_fullscreen_visualization" class="fa fa-desktop fa-lg" title="Toogle Fullscreen"></i>
	</div>

	<!-- player -->
	<div id="player"><audio id="audio-element"></audio></div>
</div>

<!-- list of song -->
<div id="ariane"></div>
<table id="song_list"></table>

<!-- message box for various actions -->
<div id="message"></div>

<!-- info + cover + lyrics from the song -->
<div id="song_info_cover_lyrics">
	<div id="cover"></div>
	<div id="song_info"></div>
	<div id="lyrics"></div>
</div>

<!-- box for search engine -->
<div id="search_help"></div>

</body>
</html>