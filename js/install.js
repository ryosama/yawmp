$(document).ready(function(){

	// add index path
	$('body').delegate('#add_path_btn','click',function(){
		var html = '<li>'+
						'<div>'+
							'Label <input class="dir_label" name="labels[]" value="Songs" placeholder="Label..." />'+
							' <a class="btn btn-danger btn-xs remove_path" href="#"><i class="fa fa-minus fa-lg"></i> Remove this path</a>'+
						'</div>'+
						'<div>Alias <input class="alias" name="alias[]" value="/songs" placeholder="Alias..." /><span class="check"></span></div>'+
						'<div>Absolute path <input class="path" name="paths[]" value="C:/my_songs" placeholder="Path..." /><span class="check"></div>'+
					'</li>';
		$('#directories').append(html);
	});

	// remove index path
	$('body').delegate('.remove_path','click',function(){
		$(this).parents('li').remove();
	});

	// save config
	$('body').delegate('#save_btn','click',function(){
		check_form();
	});

	// check if database path is writable
	$('body').delegate('#database','blur',function(){
		var check_elm = $(this).next('span.check');
		$(check_elm).html('<i class="fa fa-spinner fa-spin fa-lg"></i>');
		// check if file is writable
		$.ajax({
			url:'lib/ajax_install.php',
			data:{'what':'touch', 'path': $(this).val() },
			type:'GET',
			dataType :'html',
			success:function(result){
				if (result==1)
					$(check_elm).html('<i class="fa fa-check-circle-o fa-lg text-success"></i> Successfully write the temporary file');
				else
					$(check_elm).html('<i class="fa fa-warning fa-lg text-danger"></i> Unable to create temporary file');
			}
		});
	});


	// check if alias is accessible
	$('body').delegate('input.alias','blur',function(){
		var check_elm = $(this).next('span.check');
		$(check_elm).html('<i class="fa fa-spinner fa-spin fa-lg"></i>');
		// check if alias is crawlable
		$.ajax({
			url:'lib/ajax_install.php',
			data:{'what':'crawl', 'url': $(this).val() },
			type:'GET',
			dataType :'html',
			success:function(result){
				if (result==1)
					$(check_elm).html('<i class="fa fa-check-circle-o fa-lg text-success"></i> Successfully access to alias');
				else
					$(check_elm).html('<i class="fa fa-warning fa-lg text-danger"></i> Unable to access to alias');
			}
		});
	});


	// check if path is openable
	$('body').delegate('input.path','blur',function(){
		var check_elm = $(this).next('span.check');
		$(check_elm).html('<i class="fa fa-spinner fa-spin fa-lg"></i>');
		// check if file is writable
		$.ajax({
			url:'lib/ajax_install.php',
			data:{'what':'opendir', 'path': $(this).val() },
			type:'GET',
			dataType :'html',
			success:function(result){
				if (result==1)
					$(check_elm).html('<i class="fa fa-check-circle-o fa-lg text-success"></i> Successfully open directory');
				else
					$(check_elm).html('<i class="fa fa-warning fa-lg text-danger"></i> Unable to open directory');
			}
		});
	});


	// add one path to index when everything is ready
	$('#add_path_btn').click();
});


// check and submit the configuration
function check_form() {
	// reset status of input
	$('input').css('background','none');
	var success = true;

	if ($('#title').val().length <= 0) {
		$('#title').css('background-color','orange');
		success = false;
	}

	if ($('#database').val().length <= 0) {
		$('#database').css('background-color','orange');
		success = false;
	}

	// for each directory, check the value
	$('.dir_label, .alias, .path').each(function(){
		if ($(this).val().length <= 0) {
			$(this).css('background-color','orange');
			success = false;
		}
	});

	if (success) // everything is ok, submit the form
		$('#install_form').submit();
}