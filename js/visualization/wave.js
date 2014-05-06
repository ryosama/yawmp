function init_wave() {
	// we could configure the analyser: e.g. analyser.fftSize (for further infos read the spec)
	analyser.fftSize = 512;
	// frequencyBinCount tells you how many values you'll receive from the analyser
	frequencyData = new Uint8Array(analyser.frequencyBinCount);
	canvas_ctx.strokeStyle	= "white";
}

function render_wave() {
	analyser.getByteTimeDomainData(frequencyData);
	
	canvas_ctx.clearRect(0,0, canvas.width, canvas.height);
	canvas_ctx.beginPath();
	canvas_ctx.moveTo(0, canvas.height / 2);
	for (var i=0; i<frequencyData.length ; i++) { 
		var dot_height = (frequencyData[i] * canvas.height) / 255;
		canvas_ctx.lineTo(canvas.width / analyser.frequencyBinCount * i,dot_height);
    }
    canvas_ctx.stroke();
}