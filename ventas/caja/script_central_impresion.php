<?php
//---------------------------------------------NOTAS-------------------------------------------------------------
// Script centralizado para las impresiones de factura y ticketes respectivamente, habilitar desde el 22-02-2019
//---------------------------------------------------------------------------------------------------------------
require_once("../../includes/conexion.php");
require_once("../../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
$dirsup_sec = "S";
require_once("../../includes/rsusuario.php");
//Preferencias

require_once("../../includes/funciones_cocina.php");



$buscar = "Select * from preferencias where idempresa=$idempresa";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$contado_txt = trim($rspref->fields['contado_txt']);
$credito_txt = trim($rspref->fields['credito_txt']);
$factura_pred = trim($rspref->fields['factura_pred']);
$autoimpresor = trim($rspref->fields['autoimpresor']);
$forzar_agrupacion = trim($rspref->fields['forzar_agrupacion']);
$anteponer_moneda_fact = trim($rspref->fields['anteponer_moneda_fact']);
$ticket_fox = trim($rspref->fields['ticket_fox']);
$comanda_o_tk = trim($rspref->fields['comanda_o_tk']);
if ($forzar_agrupacion == '') {
    $forzar_agrupacion = "N";
}
if ($factura_pred == '') {
    $factura_pred = "N";
}
if ($autoimpresor == '') {
    $autoimpresor = "N";
}
if ($anteponer_moneda_fact == '') {
    $anteponer_moneda_fact = "N";
}
$consulta = "
select imprime_ticket_venta, usa_tk_prod, credito_enlaser from preferencias_caja limit 1
";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$imprime_ticket_venta = trim($rsprefcaj->fields['imprime_ticket_venta']);
$usa_tk_prod = trim($rsprefcaj->fields['usa_tk_prod']);
$credito_enlaser = trim($rsprefcaj->fields['credito_enlaser']);

// sucursales preimpresas
$consulta = "
select preimpreso_forzar from sucursales where idsucu = $idsucursal limit 1
";
$rssucauto = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if ($rssucauto->fields['preimpreso_forzar'] == 'S') {
    $autoimpresor = 'N';
}


$moduloventa = intval($_REQUEST['modventa']);
$modulomesa = intval($_REQUEST['mm']);
$agrupar_articulos = trim($rspref->fields['agrupar_items_factura']);//Indica si se imprimen las cantidades y descripciones de la venta en la factura
$describe_factura = trim(strtoupper($rspref->fields['describe_factura']))	;//Indica el texto x defecto que se va usar si esta en uso agrupar facturas
$maximo_items = intval($rspref->fields['max_items_factura']); //Idica cuantos articulos caben en la factura. Por mas que agrupar facturas sea si, se debe completar con la cantidad excta para rellenar los vacios (lineas)
$max_items_factura = $maximo_items;
$imprime_idvta = trim($rspref->fields['imprimir_idvta']);	//Imprime ek id de la vta en factura
$imprime_idped = trim($rspref->fields['imprimir_idped']);	//Imprime ek id pedido en factura
//si clase es 1, proviene de ventas x caja
$clase = intval($_REQUEST['clase']);
$script_factura_cliente = trim($rspref->fields['script_factura_cliente']);
if ($script_factura_cliente == '') {
    //usamos x defecto
    $script_factura_cliente = "http://localhost/impresorweb/ladoclientefactura.php";
}
//Identificar la ventya a imprimir
if (intval($_GET['v']) > 0) {
    $venta = intval($_GET['v']);
}
if (intval($_GET['vta']) > 0) {
    $venta = intval($_GET['vta']);
}
$idventa = $venta;
// Verificar apertura de caja
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal and tipocaja = 1 order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaja->fields['idcaja']);
$idcaja_compartida = intval($rscaja->fields['idcaja_compartida']);
if ($idcaja_compartida > 0) {
    $idcaja = $idcaja_compartida;
}
$estadocaja = intval($rscaja->fields['estado_caja']);
if ($idcaja == 0) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>" 	;
    exit;
}
if ($estadocaja == 3) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>" 	;
    exit;
}
$reimpresion = intval($_REQUEST['rei']);
$imprimir_ambos = intval($rspref->fields['imprime_ambos']);
//echo $imprimir_ambos;
//exit;
//Reciclamos la variable de tk, el cual proviene de ventas x caja y debe provenir de ventas
$tipoimpre = intval($_REQUEST['tk']);

