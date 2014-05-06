function play_song(path,add_playlist,obj) {
	var player = $('#player');

	//console.log(path);
	//autoplay="autoplay"
	html = '<audio id="audio-element" controls="controls">'+
				'<source src="'+path.replace(/%/,'%25')+'"/>'+
				'<p class="warning">Your browser can\'t play mp3 files</p>'+
			'</audio>';
	$('#player').html(html); // build new audio tag

	// record the song datetime play in database
	mark_song_as_played(path);

	// add the track in the play list and set as active
	if (add_playlist) {
		add_to_playlist(path,obj);
		var last_id_in_playlist = get_playlist_length() - 1;
		set_active_song_in_playlist(last_id_in_playlist);
	}

	// at the end of the song, play the next one in play list
	document.getElementById('audio-element').addEventListener('ended', function () {
		//console.log('song ended');
		var active_song_id = get_active_song_id();
		var li_songs = $('#playlist').children('li');

		// test if play list is over
		if (active_song_id < get_playlist_length() - 1) { // play the next song
			var next_path = $(li_songs[active_song_id + 1]).attr('path');
			set_active_song_in_playlist(active_song_id + 1);
			play_song(next_path,false);

		} else {	// play list is over
			// Check the repeat checkbox to know if the play list need to be we restarted
			if ($('#repeat').is(':checked')) { // restart the play list
				var next_path = $(li_songs[0]).attr('path');
				play_song(next_path,false);
				set_active_song_in_playlist(0);

			} else { // it's truely over
				popup("Play list is over<br\>That's all folks");
			}
		}
	});

	// display a message
	popup("Now playing<div class='song_title'>"+basename(path)+"</div>");

	// display animation
	display_visulization();

	// display the cover, infos and lyrics
	display_info_cover_lyrics(path);
}



// add a song to play list
function add_to_playlist(path,obj,dont_dispay_message,dont_save) {
	_add_to_playlist(	path,
						$(obj).parents('tr').attr('title'),
						$(obj).parents('tr').attr('artist')
					);

	if (!dont_dispay_message)
		popup("Add to play list<div class='song_title'>"+basename(path)+"</div>");

	if (!dont_save)
		save_playlist();
}


function _add_to_playlist(path,title,artist) {
	var html = 	'<li path="'+path+'">'+
				'<span onclick="play_song(\''+path.replace("'","\\'")+'\',false);set_active_song_in_playlist(this);">'+
					'<span class="title">'+title+'</span> '+
					'<span class="artist" style="display:'+(artist ? 'inline':'none')+';">by '+artist+'</span>'+
				'</span> '+
				'<i class="fa fa-eraser" title="Remove from play list"></i>'+
				'<i class="fa fa-arrow-down" title="Move down"></i>'+
				'<i class="fa fa-arrow-up" title="Move up"></i>'+
				'</li>';
	$('#playlist').append(html);
}



// took all the song from a result list and add to play list
function result_to_playlist() {
	$('#song_list tr.song').each(function(){ // foreach song
		add_to_playlist(
			$(this).attr('path'),
			$(this).find('.fa-plus'), // the li obj
			true, // don't display message
			true // don't save the play list
		);
	});

	popup("Results import to the play list");
	save_playlist();
}


function display_info_cover_lyrics(path) {
	// loading
	$('#song_info').html('');
	$('#lyrics').html('');
	$('#cover').html('<i class="fa fa-spinner fa-spin fa-5x"></i>');

	$.ajax({
		url:'lib/ajax.php',
		data:{'what':'display_info_cover_lyrics', 'path':path},
		type:'GET',
		dataType :'json',
		success:function(result){
			$('#song_info').html(result['infos']);
			$('#cover').html(result['cover']);
			$('#lyrics').html(result['lyrics']);

			// 1. Initialize fotorama manually.
		    var $fotoramaDiv = $('#fotorama').fotorama();

		    // 2. Get the API object.
		    var fotorama = $fotoramaDiv.data('fotorama');
		    //console.log(fotorama);
		}
	});
}

function move_up(i_obj) {
	var li_obj = $(i_obj).parent();
	$(li_obj).prev('li').before(li_obj);
	save_playlist();
}

function move_down(i_obj) {
	var li_obj = $(i_obj).parent();
	$(li_obj).next('li').after(li_obj);
	save_playlist();
}

function set_active_song_in_playlist(id_or_obj) {
	$('#playlist').children('li').removeClass('active'); // mark ALL the songs as inactive

	if (typeof(id_or_obj) == 'number') { // id_or_obj is the number of the track in theplay list to set to active
		var li_songs = $('#playlist').children('li');	
		$(li_songs[id_or_obj]).addClass('active'); // mark ONE song as active

	} else if (typeof(id_or_obj) == 'object') { // id_or_obj is THE li element to set to active
		$('#playlist').children('li').removeClass('active'); // mark ALL the songs as inactive
		$(id_or_obj).parent().addClass('active'); // mark ONE song as active
	}
}

