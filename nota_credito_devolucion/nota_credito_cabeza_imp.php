<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "232";
$dirsup = 'S';
require_once("../includes/rsusuario.php");

$buscar = "Select auto_impresor,max_items_factura from preferencias";
$rspp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$auto = trim($rspp->fields['auto_impresor']);
$max_items_factura = intval($rspp->fields['max_items_factura']);

if ($auto == 'S') {
    // preferencias
    $script_nota_cliente = "http://localhost/impresorweb/ladoclientenota_mas.php";
} else {
    $script_nota_cliente = "http://localhost/impresorweb/ladoclientenota_preimpresa.php";

}
$idnotacred = intval($_GET['id']);
if ($idnotacred == 0) {
    header("location: nota_credito_cabeza.php");
    exit;
}
$consulta = "
select *,
(select usuario from usuarios where nota_credito_cabeza.registrado_por = usuarios.idusu) as registrado_por,
(select descripcion from nota_cred_motivos_cli where nota_cred_motivos_cli.idmotivo = nota_credito_cabeza.idmotivo) as motivo,
(select sucursales.nombre from sucursales where sucursales.idsucu = nota_credito_cabeza.idsucursal) as sucursal
from nota_credito_cabeza 
where 
 estado = 3 
 and nota_credito_cabeza.idnotacred = $idnotacred
order by idnotacred asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idnotacred = intval($rs->fields['idnotacred']);
$notacredito_numero = $rs->fields['numero'];
$fecha_nota = $rs->fields['fecha_nota'];
$ruc_notacred = $rs->fields['ruc'];
$idcliente_notacred = $rs->fields['idcliente'];
if ($idnotacred == 0) {
    header("location: nota_credito_cabeza.php");
    exit;
}