if ($tipoimpre == 1) {
    //eligir factura
    $facturaimprime = 1;
    if ($imprimir_ambos == 1) {
        $tk = 1;
    } else {
        $tk = 0;
    }
} else {
    //eligio tickete para imprimir
    $facturaimprime = 0;
    $tk = 1;
}
//echo $tk;
//exit;

if ($facturador_electronico == 'S') {
    $consulta = "
	select iddocumentoemitido from documentos_electronicos_emitidos where  idventa = $idventa
	";
    $rsdoc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $iddocumentoemitido = $rsdoc->fields['iddocumentoemitido'];
    $token_mail_electro = md5($iddocumentoemitido.date("YmdHis").rand());
    $_SESSION['token_mail_electro'] = $token_mail_electro;

}


//cabecera
$consulta = "
Select factura,ventas.idventa,recibo,ventas.razon_social,ruchacienda,dv,idpedido,ventas.idcliente as idunicocli,ventas.ruc,
(select telefono from cliente where idcliente = ventas.idcliente) as telefono,
(select direccion from cliente where idcliente = ventas.idcliente) as direccion,
total_cobrado,total_venta,otrosgs,fecha,tipo_venta,descneto,totaliva10,totaliva5,texe,idmesa
from ventas
inner join cliente on cliente.idcliente=ventas.idcliente
where 
ventas.idcaja=$idcaja
and idventa=$venta
and ventas.estado <> 6
";

$rsvv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$finalizo_correcto = $rsvv->fields['finalizo_correcto'];
if ($finalizo_correcto == 'N') {
    $consulta = "
	update ventas set estado = 6 where idventa=$venta and finalizo_correcto = 'N'
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}
$idcliente = intval($rsvv->fields['idunicocli']);
$razon_social = limpia_puso_factura(substr($rsvv->fields['razon_social'], 0, 40));
$ruc = trim($rsvv->fields['ruc']);
if ($ruc == '') {
    $ruc = $rsvv->fields['ruchacienda'].'-'.$rsvv->fields['dv'];
}
$direccion = limpia_puso_factura(substr($rsvv->fields['direccion'], 0, 40));
$telefono = limpia_puso_factura(substr('0'.$rsvv->fields['telefono'], 0, 10));
$fecha = date("d-m-Y", strtotime($rsvv->fields['fecha']));
$tipoventa = intval($rsvv->fields['tipo_venta']);
$factura = trim($rsvv->fields['factura']);
$totalventa = intval($rsvv->fields['total_cobrado']);
$totaldescuento = intval($rsvv->fields['descneto']);
$totaliva10 = intval($rsvv->fields['totaliva10']);
$totaliva5 = intval($rsvv->fields['totaliva5']);
$totalex = intval($rsvv->fields['texe']);
$idpedido = intval($rsvv->fields['idpedido']);
$idped = $idpedido;//para motor de impresion
$idmesa = intval($rsvv->fields['idmesa']);
$idventa = intval($rsvv->fields['idventa']);
if ($idventa == 0) {
    echo "La venta fue anulada.";
    exit;
}
// si la venta a credito se imprime en laser
if ($credito_enlaser == 'S') {
    // si es una venta credito
    if ($tipoventa == 2) {
        header("location: factura_imprime_impresor_vp_pdf.php?vta=".$idventa);
        exit;
    }
}

