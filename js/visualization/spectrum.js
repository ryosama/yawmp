var gradient;
var bar_width;
var max_values;

function init_spectrum() {
	// we could configure the analyser: e.g. analyser.fftSize (for further infos read the spec)
	my_audio.analysers[0].fftSize = 128;
	
	gradient 	= my_canvas.context.createLinearGradient(0,0,0,my_canvas.element.height);
	gradient.addColorStop(0,"red");
	gradient.addColorStop(0.3,"yellow");
	gradient.addColorStop(1,"green");

	bar_width = my_canvas.element.width / my_audio.analysers[0].frequencyBinCount;

	// init array to zeros
	max_values = new Array(my_audio.analysers[0].frequencyBinCount);
	for(var i=0 ; i<max_values.length ; i++)
		max_values[i] = {'value':0 , 'touch':0};
}

function render_spectrum() {
	// update data
	my_audio.refreshData();

	// clear screen
	my_canvas.clearCanvas();

	// Draw rectangle bars for each frequency bin
	for (var i=0 ; i<my_audio.frequencies[0].length ; i++) { 
		var bar_height = (my_audio.frequencies[0][i] * my_canvas.element.height) / 255;
		my_canvas.context.fillStyle	= gradient;
		my_canvas.context.fillRect(i* bar_width, my_canvas.element.height - bar_height, bar_width, bar_height);

		// keep the max value
		if (max_values[i].value < bar_height)
			max_values[i] = {'value':bar_height, 'touch':my_canvas.frame }; // which frame the max was updated

		if (my_canvas.frame - max_values[i].touch > 100) // max wasn't updated since 10 frame --> decrease max
			max_values[i].value--;

		my_canvas.context.fillStyle	= 'red';
		my_canvas.context.fillRect(i* bar_width, my_canvas.element.height - max_values[i].value - 3, bar_width, 3);
    }
}