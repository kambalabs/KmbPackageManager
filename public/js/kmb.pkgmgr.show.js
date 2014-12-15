$(document).ready(function() {
    $("#affectedhostlist").dataTable({});
    $(document).on('click','.patch-btn',function(data){
	NProgress.start();
	$(":input").attr("disabled", "disabled");
	$("a").attr("disabled", "disabled");
	$.gritter.add({
	    title: 'Information',
	    text: 'Please wait while checking patches',
	    class_name: 'gritter-info',
	});

	$.ajax({
	    type: "POST",
	    url: $(this).data('url'),
	    data: "cve="+$(this).data('cve')+"&packages="+$(this).data('package'),
	    success: function(data) {
		$("#prepatch-modalcontent").html(data);
		$('#prepatch_detail').modal('show');
	    },
	    complete: function(data) {
		NProgress.done();
		$(":input").attr("disabled", false);
		$("a").attr("disabled", false);
	    }
	});
	
    });
    $(document).on('submit','form[data-async]', function(event) {
	var $form = $(this);
	NProgress.start();
	$(".patch-btn").attr("disabled", "disabled");
	$("a").attr("disabled", "disabled");
	$.gritter.add({
	    title: 'Patch',
	    text: 'Applying Patch',
	    class_name: 'gritter-info',
	});

	$form.parents('.modal').modal('hide');
	$.ajax({
	    type: $form.attr('method'),
	    url: $form.attr('action'),
	    data: $form.serialize(),
	    success: function(data, status) {
		if(data['status'] == "success") {
		    $.gritter.add({
			title: 'Patch',
			text: 'Patch applied successfully',
			class_name: 'gritter-success',
		    });
		    location.reload(true);
		}else if(data['status'] == "partial") {
		    var $error_list = "<ul>";
		    $.each(data['errors'], function (agent,result) {
			$error_list += "<li>"+ agent +" : "+ result +"</li>";
		    });
		    $.gritter.add({
			title: 'Patch',
			text: 'Patch applied with errors.<br>'+$error_list,
			class_name: 'gritter-warning',
		    });
		}else {
		    var $error_list = "<ul>";
		    $.each(data['errors'], function (agent,result) {
			$error_list += "<li>"+ agent +" : "+ result +"</li>";
		    });
		    $.gritter.add({
			title: 'Patch',
			text: 'Patch NOT applied. <br/>'+ $error_list,
			class_name: 'gritter-danger',
		    });		    
		}
	    },
	    error: function(data, status) {
		$.gritter.add({
		    title: 'Patch',
		    text: 'Error while applying Patch',
		    class_name: 'gritter-danger',
		});
	    },
	    complete: function(data) {
		NProgress.done();
		$(":input").attr("disabled", false);
		$("a").attr("disabled", false);
	    }
	});

	event.preventDefault();
    });

    $(document).on('click','#patchAllBtn', function(data){
	NProgress.start();
	$(":input").attr("disabled", "disabled");
	$("a").attr("disabled", "disabled");
	$.gritter.add({
	    title: 'Information',
	    text: 'Please wait while checking patches',
	    class_name: 'gritter-info',
	});

	$.ajax({
	    type: "POST",
	    url: $(this).data('url'),
	    data: "cve="+$(this).data('cve'),
	    success: function(data) {
		$("#prepatch-modalcontent").html(data);
		$('#prepatch_detail').modal('show');
	    },
	    complete: function(data) {
		NProgress.done();
		$(":input").attr("disabled", false);
		$("a").attr("disabled", false);
	    }
	});	
    });
});
