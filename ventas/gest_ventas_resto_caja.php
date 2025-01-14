<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

//elimina exigencia de pin
$_SESSION['self'] = '';

//Comprobar apertura de caja
$parametros_caja_new = [
    'idcajero' => $idusu,
    'idsucursal' => $idsucursal,
    'idtipocaja' => 1
];
$res_caja = caja_abierta_new($parametros_caja_new);
$idcaja = intval($res_caja['idcaja']);
if ($idcaja == 0) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja_new.php'/>";
    exit;
}

// activar por sucursal
$consulta = "
select modo_kg, idprod_kg from sucursales where idsucu = idsucursal
";
$rssucv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$modo_kg = $rssucv->fields['modo_kg'];
$idprod_kg = intval($rssucv->fields['idprod_kg']); //=688;

//Traemos las preferencias para la empresa
$buscar = "Select * from preferencias where idempresa=$idempresa ";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Para obligar voucher / numero o cualquier cosa
$obliga_adicional = trim($rspref->fields['obliga_adicional']);
$texto_adicional = trim($rspref->fields['txt_adicional']);
$factura_obliga = trim($rspref->fields['factura_obliga']);
// preferencias caja
$consulta = "
SELECT 
usa_canalventa, usa_clienteprevio, avisar_quedanfac, usa_ventarapida, usa_ventarapidacred, pulsera_vcaja
FROM preferencias_caja 
WHERE  
idempresa = $idempresa 
";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$avisar_quedanfac = trim($rsprefcaj->fields['avisar_quedanfac']);
$usa_canalventa = trim($rsprefcaj->fields['usa_canalventa']);
$usa_clienteprevio = trim($rsprefcaj->fields['usa_clienteprevio']);
$usa_ventarapidacred = trim($rsprefcaj->fields['usa_ventarapidacred']);
$usa_ventarapida = trim($rsprefcaj->fields['usa_ventarapida']);
$pulsera_vcaja = trim($rsprefcaj->fields['pulsera_vcaja']);

// INICIO cambio precio masivo
// busca si hay precios masivos sin aplicar cuya fecha de vigencia ya es actual
$consulta = "
SELECT idcambiopreciomasivo 
FROM cambio_precio_masivo 
where 
estado = 2 
and aplicado_el is null 
and vigencia_desde <= '$ahora'
order by vigencia_desde asc 
";
$rscamb = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcambiopreciomasivo = intval($rscamb->fields['idcambiopreciomasivo']);
// si encuentra
if ($idcambiopreciomasivo > 0) {
    while (!$rscamb->EOF) {
        $idcambiopreciomasivo = intval($rscamb->fields['idcambiopreciomasivo']);
        // cambia precios
        $consulta = "
        update productos_sucursales
        set 
        precio = (select cambio_precio_masivo_det.precio_nuevo from cambio_precio_masivo_det where idproducto = productos_sucursales.idproducto  and cambio_precio_masivo_det.idcambiopreciomasivo = $idcambiopreciomasivo  ) 
        WHERE
        idproducto in (
        select idproducto 
            from cambio_precio_masivo_det 
            inner join cambio_precio_masivo on cambio_precio_masivo.idcambiopreciomasivo = cambio_precio_masivo_det.idcambiopreciomasivo
            where 
            cambio_precio_masivo_det.idcambiopreciomasivo = $idcambiopreciomasivo
            and cambio_precio_masivo.vigencia_desde <= '$ahora'
            and cambio_precio_masivo.estado = 2
        )
        and idsucursal in (
        select idsucursal
        from cambio_precio_masivo_sucursales
        where 
        idcambiopreciomasivo = $idcambiopreciomasivo
        )
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // registra el cambio, no puede estar antes sino por el estado no se aplicaria la consulta de arriba
        $consulta = "
        update cambio_precio_masivo set aplicado_el = '$ahora', estado = 3 where idcambiopreciomasivo = $idcambiopreciomasivo
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $rscamb->MoveNext();
    }
}
// FINAL cambio precio masivo

$script = trim($rspref->fields['script_factura']);
$script_balanza = trim($rspref->fields['scipt_balanza']);
$alerta_ventas = trim($rspref->fields['alerta_ventas']);
$usa_vendedor = trim($rspref->fields['usa_vendedor']);
$obliga_vendedor = trim($rspref->fields['obliga_vendedor']);
if ($script_balanza == '') {
    //direccion x defecto p servidor en cliente
    $script_balanza = 'http://localhost/balanza/balanza_ladocliente.php';
} else {
    $script_balanza = strtolower($script_balanza);
}
$usarbcode = trim($rspref->fields['usabcode']);
$balanza = trim($rspref->fields['usa_balanza']);
//Indica si el peso al ser capturado va hacer click solo
$autopeso = trim($rspref->fields['autopeso']);
$canal = 1; // tablet
if (intval($_GET['canal']) > 0) {
    $_SESSION['canal'] = intval($_GET['canal']); // elije canal
}
if (intval($_SESSION['canal']) > 0) {
    $canal = intval($_SESSION['canal']); // asigna canal
}


// tipo de favoritos
$consulta = "
SELECT tipofavorito, maxprod, maxdias
FROM tipofavorito
where 
idempresa = $idempresa
limit 1
";
$rsfav = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$maxprod = intval($rsfav->fields['maxprod']);
$maxdias = intval($rsfav->fields['maxdias']);




// rellenar favorito si no se relleno hoy
$consulta = "
select actualizado 
from favoritos 
where
idsucursal = $idsucursal 
order by actualizado asc 
limit 1
";
$rsfav2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// si aun no se lleno hoy para esta sucursal
if ($rsfav2->fields['actualizado'] != date("Y-m-d")) {
    // borra los favoritos de la tabla temporal
    $consulta = "
	delete from favoritos 
	where 
	idsucursal = $idsucursal 
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // tipo favorito seleccionados
    if (strtoupper($rsfav->fields['tipofavorito']) == 'V') {
        $fecha_actual_calc = date("Y-m-d");
        $max_dias_mas1 = $maxdias + 1;
        $fecha_pasada_calc = date("Y-m-d", strtotime($fecha_actual."- $max_dias_mas1 days"));
        //date(ventas.fecha) >= date_sub(date(NOW()), INTERVAL $maxdias DAY)
        // lista los productos favoritos
        $consulta = "
		INSERT INTO favoritos (idproducto, idsucursal, idempresa, cantidad_venta, actualizado)
		select ventas_detalles.idprod, $idsucursal, $idempresa, sum(ventas_detalles.cantidad), current_date
		from ventas 
		inner join ventas_detalles on ventas.idventa = ventas_detalles.idventa
		inner join productos p on p.idprod_serial = ventas_detalles.idprod
		inner join productos_sucursales on productos_sucursales.idproducto = p.idprod_serial
		where
		date(ventas.fecha) > '$fecha_pasada_calc'
		and ventas.estado <> 6
		and ventas.sucursal = $idsucursal
		and p.borrado = 'N'
		and productos_sucursales.idsucursal = $idsucursal 
		and productos_sucursales.activo_suc = 1
		group by ventas_detalles.idprod
		order by count(ventas_detalles.idprod) desc, p.descripcion asc
		limit $maxprod
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // busca si luego de rellenar llego a insertar algun registro
        $consulta = "
		select *
		from favoritos 
		where 
		idsucursal = $idsucursal 
		";
        $rsfavrell = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // si no relleno nada rellena cualquier producto para evitar que siga consultando hasta que haya ventas
        if (intval($rsfavrell->fields['idproducto']) == 0) {
            $consulta = "
			INSERT INTO favoritos 
			(idproducto, idsucursal, idempresa, cantidad_venta, actualizado)
			select idprod_serial, $idsucursal, 1, 1, current_date
			from productos 
			where
			borrado = 'N'
			order by idprod_serial asc
			limit $maxprod
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }

    } else {

        // lista los productos favoritos
        /*$consulta="
        INSERT INTO favoritos (idproducto, idsucursal, idempresa, cantidad_venta, actualizado)
        SELECT idprod_serial, $idsucursal, $idempresa, 1, current_date
        from productos
        where
        borrado = 'N'
        and productos.favorito = 'S'
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));*/
        /*
        // rellenar tabla masivamente
        insert IGNORE into favoritos_sucursal (idsucursal, idproducto, favorito, orden)
        select sucursales.idsucu as idsucursal, productos.idprod_serial as idproducto, productos.favorito, 999 as orden
        from productos, sucursales
        */
        $consulta = "
		INSERT INTO favoritos 
		(idproducto, idsucursal, idempresa, cantidad_venta, actualizado)
		SELECT idproducto, idsucursal, 1, orden, current_date 
		FROM favoritos_sucursal
		inner join productos on productos.idprod_serial = favoritos_sucursal.idproducto
		where
		favoritos_sucursal.idsucursal = $idsucursal
		and favoritos_sucursal.favorito = 'S'
		and productos.borrado = 'N'
		order by favoritos_sucursal.orden desc
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }


}




