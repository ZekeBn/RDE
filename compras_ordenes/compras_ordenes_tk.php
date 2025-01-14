<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "53";
require_once("../includes/rsusuario.php");

$idordencompra = intval($_GET['id']);
if ($idordencompra == 0) {
    echo "No se envio el numero de orden.";
    exit;
}

// script viejo: teso_orden_compras.php
function ticket_orden_compra($idordencompra)
{
    global $conexion;
    global $saltolinea;
    global $ahora;

    $idordencompra = intval($idordencompra);
    $consulta = "
	Select fecha,ocnum,usuario,compras_ordenes.tipocompra,nombre,fecha_entrega,compras_ordenes.estado,cant_dias,inicia_pago,forma_pago,
	compras_ordenes.idproveedor,compras_ordenes.registrado_el,
	CASE WHEN
		compras_ordenes.tipocompra = 2
	THEN
		'CREDITO'
	ELSE
		'CONTADO'
	END as condicion
	from compras_ordenes 
	inner join proveedores on proveedores.idproveedor=compras_ordenes.idproveedor 
	inner join usuarios on usuarios.idusu=compras_ordenes.generado_por 
	where
	ocnum=$idordencompra
	";
    //echo $buscar;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= '           ORDEN DE COMPRA              '.$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'ORDEN N       : '.$idordencompra.$saltolinea;
    $factura .= 'FECHA ENTREGA : '.date("d/m/Y", strtotime($rs->fields['fecha_entrega'])).$saltolinea;
    $factura .= 'CONDICION     : '.$rs->fields['condicion'].$saltolinea;
    $factura .= 'PROVEEDOR     : '.$rs->fields['nombre'].$saltolinea;
    $factura .= 'COD PROVEEDOR : '.$rs->fields['idproveedor'].$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;

    // detalle
    $consulta = "
	select *, 
	(
	select barcode 
	from productos 
	inner join insumos_lista on insumos_lista.idproducto = productos.idprod_serial 
	where 
	insumos_lista.idinsumo =  compras_ordenes_detalles.idprod
	) as codbar
	from compras_ordenes_detalles 
	where 
	ocnum=$idordencompra
	order by descripcion asc
	";
    $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $factura .= 'CODIGO    PRODUCTO                      '.$saltolinea;
    $factura .= 'CANTIDAD  P.UNITARIO  SUBTOTAL          '.$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;
    while (!$rsdet->EOF) {
        $subtotal = $rsdet->fields['precio_compra'] * $rsdet->fields['cantidad'];
        $subtotal_acum += $subtotal;
        $factura .= agregaespacio(formatomoneda($rsdet->fields['idprod'], 4, 'N'), 8).agregaespacio($rsdet->fields['descripcion'], 32).$saltolinea;
        $factura .= agregaespacio(formatomoneda($rsdet->fields['cantidad'], 4, 'N'), 10).agregaespacio(formatomoneda($rsdet->fields['precio_compra'], 4, 'N'), 12).agregaespacio(formatomoneda($subtotal, 4, 'N'), 18).$saltolinea;

        $rsdet->MoveNext();
    }

    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'TOTAL ORDEN COMPRA : '.formatomoneda($subtotal_acum).$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'REGISTRADO POR     : '.$rs->fields['usuario'].$saltolinea;
    $factura .= 'REGISTRADO EL      : '.date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el'])).$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'IMPRESO EL: '.date("d/m/Y H:i:s", strtotime($ahora)).$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;



    return $factura;

}

$consulta = "
select *,
(select usuario from usuarios where compras_ordenes.registrado_por = usuarios.idusu) as registrado_por,
(select usuario from usuarios where compras_ordenes.generado_por = usuarios.idusu) as generado_por,
(select usuario from usuarios where compras_ordenes.borrado_por = usuarios.idusu) as borrado_por,
(select nombre from proveedores where idproveedor = compras_ordenes.idproveedor ) as proveedor
from compras_ordenes 
where 
 estado = 1 
 and ocnum = $idordencompra
order by ocnum asc
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$texto = ticket_orden_compra($idordencompra);

// trae la primera impresora
$consulta = "
SELECT * FROM 
impresoratk 
where 
idsucursal = $idsucursal 
and borrado = 'N' 
and tipo_impresora='CAJ' 
order by idimpresoratk  asc
limit 1
";
$rsimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$pie_pagina = $rsimp->fields['pie_pagina'];
$metodo_app = $rsimp->fields['metodo_app'];
$defaultprnt = "http://localhost/impresorweb/ladocliente.php";
$script_impresora = trim($rsimp->fields['script']);
if (trim($script_impresora) == '') {
    $script_impresora = $defaultprnt;
}

$url1 = 'compras_ordenes.php';
?><!DOCTYPE html>
<html lang="en">
  <head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script src="js/jquery-1.10.2.min.js"></script>
<script>

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
	if((typeof ApiChannel === 'undefined')){
		var texto = document.getElementById("texto").value;
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
							//marca_impreso('<?php echo $id; ?>');
							//document.body.innerHTML = "Impresion Enviada!";
							//$('#reimprimebox',window.parent.document).html('');
							document.location.href='<?php echo $url1; ?>';
						}else{
							$("#impresion_box").html(response);	
							document.location.href='<?php echo $url1; ?>';
						}

						// si no es correcta avisar para entrar al modulo de reimpresiones donde se pone la ultima impresion correcta y desde ahi se marca como no impreso todas las que le siguen

				}
		});
	}
	
}		

</script>
  </head>
  <body class="nav-md">
	<textarea name="texto" id="texto" style="display: none"><?php echo $texto; ?></textarea>
	<div id="impresion_box">
	
	</div>
<script>
// ejecutar al cargar la pagina
$( document ).ready(function() {
	 imprime_cliente();
});
</script>
  </body>
</html>