// get the number of track in the play list
function get_playlist_length() {
	var li_songs = $('#playlist').children('li');
	return li_songs.length;
}

// get the id of the active track in the play list
function get_active_song_id() {
	var li_songs = $('#playlist').children('li');
	for(var i=0 ; i<li_songs.length ; i++) {
		if ($(li_songs[i]).hasClass('active'))
			return i;
	}
	return null;
}

// get the element of the active track in the play list
function get_active_song_obj() {
	var li_songs = $('#playlist').children('li');
	for(var i=0 ; i<li_songs.length ; i++) {
		if ($(li_songs[i]).hasClass('active')) 
			return li_songs;
	}
	return null;
}

// erase only one song
function remove_from_playlist_by_obj(i_obj) {
	$(i_obj).parent().remove();
	save_playlist();
}

// erase all the play list
function erase_playlist() {
	if (confirm("Do you really want to erase your play list ?")) {
		$('#playlist').html('');
		save_playlist();
	}
}

// save the play list in database
function save_playlist_in_database() {
	var song_in_playlist = new Array();
	$('#playlist li').each(function(){
		song_in_playlist.push( {'path':$(this).attr('path')} );
	});

	if (song_in_playlist.length <= 0) {
		popup("Play list is empty, nothing to save");
		return false;
	}


	var name = prompt("Give a name to your play list");
	if (name) {
		$.ajax({
			url:'lib/ajax.php',
			data:{'what':'save_playlist_in_database', 'name':name, 'playlist':song_in_playlist},
			type:'POST',
			dataType :'html',
			success:function(result){
				if (result)
					popup("Play list saved");
				else
					popup("Error while saving play list");
			}
		});
	}
}


// every time we touch the play list, we save it in session
function save_playlist() {
	var song_in_playlist = new Array();
	$('#playlist li').each(function(){
		song_in_playlist.push(
			{	'path':$(this).attr('path'),
				'artist':$(this).find('span.artist').text().replace(/^by +/i,''),
				'title':$(this).find('span.title').text()
			}	
		);
	});

	$.ajax({
		url:'lib/ajax.php',
		data:{'what':'save_playlist', 'playlist':song_in_playlist},
		type:'POST',
		dataType :'html',
		success:function(result){
			//console.log(result);
		}
	});
}

// load play list in session
function load_playlist() {
	$.ajax({
		url:'lib/ajax.php',
		data:{'what':'load_playlist'},
		type:'GET',
		dataType :'json',
		success:function(json){
			$.each( json, function(song_id,song_values) {
				_add_to_playlist(song_values['path'],song_values['title'],song_values['artist']);
			});
		}
	});
}


// load play list from databases
function load_playlist_from_database(playlist_id) {
	$.ajax({
		url:'lib/ajax.php',
		data:{'what':'load_playlist_from_database','playlist_id':playlist_id},
		type:'GET',
		dataType :'json',
		success:function(json){
			$.each( json, function(song_id,song_values) {
				_add_to_playlist(song_values['path'],song_values['title'],song_values['artist']);
			});
		}
	});
}


// delete play list in databases
function delete_playlist_in_database(playlist_id,obj) {
	if (confirm("Do you really want to delete this play list ?")) {
		$.ajax({
			url:'lib/ajax.php',
			data:{'what':'delete_playlist_in_database','playlist_id':playlist_id},
			type:'GET',
			dataType :'html',
			success:function(result){
				popup("Play list deleted");
				$(obj).parents('tr').remove();
			}
		});
	}
}



// search for a string in database
function search_text() {
	var search_text = $('#search_text').val();

	if (search_text.length <= 0) { // empty search
		$('#search_text').focus();
		return false;
	}

	$.ajax({
		url:'lib/ajax.php',
		data:{'what':'search', 'text':search_text, 'where':$('#search_where').val() },
		type:'GET',
		dataType :'html',
		success:function(result) {
			$('#ariane').html('<i class="fa fa-search fa-lg"></i> Search '+search_text);
			$('#song_list').html(result);
		}
	});
}


// display the current song in the directory
function display_directory(path) {
	$.ajax({
		url:'lib/ajax.php',
		data:{'what':'ls', 'dir':path},
		type:'GET',
		dataType :'html',
		success:function(result){
			$('#ariane').html('<i class="fa fa-code-fork fa-lg"></i> Browsing '+path);
			$('#song_list').html(result);
		}
	});
}