$whereadd = "";
$cat = intval($_GET['cat']);
$busqueda = intval($_GET['bus']);
$order_by = 'combo desc, combinado desc, productos.descripcion asc';
$limite_prodcat = "limit 1000";
if ($busqueda == 0) {
    if ($cat > 0) {
        $whereadd .= " and idcategoria = $cat ";
    } elseif ($_GET['cat'] == 'cv') {
        $whereadd .= "
		and productos_listaprecios.estado = 1 
		
		";
        $limite_prodcat = "limit 500";
    } else {
        if (strtoupper($rsfav->fields['tipofavorito']) == 'F') {
            //$whereadd.=" and favorito = 'S'";
            $joinadd_fav .= "
			inner join favoritos on favoritos.idproducto = productos.idprod_serial and favoritos.idsucursal = $idsucursal
			";
            $whereadd .= "
			and productos.idprod_serial in (
			select idproducto 
			from favoritos 
			where 
			idsucursal = $idsucursal 
			)
			";
            $order_by = ' favoritos.cantidad_venta asc, productos.descripcion asc ';
        } else {
            $joinadd_fav .= "
			inner join favoritos on favoritos.idproducto = productos.idprod_serial and favoritos.idsucursal = $idsucursal
			";
            $whereadd .= "
			and productos.idprod_serial in (
			select idproducto 
			from favoritos 
			where 
			idsucursal = $idsucursal 
			)
			";
            $order_by = ' favoritos.cantidad_venta asc, productos.descripcion asc ';
        }
    }
} else {



}
$t = intval($_GET['t']);
if ($t > 0) {
    $whereadd .= " and idsubcate = $t";
}
$idlistaprecio = intval($_SESSION['idlistaprecio']);
$idcanalventa = intval($_SESSION['idcanalventa']);
$idclienteprevio = intval($_SESSION['idclienteprevio']);
if ($idclienteprevio > 0) {
    $consulta = "
	select idcliente, idvendedor, idcanalventacli
	from cliente 
	where 
	idcliente = $idclienteprevio
	and estado <> 6 
	limit 1
	";
    $rscprev = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idclienteprevio = $rscprev->fields['idcliente'];
    if ($idclienteprevio > 0) {
        $idcliente = $idclienteprevio;
        $idvendedor = intval($rscprev->fields['idvendedor']);
        $idcanalventa = intval($rscprev->fields['idcanalventacli']);
    }
}
if ($idcanalventa > 0) {
    $consulta = "
	select idlistaprecio, idcanalventa, canal_venta 
	from canal_venta 
	where 
	idcanalventa = $idcanalventa 
	and estado = 1
	limit 1
	";
    $rscv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idlistaprecio = intval($rscv->fields['idlistaprecio']);
}
$seladd_lp = " productos_sucursales.precio as p1 ";
if (intval($idlistaprecio) > 0) {
    $joinadd_lp = " inner join productos_listaprecios on productos_listaprecios.idproducto = productos.idprod_serial ";
    $whereadd_lp = "
	and productos_listaprecios.idsucursal = $idsucursal 
	and productos_listaprecios.idlistaprecio = $idlistaprecio 
	and productos_listaprecios.estado = 1
	";
    $seladd_lp = " productos_listaprecios.precio as p1";
}

// productos
$consulta = "
select *, $seladd_lp,
	(
	select 
	sum(cantidad) as total
	from tmp_ventares 
	where 
	tmp_ventares.registrado = 'N'
	and tmp_ventares.usuario = $idusu
	and tmp_ventares.borrado='N'
	and tmp_ventares.finalizado = 'N'
	and tmp_ventares.idproducto = productos.idprod_serial
	and tmp_ventares.idempresa = $idempresa
	and tmp_ventares.idsucursal = $idsucursal
	) as total
from productos 
inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
$joinadd_lp
$joinadd_fav
where
idprod_serial is not null
and productos.idempresa = $idempresa
and productos.borrado = 'N'

and productos_sucursales.idsucursal = $idsucursal 
and productos_sucursales.idempresa = $idempresa
and productos_sucursales.activo_suc = 1

$whereadd_lp

$whereadd

order by $order_by
$limite_prodcat
";
//echo $consulta;exit;
$rsprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



// total productos en carrito
$consulta = "
select count(*) as total
from tmp_ventares 
inner join productos on tmp_ventares.idproducto = productos.idprod_serial
where 
tmp_ventares.registrado = 'N'
and tmp_ventares.usuario = $idusu
and tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'N'
and tmp_ventares.idempresa = $idempresa
and tmp_ventares.idsucursal = $idsucursal
and productos.idempresa = $idempresa
";
//echo $consulta;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$totalprod = intval($rs->fields['total']);

// monto total de productos en carrito
$consulta = "
select sum(precio) as total_monto
from tmp_ventares 
inner join productos on tmp_ventares.idproducto = productos.idprod_serial
where 
tmp_ventares.registrado = 'N'
and tmp_ventares.usuario = $idusu
and tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'N'
and tmp_ventares.idempresa = $idempresa
and tmp_ventares.idsucursal = $idsucursal
and productos.idempresa = $idempresa
";

$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$montototalprod = intval($rs->fields['total_monto']);
// monto total agregados
$consulta = "
SELECT sum(precio_adicional) as montototalagregados, count(idventatmp) as totalagregados
FROM 
tmp_ventares_agregado
where
idventatmp in (
select tmp_ventares.idventatmp
from tmp_ventares 
where 
registrado = 'N'
and tmp_ventares.usuario = $idusu
and tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'N'
and tmp_ventares.idempresa = $idempresa
and tmp_ventares.idsucursal = $idsucursal
)
";
$rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$montototalag = intval($rsag->fields['montototalagregados']);
$montototalag = 0;
// productos mas agregados
$montototal = $montototalprod + $montototalag;
//echo $montototal;





$redirbus = "";
$redirbus2 = "";
if ($_GET['bus'] == 1) {
    $redirbus = "?bus=1";
    $redirbus2 = "&bus=1";
}
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Ventas</title>
<?php require_once("includes/head_ventas.php");?>
<style>
body{
	margin:0px;
	padding:0px;
	font-family:Verdana, Geneva, sans-serif;
	font-size:12px;
}
.vta_contenedor{
	width:1200px; 
	margin:0px auto;
}
.vta_izquierda{
	float:left; width:370px;
	min-height:785px;
	background-color:#FFFFFF;
	border-bottom:1px solid #CCCCCC;
	border-left:1px solid #CCCCCC;
	border-top:1px solid #CCCCCC;
	border-radius: 10px 0px 0px 10px;
	-moz-border-radius: 10px 0px 0px 10px;
	-webkit-border-radius: 10px 0px 0px 10px;
}
.vta_derecha{
	float:left; width:826px; 
	min-height:787px;
	background-color:#FFFFFF;
	border:1px solid #CCCCCC;
	border-radius: 0px 10px 10px 0px;
	-moz-border-radius: 0px 10px 10px 0px;
	-webkit-border-radius: 0px 10px 10px 0px;
}
.divconbordea{
	border:1px solid #CCCCCC;
	border-radius: 30px 30px 0px 0px;
	-moz-border-radius: 30px 30px 0px 0px;
	-webkit-border-radius: 30px 30px 0px 0px;
}
.mensaje{
	border:1px solid #FF0000; 
	background-color:#F8FFCC; 
	width:600px; 
	margin:0px auto; 
	text-align:center;
	border-radius: 10px 10px 10px 10px;
	-moz-border-radius: 10px 10px 10px 10px;
	-webkit-border-radius: 10px 10px 10px 10px;
}
</style>
<script src="js/shortcut.js"></script>

