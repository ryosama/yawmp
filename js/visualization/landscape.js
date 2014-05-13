function init_landscape() {
	// we could configure the analyser: e.g. analyser.fftSize (for further infos read the spec)
	my_audio.analysers[0].fftSize = 512;
}

function render_landscape() {
	// update data

	if (my_canvas.frame >> 1) {
		my_audio.refreshData();

		my_canvas.clearCanvas();

		my_canvas.context.strokeStyle = 'white';
		my_canvas.context.beginPath();
		my_canvas.context.moveTo(0, my_canvas.element.height / 2);
		for (var i=0; i<my_audio.waves[0].length ; i++) 
			my_canvas.context.lineTo(my_canvas.element.width / my_audio.analysers[0].frequencyBinCount * i,
									(my_audio.waves[0][i] * my_canvas.element.height) / 255);
	    
	    my_canvas.context.stroke();

	    my_canvas.context.save(); // save before transform

	    // draw remanence
	    my_canvas.context.shadowBlur=10;
		my_canvas.context.shadowColor='white';
		var remanence=0;
		for(var j=1 ; j<=20 ; j++) {
			my_canvas.context.strokeStyle = 'rgba(255,255,255,'+ (1 - 0.07*j)+')';
			my_canvas.context.transform(1.1, 0, 0, 1.05, -j * 5,0); // A translate by 0,20
			my_canvas.context.beginPath();
			my_canvas.context.moveTo(0, my_canvas.element.height / 2);
			for (var i=0; i<my_audio.waves[0].length ; i++)
				my_canvas.context.lineTo(my_canvas.element.width / my_audio.analysers[0].frequencyBinCount * i,
										(my_audio.waves[0][i] * my_canvas.element.height) / 255);
		    
		    my_canvas.context.stroke();
		    remanence++;
		}

	    my_canvas.context.restore(); // restore initial state
	}
}