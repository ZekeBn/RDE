<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "22";
require_once("includes/rsusuario.php");
$tipo = intval($_GET['tipo']);
$regser = intval($_GET['regser']);//id unico del serial
$re = intval($_GET['r']);
// si no existe caja abierta redirecciona
if ($regser == 0) {
    header("location: caja_retiros_cajero.php");
    exit;
}
// preferencias
$consulta = "SELECT * FROM preferencias WHERE  idempresa = $idempresa ";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$obligaprov = trim($rspref->fields['obligaprov']);
$impresor = trim($rspref->fields['script_ticket']);
$impresor = strtolower($impresor);
if ($impresor == '') {
    $impresor = 'http://localhost/impresorweb/ladocliente.php';
}
$script_impresora = $impresor;



// centrar nombre de empresa
$nombreempresa_centrado = corta_nombreempresa($nombreempresa);
$fecha_apertura = date("d/m/Y H:i:s", strtotime($rscaj->fields['fecha_apertura']));
$fecha_cierre = date("d/m/Y H:i:s", strtotime($rscaj->fields['fecha_cierre']));
$cajero = $rscaj->fields['cajero'];


if ($tipo == 1) {
    $buscar = "
SELECT *,
(select usuario from usuarios where idusu = caja_retiros.cajero) as cajero,
(select usuario from usuarios where idusu = caja_retiros.retirado_por) as quienllevo,
(select sucursales.nombre from sucursales where idsucu = caja_retiros.idsucursal) as sucursal
FROM caja_retiros
where
regserialretira=$regser and cajero=$idusu";
    //echo $buscar;exit;
    $rsfr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $td = $rsfr->RecordCount();
    if ($td > 0) {
        $idret = intval($rsfr->fields['regserialretira']);
        $totalret = intval($rsfr->fields['monto_retirado']);
        $quien = $rsfr->fields['quienllevo'];
        $nombresucursal1 = $rsfr->fields['sucursal'];
        $obs = $rsfr->fields['obs'];
        $retirado = 1;

        $nombreempresa_centrado = corta_nombreempresa($nombreempresa);
        $ahorta = date("d-m-Y H:i:s", strtotime($rsfr->fields['fecha_retiro']));
        $cajero1 = strtoupper($rsfr->fields['cajero']);
        $totalret = floatval($rsfr->fields['monto_retirado']);
        $hoy = date("d/m/Y H:i:s");
        $texto = "
****************************************
$nombreempresa_centrado
   RETIRO DE VALORES - REIMPRESION
SUC: $nombresucursal1
****************************************
RETIRO ID $idret 
----------------------------------------
FECHA RETIRADA    : $ahorta
Retirado por	: $quien
Entregado por   : $cajero1
Monto Retirado  :".formatomoneda($totalret)."
----------------------------------------


Firma Entregado        Firma Recibido

Reimpreso el $hoy
$obs
----------------------------------------
";
    } else {
        echo 'No pertenece a tu cajero';
        exit;
    }
}
if ($tipo == 2) {

    $buscar = "
SELECT *,
(select usuario from usuarios where idusu = caja_reposiciones.cajero) as cajero,
(select usuario from usuarios where idusu = caja_reposiciones.entregado_por) as quienllevo,
(select sucursales.nombre from sucursales where idsucu = caja_reposiciones.idsucursal) as sucursal
FROM caja_reposiciones
where
regserialentrega=$regser and cajero=$idusu
";
    $rsfr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $td = $rsfr->RecordCount();
    if ($td > 0) {
        $idret = intval($rsfr->fields['regserialentrega']);
        $totalret = intval($rsfr->fields['monto_retirado']);
        $quien = $rsfr->fields['quienllevo'];

        $obs = $rsfr->fields['obs'];
        $retirado = 1;

        $nombreempresa_centrado = corta_nombreempresa($nombreempresa);
        $ahorta = date("d-m-Y H:i:s", strtotime($rsfr->fields['fecha_reposicion']));
        $cajero1 = strtoupper($rsfr->fields['cajero']);
        $totalret = floatval($rsfr->fields['monto_recibido']);
        $nombresucursal1 = $rsfr->fields['sucursal'];
        $hoy = date("d/m/Y H:i:s");
        $texto = "
****************************************
$nombreempresa_centrado
   RECEPCION DE VALORES - REIMPRESION
SUC: $nombresucursal1
****************************************
RECEPCION ID $idret 
----------------------------------------
Recepcionado el  : $ahorta
Recibido por	: $cajero1
Entregado por   : $quien
Monto Recibido  :".formatomoneda($totalret)."
----------------------------------------


Firma Entregado        Firma Recibido

Reimpreso el $hoy
$obs
----------------------------------------
";
    } else {
        echo 'No pertenece a tu cajero';
        exit;
    }
}
//echo $texto;exit;

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

if ($re == 0) {
    $url1 = 'gest_administrar_caja.php';
} else {
    $url1 = 'gest_administrar_caja_new.php';
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Reimpresion de documentos</title>
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript">

function imprime_cliente(){
	// impresor app
	if(!(typeof ApiChannel === 'undefined')){
		$("#impresion_box").html("Enviando Impresion (app)...");
		ApiChannel.postMessage('<?php
        // lista de post a enviar
        if ($metodo_app == 'POST_URL') {
            $lista_post = [
                'tk' => $texto,
                'tk_json' => $ticket_json
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
	// impresor normal
	if((typeof ApiChannel === 'undefined')){
		var texto = document.getElementById("texto").value;
		//alert(texto);
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
							<?php if ($re == 0) { ?>
								document.location.href='<?php echo $url1; ?>';
							<?php } else { ?>
								document.location.href='<?php echo $url1; ?>';
							<?php }  ?>
						}else{
							$("#impresion_box").html(response);	
						}

				}
		});

	}
	
}
function volver_caja(){
	//document.location.href='caja_reimprime.php';
	window.close();
}	
</script>
</head>
<body bgcolor="#CCCCCC" onLoad="imprime_cliente()">
<div style="width:320px; border:1px solid; margin:0px auto; background-color:#FFFFFF; padding:5px; text-align:center; min-height:50px;" id="impresion_box">
<p align="center"><input type="button" value="imprimir" style="padding:10px;" onmouseup="imprime_cliente();"></p>
</div><br />
<div style="width:320px; border:1px solid; margin:0px auto; background-color:#FFFFFF; padding:5px; text-align:center; min-height:50px;" >
<p align="center"><input type="button" value="Cerrar" style="padding:10px;" onmouseup="volver_caja();"></p>
</div><br />
<div style="width:320px; border:1px solid; margin:0px auto; background-color:#FFFFFF; padding:5px;">
<textarea readonly id="texto" style="display:; width:315px; height:300px;"><?php echo $texto; ?></textarea>
<pre>
<?php //echo $texto;?>
</pre>
</div>

</body>
</html>