<script>
//PREVENTS ENTER ON BRCODE
$(document).ready(function(){
	$("#codigo").keydown(function(e){
		if(e.which==17 || e.which==74){
			e.preventDefault();
		}else{
			console.log(e.which);
		}
	})
<?php
if ($busqueda == 1) {
    echo '	$("#filtrar").focus();';
} else {
    if ($modo_kg == 'S') {
        echo '	$("#menu_kg").focus();';
    } else {
        if ($usarbcode == 'S' && intval($_GET['bus']) == 0) {
            echo '	$("#bccode").focus()';
        }
    }
}
?>
});	
function grupo_opciones(idproducto,metodo,id_linea){
	$("#modal_ventana").modal('show');
	$("#modal_titulo").html('Seleccionar Opcion');
	var direccionurl = 'grupo_opciones.php';
	var parametros = {
		"id"       : idproducto,
		"metodo"   : metodo,
		"id_linea" : id_linea,
	};
	$.ajax({
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			$("#modal_cuerpo").html("Cargando Opciones...");
		},
		success:  function (response) {
			$("#modal_cuerpo").html(response);
		},
		error: function(jqXHR, textStatus, errorThrown) {
			errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
		}
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
	});
}
function carro_add_grupo(idproducto){
	$('#modal_ventana').modal('hide');
	apretar2(idproducto,0,0);
}
function carro_add_grupo_lista(idproducto,id_linea){
	$('#modal_ventana').modal('hide');
	//alert(idproducto);
	agregon2(id_linea,idproducto,1);
}
// manejar cookie
function mantiene_carrito(){
	// campos
	<?php if (intval($_SESSION['canal']) == 1 or intval($_SESSION['canal']) == 0) { ?>
	var v_chapa = $("#chapa").val();
	var v_ruc = $("#ruc").val();
	var v_razon_social = $("#razon_social").val();
	var v_delivery = $("#delivery").val();
	setCookie('chapa_cookie',v_chapa,2);
	setCookie('ruc_cookie',v_ruc,2);
	setCookie('razon_social_cookie',v_razon_social,2);
	setCookie('delivery_cookie',v_delivery,2);
	// ocultar chapa si es delivery
	if(v_delivery == 'N'){
		if(v_chapa == 'DELIVERY'){
			$("#chapa").val('');
		}
		$("#chapatr").show();
	}else{
		$("#chapatr").hide();
		$("#chapa").val('DELIVERY');
	}
	// validar ruc
	if(v_ruc == ''){
		$("#ruc").val('<?php echo $ruc_pred; ?>');
	}
	if(v_razon_social == ''){
		$("#razon_social").val('<?php echo $razon_social_pred; ?>');
	}
	
	<?php } else { ?>
	var v_mesa = $("#mesa").val();
	setCookie('mesa_cookie',v_mesa,2);
	<?php } ?>
	var v_observacion = $("#observacion").val();
	setCookie('observacion_cookie',v_observacion,2);	
}
function registrar_venta(tipo){
	// recibe parametros
	var errores='';
	var obligar='<?php echo $obliga_adicional?>';
	var orden_numero=$("#ocnumero").val();						 
	var adicional='';
	var cual=0;
	var tipodocu=tipo; // ticket o factura
	var mpago=parseInt($("#mediopagooc").val());
	var observacion=$("#observacion").val();
	var idpedido = $("#idpedido").val();
	var idvendedor = $("#idvendedor").val();
	var chapa = $("#chapa").val();
	var idadherente=0;
	var idservcom = 0;
	var iddeposito = $("#iddeposito").val();
	var idmotorista = $("#idmotorista").val();
	var idcanalventa = $("#idcanalventa").val();
	var fecha_venta = $("#fecha_venta").val();
	var primervto  = $("#primervto").val();
	var facturador_electronico = '<?php echo $facturador_electronico; ?>';
	if (isNaN(mpago)){
		cual='';
	} else {
		cual=(mpago);
	}
	if (cual==''){
		cual=9;
	}
	//alert(cual);
	var totalventa= $("#totalventa_original").val();
	if (isNaN(totalventa)){
		totalventa=0;
	}
	var montorecibido = parseFloat($("#montorecibido").val());
	var efectivo=0;
	var tarjeta=0;
	var tipotarjeta=0;
	var montocheque=0;
	var numcheque='';
	var vuelto=parseInt($("#vuelto").val());//solo referencial
	if (isNaN(vuelto)){
		vuelto=0;
	}
    var tipozona = $("#tipozona").val();
	var pref1 = $("#pref1").val();
	var pref2 = $("#pref2").val();
	var fact = $("#fact").val();
	var mesa = $("#mesa").val();
	var cliente = 0;
	cliente=$("#idcliente").val();
	if (cliente==''){
		cliente = $("#occliedefe").val();
	}
	var idsucursal_clie=$("#idsucursal_clie").val();
	var domicilio=<?php echo intval($_COOKIE['dom_deliv']);?>;
	var delivery=$("#delioc").val();
	if (isNaN(delivery)){
		delivery=0;
	}
	var banco=$("#adicional2").val();
	if (isNaN(banco)){
		banco=0;
	}
	var adicional=$("#adicional1").val();
	if (isNaN(adicional)){
		adicional=0;
	}
	var llevapos=$("#llevapos").val();
	var cambiopara=$("#cambiopara").val();
	var obsdel=$("#observa").val();
	var condi=$("#tipoventa").val();
	var domiex = <?php echo intval($_COOKIE['dom_deliv']);?>;
	var motivodesc = $("#motivodesc").val();
	var descuento = $("#descuento").val();
	var codpedido_externo = $("#codpedido_externo").val();
	var iddenominaciontarjeta = $("#iddenominaciontarjeta").val();
	
	
	
	
	if (isNaN(descuento)){
		var descuento = '0';
		var motivodesc ='';
	} else {
		if (parseFloat(descuento) > 0 && motivodesc==''){
			errores=errores+'Debe indicar motivo del descuento.\n';
			
		}
	}
	//Comprobar disponible de productos  total de venta
	if (totalventa==0){
		//coerrores=errores+'Debe agregar al menos un producto para vender. \n';
	}
	if(tipo == 8){
		$("#rghbtn").hide();
	}

	/*---------------------------------CONTROLES DE MONTOS***************************************/
	
	if (cual==1){
		
		//EFECTIVO
		if ((montorecibido)==0){
			//errores=errores+'Debe indicar Monto recibido p/ efectivo.\n';
		} else {
			efectivo=(montorecibido-vuelto);	
		}	
	}
	if (cual==2){
		
		//TARJETA Credito
		tipotarjeta=1;
		if ((montorecibido)==0){
			errores=errores+'Debe indicar Monto para cobrar TC.\n';
		} else {
			tarjeta=(montorecibido);
		}
		if (obligar=='S'){
			
			adicional=$("#adicional1").val();
			if (adicional==''){
				errores=errores+'Debe indicar numero voucher para TC.\n';
				
			}
		}
			
	}
	if (cual==3){
		
		//TMIXTO
		efectivo=efectivo=parseFloat(montorecibido);
		tarjeta=parseFloat($("#tarjeta").val());
		if ((efectivo)==0){
			errores=errores+'Debe indicar porcion efectivo para cobro mixto.\n';
		}
		if ((tarjeta)==0){
			errores=errores+'Debe indicar porcion TC para cobro mixto.\n';
		}		
	}
	if (cual==4){
		
		//TARJETA DEBITO
		tipotarjeta=2;
		if ((montorecibido)==0){
			errores=errores+'Debe indicar Monto para cobrar TD .\n';
		} else {
			tarjeta=(montorecibido);
		}
		if (obligar=='S'){
			
			adicional=$("#adicional1").val();
			if (adicional==''){
				errores=errores+'Debe indicar numero voucher para TD.\n';
				
			}
		}
			
	}
	if (cual==5){
		//es cheque, exigir banco y numero
		efectivo=0;
		tarjeta=0;
		montocheque=parseFloat(montorecibido);
		numcheque=adicional;
		if (banco==0 && numcheque==0){
			errores=errores+'Debe indicar numero de cheque y seleccionar banco. \n';
		}
	} 
	if (cual==8){
		//es adherente
		efectivo=0;
		tarjeta=0;
		montocheque=0;
		numcheque=0;
		condi=2;
	} 
	
	if (cual==9){
		montorecibido=totalventa;
		efectivo=montorecibido;
		tarjeta=0;
		montocheque=0;
		numcheque=0;
		condi=1;
	}
	
	/*-----------------------------------------------------------------------------------------*/
	// CONTADO
	if (condi==1){
			//Validaciones
			var suma=parseFloat(montorecibido);
			//alert(efectivo);
			if (suma>(totalventa+delivery)){
				 //errores=errores+'Esta intentando cobrar mas del total de venta.\n';
			}
			if (suma<totalventa && montorecibido >0){
				//errores=errores+'Esta intentando cobrar menos del total de venta.\n';
			}
	}
	// CREDITO
	if (condi==2){
		//Ultimo control de seguridad x venta credito
		var gen=$("#occliedefe").val();
		if (cliente==gen && cual!=8){
			//errores=errores+'Debe registrar al cliente para acceder a linea de credito.\n';
		} else {
			if (cual==8){
				idadherente=$("#idadhetx").val();
				if (idadherente==0){
						errores=errores+'Debe indicar un adherente para registrar la venta. \n';
				}
				idservcom=$("#idservcombox").val();
				if (idservcom==0){
						errores=errores+'Debe indicar un tipo de servicio para registrar la venta. \n';
				}
			}
			
		}
	}
	
	if (cual==''){
		errores=errores+'Medio de pago incorrecto. \n';
	}
	if(domiex > 0){
		if(tipozona == '0 - 0'){
			errores=errores+'Debe indicar la zona cuando es delivery. \n';
		}
	}
	if(tipo == 1){
		if(!fact>0){
			errores=errores+'Debe indicar el numero de factura. \n';
		}
	}
<?php if ($obliga_vendedor == 'S') { ?>
		if(!idvendedor>0){
			errores=errores+'Debe indicar el vendedor. \n';
		}
<?php } ?>
	if (errores==''){
		
		$("#terminar").hide();
		//alert('enviar');
		if(tipo == 2){
			fact='';
		}
		
		// INICIO REGISTRAR VENTAS //
		//alert(cual);
	    var parametros = {
			"pedido"         : idpedido,
			"idzona"         : tipozona, // zona costo delivery
			"idadherente"    : idadherente,
			"idservcom"      : idservcom, // servicio comida
			"banco"          : banco,
			"adicional"      : adicional, // numero de cheque, tarjeta, etc
			"condventa"      : condi, // credito o contado
			"mediopago"      : cual, // forma de pago
			"fac_suc"        : pref1,
			"fac_pexp"       : pref2,
			"fac_nro"        : fact,
			"domicilio"      : '<?php echo intval($_COOKIE['dom_deliv']);?>', // codigo domicilio
			"llevapos"       : llevapos,
			"cambiode"       : cambiopara,
			"observadelivery": obsdel,
			"observacion"    : observacion,
			"mesa"           : 0,
			"canal"          : 2, // delivery, carry out, mesa, caja
			"fin"            : 3,
			"idcliente"      : cliente,
			"idsucursal_clie": idsucursal_clie,
			"monto_recibido" : montorecibido,
			"descuento"      : descuento,
			"motivo_descuento": motivodesc,
			"chapa"          : chapa,
			"montocheque"    : montocheque,
			"idvendedor"     : idvendedor,
			"iddeposito"     : iddeposito,
			"idmotorista"    : idmotorista,
			"idcanalventa"   : idcanalventa,
			"fecha_venta"    : fecha_venta,
			"codpedido_externo" : codpedido_externo,
			"primervto"      : primervto,
			"ocnumero"		 : orden_numero,
			"iddenominaciontarjeta"		 : iddenominaciontarjeta,
			"json"           : 'S'
			
			
        };
		
       $.ajax({
                data:  parametros,
                url:   'registrar_venta.php',
                type:  'post',
                beforeSend: function () {
                        $("#carrito").html("<br /><br />Registrando...<br /><br />");
                },
                success:  function (response) {
					$("#carrito").html(response);
						
					if(IsJsonString(response)){
						var obj = jQuery.parseJSON(response);
						 borra_carrito();
						 <?php $script = "script_central_impresion.php";?>
						if(obj.error == ''){
								document.body.innerHTML='<meta http-equiv="refresh" content="0; url=<?php echo $script?>?tk='+tipo+'&clase=1&v='+obj.idventa+'<?php echo $redirbus2; ?>">';
						}else{
							alertar_redir('NO SE REGISTRO LA VENTA',obj.error,'error','ACEPTAR','gest_ventas_resto_caja.php');
						}
					}else{
						alert(response);
					}
                }
        });
		
		// FIN REGISTRAR VENTAS //
		
		
		
		
	} else {
		$("#terminar").show();
		//document.getElementById('montorecibido').focus();
		alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');	
	}

}
<?php
$consulta = "
select idforma, idformapago_set 
from formas_pago 
where 
estado <> 6
";
$rsfpagset = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
while (!$rsfpagset->EOF) {
    $idforma = $rsfpagset->fields['idforma'];
    $idformapago_set = $rsfpagset->fields['idformapago_set'];
    $formapag[$idforma] = $idformapago_set;
    $rsfpagset->MoveNext();
}

