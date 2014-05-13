// some global variables
var my_canvas, my_audio;
var selected_visualization = 2; // second one by default
var initial_visualization_width, initial_visualization_height;


if (is_firefox())// only work for firefox
$(document).ready(function() { // on document ready, load events
	initial_visualization_width 	= $('#visualization').attr('width');
	initial_visualization_height 	= $('#visualization').attr('height');

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
		$('#visualization').css({	'background-color':'black',
									'position':'fixed',
									'top': 0,'left':0
								})
							.attr({	'width': parseInt($(document).width()),
									'height':parseInt($(document).height()) - 40
								});


	} else {												
		$('#visualization').css({	'background':'none',	// reduce animation to initial size
									'position':  'static',
								})
							.attr({	'width': initial_visualization_width,
									'height':initial_visualization_height
								});
	}

	// width and height have change
	initVisualization();
}


function display_visulization() {
	my_canvas 	= new SimpleCanvas( $('#visualization').get(0) );
	my_audio 	= new SimpleAudio( $('#audio-element').get(0) ); // create context, connection, splitter, analysers from audio tag
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

	my_canvas.frame++;
	eval('render_'+visualization_effects[selected_visualization]+'()');
}