//Factura
if ($facturaimprime == 1) {
    // toma prioridad de la tabla de timbrado no de preferencias
    $consulta = "
	SELECT idventa, tipoimpreso, idtimbrado 
	FROM facturas
	inner join ventas on ventas.idtandatimbrado = facturas.idtanda 
	where
	idventa = $idventa
	limit 1
	";
    $rstim = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (trim($rstim->fields['tipoimpreso']) == 'AUT') {
        $autoimpresor = "S";
    } else {
        $autoimpresor = "N";
    }

    if ($autoimpresor == 'N') {
        //detalle
        $consulta = "
		Select  
		idprod, 
		sum(cantidad) as cantidad, 
		sum(subtotal) as subtotal,
		pventa, iva,
		(select descripcion from productos where idprod_serial = ventas_detalles.idprod) as producto
		from ventas_detalles 
		where 
		idventa=$venta 
		group by idprod
		order by (select descripcion from productos where idprod_serial = ventas_detalles.idprod) asc
		";

        $rscuerpo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $tdcuerpo = $rscuerpo->RecordCount();
        $totitems = $tdcuerpo;
        $descuento_item = intval($rscuerpo->fields['descuento']);
        // agregados
        $consulta = "
		select sum(precio_adicional) as totalagregado, count(*) as cantagregado
		from ventas_agregados
		where idventadet in
		(
		Select idventadet
		from ventas_detalles 
		where 
		idventa=$venta 
		and idemp=$idempresa
		)
		";
        $rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $totag = intval($rsag->RecordCount());
        $cantagregado = $rsag->fields['cantagregado'];
        if (intval($rsag->fields['totalagregado']) == 0) {
            $totag = 0;
        }
        $delivery_costo = $rsvv->fields['otrosgs'];
        if (intval($delivery_costo) > 0) {
            $totdel = 1;
        } else {
            $totdel = 0;
        }

        if (intval($descuento) > 0) {
            $totdesc = 1;
        } else {
            $totdesc = 0;
        }

        // Contado o Credito
        if ($rsvv->fields['tipo_venta'] == 1) {
            $contado = "X";
            $credito = "";
        } else {
            $contado = "";
            $credito = "X";
        }


        //con todos los datos insertamos en el localhost
        //armar cuerpo
        if ($agrupar_articulos == 'N') {
            $arraycuerpo = '';
            while (!$rscuerpo->EOF) {
                $cantidad = floatval($rscuerpo->fields['cantidad']);
                $descripcion = limpia_puso_factura(trim($rscuerpo->fields['producto']));
                $precioventa = floatval($rscuerpo->fields['pventa']);
                $subiva5 = 0;
                $subexenta = 0;
                $subiva10 = floatval($rscuerpo->fields['subtotal']);
                $descuento = 0;



                $concat = $cantidad.'}'.$descripcion.'}'.$precioventa.'}'.$subiva5.'}'.$subiva10.'}'.$descuento_item;
                $arraycuerpo = $arraycuerpo.$concat.'}';


                $rscuerpo->MoveNext();
            }
        } else {
            $cantidad = 0;
            $descripcion = trim($describe_factura);
            $precioventa = floatval($totalventa);
            $subiva5 = 0;
            $subexenta = 0;
            $subiva10 = floatval($totalventa);
            $descuento = 0;



            $concat = $cantidad.'}'.$descripcion.'}'.$precioventa.'}'.$subiva5.'}'.$subiva10.'}'.$descuento_item;
            $arraycuerpo = $arraycuerpo.$concat.'}';


        }
        // agregados
        if (intval($rsag->fields['totalagregado']) > 0) {
            $subiva5 = 0;
            $subiva10 = intval($rsag->fields['totalagregado']);
            $preciounit = round(intval($rsag->fields['totalagregado'] / $cantagregado), 0);
            $descuento_item = 0;
            $arraycuerpo .= $cantagregado.'}'.'AGREGADOS'.'}'.$preciounit.'}'.$subiva5.'}'.$subiva10.'}'.$descuento_item.'}';
        }

        // delivery
        if (intval($delivery_costo) > 0) {
            $subiva5 = 0;
            $subiva10 = intval($delivery_costo);
            $descuento_item = 0;
            $arraycuerpo .= '1}'.'DELIVERY'.'}'.intval($delivery_costo).'}'.$subiva5.'}'.$subiva10.'}'.$descuento_item.'}';
        }


        $razon_social = trim($razon_social);

        $redirbus = "";
        if ($_GET['bus'] == 1) {
            $redirbus = "?bus=1";
        }


        ///////////////////////////// IMPRESOR NUEVO /////////////////////////////////////////
        $factura_json = factura_preimpresa($idventa);
        //echo $factura_json;

        //print_r(json_decode($factura_json,true));
        //exit;
        ///////////////////////////// IMPRESOR NUEVO /////////////////////////////////////////
    } else { // if($autoimpresor == 'N'){

        // auto impresor
        $factura_auto = factura_autoimpresor($idventa);


    }

}
// buscar impresora si pertenece
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
//Tickete
if ($tk == 1) {




    if ($ticket_fox == 'S') {
        $texto_json = ticket_venta_json($idventa);
        $texto = "";
    } else {
        $texto_json = "";
        if ($comanda_o_tk == 'T') {
            $texto = ticket_venta($idventa);
        } else {
            // ticket de cocina para caja
            $impresor_tip = "CAJ";
            if ($idmesa > 0) {
                $impresor_tip = "MES";
            }
            $parametros_array = [
                'idimpresoratk' => $rsimp->fields['idimpresoratk'],
                'idpedido' => $idpedido,
                'idmesa' => $idmesa,
                'v' => $idventa,
                'impresor_tip' => $impresor_tip
            ];
            //print_r($parametros_array);
            $res = comanda_cocina_consolidado($parametros_array);
            $texto = $res['ticket'];


        }
        if ($usa_tk_prod == 'S') {
            // poner aca una preferencia
            $parametros_array = [
                'idimpresoratk' => $rsimp->fields['idimpresoratk'],
                'idpedido' => $idpedido,
                'idmesa' => $idmesa,
                'v' => $idventa,
                'impresor_tip' => $impresor_tip
            ];
            $res = ticket_producto($parametros_array);
            $texto = $res['ticket'];
        }
    }
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
//print_r(json_decode($texto_json,true));
//exit;
?>
<html>
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script src="../../js/jquery-1.10.2.min.js"></script>

<title>Impresiones Centralizadas</title>
<script>
<?php if ($facturador_electronico == 'S') { ?>
function mail_electronico(){
	var parametros = {
			"id"    : '<?php echo $iddocumentoemitido; ?>',
			"token" : '<?php echo $token_mail_electro?>'
	};
   $.ajax({
		data:  parametros,
		url:   'documentos_set_envio_mail.php',
		type:  'post',
		dataType: 'html',
		beforeSend: function () {
			$("#mail_box").html("Enviando Mail...");
		},
		crossDomain: true,
		success:  function (response) {
			$("#mail_box").html(response);	
		}
	});
}
<?php } ?>
function llamaimprime(){
	setTimeout("imprimir_factura()",100);	
}
<?php
    //echo 'aca';exit;
    if ($moduloventa == 0) {
        $url1 = "gest_ventas_resto_caja.php$redirbus";
    }
if ($moduloventa == 1) {
    $url1 = "gest_ventas_resto.php$redirbus";
}
if ($moduloventa == 3) {
    $url1 = "ventas_registradora.php";
}
if ($moduloventa == 4) {
    $idatc = intval($_REQUEST['idatc']);
    $idmesa = intval($_REQUEST['idm']);
    $pr = intval($_REQUEST['pr']);
    if ($pr == 1) {
        $url1 = "mesas/cerrar_mesa.php?idatc=$idatc&idm=$idmesa&pr=1&impre=1";
    } else {
        $url1 = "mesas/ventas_salon.php";
    }
}
if ($moduloventa == 5) {
    $url1 = "venta_pedidos.php";
}
if ($moduloventa == 6) {
    $url1 = "facturar_recurrente.php";
}
if ($moduloventa == 7) {
    $url1 = "central_pedidos.php";
}
if ($moduloventa == 8) {
    $url1 = "cobro_pedidos_rapido.php";
}
if ($moduloventa == 9) {
    $url1 = "surtidor/index.php";
}
if ($moduloventa == 10) {
    $url1 = "ventasm/index.php";
}
if ($moduloventa == 11) {
    $url1 = "self2/index.php";
}
if ($moduloventa == 12) {
    $url1 = "pulseras/pulseras_carga.php";
}
if ($moduloventa == 13) {
    $consulta = "
		select idpulsera from pulseras_transacciones where idventa = $idventa limit 1
		";
    $rspuls = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idpulsera = intval($rspuls->fields['idpulsera']);
    $url1 = "pulseras/pulseras_listo.php?id=".$idpulsera;
}
if ($moduloventa == 14) {
    $url1 = "cortesia/cortesia_diaria.php";
}
if ($moduloventa == 15) {
    $url1 = "cat_adm_pedidos_new.php";
}
if ($moduloventa == 16) {
    $url1 = "facturar_anticipos/facturar_anticipos.php";
}
if ($moduloventa == 17) {
    $url1 = "adherentes_cobranzas.php";
}
if ($moduloventa == 20) {
    $url1 = "wellness/well_facturar.php";
}
?>
<?php if ($tk == 1) { 	?>	
<?php
if ($imprime_ticket_venta == 'S') { ?>

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
						
							marca_impreso('<?php echo $venta; ?>');
							$("#impresion_box").html(response);	
							document.location.href='<?php echo $url1; ?>';
											
						// si no es correcta avisar para entrar al modulo de reimpresiones donde se pone la ultima impresion correcta y desde ahi se marca como no impreso todas las que le siguen
						
                }
        });
	}
	
}	
<?php
} else { // if($imprime_ticket_venta == 'S'){?>
function imprime_cliente(){
	document.location.href='<?php echo $url1; ?>';
}
<?php } // if($imprime_ticket_venta == 'S'){?>
<?php } //  if ($tk==1){?>