$json_fpag = json_encode($formapag, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

?>
function forma_pago_mixsel(forma){
	var json_fpag = '<?php echo $json_fpag; ?>';
	var obj = jQuery.parseJSON(json_fpag);
	var forma_str=forma.toString();
	var forma_pago_set = obj[forma];
	//alert(obj[forma]);
	// cheque
	if(forma_pago_set == 2){
		$("#iddenominaciontarjeta_mixsel_box").hide();
		$("#banco_mixsel_box").show();
		$("#cheque_numero_mixsel_box").show();
	// tarjeta credito y debito
	}else if(forma_pago_set == 3 || forma_pago_set == 4){
		$("#iddenominaciontarjeta_mixsel_box").show();
		$("#banco_mixsel_box").hide();
		$("#cheque_numero_mixsel_box").hide();
	}else{
		$("#iddenominaciontarjeta_mixsel_box").hide();
		$("#banco_mixsel_box").hide();
		$("#cheque_numero_mixsel_box").hide();
	}
	
}
function cobranza(cual,idpedido,pri){
	    if (isNaN(cual)){
			cual='';
		}
	    if (isNaN(idpedido)){
			idpedido=0;
		}
		var totzonas = $("#totzonas").val();
		//Traemos el total deventa acumulada
		var tprod=parseInt(($("#totalventa").val()));
		var tvreal = parseInt($("#totalventa_real").val());
		// si hay pedido
		if(idpedido > 0){
			tprod = tvreal;
		}
		// si no hay pedido
		if(idpedido == 0 && pri == 1){
			//$("#totalventa_real").val(tprod);
			tvreal = tprod;
		}
		
		//if (tprod > 0){
			
			var domicilio=($("#ubica").val());
			var direccionurl='cobramini.php';
			var parametros = {
				  "idpedido" : idpedido,
				  "tipocobro": cual,
				  "domicilio": <?php echo intval($_COOKIE['dom_deliv']);?>

		   };
		   $.ajax({
					data:  parametros,
					url:   direccionurl,
					type:  'post',
					beforeSend: function () {
						   // $("#pop4").html("Cargando...");
					},
					success:  function (response) {
						
						$('#modal_ventana').modal('show');
						$("#modal_titulo").html('Cobrar');
						$("#modal_cuerpo").html(response);
							if (cual !=''){
								//alert(cual);
								$("#ocultoad").hide();
								$("#oculto1").show();
								$("#oculto2").show();
								$("#warpago").hide();
								$("#cuerpo3").show();
								$("#mediopagooc").val(cual);
								//var totalventa = parseInt($("#totalventa").val());
								//$("#montorecibido").val(totalventa);
								$("#montorecibido").select();
								if (cual==1){
									//EF
									$("#adicional1").hide();
									$("#adicional2").hide();



								} else {
									if (cual!=7){
										$("#adicional1").show();
										$("#adicional2").show();
									} else {
										$("#adicional1").hide();
										$("#adicional2").hide();
										
									}
									if (cual==5){
									//CHEQUE
										$("#warpago").show();

									}
									if (cual==8){
											//adherente
											$("#adicional1").hide();
											$("#adicional2").hide();
											$("#oculto1").hide();
											$("#oculto2").hide();
											$("#ocultoad").show();
											$("#cuerpo3").hide();
											$("#adherentebus").focus();
									}
								} 
							}
						
						esplitear($("#tipozona").val());
						actualiza_monto();
						$("#montorecibido").select();
					}
			});	
		/*} else {
			
			alertar('ATENCION: Algo salio mal.','Debe agregar al menos un producto al carrito.','error','Lo entiendo!');
		}*/
		
		
} 
function carry_out(obj=0){
	var deliv = <?php echo intval($_COOKIE['dom_deliv']); ?>;
	// si hay delivery
	if(deliv > 0){
		if(window.confirm('Se borrara el delivery, esta seguro?')){
			//$("#pop6").hide();
			//$("#pop6").html('Borrando Delivery...');
			$("#modal_titulo").html('Carry Out');
			$("#modal_cuerpo").html('Borrando Delivery...');
			borra_delivery();	
			//setCookie('dom_deliv',null,-1);
		}else{
			//$("#pop6").hide();
			$('#modal_ventana').modal('hide');
			document.location.href='gest_ventas_resto_caja.php';
		}
	}else{
	 var parametros = {
                "idpedido" : 0
        };
       $.ajax({
                data:  parametros,
                url:   'carry_out.php',
                type:  'post',
                beforeSend: function () {
					$('#modal_ventana').modal('show');
					$("#modal_titulo").html('Carry Out');
					$("#modal_cuerpo").html('Cargando...');
                },
                success:  function (response) {
					$("#modal_titulo").html('Carry Out');
					$("#modal_cuerpo").html(response);
					$("#ruc_carry").focus();
					if(obj != 0){
						$("#razon_social_carry").val(obj.razon_social);
						$("#ruc_carry").val(obj.ruc);
						$("#telefono_carry").val(obj.telefono);	
					}

                }
        });
	}
}
function delivery_pedidos(){
	var deliv = <?php echo intval($_COOKIE['dom_deliv']); ?>;
		 var parametros = {
					"idpedido" : 0
			};
		   $.ajax({
					data:  parametros,
					url:   'delivery_ped_caja.php',
					type:  'post',
					beforeSend: function () {
							$("#pop6").html("Cargando...");
					},
					success:  function (response) {
							//popupasigna8();
							$("#pop6").html(response);
					}
			});
}
function registrar_pedido(idcanal){
	var razon_social_carry = $("#razon_social_carry").val();
	var ruc_carry = $("#ruc_carry").val();
	var chapa_carry = $("#chapa_carry").val();
	var observacion_carry = $("#observacion_carry").val();
	var telefono_carry = $("#telefono_carry").val();
	var llevapos = $("#llevapos_deliv").val();
	var cambio = $("#cambio_deliv").val();
	var observacion_carry = $("#observacion_carry").val();
	var idsucu_deliv = $("#idsucu_deliv").val();
	var iddomicilio = <?php echo intval($_COOKIE['dom_deliv']); ?>;
	
	
	var valido = 'S';
	var errores = '';
	// validaciones

	// carry out
	if(idcanal == 1){
		if(ruc_carry == ''){
			valido = 'N';
			errores=errores+'Debe completar el ruc. \n';
		}
		if(razon_social_carry == ''){
			valido = 'N';
			errores=errores+'Debe completar la razon social. \n';
		}
		if(chapa_carry == ''){
			valido = 'N';
			errores=errores+'Debe completar el nombre. \n';
		}
	}
	// delivery
	if(idcanal == 3){
		if(iddomicilio > 0){
			if(ruc_carry == ''){
				valido = 'N';
				errores=errores+'Debe completar el ruc. \n';
			}
			if(razon_social_carry == ''){
				valido = 'N';
				errores=errores+'Debe completar la razon social. \n';
			}

			if(llevapos == ''){
				valido = 'N';
				errores=errores+'Debe indicar si lleva pos o no. \n';
			}
			if(idsucu_deliv == ''){
				valido = 'N';
				errores=errores+'Debe indicar la sucursal. \n';
			}
			if(llevapos == 'N'){
				if(cambio == ''){
					valido = 'N';
					errores=errores+'Debe indicar cambio de cuanto si no lleva pos por ser efectivo. \n';
				}
			}
		
		}else{
			valido = 'N';
			errores=errores+'Debe tomar los datos delivery. \n';	
		}
	}
	
	if(valido == 'S'){
		var parametros = {
			"razon_social"       : razon_social_carry,
			"ruc"                : ruc_carry,
			"chapa"              : chapa_carry,
			"telefono"           : telefono_carry,
			"observacion"        : observacion_carry,
			"mesa"               : 0,
			"llevapos"           : llevapos,
			"cambio"             : cambio,
			"idsucu"             : idsucu_deliv,
			"canal"              : idcanal,
			"MM_insert"          : 'form1'
		};
		$.ajax({
			data:  parametros,
			url:   'registrar_pedido.php',
			type:  'post',
			beforeSend: function () {
				$("#regpedido").hide();
				$("#regpedidobox").html('Registrando...');
			},
			success:  function (response) {
				$("#carrito").html(response);
				if(IsJsonString(response)){
					var obj = jQuery.parseJSON(response);
					borra_carrito();
					
					if(obj.error == ''){
						 borra_delivery();	
						 document.body.innerHTML='<meta http-equiv="refresh" content="0; url=gest_ventas_resto_caja.php">';
					}else{
						alertar_redir('NO SE REGISTRO EL PEDIDO',obj.error,'error','ACEPTAR','gest_ventas_resto_caja.php');
					}
				}else{
					alert(response);
				}
				//alert(response);
	
			}
			
		});
		
	}else{ // if(valido == 'S'){
		alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
	}
		
}

function obtener_peso(posicion){
		var direccionurl='<?php echo $script_balanza?>';
		var parametros = {
              "id" : 0
	   };
       $.ajax({
                data:  parametros,
                url:   direccionurl,
                type:  'post',
                beforeSend: function () {
                        //$("#obtenido").html("Cargando...");
					<?php if ($autopeso == 'S') {?>
						$("#obtp_"+posicion).hide();
					<?php }?>
                },
                success:  function (response) {
						$("#cvender_"+posicion).val(response);
						<?php if ($autopeso == 'S') {?>
						$("#cv_"+posicion).click();
						setTimeout(function(){ $("#obtp_"+posicion).show(); }, 2000);
						<?php }?>
                }
        });	
}

function credito_rapido(){
	var htmlcarr = $("#carrito").html();
	var codigo_vrapida = $("#codigo_vrapida").val();
	if(codigo_vrapida != ''){
		var parametros = {
			"pedido"         : '',
			"idzona"         : '', // zona costo delivery
			"idadherente"    : '',
			"idservcom"      : '', // servicio comida
			"banco"          : '',
			"adicional"      : '', // numero de cheque, tarjeta, etc
			"condventa"      : 2, // credito o contado
			"mediopago"      : 7, // forma de pago
			"fac_suc"        : '',
			"fac_pexp"       : '',
			"fac_nro"        : '',
			"domicilio"      : <?php echo intval($_COOKIE['dom_deliv']);?>, // codigo domicilio
			"llevapos"       : 'N',
			"cambiode"       : '',
			"observadelivery": '',
			"observacion"    : '',
			"mesa"           : 0,
			"canal"          : 2, // delivery, carry out, mesa, caja
			"fin"            : 3,
			"idcliente"      : '',
			"monto_recibido" : 0,
			"descuento"      : 0,
			"motivo_descuento": '',
			"chapa"          : '',
			"montocheque"    : 0,
			"idvendedor"     : '',
			"iddeposito"     : '',
			"idmotorista"    : '',
			"idcanalventa"   : '',
			"fecha_venta"    : '',
			"json"           : 'S',
			"codigo_vrapida" : codigo_vrapida
		};
			
		$.ajax({
		data:  parametros,
		url:   'registrar_venta.php',
		type:  'post',
			beforeSend: function () {
					$("#carrito").html("<br /><br />Registrando...<br /><br />");
			},
			success:  function (response) {
				//$("#carrito").html(response);
					
				if(IsJsonString(response)){
					var obj = jQuery.parseJSON(response);
					 //borra_carrito();
					 <?php $script = "script_central_impresion.php";?>
					 //alert(obj.error);
					if(obj.error == ''){
							document.body.innerHTML='<meta http-equiv="refresh" content="0; url=<?php echo $script?>?tk=2&clase=1&v='+obj.idventa+'<?php echo $redirbus2; ?>">';
					}else{
						/*alertar_redir('NO SE REGISTRO LA VENTA',obj.error,'error','ACEPTAR','gest_ventas_resto_caja.php');*/
						alertar('NO SE REGISTRO LA VENTA',obj.error,'error','ACEPTAR');
						$("#carrito").html(htmlcarr);
						
						
						setTimeout(function() {  $("#codigo_vrapida").val(codigo_vrapida) }, 1000); 
					}
				}else{
					alert(response);
				}
			}
		});
		
	}else{
		alert('No ingresaste el codigo.');
	}
}
<?php
$consulta = "
select idcliente from cliente where borrable = 'N' limit 1
";
$rsclidef = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idclientedef = intval($rsclidef->fields['idcliente']);
?><?php
// preferencias caja
$consulta = "
SELECT 
usa_canalventa, usa_clienteprevio, avisar_quedanfac, usa_ventarapida, usa_ventarapidacred 
FROM preferencias_caja 
limit 1
";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$avisar_quedanfac = trim($rsprefcaj->fields['avisar_quedanfac']);
$usa_canalventa = trim($rsprefcaj->fields['usa_canalventa']);
$usa_clienteprevio = trim($rsprefcaj->fields['usa_clienteprevio']);
$usa_ventarapidacred = trim($rsprefcaj->fields['usa_ventarapidacred']);
$usa_ventarapida = trim($rsprefcaj->fields['usa_ventarapida']);

if ($usa_ventarapida == 'S') {

    $tkvr = 2; // ticket
    if ($factura_obliga == 'S') {
        $tkvr = 1; // factura
        $ano = date("Y");
        // busca si existe algun registro
        $buscar = "
		Select idsuc, numfac as mayor 
		from lastcomprobantes 
		where 
		idsuc=$factura_suc 
		and pe=$factura_pexp 
		and idempresa=$idempresa 
		order by ano desc 
		limit 1";
        $rsfactura = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        //$maxnfac=intval(($rsfactura->fields['mayor'])+1);
        // si no existe inserta
        if (intval($rsfactura->fields['idsuc']) == 0) {
            $consulta = "
			INSERT INTO lastcomprobantes
			(idsuc, factura, numfac, recibo, numrec, tickete, numtk, ano, pe, numcheque, secuencia_cancela, 
			numhoja, hojalevante, idempresa) 
			VALUES
			($factura_suc, 0, 0, NULL, 0, NULL, 0, $ano, $factura_pexp, NULL, 
			NULL, 0, '', $idempresa)
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }
        $ultfac = intval($rsfactura->fields['mayor']);
        if ($ultfac == 0) {
            $maxnfac = 1;
        } else {
            $maxnfac = intval(($rsfactura->fields['mayor']) + 1);
        }
        $parte1 = intval($factura_suc);
        $parte2 = intval($factura_pexp);
        if ($parte1 == 0 or $parte2 == 0) {
            $parte1f = '001';
            $parte2f = '001';
        } else {
            $parte1f = agregacero($parte1, 3);
            $parte2f = agregacero($parte2, 3);
        }

    } else {
        $parte1f = "''";
        $parte2f = "''";
        $maxnfac = "''";
    }
    ?>
function venta_rapida(){
	var htmlcarr = $("#carrito").html();
	var parametros = {
		"pedido"         : '',
		"idzona"         : '', // zona costo delivery
		"idadherente"    : '',
		"idservcom"      : '', // servicio comida
		"banco"          : '',
		"adicional"      : '', // numero de cheque, tarjeta, etc
		"condventa"      : 1, // credito o contado
		"mediopago"      : 1, // forma de pago
		"fac_suc"        : <?php echo $parte1f ?>,
		"fac_pexp"       : <?php echo $parte2f ?>,
		"fac_nro"        : <?php echo $maxnfac ?>,
		"domicilio"      : <?php echo intval($_COOKIE['dom_deliv']);?>, // codigo domicilio
		"llevapos"       : 'N',
		"cambiode"       : '',
		"observadelivery": '',
		"observacion"    : '',
		"mesa"           : 0,
		"canal"          : 2, // delivery, carry out, mesa, caja
		"fin"            : 3,
		"idcliente"      : <?php echo  $idclientedef ?>,
		"monto_recibido" : 0,
		"descuento"      : 0,
		"motivo_descuento": '',
		"chapa"          : '',
		"montocheque"    : 0,
		"idvendedor"     : '',
		"iddeposito"     : '',
		"idmotorista"    : '',
		"idcanalventa"   : '',
		"fecha_venta"    : '',
		"json"           : 'S',
		"codigo_vrapida" : ''
	};
		
	$.ajax({
	data:  parametros,
	url:   'registrar_venta.php',
	type:  'post',
		beforeSend: function () {
				$("#carrito").html("<br /><br />Registrando...<br /><br />");
		},
		success:  function (response) {
			//$("#carrito").html(response);
				
			if(IsJsonString(response)){
				var obj = jQuery.parseJSON(response);
				 //borra_carrito();
				 <?php $script = "script_central_impresion.php";?>
				 //alert(obj.error);
				if(obj.error == ''){
						document.body.innerHTML='<meta http-equiv="refresh" content="0; url=<?php echo $script?>?tk=<?php echo $tkvr; ?>&clase=1&v='+obj.idventa+'<?php echo $redirbus2; ?>">';
				}else{
					/*alertar_redir('NO SE REGISTRO LA VENTA',obj.error,'error','ACEPTAR','gest_ventas_resto_caja.php');*/
					alertar('NO SE REGISTRO LA VENTA',obj.error,'error','ACEPTAR');
					$("#carrito").html(htmlcarr);

				}
			}else{
				alert(response);
			}
		}
	});

}
function credito_rapido_buscli(busq){
	var parametros = {
		'codigo_vrapida' : busq
	};
	$.ajax({
	data:  parametros,
	url:   'codigo_vrapida_busq.php',
	type:  'post',
		beforeSend: function () {
			$("#clirapid").html("<br />Buscando...<br />");
		},
		success:  function (response) {
			$("#clirapid").html(response);
		}
	});
}
<?php } // if($usa_ventarapida == 'S'){?>
<?php if ($pulsera_vcaja == 'S') {
    $ahora_puls = date("YmdHis");

    ?>	
function pulsera_enter(e){
	// si apreto enter
	if(e.keyCode == 13){
		//alert('enter');
		pulsera_cobra();
	}
}
function pulsera_cobra(){
	var htmlcarr = $("#carrito").html();
	var cod_pulsera = $("#cod_pulsera_<?php echo $ahora_puls; ?>").val();
	var parametros = {
		"pedido"         : '',
		"idzona"         : '', // zona costo delivery
		"idadherente"    : '',
		"idservcom"      : '', // servicio comida
		"banco"          : '',
		"adicional"      : '', // numero de cheque, tarjeta, etc
		"condventa"      : 1, // credito o contado
		"mediopago"      : 1, // forma de pago
		"fac_suc"        : '',
		"fac_pexp"       : '',
		"fac_nro"        : '',
		"domicilio"      : '', // codigo domicilio
		"llevapos"       : 'N',
		"cambiode"       : '',
		"observadelivery": '',
		"observacion"    : '',
		"mesa"           : 0,
		"canal"          : 8, // delivery, carry out, mesa, caja
		"fin"            : 3,
		"idcliente"      : '<?php echo  $idclientedef ?>',
		"monto_recibido" : 0,
		"descuento"      : 0,
		"motivo_descuento": '',
		"chapa"          : '',
		"montocheque"    : 0,
		"idvendedor"     : '',
		"iddeposito"     : '',
		"idmotorista"    : '',
		"idcanalventa"   : '',
		"fecha_venta"    : '',
		"json"           : 'S',
		"cod_pulsera"    : cod_pulsera
	};
		
	$.ajax({
	data:  parametros,
	url:   'registrar_venta.php',
	type:  'post',
		beforeSend: function () {
			$("#carrito").html("<br /><br />Registrando...<br /><br />");
			$("#cod_pulsera_<?php echo $ahora_puls; ?>").val('');
		},
		success:  function (response) {
			//$("#carrito").html(response);
			$("#cod_pulsera_<?php echo $ahora_puls; ?>").val('');	
			if(IsJsonString(response)){
				var obj = jQuery.parseJSON(response);
				 //borra_carrito();
				 <?php $script = "script_central_impresion.php";?>
				 //alert(obj.error);
				if(obj.error == ''){
						document.body.innerHTML='<meta http-equiv="refresh" content="0; url=<?php echo $script?>?tk=<?php echo $tkvr; ?>&clase=1&v='+obj.idventa+'<?php echo $redirbus2; ?>">';
				}else{
					/*alertar_redir('NO SE REGISTRO LA VENTA',obj.error,'error','ACEPTAR','gest_ventas_resto_caja.php');*/
					alertar('NO SE REGISTRO LA VENTA',obj.error,'error','ACEPTAR');
					$("#carrito").html(htmlcarr);

				}
			}else{
				alert(response);
			}
		}
	});

}
function abrir_lector_app(){
	if(!(typeof ApiChannel === 'undefined')){
		$("#puls_msg").html("Abiendo Camara...");
		$("#puls_msg").show();
		ApiChannel.postMessage('<?php
            //parametros para la funcion
            $parametros_array_tk = [
                'metodo' => 'lector'
            ];
    echo texto_para_app($parametros_array_tk);

    ?>');
	}else{
		$("#cod_pulsera_<?php echo $ahora_puls; ?>").focus();
		$("#puls_msg").hide();
		$("#puls_msg").html("");
	}
}
function qr_respuesta(obj){
	$("#puls_msg").hide();
	$("#puls_msg").html("");
	$("#cod_pulsera_<?php echo $ahora_puls; ?>").val(obj.texto);
	pulsera_cobra();
}
<?php } // if($pulsera_vcaja=='S'){?>
<?php if ($alerta_ventas != '') { ?>
setInterval(function() {
    $('.alerta_recuerda').fadeIn(700).fadeOut(700);
}, 1000);
<?php }?>
</script>
<?php if ($alerta_ventas != '') { ?>
<style>
.alerta_recuerda{
	text-align:center;
	height:25px;
	padding:5px;
	width:90%;
	border:1px solid #000;
	background-color:#FF0;
	color:#F00;
	font-weight:bold;
	font-size:16px;
	margin:0px auto;
}
</style>
<?php }?>
<link rel="stylesheet" href="fonts/ekaruff.css" />
</head>
<body bgcolor="#E1F0FF" onLoad="inicio();" style="overflow: auto;">
<div class="vta_contenedor">

<div class="vta_izquierda">
<?php if ($pulsera_vcaja == 'S') { ?>
<div id="puls_msg" style="display: none;"></div>
<div style="margin:0px auto; width:100%; text-align:center; background-color: aliceblue;">PULSERA
<input name="cod_pulsera_<?php echo $ahora_puls; ?>" id="cod_pulsera_<?php echo $ahora_puls; ?>" type="text" style="width:120px;height:30px;" placeholder="codigo pulsera" onkeyup="pulsera_enter(event);" value="" autofocus autocomplete="new-password"  />
	<a href="#" class="btn btn-sm btn-default" onClick="abrir_lector_app();"><span class="fa fa-barcode"></span></a>
</div>
<?php } ?>
<?php if ($usa_ventarapidacred == 'S') { ?>
<div style="margin:0px auto; width:100%; text-align:center; background-color: aliceblue;">
<input name="codigo_vrapida" id="codigo_vrapida" type="text" style="width:120px;height:30px;" placeholder="Codigo" onkeyup="credito_rapido_enter(event);" value="<?php echo htmlentities($_GET['vrc']); ?>" onChange="credito_rapido_buscli(this.value);" />
<input name="" type="button" value="Credito Rapido" style="height:30px;" onmouseup="credito_rapido();"  />
<div id="clirapid"></div>
</div>
<?php } ?>
	<div  id="carrito">
    <?php require_once("gest_ventas_resto_carrito.php"); ?>
	</div>
</div>
<div class="vta_derecha">



<div class="productos">
<?php
$ahora = date("Y-m-d H:i:s");
$ahorad = date("Y-m-d");
$ahoratime = date("H:i:s");
$diasemanahoy = strtolower(diasemana(date("Y-m-d")));
$consulta = "
select * , $seladd_lp
from productos 
inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
$joinadd_lp
where
productos.idprod_serial in 
	(
		select idproducto 
		from promociones
		where
		idproducto is not null
		and estado = 1
		and '$ahora' >= desde
		and '$ahora' <= hasta
		and '$ahoratime' >= hora_activa_desde 
		and '$ahoratime' <= hora_activa_hasta 
		and (
				(
					$diasemanahoy = 'S'
				)
			OR
				(
					CASE WHEN 
						feriados = 'S'
					THEN
						(SELECT idferiado FROM feriados WHERE fecha = '$ahorad' limit 1) 
					ELSE
						'0'
					END > 0
				
				)
			)
	)

and productos_sucursales.idsucursal = $idsucursal 
and productos_sucursales.idempresa = $idempresa
and productos_sucursales.activo_suc = 1

$whereadd_lp

order by descripcion asc
limit 1
";
//echo $consulta;
//$rspromo=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
//$tr=$rspromo->RecordCount();

// hay promo
if ($rspromo->fields['idprod_serial'] > 0) {
    $haypromo = "S";
}


$consulta = "
SELECT * 
FROM categorias
where
estado = 1
and muestra_venta = 'S'
order by orden asc, nombre asc
";
//echo $consulta;
$rscateg = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$totalcat = $rscateg->RecordCount();
if ($totalcat <= 8) {
    ?>
<table width="98%" border="1" class="categoria" bgcolor="#FFFFFF">
  <tbody>
    <tr>
    
	  <td align="center" <?php if (intval($_GET['bus']) == 1) { ?>bgcolor="#F8FFCC"<?php } ?>  onClick="busqueda_lupa();"><img src="img/buscar1.png" width="50"  title="B&uacute;squeda"/></td>
	  <td align="center" <?php if (($cat == 0) && (intval($_GET['bus']) == 0) && (intval($_GET['prom']) == 0)) { ?>bgcolor="#F8FFCC"<?php } ?> onClick="categoria_sel(0);"><img src="tablet/gfx/iconos/estrella.fw.png" width="50"  alt="Favorito"/></td>
<?php if ($haypromo == 'S') { ?>
      <td align="center" <?php if (intval($_GET['prom']) == 1) { ?>bgcolor="#F8FFCC"<?php } ?> onClick="document.location.href='?prom=1'"><img src="tablet/gfx/iconos/promo_mini.jpg" width="50"  alt="Promociones"/></td>
<?php } ?>
<?php while (!$rscateg->EOF) {

    $img = "tablet/gfx/iconos/cat_".$rscateg->fields['id_categoria'].".png";
    if (!file_exists($img)) {
        //$img="tablet/gfx/iconos/cat_0.png";
        $cat_btn = '<a href="javascript:void(0);" onClick="categoria_sel('.$rscateg->fields['id_categoria'].');" class="btn btn-xs btn-default"><span class="fa fa-filter"></span> '.substr(trim(antixss($rscateg->fields['nombre'])), 0, 10).'</a>';
    } else {
        $cat_btn = '<img src="'.$img.'" width="50" />';
    }

    ?>
      <td align="center" <?php if ($cat == $rscateg->fields['id_categoria']) { ?>bgcolor="#F8FFCC"<?php } ?>  onClick="categoria_sel(<?php echo $rscateg->fields['id_categoria']; ?>);">
	  <!--<img src="<?php echo $img; ?>" width="50" alt="<?php echo $rscateg->fields['nombre']; ?>"/>-->
	  <?php echo $cat_btn; ?>
	  
	  
	</td>
<?php $rscateg->MoveNext();
}?>
    </tr>
  </tbody>
</table>
<?php } else { ?>
<div style="width:98%; margin:0px auto;">
<img src="img/buscar1.png" width="50"  title="B&uacute;squeda" onClick="busqueda_lupa();" style="float:left;" /><select onChange="categoria_sel(this.value);" style="width:90%; height:60px; font-size:16px;">
  <option value="0">Favoritos</option>
<?php if (intval($_SESSION['idcanalventa']) > 0) {

    ?>
	<option value="cv" <?php if ($_GET['cat'] == 'cv') { ?>selected="selected"<?php } ?>><?php echo $canal_venta; ?></option>
<?php } ?>
<?php while (!$rscateg->EOF) { ?>
  <option value="<?php echo $rscateg->fields['id_categoria']; ?>" <?php if ($cat == $rscateg->fields['id_categoria']) { ?>selected="selected"<?php } ?>><?php echo capitalizar($rscateg->fields['nombre']); ?></option>
<?php $rscateg->MoveNext();
}?>
</select>

</div>
<?php } ?>
<?php if ($modo_kg == 'S') {?>
	<table width="99%" border="0">
	  <tbody>
	    <tr>
	      <td align="right" width="36"><img src="img/balanza.jpg" width="32" height="32" /></td>
	      <td align="left"><input type="text" id="menu_kg" name="menu_kg"  style="width: 99%; height: 40px;" placeholder="Ingrese el peso" value="" onKeyUp="registra_peso(<?php echo $idprod_kg; ?>,event);"></td>
	      </tr>
	    </tbody>
	  </table>
	
<?php } ?>
<?php if ($usarbcode == 'S') {?>
<br />
<div id='bcode'>
	<table width="99%;">
		<tr>
			<td width="6%"><img src="img/bar-code.png" width="32" height="32" alt=""/></td>
			<td width="68%"><input type="text" name="bccode" id="bccode"  style="width: 99%; height: 40px;" placeholder="Ingrese codigo de barras "/></td>
			<td width="26%"><input type="text" name="cantcode" id="cantcode" style="width: 99%; height: 40px;" placeholder="Cantidad"/></td>
		</tr>
	</table>
	
</div>
<?php }?>

	<hr />
<div class="lista_prod" id="lista_prod">
<?php
if (intval($_GET['prom']) == 0) {
    if ($busqueda == 0) {
        ?><?php if (intval($cat) > 0 && intval($t) == 0) {
            $consulta = "
SELECT * 
FROM sub_categorias
where
idcategoria = $cat
and estado = 1
and muestrafiltro = 'S'
and idempresa = $idempresa
order by descripcion asc
";
            $rssub = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            while (!$rssub->EOF) {
                ?>
<div id="" class="producto" onClick="filtrar_subcat('<?php echo $cat; ?>','<?php echo $rssub->fields['idsubcate']; ?>');">
   <img src="tablet/gfx/images/filter.png" height="81" width="163" border="0" alt="Filtrar" title="Filtrar" /><br />
   <strong><?php echo $rssub->fields['descripcion']; ?><br />[Filtrar]
    </strong><br />
</div>
<?php
                $rssub->MoveNext();
            }
        }
        ?><?php  if (intval($t) > 0) {
            $idsubcate = intval($t);
            $consulta = "
	select idsubcate, descripcion from sub_categorias where idsubcate = $idsubcate limit 1
	";
            $rssubsel = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            ?>
 <strong>Filtrando por: <?php echo $rssubsel->fields['descripcion']; ?></strong>&nbsp;&nbsp;<a href="javascript:void(0);" onClick="categoria_sel(<?php echo $cat; ?>);" class="btn btn-sm btn-default" title="Borrar filtro" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar filtro"><span class="fa fa-trash-o"></span></a>
 <br /><br />
<?php } ?>
<?php
        $col = intval(ceil($totalprod / $totalcol));
        $x = 1;

        while (!$rsprod->EOF) {
            $img = "gfx/productos/prod_".$rsprod->fields['idprod_serial'].".jpg";
            if (!file_exists($img)) {
                $img = "gfx/productos/prod_0.jpg";
            }

            ?><div id="prod_<?php echo $rsprod->fields['idprod_serial']; ?>" class="producto" <?php
if ($rsprod->fields['idtipoproducto'] == 1) { // producto
    echo "onClick=\"apretar('".$rsprod->fields['idprod_serial']."',0,0);\" ";
} elseif ($rsprod->fields['idtipoproducto'] == 2) {  // combo
    echo "onClick=\"apretar_combo('".$rsprod->fields['idprod_serial']."');\" ";
} elseif ($rsprod->fields['idtipoproducto'] == 3) {  // combinado simple
    echo "onClick=\"apretar_pizza('".$rsprod->fields['idprod_serial']."');\" ";
} elseif ($rsprod->fields['idtipoproducto'] == 4) {  // combinado extendido
    echo "onClick=\"apretar_combinado('".$rsprod->fields['idprod_serial']."');\" ";
} elseif ($rsprod->fields['idtipoproducto'] == 13) {   // grupo opciones
    echo "onClick=\"grupo_opciones('".$rsprod->fields['idprod_serial']."',1,0);\" ";
} else { // por defecto producto
    echo "onClick=\"apretar('".$rsprod->fields['idprod_serial']."',0,0);\" ";
}
            ?> ><div class="contador" id="contador_<?php echo $rsprod->fields['idprod_serial']; ?>" ><?php echo intval($rsprod->fields['total']); ?></div>
    <?php if (trim($rsprod->fields['descripcion']) != '') { ?><img src="<?php echo $img ?>" height="81" width="163" border="0" alt="<?php echo Capitalizar(trim($rsprod->fields['descripcion'])); ?>" title="<?php echo Capitalizar(trim($rsprod->fields['descripcion'])); ?>" /><br /><?php echo Capitalizar(trim($rsprod->fields['descripcion'])); ?><br />Gs. <?php if (trim($rsprod->fields['combinado']) == 'N') { ?><?php echo formatomoneda(trim($rsprod->fields['p1'])); ?><?php } else {?>Precio Variable<?php } ?><input type="hidden" value="<?php echo $rsprod->fields['p1']; ?>" name="precio_<?php echo $rsprod->fields['idprod_serial']; ?>" id="precio_<?php echo $rsprod->fields['idprod_serial']; ?>">
    <br /><?php } ?>
</div>
    <?php $x++;
            $rsprod->MoveNext();
        }?>
<?php /*}*/
    } else { //if ($busqueda==0){

        ?>

    <div align="center">
        <input type="text" name="filtrar" id="filtrar" placeholder="Ingrese producto a buscar" style="height:40px; width:70%;" onKeyUp="filtra(this.value);"  />
    
    </div>
    <br />
    <div id="minicentro" class="">
        
        
        
    </div>
<?php } // if ($busqueda==0){?>
<?php

} else {

    //require_once("lista_promo_vta.php");

} // if(intval($_GET['prom']) == 0){?>
</div>

<div class="clear"></div>
<br />
</div>
<div class="clear"></div>
</div>
<div class="clear"></div><br /><br />
</div>
<!------------------CONTENEDOR PARA PAGO X CAJA-------------------------->
<div id="pop5" class="mfp-hide"  ><?php require_once('gest_buscar_codigos.php');?></div>
<div id="pop7" class="mfp-hide" ><div id="adh_box">Adherente</div></div>

<!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
            </button>
            <h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
            ...
            </div>
            <div class="modal-footer" id="modal_pie">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<!-- POPUP DE MODAL OCULTO -->
<!-- Bootstrap -->
<script src="vendors/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="js/ventas_caja.js?20230411160500"></script>
<script src="js/mantienesession.js?20201017190100"></script>
<script>
function registrar_venta_pos(tipo){

	var direccionurl='registrar_venta_pos.php';	
	var parametros = {
	  "MM_insert" : "form1"
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			$("#terminar").html("<br /><br />Registrando...<br /><br />");			
		},
		success:  function (response, textStatus, xhr) {
			$("#terminar").html(response);
		},
		error: function(jqXHR, textStatus, errorThrown) {
			errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
		}
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
	});

}
function errores_ajax_manejador(jqXHR, textStatus, errorThrown, tipo){
	// error
	if(tipo == 'error'){
		if(jqXHR.status == 404){
			alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
		}else if(jqXHR.status == 0){
			alert('Se ha rechazado la conexión.');
		}else{
			alert(jqXHR.status+' '+errorThrown);
		}
	// fail
	}else{
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
	}
}	
	
	
<?php if ($busqueda == 1) {?>
document.getElementById('filtrar').focus();
<?php } ?>
$(document).ready(function(){
	setInterval(function(){ mantiene_session(); }, 1200000); // 20min
});
</script>
</body>
</html>