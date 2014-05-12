// some global variables
var canvas,canvas_ctx;
var my_audio;
var selected_visualization = 1; // second one by default
var initial_visualization_width, initial_visualization_height;


if (is_firefox())// only work for firefox
$(document).ready(function() { // on document ready, load events
	initial_visualization_width 	= $('#visualization').css('width');
	initial_visualization_height 	= $('#visualization').css('height');

	// click on change visualizaion effect
	$('body').delegate('#change_visualization','click',function(){
		if (selected_visualization >= visualization_effects.length -1 ) // last one
			selected_visualization = 0;
		else
			selected_visualization++;

		initVisualization();
	});


	// click on fullscreen visualizaion effect
	$('body').delegate('#toogle_fullscreen_visualization','click',function(){
		toogle_fullscreen_visualization();
	});
});


function toogle_fullscreen_visualization() {

	if ($('#visualization').css('position') == 'static') { // go fullscreen
		$('#visualization').css({	'width':$(document).width(),
									'height':$(document).height() - 40,
									'background-color':'black',
									'position':'fixed',
									'top':0,
									'left':0
								});

	} else {												// reduce animation to initial size
		$('#visualization').css({	'width':initial_visualization_width,
									'height':initial_visualization_height,
									'background':'none',
									'position':'static',
								});
	}
}


function display_visulization() {
	canvas 			= $('#visualization').get(0);
	canvas_ctx 		= canvas.getContext('2d');

	my_audio = new SimpleAudio( $('#audio-element').get(0) ); // create context, connection, splitter, analysers from audio tag
	my_audio.element.play();

	// load visualization
	initVisualization();
}


function initVisualization(){
	eval('init_'+visualization_effects[selected_visualization]+'()');
	renderVisualization();
}


function renderVisualization() {
	if (typeof requestAnimationFrame === 'function') 	// ff
		requestAnimationFrame(renderVisualization);
	else 												//chrome
		webkitRequestAnimationFrame(renderVisualization);

	if (my_audio.element.paused) return; // if paused, don't do nothing

	eval('render_'+visualization_effects[selected_visualization]+'()');
}