function nc_noauto($idnotanum)
{

    global $conexion;
    global $ahora;
    global $idempresa;
    //Cabecera
    $idnc = intval($idnotanum);
    $buscar = "Select  max_items_factura from preferencias";
    $rspp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $max_items_factura = intval($rspp->fields['max_items_factura']);

    $buscar = "
	select *,(select direccion from cliente where idcliente=nota_credito_cabeza.idcliente) as direccion,
	(select sum(subtotal) as total from nota_credito_cuerpo where nota_credito_cuerpo.idnotacred=nota_credito_cabeza.idnotacred) as tnc
	from nota_credito_cabeza
	where 
	idnotacred=$idnc
	and nota_credito_cabeza.estado <> 6 
	limit 1
	";
    $rsni = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $fechanota = date("Y-m-d", strtotime($rsni->fields['fecha_nota']));
    $direccion = trim($rsni->fields['direccion']);
    $total_factura = floatval($rsni->fields['tnc']);

    $idtandatimbrado = intval($rsni->fields['idtandatimbrado']);
    $razon_social = trim($rsni->fields['razon_social']);
    $ruc = trim($rsni->fields['ruc']);
    $notanum = trim($rsni->fields['numero']);
    if ($notanum == '') {
        echo "NC no generada";
        exit;
    }
    $fechahora = date("d/m/Y H:i", strtotime($rsv->fields['fecha']));
    $idsucursal = intval($rsv->fields['idsucursal']);
    // numeros a letras
    require_once("../includes/num2letra.php");

    //$total_nc=$rsv->fields['total_venta']-$descuento; // incluido el descuento
    $total_nc_txt = strtoupper(num2letras(floatval($total_factura)));


    $consulta = "
	Select nota_credito_cuerpo.descripcion as producto,nota_credito_cuerpo.cantidad,nota_credito_cuerpo.precio,nota_credito_cuerpo.subtotal,nota_credito_cuerpo.factura,nota_credito_cuerpo.iva_porc,
	productos.barcode
	from nota_credito_cuerpo
	inner join productos on productos.idprod_serial = nota_credito_cuerpo.codproducto
	where 
	idnotacred=$idnc
	";
    $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $totaldetalles = $rsdet->RecordCount();

    $buscar = "Select distinct factura from nota_credito_cuerpo
	where idnotacred=$idnc";
    $rsfac = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $tfactura = $rsfac->RecordCount();
    $muestracabe = 0;
    $caberef = '';
    if ($tfactura == 1) {
        $muestracabe = 1;
        $caberef = $rsfac->fields['factura'];
    }
    //echo $caberef;exit;
    // tipos de iva de la factura actual
    $consulta = "
	select  iva_porc, sum(subtotal) as subtotal_poriva, (sum(subtotal)-(sum(subtotal)/(1+iva_porc/100))) as subtotal_monto_iva,
	0 as descneto10,
	0 as descnetoiva10
	from nota_credito_cuerpo 
	where 
	idnotacred = $idnc
	group by iva_porc 
	order by iva_porc desc
	";
    //echo $consulta;
    $rsivaporc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



    while (!$rsdet->EOF) {
        if ($muestracabe == 1) {
            $fcu = "";
        } else {

            $fcu = trim($rsdet->fields['factura']);
        }
        $iva_porc = floatval($rsdet->fields['iva_porc']);
        //echo $iva_porc;exit;
        if ($rsdet->fields['iva_porc'] != 0) {
            $ivamonto = $rsdet->fields['subtotal'] / $rsdet->fields['iva_porc'];
        } else {
            $ivamonto = 0;
        }
        $factura_det[] = [
            'aplica_factura' => $fcu,
            'cantidad' => $rsdet->fields['cantidad'],
            'descripcion' => limpiar_txt_fac(trim($rsdet->fields['producto'])),
            'precio_unitario' => $rsdet->fields['precio'],
            'subtotal' => $rsdet->fields['subtotal'], //
            'iva_monto' => $ivamonto,
            'iva_porc' => $rsdet->fields['iva_porc'],
            'codigo_barras' => trim($rsdet->fields['barcode']),
            'codigo_producto' => $rsdet->fields['idprod_serial']

        ];
        $rsdet->MoveNext();

    }







    //while por cada tipo de iva y totaliza metiendo en un array
    while (!$rsivaporc->EOF) {
        $factura_det_impuesto[] = [
            'subtotal_poriva' => $rsivaporc->fields['subtotal_poriva'] - $rsivaporc->fields['descneto10'],
            'iva_monto_total' => $rsivaporc->fields['subtotal_monto_iva'] - $rsivaporc->fields['descnetoiva10'],
            'iva_porc_total' => $rsivaporc->fields['iva_porc']
        ];
        $rsivaporc->MoveNext();
    }



    $factura_cab = [
        'ref' => $caberef,
        'idnotacred' => $idnc,
        'ruc' => $ruc,
        'razon_social' => limpiar_txt_fac(trim($razon_social)),
        'direccion' => limpiar_txt_fac(trim($direccion)),
        'telefono' => trim($rsv->fields['telefono']),
        'notanum' => $notanum,
        'fecha_emision' => $fechanota, // 2019-05-17 opcional
        'total_nota' => $total_factura,
        'total_nota_txt' => $total_nc_txt,
        'max_items_nota' => $max_items_factura, // cantidad maxima de items que entran en el cuerpo de la factura
        'total_detalles' => $totaldetalles,
        'detalle_factura' => $factura_det,
        'detalle_impuesto' => $factura_det_impuesto

    ];
    //print_r($factura_cab);exit;

    // convierte a formato json
    $factura_json = json_encode($factura_cab, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

    return $factura_json;



}


function nc_autoimpresor($idnc)
{
    global $conexion;
    global $ahora;
    global $idempresa;
    global $saltolinea;



    $consulta = "
	select * from empresas where idempresa = $idempresa
	";
    $rsemp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $razon_social_empresa = trim($rsemp->fields['razon_social']);
    $ruc_empresa = trim($rsemp->fields['ruc']).'-'.trim($rsemp->fields['dv']);
    $direccion_empresa = trim($rsemp->fields['direccion']);
    $nombrefanta = trim($rsemp->fields['empresa']);



    $buscar = "
	select *,(select sum(subtotal) as total from nota_credito_cuerpo where nota_credito_cuerpo.idnotacred=nota_credito_cabeza.idnotacred) as tnc
	from nota_credito_cabeza
	
	where 

	idnotacred=$idnc
	and nota_credito_cabeza.estado <> 6 
	limit 1
	";
    $rsni = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $fechanota = date("d/m/Y", strtotime($rsni->fields['fecha_nota']));

    $total_factura = floatval($rsni->fields['tnc']);
    $idtandatimbrado = intval($rsni->fields['idtandatimbrado']);
    $razon_social = trim($rsni->fields['razon_social']);
    $ruc = trim($rsni->fields['ruc']);
    $notanum = trim($rsni->fields['numero']);
    if ($notanum == '') {
        echo "NC no generada";
        exit;
    }
    $fechahora = date("d/m/Y H:i", strtotime($rsv->fields['fecha']));
    $idsucursal = intval($rsv->fields['idsucursal']);
    // numeros a letras
    require_once("../includes/num2letra.php");


    //$total_nc=$rsv->fields['total_venta']-$descuento; // incluido el descuento
    $total_nc_txt = strtoupper(num2letras(floatval($total_factura)));

    // solo sucursal que no es casa matriz por eso idsucu > 0
    $consulta = "
	SELECT * 
	FROM sucursales 
	where 
	idempresa = $idempresa 
	and idsucu = $idsucursal
	and idsucu > 1
	";
    //echo $consulta;
    $rssuc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    if ($idtandatimbrado > 0) {
        //$ahorad=date("Y-m-d",strtotime($ahora));

        /*
        $buscar="Select * from tmp_timbrado where idtandatimbrado=$idtandatimbrado
        and hasta >= '$ahorad'
        and desde <= '$ahorad'
        ";
        $rstimbrado=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));


        $timbrado=trim($rstimbrado->fields['numero']);
        $valido_desde=date("d/m/Y",strtotime($rstimbrado->fields['desde']));
        $valido_hasta=date("d/m/Y",strtotime($rstimbrado->fields['hasta']));
        $idtanda=intval($rstimbrado->fields['idtandatimbrado']);
        if($idtanda == 0){
            echo "Timbrado vencido o inexistente.";
            exit;
        }*/
        $timbrado = intval($rsni->fields['timbrado']);
        $valido_desde = date("d/m/Y", strtotime($rsni->fields['timb_valido_desde']));
        $valido_hasta = date("d/m/Y", strtotime($rsni->fields['timb_valido_hasta']));

    } else {

        echo "No hay timbrado activo.";
        exit;
    }

    // busca si hay productos con iva multiple
    $consulta = "
	select idnotacreddetimp 
	from nota_credito_cuerpo_impuesto 
	where 
	idnotacred = $idnc
	and idtipoiva in (
		select idtipoiva
		from 
			(
				SELECT tipo_iva.idtipoiva, count(*) as total
				FROM tipo_iva 
				inner join tipo_iva_detalle on tipo_iva_detalle.idtipoiva = tipo_iva.idtipoiva
				group by tipo_iva.idtipoiva
			) ivas
		where 
		total > 1
	)
	limit 1
	";
    //echo $consulta;exit;
    $rsexmult = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsexmult->fields['idnotacreddetimp']) > 0) {
        $iva_multiple = "S";
    } else {
        $iva_multiple = "N";
    }
    //$iva_multiple="S";

    $consulta = "
	Select descripcion as producto,cantidad,precio,subtotal, iva_porc, registro as idnotacreddet
	from 
	nota_credito_cuerpo
	where 
	idnotacred=$idnc
	
	";
    $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $descuento = 0;
    // tipos de iva de la factura actual
    if ($iva_multiple == 'N') {
        $consulta = "
		select  iva_porc as iva_porc, sum(subtotal) as subtotal_poriva, (sum(subtotal)-(sum(subtotal)/(1+iva_porc/100))) as subtotal_monto_iva,
		CASE WHEN
			iva_porc = 10
		THEN
			$descuento
		ELSE
			0
		END as descneto10,
		CASE WHEN
			iva_porc = 10
		THEN
			$descuento/11
		ELSE
			0
		END as descnetoiva10
		from nota_credito_cuerpo 
		where 
		idnotacred = $idnc
		group by iva_porc 
		order by iva_porc desc
		";
        //echo $consulta;
        $rsivaporc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    } else {

        $consulta = "
		select iva_porc_col as iva_porc, sum(monto_col) as subtotal_poriva, sum(ivaml) as subtotal_monto_iva
		from nota_credito_cuerpo_impuesto 
		where 
		idnotacred = $idnc
		group by iva_porc_col
		order by iva_porc_col desc
		";
        //echo $consulta;
        $rsivaporc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }



    // conversiones

    $factura = "";
    $factura .= $saltolinea.$saltolinea;
    $factura .= texto_tk(trim($nombrefanta), 40, 'S').$saltolinea;
    $factura .= texto_tk(trim($razon_social_empresa), 40, 'S').$saltolinea;
    $factura .= texto_tk("RUC: ".trim($ruc_empresa), 40, 'S').$saltolinea;
    $factura .= 'C Matriz: '.trim($direccion_empresa).$saltolinea;
    if ($rssuc->fields['idsucu'] > 0) {
        $factura .= 'Sucursal: '.trim($rssuc->fields['nombre']).$saltolinea;
        $factura .= trim($rssuc->fields['direccion']).$saltolinea;
    }
    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= "TIMBRADO         : ".$timbrado.$saltolinea;
    $factura .= "Inicio Vigencia  : ".$valido_desde.$saltolinea;
    $factura .= "Fin Vigencia     : ".$valido_hasta.$saltolinea;
    $factura .= 'Nro: '.$notanum.' - Fecha: '.$fechanota.$saltolinea;
    $factura .= texto_tk("NOTA DE CREDITO", 40, 'S').$saltolinea;
    $factura .= texto_tk("IVA INCLUIDO", 40, 'S').$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'CI / RUC : '.$ruc.$saltolinea;
    $factura .= 'Cliente  : '.$razon_social.$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'Cant    Descripcion'.$saltolinea;
    if ($iva_multiple == 'S') {
        $factura .= 'P.U.              P.T.                  '.$saltolinea;
        $factura .= 'Valores discriminados por impuesto      '.$saltolinea;
    } else {
        $factura .= 'P.U.              P.T.             Tasa%'.$saltolinea;
    }
    $factura .= '----------------------------------------'.$saltolinea;
    while (!$rsdet->EOF) {


        $factura .= agregaespacio(formatomoneda($rsdet->fields['cantidad'], 4, 'N'), 8).agregaespacio($rsdet->fields['producto'], 32).$saltolinea;


        if ($iva_multiple == 'S') {
            $factura .= agregaespacio(formatomoneda($rsdet->fields['precio'], 4, 'N'), 18).agregaespacio(formatomoneda($rsdet->fields['subtotal'], 4, 'N'), 17).agregaespacio('', 5).$saltolinea;
        } else {

            $factura .= agregaespacio(formatomoneda($rsdet->fields['precio'], 4, 'N'), 18).agregaespacio(formatomoneda($rsdet->fields['subtotal'], 4, 'N'), 17).agregaespacio(formatomoneda($rsdet->fields['iva_porc'], 4, 'N'), 5).$saltolinea;
        }

        if ($iva_multiple == 'S') {

            // discriminar tasa
            $idnotacreddet = $rsdet->fields['idnotacreddet'];
            $consulta = "
			select idproducto, 	iva_porc_col, sum(monto_col) as monto_col 
			from nota_credito_cuerpo_impuesto
			where
			idnotacreddet in (
				select idnotacreddet 
				from nota_credito_cuerpo
				where 
				idnotacred = $idnc
				and idnotacreddet = $idnotacreddet

				)
			group by idproducto, iva_porc_col
			";
            //echo $consulta;exit;
            $rsdetimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            while (!$rsdetimp->EOF) {
                $iva_porc_col = floatval($rsdetimp->fields['iva_porc_col']);
                if ($iva_porc_col == 0) {
                    $nombre_col = "Exenta";
                } else {
                    $nombre_col = 'Grav. '.$iva_porc_col.'%';
                }
                $factura .= agregaespacio(' -'.$nombre_col, 11).' : '.agregaespacio(formatomoneda($rsdetimp->fields['monto_col'], 0, 'N'), 18).$saltolinea;
                /*$factura_det_cols[]=array(
                    'iva_porc_col' => floatval($rsdetimp->fields['iva_porc_col']),
                    'monto_col' => $rsdetimp->fields['monto_col'],
                );*/
                $rsdetimp->MoveNext();
            }

        } // if($iva_multiple == 'S'){


        $rsdet->MoveNext();
    }

    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'Total  GS: '.formatomoneda($total_factura, 4, 'N').$saltolinea;
    $factura .= $total_nc_txt.$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;
    while (!$rsivaporc->EOF) {
        if ($rsivaporc->fields['iva_porc'] > 0) {
            $factura .= 'Total Grav. '.agregaespacio($rsivaporc->fields['iva_porc'].'%', 3).' : '.formatomoneda($rsivaporc->fields['subtotal_poriva'] - $rsivaporc->fields['descneto10'], 0, 'N').$saltolinea;
        } else {
            $factura .= 'Total Excenta   : '.formatomoneda($rsivaporc->fields['subtotal_poriva'], 0, 'N').$saltolinea;
        }
        $rsivaporc->MoveNext();
    }
    $rsivaporc->MoveFirst();
    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'Liquidacion del I.V.A.'.$saltolinea;
    while (!$rsivaporc->EOF) {
        if ($rsivaporc->fields['iva_porc'] > 0) {
            $subtotal_monto_iva_acum += $rsivaporc->fields['subtotal_monto_iva'] - $rsivaporc->fields['descnetoiva10'];
            $factura .= ''.agregaespacio($rsivaporc->fields['iva_porc'].'%', 3).' : '.formatomoneda($rsivaporc->fields['subtotal_monto_iva'] - $rsivaporc->fields['descnetoiva10'], 0, 'N').$saltolinea;
        }
        $rsivaporc->MoveNext();
    }
    //$factura.='Total I.V.A. : '.formatomoneda($total_factura/11,0,'N').$saltolinea;
    $factura .= 'Total I.V.A. : '.formatomoneda($subtotal_monto_iva_acum, 0, 'N').$saltolinea;
    $factura .= '----------------------------------------'.$saltolinea;
    $factura .= 'Original: Cliente'.$saltolinea;
    $factura .= 'Duplicado: Archivo Tributario'.$saltolinea;
    $factura .= $saltolinea.$saltolinea.$saltolinea;

    return $factura;

}
if ($auto == 'S') {
    $nota_auto = nc_autoimpresor($idnotacred);
} else {

    $nota_auto = nc_noauto($idnotacred);

}