// display play list of the user
function display_playlist() {
	$.ajax({
		url:'lib/ajax.php',
		data:{'what':'display_playlist'},
		type:'GET',
		dataType :'html',
		success:function(result){
			$('#ariane').html('<i class="fa fa-bars fa-lg"></i> Play list');
			$('#song_list').html(result);
		}
	});
}


// search for the last songs played
function display_last_played() {
	$.ajax({
		url:'lib/ajax.php',
		data:{'what':'display_last_played'},
		type:'GET',
		dataType :'html',
		success:function(result){
			$('#ariane').html('<i class="fa fa-clock-o fa-lg"></i> Last played');
			$('#song_list').html(result);
		}
	});
}

// display the x most listen songs
function display_most_played() {
	$.ajax({
		url:'lib/ajax.php',
		data:{'what':'display_most_played'},
		type:'GET',
		dataType :'html',
		success:function(result){
			$('#ariane').html('<i class="fa fa-thumbs-o-up fa-lg"></i> Most played');
			$('#song_list').html(result);
		}
	});
}


// display the x most listen songs
function display_favorite() {
	$.ajax({
		url:'lib/ajax.php',
		data:{'what':'display_favorite'},
		type:'GET',
		dataType :'html',
		success:function(result){
			$('#ariane').html('<i class="fa fa-star fa-lg"></i> Favorite');
			$('#song_list').html(result);
		}
	});
}


// display the x most listen songs
function display_stats() {
	$.ajax({
		url:'lib/ajax.php',
		data:{'what':'display_stats'},
		type:'GET',
		dataType :'html',
		success:function(result){
			$('#ariane').html('<i class="fa fa-bar-chart-o fa-lg"></i> Statistics');
			$('#song_list').html(result);
		}
	});
}


// display the entier album
function display_album(id) {
	$.ajax({
		url:'lib/ajax.php',
		data:{'what':'display_album','id':id},
		type:'GET',
		dataType :'html',
		success:function(result){
			$('#ariane').html('<i class="fa fa-book fa-lg"></i> Album');
			$('#song_list').html(result);
		}
	});
}


// display the entier album
function display_artist(id) {
	$.ajax({
		url:'lib/ajax.php',
		data:{'what':'display_artist','id':id},
		type:'GET',
		dataType :'html',
		success:function(result){
			$('#ariane').html('<i class="fa fa-user fa-lg"></i> Artist');
			$('#song_list').html(result);
		}
	});
}


// change the page of the result
function change_page(select_obj) {
	$.ajax({
		url:'lib/ajax.php',
		data:{'what':'change_page', 'page': $(select_obj).val()},
		type:'GET',
		dataType :'html',
		success:function(result) {
			$('#song_list').html(result);
		}
	});
}


// change the size of the result
function change_page_size(select_obj) {
	$.ajax({
		url:'lib/ajax.php',
		data:{'what':'change_page_size', 'size': $(select_obj).val()},
		type:'GET',
		dataType :'html',
		success:function(result) {
			$('#song_list').html(result);
		}
	});
}



// change sort of the result
function change_sort(column) {
	$.ajax({
		url:'lib/ajax.php',
		data:{'what':'change_sort', 'column': column},
		type:'GET',
		dataType :'html',
		success:function(result) {
			$('#song_list').html(result);
		}
	});
}

// equivalent to basename in php
function basename(path) {
	var path_exploded = path.split(/[\\\/]/);
	return path_exploded[path_exploded.length - 1];
}

// display a message information
function popup(html) {
	html = "<i class='fa fa-comment-o fa-3x'></i> " + html;
	$('#message').html(html).slideDown('normal',function(){
		$(this).delay('2000').slideUp('normal');
	});

}

// mark the song as played
function mark_song_as_played(path_or_rowid) {
	$.ajax({
		url:'lib/ajax.php',
		data:{'what':'mark_song_as_played', 'song':path_or_rowid},
		type:'GET',
		dataType :'html',
		success:function(result) { }
	});
}

// check or uncheck favortire songs
function toogle_favorite(path_or_rowid,li_obj) {
	$.ajax({
		url:'lib/ajax.php',
		data:{'what':'toogle_favorite', 'song':path_or_rowid},
		type:'GET',
		dataType :'html',
		success:function(result) {
			//console.log(result);
			if 			(result == 'add') { // added to favorite
				$(li_obj).removeClass('fa-star-o').addClass('fa-star');
			} else if 	(result == 'remove') { // remove from favorite
				$(li_obj).removeClass('fa-star').addClass('fa-star-o');
			}
		}
	});
}


