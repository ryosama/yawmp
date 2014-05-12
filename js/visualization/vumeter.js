function init_vumeter() {
	// we could configure the analyser: e.g. analyser.fftSize (for further infos read the spec) for merge channels
	my_audio.analysers[0].fftSize = 32;

	// create gradient
	var gradient 	= canvas_ctx.createLinearGradient(0,0,canvas.width,0);
	gradient.addColorStop(0,"green");
	gradient.addColorStop(0.7,"yellow");
	gradient.addColorStop(1,"red");
	canvas_ctx.fillStyle = gradient;
}

function render_vumeter() {
	// update data
	my_audio.refreshData();

	// clear screen
	canvas_ctx.clearRect(0,0, canvas.width, canvas.height);

	// draw vu meter for each channels (but the main)
	for(var i=1 ; i<=my_audio.source.channelCount ; i++) {
		canvas_ctx.fillRect(0, canvas.height / my_audio.source.channelCount * (i -1), my_audio.volumes[i] * canvas.width/255 * 1.3, canvas.height / my_audio.source.channelCount);
	}
}