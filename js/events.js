$(document).ready(function(){ // on document ready, load events

	//////////////////////////////////////////////////////////////////////// authentification button
	$('body').delegate('#auth_btn','click',function(){
		login();
	});

	// press ENTER on auth input
	$('body').delegate('#input_user, #input_password','keyup',function(e){
		if (e.which == 13) // appuie sur entrée
			login();
	});


	//////////////////////////////////////////////////////////////////////// top action buttons
	$('body').delegate('#search_btn','click',function(){
		search_text();
		$('#search_help').css('display','none'); // hide search help box
		hide_dropdown_menu();
	});

	$('body').delegate('#browse_file_btn','click',function(){
		display_directory('');
		hide_dropdown_menu();
	});

	$('body').delegate('#playlist_btn','click',function(){
		display_playlist();
		hide_dropdown_menu();
	});

	$('body').delegate('#last_played_btn','click',function(){
		display_last_played();
		hide_dropdown_menu();
	});

	$('body').delegate('#most_played_btn','click',function(){
		display_most_played();
		hide_dropdown_menu();
	});

	$('body').delegate('#favorite_btn','click',function(){
		display_favorite();
		hide_dropdown_menu();
	});

	$('body').delegate('#stats_btn','click',function(){
		display_stats();
		hide_dropdown_menu();
	});

	$('body').delegate('#password_btn','click',function(){
		change_password();
		hide_dropdown_menu();
	});

	$('body').delegate('#logout_btn','click',function(){
		logout();
		hide_dropdown_menu();
	});

	// toogle the action btn
	$('body').delegate('#actions_btn','click',function(){
		var menu = $(this).next('.dropdown-menu');
		if ($(menu).css('visibility') == 'visible') // hide the menu
			$(menu).css('visibility','hidden');
		else										// show the menu
			$(menu).css('visibility','visible');
	});

	//////////////////////////////////////////////////////////////////////// search events
	// press ENTER on search input
	$('body').delegate('#search_text','keyup',function(e){
		if (e.which == 13) { // appuie sur entrée
			search_text();
			$('#search_help').css('display','none'); // hide search help box
		} else if ($(this).val().length >= 2) { // at least 2 car to lauch a suggestion
			search_help(this);
		} else if ($(this).val().length <= 1) { // not enought caractere to launch suggestion
			$('#search_help').css('display','none'); // hide search help box
		}
	});

	// change page of results
	$('body').delegate('#current_page','change',function(e){
		change_page(this);
	});

	// change size of results
	$('body').delegate('#page_size','change',function(e){
		change_page_size(this);
	});


	//////////////////////////////////////////////////////////////////////// song list events
	// click on header to change sort direction
	$('body').delegate('th.sortable','click',function(){
		change_sort($(this).text().trim());
	});

	// click on play
	$('body').delegate('.fa-play, #song_list td.title','click',function(){
		var virtual_song_path = $(this).parents('tr').attr('path');
		play_song(virtual_song_path,true,this);
	});

	// click on plus to add in play list
	$('body').delegate('.fa-plus','click',function(){
		var virtual_song_path = $(this).parents('tr').attr('path');
		add_to_playlist(virtual_song_path,this);
	});

	// click on star (open or close)
	$('body').delegate('.fa-star, .fa-star-o','click',function(){
		var virtual_song_path = $(this).parents('tr').attr('path');
		toogle_favorite(virtual_song_path,this);
	});
	
	// click on add to play list button
	$('body').delegate('#result_to_playlist_btn','click',function(){
		result_to_playlist();
	});


	// click on add to play list button
	$('body').delegate('#change_password_btn','click',function(){
		save_new_password();
	});

	//////////////////////////////////////////////////////////////////////// cover+lyrics box event
	// mouse is over the lyrics box
	$('body').delegate('#song_info_cover_lyrics','mouseover',function(e){
		$(this).css('max-height',$(document).height());
	});

	// mouse is out from the lyrics box
	$('body').delegate('#song_info_cover_lyrics','mouseout',function(e){
		$(this).css('max-height','165px');
	});


	//////////////////////////////////////////////////////////////////////// play list events
	// click on move up
	$('body').delegate('#playlist .fa-arrow-up','click',function(){
		move_up(this);
	});

	// click on move down
	$('body').delegate('#playlist .fa-arrow-down','click',function(){
		move_down(this);
	});

	// click on remove song from play list
	$('body').delegate('#playlist li .fa-eraser','click',function(){
		remove_from_playlist_by_obj(this);
	});

	// click on remove song from play list
	$('body').delegate('#save_playlist_in_database','click',function(){
		save_playlist_in_database();
	});

	// click on remove song from play list
	$('body').delegate('#erase_playlist','click',function(){
		erase_playlist();
	});

	// make the play list sortable
	$('ol.sortable').sortable({
		'onDrop':function ($item, container, _super) {
					$item.removeClass('dragged').removeAttr('style'); // default action
					$('body').removeClass('dragging');	 // default action
					save_playlist(); // save the play list afert droping item
				}
	});


	//////////////////////////////////////////////////////////////////////// search help events
	// click on text
	$('body').delegate('#search_help li','click',function(){
		$('#search_help').css('display','none'); // hide search help box
		$('#search_text').val($(this).text()); // copy the text to the search box
		search_text(); // launch the final search
	});

});