<?php
/*----------------------------------
UR: 20102021

---------------------------------*/
require_once("../../includes/conexion.php");
require_once("../../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "22";
$dirsup_sec = "S";
require_once("../../includes/rsusuario.php");

require_once("../../includes/funciones_caja.php");

$idcaja = intval($_GET['idcaja']);
$whereadd = "";
if ($idcaja > 0) {
    $whereadd = " and idcaja = $idcaja ";
}

$origen = intval($_REQUEST['ori']);

if ($origen == 2) {
    $idtipocaja = 2;//tesoreria
} else {
    $idtipocaja = 1;//gestion
}
// trae la ultima caja cerrada del usuario a menos que se envie id de caja
$buscar = "
Select * , (select tipotk from usuarios where idusu = caja_super.cajero) as tipotk
from caja_super 
where 
estado_caja=3 
and cajero=$idusu 
and tipocaja=$idtipocaja
$whereadd
order by fecha_cierre desc
limit 1
";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaja->fields['idcaja']);
if ($idcaja == 0) {
    echo "La caja que intentas imprimir no existe o no pertenece a tu usuario.";
    exit;
}
//actualiza_caja($idcaja);
recalcular_caja($idcaja);




$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));




$parametros_array = [
    'idcaja' => $idcaja,
    'tipo_ticket' => $rscaja->fields['tipotk']
];
$res = imprime_cierre_caja($parametros_array);
$texto = $res['ticket'];



// trae la primera impresora
$consulta = "SELECT * FROM impresoratk where idempresa = $idempresa  and idsucursal = $idsucursal and borrado = 'N' and tipo_impresora = 'CAJ' order by idimpresoratk asc limit 1";
$rsimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$pie_pagina = $rsimp->fields['pie_pagina'];
$defaultprnt = "http://localhost/impresorweb/ladocliente.php";
$script_impresora = trim($rsimp->fields['script']);
if (trim($script_impresora) == '') {
    $script_impresora = $defaultprnt;
}

// url redireccion por defecto
$url1 = 'gest_administrar_caja_new.php';

if ($idtipocaja == 2) {
    $url1 = 'teso_abrir_caja.php';
} else {
    $url1 = 'gest_administrar_caja_new.php';

}

// buscar impresora remota
$consulta = "
SELECT * FROM 
impresoratk 
where 
idsucursal = $idsucursal
and borrado = 'N' 
and tipo_impresora='REM' 
order by idimpresoratk  asc
limit 1
";
$rsimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$pie_pagina = $rsimp->fields['pie_pagina'];
$metodo_app = $rsimp->fields['metodo_app'];
$defaultprnt = "http://localhost/impresorweb/ladocliente.php";
$script_impresora_app = trim($rsimp->fields['script']);
if (trim($script_impresora_app) == '') {
    $script_impresora_app = $defaultprnt;
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Cierre de Caja</title>
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript">

function imprime_cliente(){
	if(!(typeof ApiChannel === 'undefined')){

							   
		ApiChannel.postMessage('<?php
        // lista de post a enviar
        if ($metodo_app == 'POST_URL') {
            $lista_post = [
                'tk' => $texto,
                'tk_json' => '' // en ticket usar $ticket_json  // no aplica en factura
            ];
        }
//parametros para la funcion
$parametros_array_tk = [
    'texto_imprime' => $texto, // texto a imprimir
    'url_redir' => $url1, // redireccion luego de imprimir
    'lista_post' => $lista_post, // se usa solo con metodo POST_URL
    'imp_url' => $script_impresora_app, // se usa solo con metodo POST_URL
    'metodo' => $metodo_app // POST_URL, SUNMI, ''
];
echo texto_para_app($parametros_array_tk);

?>');
							   
	}
	if((typeof ApiChannel === 'undefined')){		
		var texto = document.getElementById("texto").value;
        var parametros = {
                "tk"            : texto,
				"duplic_control" : "N"
        };
       $.ajax({
                data:  parametros,
                url:   '<?php echo $script_impresora; ?>',
                type:  'post',
				dataType: 'html',
                beforeSend: function () {
                        $("#impresion_box").html("Enviando Impresion...");
                },
				crossDomain: true,
                success:  function (response) {
						//$("#impresion_box").html(response);	
						//si impresion es correcta marcar
						var str = response;
						var res = str.substr(0, 18);
						//alert(res);
						if(res == 'Impresion Correcta'){
								document.location.href='<?php echo $url1?>';
						}else{
							$("#impresion_box").html(response);	
						}
						
                },
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			alert('Pagina no encontrada [404]');
		
		} else if (jqXHR.status == 500) {
		
			alert('Internal Server Error [500].');
		
		} else if (textStatus === 'parsererror') {
		
			alert('Requested JSON parse failed.');
		
		} else if (textStatus === 'timeout') {
		
			alert('Tiempo de espera agotado, time out error.');
		
		} else if (textStatus === 'abort') {
		
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
		
		} else {
		
			alert('Uncaught Error: ' + jqXHR.responseText);
		
		}
	});
			
	}
}
function volver_caja(){
	document.location.href='<?php echo $url1; ?>';
}	
</script>
</head>

<body bgcolor="#CCCCCC" onLoad="imprime_cliente()">
<div style="width:320px; border:1px solid; margin:0px auto; background-color:#FFFFFF; padding:5px; text-align:center; min-height:50px;" id="impresion_box">
<p align="center"><input type="button" value="imprimir" style="padding:10px;" onmouseup="imprime_cliente();"></p>
</div><br />
<div style="width:320px; border:1px solid; margin:0px auto; background-color:#FFFFFF; padding:5px; text-align:center; min-height:50px;" >
<p align="center"><input type="button" value="Volver a la Caja" style="padding:10px;" onmouseup="volver_caja();"></p>
</div><br />
<div style="width:320px; border:1px solid; margin:0px auto; background-color:#FFFFFF; padding:5px;">
<textarea readonly id="texto" style="display:; width:315px; height:420px;"><?php echo $texto; ?></textarea>
<pre>
<?php //echo $texto;?>
</pre>
</div>

</body>
</html>