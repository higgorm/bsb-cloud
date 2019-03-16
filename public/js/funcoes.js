$(document).ready(function() {

    $(".campoCPF").keypress(function() {
        var c = $(this).val();
        if (c.replace(/\./g, '').replace(/\-/g, '').replace(/\_/g, '') !== '')
            mascaraPrincipal(this, cpf);
    }).blur(function() {
        var c = $(this).val();
        if (c.replace(/\./g, '').replace(/\-/g, '').replace(/\_/g, '') !== '') {
            if (!$(this).validateCPF()) {
                alert('Digite um número de CPF válido!');
                $(this).val('').focus();
            }
        }
    });
//    .attr('maxlength', '14')
//            .blur(function() {
//                if (this.value.length < 14) {
//                    this.value = '';
//                }
//            });

    formatReal = function(number) 
    {        
        var decimals = 2;
        var dec_point = '.';
        var thousands_sep = '';

        var n = number, c = isNaN(decimals = Math.abs(decimals)) ? 2
        : decimals;
        var d = dec_point == undefined ? "," : dec_point;
        var t = thousands_sep == undefined ? "." : thousands_sep, s = n < 0 ? "-" : "";
        var i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
        return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
    }
    
     validaData = function(valor) {
        var date=valor;
        var ardt=new Array;
        var ExpReg=new RegExp("(0[1-9]|[12][0-9]|3[01])/(0[1-9]|1[012])/[12][0-9]{3}");
        ardt=date.split("/");
        erro=false;
        if ( date.search(ExpReg)==-1){
            erro = true;
        }
        else if (((ardt[1]==4)||(ardt[1]==6)||(ardt[1]==9)||(ardt[1]==11))&&(ardt[0]>30))
            erro = true;
        else if ( ardt[1]==2) {
            if ((ardt[0]>28)&&((ardt[2]%4)!=0))
                erro = true;
            if ((ardt[0]>29)&&((ardt[2]%4)==0))
                erro = true;
        }
        if (erro) {
            return false;
        }
        return true;
    }
});
	function formatar(mascara, documento, numero = false, evt = null){
		var i = documento.value.length;
		var saida = mascara.substring(0,1);
		var texto = mascara.substring(i);
		if( numero == true ){
			//var tecla=(window.event)?event.keyCode:e.which;   
			var tecla = (evt.which) ? evt.which : event.keyCode;
			if((tecla > 47 && tecla < 58 || tecla < 20)){
				if( tecla > 20 ){
					if (texto.substring(0,1) != saida)
						documento.value += texto.substring(0,1);
				}
			}else{ 
				return false;
			}
		}else{
			if (texto.substring(0,1) != saida)
				documento.value += texto.substring(0,1);
		}
	}

	function moeda(z){
		v = z.value;
		v=v.replace(/\D/g,"") // permite digitar apenas numero
		//v=v.replace(/(\d{1})(\d{14})$/,"$1.$2") // coloca ponto antes dos ultimos digitos
		//v=v.replace(/(\d{1})(\d{11})$/,"$1.$2") // coloca ponto antes dos ultimos 11 digitos
		//v=v.replace(/(\d{1})(\d{8})$/,"$1.$2") // coloca ponto antes dos ultimos 8 digitos
		//v=v.replace(/(\d{1})(\d{5})$/,"$1.$2") // coloca ponto antes dos ultimos 5 digitos
		v=v.replace(/(\d{1})(\d{1,2})$/,"$1.$2") // coloca virgula antes dos ultimos 2 digitos
		z.value = v;
	}
	
	function formataAll(mascara, str){
		var i = str.length;
		var saida = mascara.substring(0,1);
		var texto = mascara.substring(i);
		var retorno = '';

		if (texto.substring(0,1) != saida)
			str += texto.substring(0,1);
	}
	
	//formata de forma generica os campos
	function formataCampo(campoSoNumeros, Mascara) { 
        var boleanoMascara; 

        var posicaoCampo = 0;    
        var NovoValorCampo="";
        var TamanhoMascara = campoSoNumeros.length;; 

        for(i=0; i<= TamanhoMascara; i++) { 
            boleanoMascara  = ((Mascara.charAt(i) == "-") || (Mascara.charAt(i) == ".")
                                                          || (Mascara.charAt(i) == "/")) 
            boleanoMascara  = boleanoMascara || ((Mascara.charAt(i) == "(") 
                                             || (Mascara.charAt(i) == ")") || (Mascara.charAt(i) == " ")) 
            if (boleanoMascara) { 
                NovoValorCampo += Mascara.charAt(i); 
                TamanhoMascara++;
            }else { 
                NovoValorCampo += campoSoNumeros.charAt(posicaoCampo); 
                posicaoCampo++; 
            }              
        }      
        return NovoValorCampo;
	}
	
	