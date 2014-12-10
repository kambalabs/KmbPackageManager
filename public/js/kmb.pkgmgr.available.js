$(document).ready(function(){
    $('#fixlist').dataTable($.extend({}, DATATABLES_NPROGRESS_DEFAULT_SETTINGS, {
	"serverSide": true,
	"ajax": {
	    "url": window.location,
	    "complete": function() {
		NProgress.done();
	    },
	    "error": function (cause) {
		NProgress.done();
	    }
	}
    }));
});