// mark the song as played
function login() {
	var user 	= $('#auth_user input').val();
	var password= $('#auth_password input').val();
	var success	=  true;

	if (user.length > 0 && password.length > 0) {
		$.ajax({
			url:'lib/ajax.php',
			data:{'what':'login', 'user':user, 'password':password },
			type:'POST',
			dataType :'html',
			success:function(result) {
				if (result == 0) {
					popup("Error : wrong password for user "+user);
					success = false;
				} else if (result == 1) { // new user
					popup("Register new user : "+user);
				} else if (result == 2) { // existing user
					popup("Welcome "+user);
				}
				
				display_last_played();
				return success;
			}
		});
	} else {
		if (user.length <= 0) {
			$('#input_user').focus();
			return false;
		}

		if (password.length <= 0) {
			$('#input_password').focus();
			return false;
		}
	}
}



// display interface for password changing
function change_password() {
	var html = '';
	html += '<tr class="password"><td class="password_label"><label for="current_password">Current password</label></td><td><input name="current_password" id="current_password" value="" placeholder="Current password" type="password"/></td></tr>';
	html += '<tr class="password"><td class="password_label"><label for="new_password">New password</label></td><td><input name="new_password1" id="new_password" value="" placeholder="New password" type="password"/></td></tr>';
	html += '<tr class="password"><td class="password_label"><label for="confirm_password">Confirm password</label></td><td><input name="confirm_password" id="confirm_password" value="" placeholder="Confirm new password" type="password"/></td></tr>';
	html += '<tr class="password"><td class="password_label" colspan="2"><a id="change_password_btn" class="btn btn-success btn-sm" href="#"><i class="fa fa-save fa-lg"></i> Save new password</a></tr>';

	$('#song_list').html(html);
}


// save the new password
function save_new_password() {
	console.log($('#new_password').val());
	console.log($('#confirm_password').val());

	if (	$('#current_password').val().length > 0
		&&	$('#new_password').val().length > 0
		&&	$('#confirm_password').val().length > 0
		)
	{
		if ($('#new_password').val() == $('#confirm_password').val()) {
			$.ajax({
				url:'lib/ajax.php',
				data:{'what':'save_new_password', 'current_password':$('#current_password').val(), 'new_password':$('#new_password').val() },
				type:'POST',
				dataType :'html',
				success:function(result) {
					if (result==1) {
						popup("New password saved");
					} else {
						popup("Error : Can't save your new password");
					}
				}
			});
		} else {
			popup("New password and confirm password doesn't matchs");
		}
	} else {
		popup("One of the fields is empty");
	}
}


// logout
function logout() {
	$.ajax({
		url:'lib/ajax.php',
		data:{'what':'logout' },
		type:'POST',
		dataType :'html',
		success:function(result) {
			display_last_played();
		}
	});
}

// hide the drop down menu
function hide_dropdown_menu() {
	$('#actions_btn').next('.dropdown-menu').css('visibility','hidden');
}

// 
function search_help(input_obj) {
	//console.log($(input_obj).val());
	var text = $(input_obj).val();
	$.ajax({
		url:'lib/ajax.php',
		async:true,
		data:{'what':'search_help','text':text},
		type:'POST',
		dataType :'json',
		success:function(json) {
			if (	text 						// something is search
				&& 	text == json['text']		// if the text return is the search text
				&&	(json['artist'].length > 0 || json['album'].length > 0 || json['title'].length > 0)	// has results
				) { 
				var html = '';
				var re=new RegExp('('+text+')','i');

				if (json['artist'].length > 0) {
					html += '<div>Artists</div><ul>';
					for(var i=0 ; i<json['artist'].length ; i++)
						html += '<li>'+json['artist'][i].replace(re,'<span class="found">$1</span>')+'</li>';
					html += '</ul>';
				}

				if (json['album'].length > 0) {
					html += '<div>Albums</div><ul>';
					for(var i=0 ; i<json['album'].length ; i++)
						html += '<li>'+json['album'][i].replace(re,'<span class="found">$1</span>')+'</li>';
					html += '</ul>';
				}

				if (json['title'].length > 0) {
					html += '<div>Titles</div><ul>';
					for(var i=0 ; i<json['title'].length ; i++)
						html += '<li>'+json['title'][i].replace(re,'<span class="found">$1</span>')+'</li>';
					html += '</ul>';
				}

				var search_text_offset = $('#search_text').offset();
				$('#search_help').html(html).css({	'top':search_text_offset.top + $('#search_text').height() + 6,
													'left':search_text_offset.left,
													'display':'block'
												});

			} else { // hide the box
				$('#search_help').css('display','none');
			}
		}
	});
}
