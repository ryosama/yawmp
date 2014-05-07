var bar_width;
function init_spectrum() {
	// we could configure the analyser: e.g. analyser.fftSize (for further infos read the spec)
	analyser.fftSize = 128;
	// frequencyBinCount tells you how many values you'll receive from the analyser
	frequencyData = new Uint8Array(analyser.frequencyBinCount);

	var gradient 	= canvas_ctx.createLinearGradient(0,0,0,canvas.height);
	gradient.addColorStop(0,"red");
	gradient.addColorStop(0.3,"yellow");
	gradient.addColorStop(1,"green");
	canvas_ctx.fillStyle	= gradient;
	bar_width = canvas.width / analyser.frequencyBinCount;
}

function render_spectrum() {
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