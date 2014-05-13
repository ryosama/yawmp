//////////////////////////////////// SIMPLE AUDIO OBJECT //////////////////////////////////////////
function SimpleCanvas(obj) {
	// attributs
	this.element = obj; // html audio tag
	this.context = this.element.getContext('2d');
    this.frame 	 = 0;
}

SimpleCanvas.prototype.clearCanvas = function() {
	my_canvas.context.clearRect(0,0, this.element.width, this.element.height);
};