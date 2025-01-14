<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    </head>
    <body>
        <h1>JavaScript Handlers</h1>
        <script>
		window.ready = function() {
            window.addEventListener("flutterInAppWebViewPlatformReady", function(event) {
                document.getElementById("capacityButton").addEventListener("click", function() {
                    // Llama a la función 'hola()' y envía el resultado al canal 'ApiChannel' en Flutter.
                    var result =hola(1);
                    alert(result);
                    window.flutter_inappwebview.callHandler('ApiChannel', result);
                });
            });

            function hola(length){
				
                var rsp = '{"metodo":"BLUETOOTH", "lat":"-25.28754","long":"-57.59911"}';
                
                return rsp;
                 
             }
        }
		
            window.addEventListener("flutterInAppWebViewPlatformReady", function(event) {
                document.getElementById("capacityButton").addEventListener("click", function() {
                    // Llama a la función 'hola()' y envía el resultado al canal 'ApiChannel' en Flutter.
                    var result ='{"metodo":"BLUETOOTH", "lat":"-25.28754","long":"-25.28754,-57.59911"}';
                    alert(result);
                    window.flutter_inappwebview.callHandler('ApiChannel', result);
                });
            });
			function hola(length){
				
                var rsp = '{"metodo":"BLUETOOTH", "lat":"-25.28754","long":"-25.28754,-57.59911"}';
                
                return rsp;
                 
             }
             
             function google_maps_flutter(t) {
                // var result = '{"metodo":"BLUETOOTH", "lat":"-25.28754","long":"'+t+'"}';
	        var result= '{"texto_imprime":["      KUDE de Factura Electronica","             DEMO INNOVASYS","           DE INNOVASYS S.R.L","            RUC: 80028485-2","Actividad Economica: ACTIVIDADES   DE","PARRILLADA","C MATRIZ: CNEL IGOR ORANGEIEREFF C\/","MORQUIO","Tel: 021-333555","           TIMBRADO: 15565344","      Inicio Vigencia: 06\/11\/2023","  FACTURA ELECTRONICA: 001-001-0000493","          COND VENTA: CONTADO","     Fecha y Hora: 08\/11\/2023 16:21","----------------------------------------","RUC      : 2192136-9","CI       : 0","Cliente  : OMAR DANIEL ALBERT QUEIROZ","----------------------------------------","Cant    Descripcion","P.U.              P.T.             Tasa%","----------------------------------------","1       1- SUKIYAKI 26 GDFG             ","400.000           400.000          10   ","1       PAN DE 3 SEMILLAS               ","20.000            20.000           10   ","1       10%                             ","100.000           100.000          10   ","1       DELIVERY 10.000                 ","10.000            10.000           10   ","----------------------------------------","Total a pagar en GS: 530.000","QUINIENTOS TREINTA MIL","----------------------------------------","Total Grav. 10% : 530.000","Total Grav. 5%  : 0","Total Exenta    : 0","----------------------------------------","Liquidacion del I.V.A.","10% : 48.182","5%  : 0","Total I.V.A. : 48.182","----------------------------------------","Pagos: ","TARJETA DE CREDITO    :          530.000","----------------------------------------","Cajero: OMARALBERT","----------------------------------------","DELIVERY: ","MOTORISTA: OMAR","LLEVA POS: SI","Telefono: 0981825580","Cliente: OMAR ALBERT","Direccion: AMISTAD Y  DOMINICANA","----------------------------------------","Caja: #419 Vta: #1705 Ped: #85","----------------------------------------","Impreso: 08\/01\/2024 10:55:17","----------------------------------------","Consulte la validez de esta Factura","Electronica con el numero de CDC impreso","abajo en:","https:\/\/ekuatia.set.gov.py\/consultas","CDC: ","ESTE DOCUMENTO ES UNA REPRESENTACION","GRAFICA DE UN DOCUMENTO ELECTRONICO","(XML)","\u003CQR\u003E\u003C\/QR\u003E","----------------------------------------","     * GRACIAS POR SU COMPRA *","LOS DATOS IMPRESOS REQUIEREN DE CUIDADOS","ESPECIALES. PARA ELLO DEBE EVITARSE EL","CONTACTO DIRECTO CON PLASTICOS,","SOLVENTES DE PRODUCTOS QUIMICOS. EVITE","TAMBIEN LA EXPOSICION AL CALOR Y HUMEDAD"," EN EXCESO, LUZ SOLAR O LAMPARAS","FLUORESCENTES.","----------------------------------------","ORIGINAL: CLIENTE","DUPLICADO: ARCHIVO TRIBUTARIO","TRIPLICADO: CONTABILIDAD","","PARA CUALQUIER RECLAMO O SUGERENCIA,","FAVOR COMUNIQUESE","AL 0904 111111","",""],"url_redir":"reimprimir_facturas_retro.php","gps_obtener":null,"lista_post":[null],"imp_url":"http:\/\/localhost\/impresorweb\/ladocliente.php","metodo":"BLUETOOTH"}';

                window.flutter_inappwebview.callHandler('ApiChannel', result);


                }
                            
			
			
        </script>
		
		<input type="button" value="Capacity Chart" id="capacityButton">

		<input type="button" value="Capacity Cha3rt" id="hola" onclick="google_maps_flutter('-25.28754,-57.59911')">

    </body>
</html>