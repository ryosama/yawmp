// some global variables
var canvas,canvas_ctx,audio_ctx,audio,audioSrc,analyser,frequencyData;
var selected_visualization = 1; // second one by default
var initial_visualiaztion_width, initial_visualiaztion_height;

$(document).ready(function(){ // on document ready, load events

	initial_visualiaztion_width = $('#visualization').css('width');
	initial_visualiaztion_height = $('#visualization').css('height');

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
		$('#visualization').css({	'width':initial_visualiaztion_width,
									'height':initial_visualiaztion_height,
									'background':'none',
									'position':'static',
								});
	}
}



function display_visulization() {
	canvas 			= $('#visualization').get(0);
	canvas_ctx 		= canvas.getContext('2d');

	audio_ctx = window.AudioContext 		? new window.AudioContext() :
           		window.webkitAudioContext 	? new window.webkitAudioContext() :
           		window.mozAudioContext 		? new window.mozAudioContext() :
           		window.oAudioContext 		? new window.oAudioContext() :
           		window.msAudioContext 		? new window.msAudioContext() :
           		undefined;
    
	audio 		= $('#audio-element').get(0);
	audioSrc 	= audio_ctx.createMediaElementSource(audio);
	analyser 	= audio_ctx.createAnalyser();

	/*
	console.log("audioSrc="+audioSrc);
	console.log("channelCount="+audioSrc.channelCount);
	console.log("channelInterpretation="+audioSrc.channelInterpretation);
	*/

	// we have to connect the MediaElementSource with the analyser
	audioSrc.connect(analyser);

	// we connect the source to the destination (mp3 to speaker)
	audioSrc.connect(audio_ctx.destination);

	// we're ready to receive some data!
	audio.play();

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

	if (audio.paused) return; // if paused, don't do nothing

	eval('render_'+visualization_effects[selected_visualization]+'()');
}
