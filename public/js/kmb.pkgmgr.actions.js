function getResult(data,id,discovered_nodes,refreshResult,translation) {
    $ .ajax({
        type: 'GET',
        url:  '/mcollective/results/' + data['actionid'],
	dataType: 'json',
	success: function(data,status) {
            resultsReceived = Object.keys(data).length;
	    console.log("Success in resultUrl : " + resultsReceived);

            // 0 => all ok
            // 1 => partial failure
            // 2 => failure

            var globalStatus = 0;
            var errorCount = 0;
            var actions = [];

            var finished = true;
            $.each(data, function(index, obj) {
                console.log(obj['action'] + ' : ' + obj['finished'] + ' / ' + obj['statuscode']);
                if(jQuery.inArray( obj['action'], actions ) == -1)
                {
                    actions.push(obj['action']);
                }

                if(! obj['finished']) {
                    finished=false;
                }
                if(obj['statuscode'] != 0) {
                    globalStatus = 1;
                    errorCount += 1;
                }

                if (errorCount == resultsReceived) {
                    globalStatus = 2;
                }
            });

            if(finished && (actions.length >= 3 )) {
                console.log('Stopping polling... Received : ' + resultsReceived + ' - Discovered : ' + discovered_nodes);
                clearInterval(refreshResult);
                NProgress.done();
	        $(":input").attr("disabled", false);
	        $("a").attr("disabled", false);


                if (globalStatus == 0) {
                    $.gritter.add({
	                title: translation['patchTitle'],
	                text: translation['patchSuccess'],
	                class_name: 'gritter-success',
	            });
                    // Let some time to user to see patch result (and avoid refresh too soon)
                    sleep(5000);
	            location.reload(true);
                } else if(globalStatus == 1) {
                    $.gritter.add({
	                title: translation['patchTitle'],
	                text: translation['patchPartially'],
	                class_name: 'gritter-warning',
	            });
                } else {
                    $.gritter.add({
	                title: translation['patchTitle'],
	                text: translation['patchNotApplied'],
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
    var pkgmgrTranslation = null;

    $.ajax({
	'async': false,
	'global': false,
	'url' : '/package-manager/translation',
	'dataType' : 'json',
	'success' : function(json){
	    pkgmgrTranslation = json;
	}
    });

    $(document).on('click','.patch-btn',function(data){
	NProgress.start();
	$(":input").attr("disabled", "disabled");
	$("a").attr("disabled", "disabled");
	$.gritter.add({
	    title: pkgmgrTranslation['infoTitle'],
	    text: pkgmgrTranslation['patchWaitCheck'],
	    class_name: 'gritter-info',
	});

	$.ajax({
	    type: "POST",
	    url: $(this).data('url'),
	    data: "cve="+$(this).data('cve')+"&packages="+$(this).data('package'),
	    success: function(data) {
		$("#prepatch-modalcontent").html(data);
                $("#patchTable").DataTable();
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
	    title: pkgmgrTranslation['patchTitle'],
	    text: pkgmgrTranslation['patchApply'],
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

                    var refreshResult = setInterval(function() {
                                            getResult(data,id,discovered_nodes,refreshResult,pkgmgrTranslation);
                                        }, 10000);

                    getResult(data,id,discovered_nodes,refreshResult,pkgmgrTranslation);
                });
	    },
	    error: function(data, status) {
		$.gritter.add({
		    title: pkgmgrTranslation['patchTitle'],
		    text: pkgmgrTranslation['patchError'],
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

});
