<?php
require_once("includes/conexion.php");
$modulo = "1";
$submodulo = "222";
require_once("includes/rsusuario.php");
require_once("includes/funciones.php");
require_once("includes/funciones_traslados.php");

$idtanda = intval($_GET['id']);
$texto = traslado_ticket($idtanda);
/*echo "<pre>";
echo $texto;
echo "<pre>";*/


$redir = intval($_GET['redir']);
$url1 = "gest_transferencias.php";
if ($redir == 1) {
    $url1 = "gest_transferencias.php";
}
if ($redir == 2) {
    $url1 = "gest_transferencias_rec.php";
}
if ($redir == 3) {
    $url1 = "gest_adm_depositos_mover_tanda.php?l=1";
}


// trae la primera impresora
$consulta = "
SELECT * 
FROM impresoratk 
where 
idsucursal = $idsucursal 
and borrado = 'N' 
and tipo_impresora = 'CAJ' 
order by idimpresoratk asc 
limit 1
";
$rsimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$pie_pagina = $rsimp->fields['pie_pagina'];
$defaultprnt = "http://localhost/impresorweb/ladocliente.php";
$script_impresora = trim($rsimp->fields['script']);
if (trim($script_impresora) == '') {
    $script_impresora = $defaultprnt;
}

?><html>
<head>
<script src="js/jquery-1.10.2.min.js"></script>
<meta charset="utf-8">
<title>Medidor de Factura</title>
<script>
function imprime_cliente(){
	//alert('a');
	var texto = document.getElementById("texto").value;
	if(!(typeof ApiChannel === 'undefined')){
		ApiChannel.postMessage('<?php

        $parametros_array_tk = [
            'texto_imprime' => $texto,
            'url_redir' => $url1
        ];
echo texto_para_app($parametros_array_tk);

?>');
	}
	var texto = $("#texto").val();	
	var parametros = {
		"tk"      : texto,
		'tk_json' : '<?php echo $texto_json; ?>'
	};
   $.ajax({
		data:  parametros,
		url:   '<?php echo $script_impresora ?>',
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
				document.location.href='<?php echo $url1; ?>';
			}else{
				$("#impresion_box").html(response);	
				document.location.href='<?php echo $url1; ?>';
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
// ejecutar al cargar la pagina
$( document ).ready(function() {
	imprime_cliente();

});
</script>
</head>
<body>
	<textarea name="texto" id="texto" style="display: none;"><?php echo $texto; ?></textarea>
	<div id="impresion_box">
	
	</div>
	</body>
</html>