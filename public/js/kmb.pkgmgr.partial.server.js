$(window).load(function () {
//    console.log(document.location.pathname);
    var serverUriMatch = document.location.pathname.match(/\/server\/(.*)/);
    var server = serverUriMatch ? serverUriMatch[1] : null;
    var envUriMatch = document.location.pathname.match(/^\/env\/([1-9][0-9]*)\//);
    var environment = envUriMatch ? envUriMatch[1] : 0;
    if(server) {
        $.ajax({
            'async': false,
            'global': false,
            'url' : '/patchlist/'+server,
            'dataType' : 'json',
            'success' : function(json){
        	patchList = json;
            }
        });
        var patchnr = patchList.length;
        $("#vul_count").text(patchnr + ' cve');
        if(patchnr > 10) {
            $("#fullpatch-btn").addClass('btn-danger');
            $("#vul_count").addClass('red');
        }else if (patchnr <= 10 && patchnr > 5 ){
            $("#fullpatch-btn").addClass('btn-warning');
            $("#vul_count").addClass('yellow');
        }else if (patchnr == 0) {
            $("#fullpatch-btn").addClass('btn-success');
            $("#fullpatch-btn").text('OK');
            $("#vul_count").addClass('green');
        }else{
            $("#fullpatch-btn").addClass('btn-success');
            $("#vul_count").addClass('green');
        }
    }else{
        $("#vul_count").text('n/a');
    }
    if(environment != null){
        $('#nodefixlist').dataTable($.extend({}, DATATABLES_NPROGRESS_DEFAULT_SETTINGS, {
            "serverSide": true,
            "ajax": {
                //            "url": window.location,
                "url": '/env/'+ environment +'/security-fix/'+server,
                "complete": function() {
                    NProgress.done();
                },
                "error": function (cause) {
                    NProgress.done();
                }
            }
        }));
    }
    $('#patchTable').dataTable({});
});