?><!DOCTYPE html>
<html lang="en">
  <head>
	<title>Impimir Nota de Credito</title>
<script src="vendors/jquery/dist/jquery.min.js"></script>
<script>
<?php if ($auto == 'S') { ?>
function imprimir_nota_auto(){
	
	var texto = $("#texto_nota").val();
	//alert(texto);
	var parametros = {
			"tk" : texto,
			'fac': 'S'
	};
	$.ajax({
			data:  parametros,
			url:   '<?php echo $script_nota_cliente; ?>',
			type:  'post',
			dataType: 'html',
			beforeSend: function () {
					$("#impresion_box").html("Enviando Impresion...");
			},
			crossDomain: true,
			success:  function (response) {
					//$("#impresion_box").html(response);	
					//si impresion es correcta marcar
					//var str = response;
					//var res = str.substr(0, 18);
					//alert(res);
						$("#impresion_box").html(response);	
					
						document.location.href='nota_credito_cabeza_det.php?id=<?php echo $idnotacred; ?>';
					
					// si no es correcta avisar para entrar al modulo de reimpresiones donde se pone la ultima impresion correcta y desde ahi se marca como no impreso todas las que le siguen
					
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
<?php } else { ?>
function imprimir_nc_noauto(){
			
	 var parametros = {
			
			"factura_json"    : '<?php echo $nota_auto; ?>'
        };

       $.ajax({
                data:  parametros,
                url:  '<?php echo $script_nota_cliente ?>',
                type:  'post',
                beforeSend: function () {
                      
                },
                success:  function (response) {
					$("#imprimirres").html(response);
					document.location.href='nota_credito_cabeza_det.php?id=<?php echo $idnotacred; ?>';	
                }
        });		
	
}
<?php } ?>
</script>
  </head>
<?php
if ($auto == 'S') {
    $onlo = " imprimir_nota_auto(); ";
} else {
    $onlo = " imprimir_nc_noauto(); ";

}


?>
  <body onLoad="<?php echo $onlo ?>" style="text-align:center;">
<div id="imprimirres">N &deg; </div>
<div id="impresion_box"></div>
<textarea name="texto_nota" id="texto_nota" style="display:;" cols="42" rows="40"><?php echo $nota_auto; ?></textarea>
  </body>
</html>
