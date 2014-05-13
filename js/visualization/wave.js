function init_wave() {
	// we could configure the analyser: e.g. analyser.fftSize (for further infos read the spec)
	my_audio.analysers[0].fftSize = 512;
}

function render_wave() {

	if (my_canvas.frame >> 1) {

		// update data
		my_audio.refreshData();

		my_canvas.clearCanvas();

		if (my_audio.volumes[0] > 80)
			my_canvas.context.strokeStyle	= 'red';
		else
			my_canvas.context.strokeStyle	= 'white';

		my_canvas.context.beginPath();
		my_canvas.context.moveTo(0, my_canvas.element.height / 2);
		for (var i=0; i<my_audio.waves[0].length ; i++) { 
			var dot_height = (my_audio.waves[0][i] * my_canvas.element.height) / 255;
			my_canvas.context.lineTo(my_canvas.element.width / my_audio.analysers[0].frequencyBinCount * i, dot_height);
	    }
	    my_canvas.context.stroke();
	}
}