var splitter;
var analysers 		= new Array();
var frequencyDatas 	= new Array();

function init_vumeter() {
	// we could configure the analyser: e.g. analyser.fftSize (for further infos read the spec) for merge channels
	analyser.fftSize = 32;

	// frequencyBinCount tells you how many values you'll receive from the analyser for merge channels
	frequencyData = new Uint8Array(analyser.frequencyBinCount);
	
	// separate the channels of audio
	splitter = audio_ctx.createChannelSplitter(audioSrc.channelCount);

	for(var i=0 ; i<audioSrc.channelCount ; i++) {
		// create analysers of each channel
		analysers[i] = audio_ctx.createAnalyser();
		// precision
		analysers[i].fftSize = 32;

		// create array for frequencies datas
		frequencyDatas[i] = new Uint8Array(analysers[i].frequencyBinCount);
	}

	// connect the splitter to the source
	audioSrc.connect(splitter);

	// connect the analyser to the splitter
	for(var i=0 ; i<audioSrc.channelCount ; i++) {
		splitter.connect(analysers[i],i);
	}

	/// create gradient
	var gradient 	= canvas_ctx.createLinearGradient(0,0,canvas.width,0);
	gradient.addColorStop(0,"green");
	gradient.addColorStop(0.7,"yellow");
	gradient.addColorStop(1,"red");
	canvas_ctx.fillStyle = gradient;
}

function render_vumeter() {
	// determine average volume
	var total_volume=0;
	var total_volumes = new Array();
	// init volumes of each channels to 0
	for(var i=0 ; i<audioSrc.channelCount ; i++)
		total_volumes[i]=0;

	// update data in frequencyData for merge channels
	analyser.getByteFrequencyData(frequencyData);

	for(var i=0 ; i<audioSrc.channelCount ; i++) {
		// update data for each channels
		analysers[i].getByteFrequencyData(frequencyDatas[i]);

		// compute average volume for each frequence
		for (var j=0; j<frequencyDatas[i].length ; j++) {
			total_volumes[i] += frequencyDatas[i][j];
		}
	}

	// clear screen
	canvas_ctx.clearRect(0,0, canvas.width, canvas.height);

	// draw vu meter only  for merge channels
	//var average_volume 		= total_volume/frequencyData.length * 1.3; // *1.3 because high frquency are very loud
	//canvas_ctx.fillRect(0, 0, average_volume , canvas.height / 3);

	// draw vu meter for each channels
	for(var i=0 ; i<audioSrc.channelCount ; i++) {
		var average_volume = total_volumes[i]/frequencyDatas[i].length * 1.3; // *1.3 because high frquency are very loud
		canvas_ctx.fillRect(0, canvas.height / audioSrc.channelCount * i , average_volume * canvas.width/255, canvas.height / audioSrc.channelCount);
	}
		
}