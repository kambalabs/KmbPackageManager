function getResult(data,id,discovered_nodes,refreshResult) {
    $ .ajax({
        type: 'GET',
	url:  '/mcollective/results/' + data['actionid'] + '/requestid/' + id,
	dataType: 'json',
	success: function(data,status) {
            resultsReceived = Object.keys(data).length;
	    console.log("Success in resultUrl : " + resultsReceived);

            if (resultsReceived == discovered_nodes) {
                console.log('Stopping polling... Received : ' + resultsReceived + ' - Discovered : ' + discovered_nodes);
                clearInterval(refreshResult);
                NProgress.done();
		$(":input").attr("disabled", false);
		$("a").attr("disabled", false);

                // 0 => all ok
                // 1 => partial failure
                // 2 => failure

                var globalStatus = 0;
                var errorCount = 0;
                $.each(data, function(index, obj) {
                    if(obj['statuscode'] != 0) {
                        globalStatus = 1;
                        errorCount += 1;
                    }

                    if (errorCount == discovered_nodes) {
                        globalStatus = 2;
                    }
                });

                if (globalStatus == 0) {
                    $.gritter.add({
			title: 'Patch',
			text: 'Patch applied successfully',
			class_name: 'gritter-success',
		    });
                    // Let some time to user to see patch result (and avoid refresh too soon)
                    sleep(5000);
		    location.reload(true);
                } else if(globalStatus == 1) {
                    $.gritter.add({
			title: 'Patch',
			text: 'Patch partially applied.<br/>See logs for details',
			class_name: 'gritter-warning',
		    });
                } else {
                    $.gritter.add({
			title: 'Patch',
			text: 'Patch NOT applied.<br/>See logs for details',
			class_name: 'gritter-danger',
		    });
                }

            }
        }
    });

}



function sleep(milliseconds) {
    var start = new Date().getTime();
    for (var i = 0; i < 1e7; i++) {
        if ((new Date().getTime() - start) > milliseconds){
            break;
        }
    }
}


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
                $.each(data['requestid'], function(id, content) {
                    discovered_nodes = content['hosts'].length;
                    // 'route' => '/mcollective/results/:actionid[/requestid/:requestid]',

                    var refreshResult = setInterval(function() {
                                            getResult(data,id,discovered_nodes,refreshResult);
                                        }, 10000);

                    getResult(data,id,discovered_nodes,refreshResult);
                });
		// if(data['status'] == "success") {
		//     $.gritter.add({
		// 	title: 'Patch',
		// 	text: 'Patch applied successfully',
		// 	class_name: 'gritter-success',
		//     });

                //     console.log(data);

		//     location.reload(true);
		// }else if(data['status'] == "partial") {
		//     var $error_list = "<ul>";
		//     $.each(data['errors'], function (agent,result) {
		// 	$error_list += "<li>"+ agent +" : "+ result +"</li>";
		//     });
		//     $.gritter.add({
		// 	title: 'Patch',
		// 	text: 'Patch applied with errors.<br>'+$error_list,
		// 	class_name: 'gritter-warning',
		//     });
		// }else {
		//     var $error_list = "<ul>";
		//     $.each(data['errors'], function (agent,result) {
		// 	$error_list += "<li>"+ agent +" : "+ result +"</li>";
		//     });
		//     $.gritter.add({
		// 	title: 'Patch',
		// 	text: 'Patch NOT applied. <br/>'+ $error_list,
		// 	class_name: 'gritter-danger',
		//     });
		// }
	    },
	    error: function(data, status) {
		$.gritter.add({
		    title: 'Patch',
		    text: 'Error while applying Patch',
		    class_name: 'gritter-danger',
		});
	    },
	    complete: function(data) {
		// NProgress.done();
		// $(":input").attr("disabled", false);
		// $("a").attr("disabled", false);
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
