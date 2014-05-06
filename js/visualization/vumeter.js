function init_vumeter() {
	// we could configure the analyser: e.g. analyser.fftSize (for further infos read the spec)
	analyser.fftSize = 32;
	// frequencyBinCount tells you how many values you'll receive from the analyser
	frequencyData = new Uint8Array(analyser.frequencyBinCount);

	var gradient 	= canvas_ctx.createLinearGradient(0,0,canvas.width,0);
	gradient.addColorStop(0,"green");
	gradient.addColorStop(0.7,"yellow");
	gradient.addColorStop(1,"red");
	canvas_ctx.fillStyle = gradient;
}

function render_vumeter() {
	// update data in frequencyData
	analyser.getByteFrequencyData(frequencyData);

	// determine average volume
	var total_volume=0;
	for (var i=0; i<frequencyData.length ; i++) { 
		total_volume += frequencyData[i];
	}

	// clear screen
	canvas_ctx.clearRect(0,0, canvas.width, canvas.height);

	// draw vu meter only on one channel
	var average_volume = total_volume/frequencyData.length * 1.5; // *1.5 because high frquency are very loud
	canvas_ctx.fillRect(0, 0, average_volume , canvas.height);
}