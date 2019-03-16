$(document).ready(function() {
    
   moveRelogio = function(){ 
   	momentoAtual = new Date() ;
   	hora = momentoAtual.getHours() ;
   	minuto = momentoAtual.getMinutes() ;
   	segundo = momentoAtual.getSeconds() ;

   	horaImprimivel = hora + " : " + minuto + " : " + segundo ;

   	//document.frmPrincipal.relogio.value = horaImprimivel ;

        $('#relogio').val(horaImprimivel);
   	setTimeout("moveRelogio()",1000) ;
    },
    verificaAgenda = function(_horaInicio, _horaFim, _intervalo){ 
            momentoAtual = new Date() ;
            hora = momentoAtual.getHours() ;
            minuto = momentoAtual.getMinutes() ;
            segundo = momentoAtual.getSeconds() ;

            horaImprimivel = hora + " : " + minuto + " : " + segundo ;
            $('#relogio').val(horaImprimivel);
            
            if(segundo == 0 && (hora >= _horaInicio || hora <= _horaFim) && (minuto == 25 || minuto == 55))
            {
                $.ajax({
                    type: 'get',
                    url: '/agenda/verificaagenda',
                    data: {
                            'h': hora,
                            'm': minuto
                        },
                    success: function(data) {
                        var txtAlert = '';
                        var data2 = eval("(" + data + ")");                        
                        if(data2 != ''){
                            $.each(data2, function() {
                                $.each(this, function(cliente, maca) {
                                    txtAlert += '<li>cliente ' + cliente + ' na maca ' + maca + '</li>';
                                });
                            });
                            //var txt = 'Chamar ' + txtAlert;
                            //alert(txtAlert);
                            $('#bodyModalRelogio').html('<table><tr><td><ul>'+txtAlert+'</ul></td></tr></table>');
                            $('#modalRelogio').modal('show');
                        }
                    }
                });
            }
            setTimeout("verificaAgenda("+_horaInicio+","+_horaFim+","+_intervalo+")",1000) ;
    }
});