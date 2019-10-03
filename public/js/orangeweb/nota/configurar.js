
var notaConfigurar = {
    init: function() {},
    recuperarUltimaNota: function (obj) {

        $("#NR_NFE").hide();
        $("#NR_NFCE").hide();
        $("#fsTokenNFCE").hide();

        if ($(obj).val().toString().toUpperCase() == 'NFE'){
            $("#NR_NFE").show();
        } else {
            $("#NR_NFCE").show();
            $("#fsTokenNFCE").show();
        }
    }
}