function imprimir_factura_auto(){
	// impresor app
	if(!(typeof ApiChannel === 'undefined')){
		$("#impresion_box").html("Enviando Impresion (app)...");
		ApiChannel.postMessage('<?php
        // lista de post a enviar
        if ($metodo_app == 'POST_URL') {
            $lista_post = [
                'tk' => $factura_auto,
                'tk_json' => '' // en ticket usar $ticket_json  // no aplica en factura
            ];
        }
//parametros para la funcion
$parametros_array_tk = [
    'texto_imprime' => $factura_auto, // texto a imprimir
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
		var texto = $("#texto_fac").val();
		var parametros = {
				"tk" : texto,
				'fac': 'S'
		};
		$.ajax({
			data:  parametros,
			url:   '<?php echo $script_factura_cliente; ?>',
			type:  'post',
			dataType: 'html',
			beforeSend: function () {
				$("#impresion_box").html("Enviando Impresion...");
			},
			crossDomain: true,
			success:  function (response) {
				$("#impresion_box").html(response);	
				document.location.href='<?php echo $url1; ?>';

			}
		});
	}
	
}	

function imprimir_factura(){
	
	var idventa=<?php echo $venta?>;
	var idcliente=<?php echo $idcliente ?>;
	var razon=<?php echo "'$razon_social'" ?>;
	var fact=<?php echo "'$factura'" ?>;
	var fechaventa=<?php echo "'$fecha'"  ?>;
	var tipoventa=<?php echo $tipoventa ?>;
	var idpedido=<?php echo $idpedido ?>;	
	var totalventa=<?php echo $totalventa ?>;
	var totaldescuento=<?php echo $totaldescuento ?>;
	var totaliva10=<?php echo $totaliva10?>;
	var totaliva5=<?php echo $totaliva5?>;
	var totalex=<?php echo $totalex ?>;
	var ruc='<?php echo $ruc ?>';
	var dt='<?php echo $arraycuerpo ?>';
   	var direccion='';
	var telefono='';
	var como='<?php echo $script_factura_cliente?>';
	var maxitms=<?php echo $maximo_items?>;
	var impvta='<?php echo $imprime_idvta ?>';
	var imped='<?php echo $imprime_idped ?>';
	
	 var parametros = {
			"idventa"         :idventa,
			"idcliente"       : idcliente,
			"razon"           :razon,
			"fact"            : fact,
			"fechaventa"      : fechaventa,
			"tipoventa"       : tipoventa,
			"totalventa"      : totalventa,
			"totaldescuento"  : totaldescuento,
			"totaliva10"      : totaliva10,
			"totaliva5"       : totaliva5,
			"totalex"         : totalex,
			"dt"              : dt,
		 	"ruc"             : ruc,
		    "idpedido"        : idpedido,
		    "direccion"       : direccion,
		 	"telefono"        : telefono,
		  	"maximoitem"	  : maxitms,
		 	"imprimirvta"	  : impvta,
		 	"imprimirped"	  : imped,
			"factura_json"    : '<?php echo $factura_json; ?>'
        };

       $.ajax({
                data:  parametros,
                url:   como,
                type:  'post',
                beforeSend: function () {
                      //  $("#imprimir").html("<br /><br />Enviando Impresion...<br /><br />");
                },
                success:  function (response) {
					if(IsJsonString(response)){	
											// convierte a objeto
						var obj = jQuery.parseJSON(response);	
						// si es valido	
						if (obj.valido=='S'){
							// redirecciona
							document.location.href='<?php echo $url1; ?>';
						}else{
							$("#imprimir").html(obj.errores);
							alert(obj.errores);
						}
					
					}else{
						if (response=='ok'){
							document.location.href='<?php echo $url1; ?>';
						}else{
							$("#imprimir").html(response);
							alert(response);
						}
						
					}
						
                }
        });		

	
	
}
function marca_impreso(id){
 		var parametros = {
                "id" : id
        };
      $.ajax({
                data:  parametros,
                url:   'impresion_marca.php',
                type:  'post',
                beforeSend: function () {
                        $("#impresion_box").html("Marcando com Impreso...");
                },
                success:  function (response) {
						//$("#impresion_box").html(response);
						//document.location.href='impresor_ticket_caja.php?imp=<?php echo $idimpresoratk; ?>';
                }
        });
}
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
// manejar cookie
function setCookie(cname,cvalue,exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires=" + d.toGMTString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for(var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function checkCookie() {
    var user=getCookie("username");
    if (user != "") {
        alert("Welcome again " + user);
    } else {
       user = prompt("Please enter your name:","");
       if (user != "" && user != null) {
           setCookie("username", user, 30);
       }
    }
}
// manejar cookie
function borra_domicilio(){
	setCookie("dom_deliv",0,0);
}
// ejecutar al cargar la pagina
$( document ).ready(function() {
	borra_domicilio();
<?php

// llamadores de funcion javascript
$imprimeticket = "	imprime_cliente();";
if ($autoimpresor == 'S') {
    $imprimefactura = "	imprimir_factura_auto();";
} else {
    $imprimefactura = "	imprimir_factura();";
}
// factura
if ($facturaimprime == 1) {
    echo $imprimefactura;
}
// ticket
if ($tk == 1) {
    // si es ventas tablet
    if ($moduloventa == 1) {
        // si las preferencias permiten imprimir al finalizar
        if ($rspref->fields['imprime_alfinalizar'] == 'S') {
            echo $imprimeticket;
        } else {
            echo "	document.location.href='".$url1."';";
        }
        // si no es ventas por caja imprime el ticket siempre
    } else {
        echo $imprimeticket;
    }

}

if ($facturador_electronico == 'S') {
    echo "mail_electronico();";
}

?>

});
</script>
</head>
<body>
	<textarea name="texto" id="texto" style="display: none"><?php echo $texto; ?></textarea>
    <textarea name="texto_fac" id="texto_fac" style="display: none"><?php echo $factura_auto; ?></textarea>
    
	<div id="imprimir">
		
		
	</div>
	<div id="impresion_box">
	
	</div>
<?php if ($facturador_electronico == 'S') { ?>
	<div id="mail_box">
	
	</div>
<?php } ?>
	</body>
</html>