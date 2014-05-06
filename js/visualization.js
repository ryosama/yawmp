// some global variables
var canvas,bar_width,canvas_ctx,audio_ctx,audio,audioSrc,analyser,channels,frequencyData;

var selected_visualization = 1; // second one by default

$(document).ready(function(){ // on document ready, load events

	$('body').delegate('#change_visualization','click',function(){
		if (selected_visualization >= visualization_effects.length -1 ) // last one
			selected_visualization = 0;
		else
			selected_visualization++;

		initVisualization();
	});
});



function display_visulization() {
	canvas 			= document.getElementById('visualization');
	canvas_ctx 		= canvas.getContext('2d');

	audio_ctx = window.AudioContext 		? new window.AudioContext() :
           		window.webkitAudioContext 	? new window.webkitAudioContext() :
           		window.mozAudioContext 		? new window.mozAudioContext() :
           		window.oAudioContext 		? new window.oAudioContext() :
           		window.msAudioContext 		? new window.msAudioContext() :
           		undefined;
    
	audio 		= document.getElementById('audio-element');
	audioSrc 	= audio_ctx.createMediaElementSource(audio);
	analyser 	= audio_ctx.createAnalyser();
	channels 	= audio_ctx.createChannelSplitter();

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

	//console.log('renderVisualization');
	eval('render_'+visualization_effects[selected_visualization]+'()');
}
