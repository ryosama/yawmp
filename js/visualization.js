var canvas,bar_width,canvas_ctx,audio_ctx,audio,audioSrc,analyser,channels,frequencyData;
var selected_visualization = 'spectrum';

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

	console.log("audioSrc="+audioSrc);
	console.log("channelCount="+audioSrc.channelCount);
	console.log("channelInterpretation="+audioSrc.channelInterpretation);

	// we have to connect the MediaElementSource with the analyser
	audioSrc.connect(analyser);

	// we connect the source to the destination (mp3 to speaker)
	audioSrc.connect(audio_ctx.destination);

	// we're ready to receive some data!
	audio.play();

	// select visualization
	if 		(selected_visualization == 'spectrum') {
		// we could configure the analyser: e.g. analyser.fftSize (for further infos read the spec)
		analyser.fftSize = 64;
		// frequencyBinCount tells you how many values you'll receive from the analyser
		frequencyData = new Uint8Array(analyser.frequencyBinCount);

		var gradient 	= canvas_ctx.createLinearGradient(0,0,0,canvas.height);
		gradient.addColorStop(0,"red");
		gradient.addColorStop(0.3,"yellow");
		gradient.addColorStop(1,"green");
		canvas_ctx.fillStyle	= gradient;
		bar_width = canvas.width / analyser.frequencyBinCount;
		renderSpectrum();

	} else if (selected_visualization == 'vumeter') {
		// we could configure the analyser: e.g. analyser.fftSize (for further infos read the spec)
		analyser.fftSize = 32;
		// frequencyBinCount tells you how many values you'll receive from the analyser
		frequencyData = new Uint8Array(analyser.frequencyBinCount);

		var gradient 	= canvas_ctx.createLinearGradient(0,0,canvas.width,0);
		gradient.addColorStop(0,"green");
		gradient.addColorStop(0.7,"yellow");
		gradient.addColorStop(1,"red");
		canvas_ctx.fillStyle	= gradient;
		renderVUMeter();

	} else if (selected_visualization == 'wave') {
		// we could configure the analyser: e.g. analyser.fftSize (for further infos read the spec)
		analyser.fftSize = 512;
		// frequencyBinCount tells you how many values you'll receive from the analyser
		frequencyData = new Uint8Array(analyser.frequencyBinCount);
		//dot_spacing = canvas.width / analyser.frequencyBinCount;
		canvas_ctx.strokeStyle	= "white";
		renderWave();
	}

}

function renderSpectrum() {
	if (typeof requestAnimationFrame === 'function') // ff
		requestAnimationFrame(renderSpectrum);
	else //chrome
		webkitRequestAnimationFrame(renderSpectrum);

	if (audio.paused) return; // if paused, don't do nothing

	// update data in frequencyData
	analyser.getByteFrequencyData(frequencyData);

	// clear screen
	canvas_ctx.clearRect(0,0, canvas.width, canvas.height);

	// Draw rectangle bars for each frequency bin
	for (var i=0; i<frequencyData.length ; i++) { 
		var bar_height = (frequencyData[i] * canvas.height) / 255;
		canvas_ctx.fillRect(i* bar_width, canvas.height - bar_height, bar_width, bar_height);
    }
}


function renderVUMeter() {
	if (typeof requestAnimationFrame === 'function') // ff
		requestAnimationFrame(renderVUMeter);
	else //chrome
		webkitRequestAnimationFrame(renderVUMeter);

	if (audio.paused) return; // if paused, don't do nothing

	// update data in frequencyData
	analyser.getByteFrequencyData(frequencyData);

	// determine average volume
	var total_volume=0;
	for (var i=0; i<frequencyData.length ; i++) { 
		total_volume += frequencyData[i];
	}

	// clear screen
	canvas_ctx.clearRect(0,0, canvas.width, canvas.height);

	var average_volume = total_volume/frequencyData.length;
	canvas_ctx.fillRect(0, 0, average_volume , canvas.height);
}

function renderWave() {
	if (typeof requestAnimationFrame === 'function') // ff
		requestAnimationFrame(renderWave);
	else //chrome
		webkitRequestAnimationFrame(renderWave);

	if (audio.paused) return; // if paused, don't do nothing

	// update data in frequencyData
	analyser.getByteTimeDomainData(frequencyData);
	//console.log(frequencyData);
	
	canvas_ctx.clearRect(0,0, canvas.width, canvas.height);
	canvas_ctx.beginPath();
	canvas_ctx.moveTo(0, canvas.height / 2);
	for (var i=0; i<frequencyData.length ; i++) { 
		var dot_height = (frequencyData[i] * canvas.height) / 255;
		//canvas_ctx.fillRect(i* bar_width, canvas.height - dot_height, bar_width, 1);
		canvas_ctx.lineTo(canvas.width / analyser.frequencyBinCount * i,dot_height);
    }
    //canvas_ctx.closePath();
    canvas_ctx.stroke();
}