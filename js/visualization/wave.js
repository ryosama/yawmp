function init_wave() {
	// we could configure the analyser: e.g. analyser.fftSize (for further infos read the spec)
	my_audio.analysers[0].fftSize = 512;

	
}

function render_wave() {
	// update data
	my_audio.refreshData();

	canvas_ctx.clearRect(0,0, canvas.width, canvas.height);

	if (my_audio.volumes[0] > 80)
		canvas_ctx.strokeStyle	= 'red';
	else
		canvas_ctx.strokeStyle	= 'white';

	canvas_ctx.beginPath();
	canvas_ctx.moveTo(0, canvas.height / 2);
	for (var i=0; i<my_audio.waves[0].length ; i++) { 
		var dot_height = (my_audio.waves[0][i] * canvas.height) / 255;
		canvas_ctx.lineTo(canvas.width / my_audio.analysers[0].frequencyBinCount * i, dot_height);
    }
    canvas_ctx.stroke();
}