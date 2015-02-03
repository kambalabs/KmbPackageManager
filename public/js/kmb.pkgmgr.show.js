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


    $("#affectedhostlist").dataTable({});
    $(document).on('click','#patchAllBtn', function(data){
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
