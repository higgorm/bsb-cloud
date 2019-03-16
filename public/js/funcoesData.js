$(document).ready(function() {
    formataData = function(strData){
            var data = new Date(strData * 1000);
                                    
            var dia = data.getDate();
            dia = (dia.toString().length < 2) ? "0"+dia.toString() : dia;
            var mes = data.getMonth()+1;
            mes = (mes.toString().length < 2) ? "0"+mes.toString() : mes;

            return [dia, mes, data.getFullYear()].join('/');
        }
        ,       
        formatDate = function(date, fmt) { //%d/%M/%Y
            function pad(value) {
                return (value.toString().length < 2) ? '0' + value : value;
            }
            return fmt.replace(/%([a-zA-Z])/g, function (_, fmtCode) {
                switch (fmtCode) {
                case 'Y':
                    return date.getUTCFullYear();
                case 'M':
                    return pad(date.getUTCMonth() + 1);
                case 'd':
                    return pad(date.getUTCDate());
                case 'H':
                    return pad(date.getUTCHours());
                case 'm':
                    return pad(date.getUTCMinutes());
                case 's':
                    return pad(date.getUTCSeconds());
                default:
                    throw new Error('Unsupported format code: ' + fmtCode);
                }
            });
        }
});