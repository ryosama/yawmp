var gradient;
var max_values;

function init_vumeter() {
	// we could configure the analyser: e.g. analyser.fftSize (for further infos read the spec) for merge channels
	my_audio.analysers[0].fftSize = 32;

	// create gradient
	gradient 	= my_canvas.context.createLinearGradient(0,0,my_canvas.element.width,0);
	gradient.addColorStop(0,"green");
	gradient.addColorStop(0.7,"yellow");
	gradient.addColorStop(1,"red");

	// init array to zeros
	max_values = new Array(my_audio.analysers[0].frequencyBinCount);
	for(var i=0 ; i<max_values.length ; i++)
		max_values[i] = {'value':0 , 'touch':0};
}

function render_vumeter() {
	// update data
	my_audio.refreshData();

	// clear screen
	my_canvas.clearCanvas();

	// draw vu meter for each channels (but the main)
	for(var i=1 ; i<=my_audio.source.channelCount ; i++) {
		var bar_width = my_audio.volumes[i] * my_canvas.element.width/255 * 1.3;
		my_canvas.context.fillStyle	= gradient;
		my_canvas.context.fillRect(	0,																		// x
									my_canvas.element.height / my_audio.source.channelCount * (i -1),		// y
									bar_width,																// w
									my_canvas.element.height / my_audio.source.channelCount					// h
								);

		// keep the max value
		if (max_values[i].value < bar_width)
			max_values[i] = {'value':bar_width, 'touch':my_canvas.frame }; // which frame the max was updated

		if (my_canvas.frame - max_values[i].touch > 100) // max wasn't updated since 10 frame --> decrease max
			max_values[i].value--;

		my_canvas.context.fillStyle	= 'red';
		my_canvas.context.fillRect(	max_values[i].value,													// x
									my_canvas.element.height / my_audio.source.channelCount * (i -1),		// y
									3,																		// w
									my_canvas.element.height / my_audio.source.channelCount					// h
								);
	}
}