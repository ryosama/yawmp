//////////////////////////////////// SIMPLE AUDIO OBJECT //////////////////////////////////////////
function SimpleAudio(obj) {
	// attributs
	this.element 		= obj; // html audio tag
	this.context 		= 	window.AudioContext 		? new window.AudioContext() :
           					window.webkitAudioContext 	? new window.webkitAudioContext() :
           					window.mozAudioContext 		? new window.mozAudioContext() :
           					window.oAudioContext 		? new window.oAudioContext() :
           					window.msAudioContext 		? new window.msAudioContext() :
           					undefined;

    this.source 		= this.context.createMediaElementSource(this.element);

    this.splitter 		= this.context.createChannelSplitter(this.source.channelCount);

    // store analysers
    this.analysers 		= new Array(this.source.channelCount + 1);
    // store volumes [0] for main and [i] for channels
    this.volumes 		= new Array(this.source.channelCount + 1);

    // store waves [0] for main and [i] for channels
    this.waves 			= new Array(this.source.channelCount + 1);

    // store frequencies [0] for main and [i] for channels
    this.frequencies 	= new Array(this.source.channelCount + 1);


    // logic
    // connect source to speakers
    this.source.connect(this.context.destination);

    // create main analyser    
    this.analysers[0]	= this.context.createAnalyser();
    this.source.connect(this.analysers[0]);
    this.analysers[0].fftSize = 32; // default precision

    // create 1 analyser per channel
    for(var i=1 ; i<=this.source.channelCount ; i++) {
		this.analysers[i] = this.context.createAnalyser();
		this.source.connect(this.analysers[0]);
		this.analysers[i].fftSize = 32;
	}

    // connect the splitter to the source
	this.source.connect(this.splitter);

	// connect the channels analysers to the splitter
	for(var i=1 ; i<=this.source.channelCount ; i++) {
		this.splitter.connect(this.analysers[i],i-1); // connect analyser to channels
	}
}


// update frequencies, volumes and waves
SimpleAudio.prototype.refreshData = function () {
	// FREQUENCY //////////////////////////////////////////////////////////
	for(var i=0 ; i<=this.source.channelCount ; i++) {
		this.frequencies[i] = new Uint8Array(this.analysers[i].frequencyBinCount);
		this.analysers[i].getByteFrequencyData(this.frequencies[i]);
	}

	// VOLUME /////////////////////////////////////////////////////////////
	// compute average volume for each channel
	for(var i=0 ; i<=this.source.channelCount ; i++) { // main  + channels
		var total_volumes = 0;
		for (var j=0; j<this.frequencies[i].length ; j++) {
			total_volumes += this.frequencies[i][j];
		}
		this.volumes[i] = total_volumes / this.frequencies[i].length;
	}

	// WAVE //////////////////////////////////////////////////////////////
	// update wave data for each channels
	for(var i=0 ; i<=this.source.channelCount ; i++) {
		this.waves[i] = new Uint8Array(this.analysers[i].frequencyBinCount);
		this.analysers[i].getByteTimeDomainData(this.waves[i]);
	}
};