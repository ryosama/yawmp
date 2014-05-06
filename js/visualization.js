var canvas,bar_width,canvas_ctx,audio_ctx,audio,audioSrc,analyser,frequencyData;

function display_visulization(){
	canvas 			= document.getElementById('visualization');
	canvas_ctx 		= canvas.getContext('2d');

	var gradient 	= canvas_ctx.createLinearGradient(0,0,0,canvas.height);
	gradient.addColorStop(0,"red");
	gradient.addColorStop(0.3,"yellow");
	gradient.addColorStop(1,"green");
	canvas_ctx.fillStyle	= gradient;
	//canvas_ctx.fillStyle = "grey";

	audio_ctx = window.AudioContext 		? new window.AudioContext() :
           		window.webkitAudioContext 	? new window.webkitAudioContext() :
           		window.mozAudioContext 		? new window.mozAudioContext() :
           		window.oAudioContext 		? new window.oAudioContext() :
           		window.msAudioContext 		? new window.msAudioContext() :
           		undefined;
	audio 			= document.getElementById('audio-element');
	audioSrc 		= audio_ctx.createMediaElementSource(audio);
	analyser 		= audio_ctx.createAnalyser();
	// we have to connect the MediaElementSource with the analyser
	audioSrc.connect(analyser);
	// we could configure the analyser: e.g. analyser.fftSize (for further infos read the spec)
	analyser.fftSize = 32;

	// frequencyBinCount tells you how many values you'll receive from the analyser
	frequencyData = new Uint8Array(analyser.frequencyBinCount);

	bar_width = Math.round(canvas.width / analyser.frequencyBinCount);

	/*
	console.log("canvas.width="+canvas.width);
	console.log("analyser.frequencyBinCount="+analyser.frequencyBinCount);
	console.log("bar_width="+bar_width);
	*/

	// we're ready to receive some data!
	audio.play();
	renderFrame();
}

function renderFrame() {
	requestAnimationFrame(renderFrame);
	if (audio.paused) return; // if paused, don't do nothing

	// update data in frequencyData
	analyser.getByteFrequencyData(frequencyData);
	//console.log(frequencyData);

	// clear screen
	canvas_ctx.clearRect(0,0, canvas.width, canvas.height);
	for (var i=0; i<frequencyData.length ; i++) { // Draw rectangle bars for each frequency bin
		var bar_height = Math.round((frequencyData[i] * canvas.height) / 255);
		canvas_ctx.fillRect(i* bar_width, canvas.height - bar_height, bar_width, bar_height);
    }
}