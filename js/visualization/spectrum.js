var bar_width;

function init_spectrum() {
	// we could configure the analyser: e.g. analyser.fftSize (for further infos read the spec)
	my_audio.analysers[0].fftSize = 128;
	
	var gradient 	= canvas_ctx.createLinearGradient(0,0,0,canvas.height);
	gradient.addColorStop(0,"red");
	gradient.addColorStop(0.3,"yellow");
	gradient.addColorStop(1,"green");
	canvas_ctx.fillStyle	= gradient;
	bar_width = canvas.width / my_audio.analysers[0].frequencyBinCount;
}

function render_spectrum() {
	// update data
	my_audio.refreshData();

	// clear screen
	canvas_ctx.clearRect(0,0, canvas.width, canvas.height);

	// Draw rectangle bars for each frequency bin
	for (var i=0; i<my_audio.frequencies[0].length ; i++) { 
		var bar_height = (my_audio.frequencies[0][i] * canvas.height) / 255;
		canvas_ctx.fillRect(i* bar_width, canvas.height - bar_height, bar_width, bar_height);
    }
}