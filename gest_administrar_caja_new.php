<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "22";
require_once("includes/rsusuario.php");

//Verificamos si hay una caja abierta por este usuario
$buscar = "
Select * 
from caja_super 
where 
estado_caja=1 
and cajero=$idusu  
and tipocaja=1
order by fecha desc 
limit 1
";
$rscaj = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaj->fields['idcaja']);
$idcaja_compartida = intval($rscaj->fields['idcaja_compartida']);
$idnumeradorcab = intval($rscaj->fields['idnumeradorcab']);
// busca si el salon es una playa
if ($idsalon_usu > 0) {
    $consulta = "
	select idsalon, playa
	from salon 
	where 
	idsalon = $idsalon_usu
	";
    $rssalon = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $playa = trim($rssalon->fields['playa']);
}



if ($idcaja == 0) {
    //No existe un registro para la caja, por lo cual
    $fechahoy = date("Y-m-d");
    if (isset($_POST['fecha']) && ($_POST['fecha'] != '')) {
        $fecha = antisqlinyeccion($_POST['fecha'], 'date');
        $fechahoy = str_replace("'", "", $fecha);
    }
} else {

    // valida que sea la misma sucursal del cajero
    if (intval($rscaj->fields['sucursal']) != $idsucursal) {
        echo "Tu usuario tiene una caja abierta en otra sucursal, cierra primero esa caja antes de abrir otra.<br /> <a href='caja_cierre_forzar_cajero.php'>[Forzar Cierre]</a>";
        exit;
    }

    $consulta = "
		insert into caja_gestion
		(idcajaold, fecha_apertura, fechahora_apertura, estado, cajero, idsucursal, monto_apertura, monto_cierre, total_ingresos, total_egresos, faltante, sobrante, fecha_cierre, fechahora_cierre, idtipocaja)
		select $idcaja, fecha_apertura, fecha_apertura, 1, cajero, sucursal as idsucursal, monto_apertura, monto_cierre, 0 as total_ingresos, 0 as total_egresos, 0 as faltante, 0 as sobrante, NULL as fecha_cierre, NULL as fechahora_cierre, 1 as idtipocaja
		from caja_super
		where
		idcaja = $idcaja
		and idcaja not in (select idcajaold from caja_gestion where idcajaold is not null)
		";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}
/*-----------------------------Preferencias y tipo de impresoras-----------------------------------*/
$consulta = "SELECT * FROM preferencias WHERE  idempresa = $idempresa ";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$obligaprov = trim($rspref->fields['obligaprov']);
$impresor = trim($rspref->fields['script_ticket']);
$hab_monto_fijo_chica = trim($rspref->fields['hab_monto_fijo_chica']);
$hab_monto_fijo_recau = trim($rspref->fields['hab_monto_fijo_recau']);
$muestraventasciega = trim($rspref->fields['muestra_ventas_ciega']);
$usacajachica = trim($rspref->fields['usa_cajachica']);
$pagoxcajarec = trim($rspref->fields['pagoxcaja_rec']);
$pagoxcajachica = trim($rspref->fields['pagoxcaja_chic']);

$consulta = "SELECT * FROM preferencias_caja WHERE  idempresa = $idempresa ";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$caja_compartida = trim($rsprefcaj->fields['caja_compartida']);
$usar_turnos_caja = trim($rsprefcaj->fields['usa_turnos']);
$turno_automatico_caja = trim($rsprefcaj->fields['turno_automatico_caja']);

$arrastre_saldo_anterior = trim($rsprefcaj->fields['arrastre_saldo_anterior']);
$tipo_arrastre = trim($rsprefcaj->fields['tipo_arrastre']);
$cierre_caja_email = trim($rsprefcaj->fields['cierre_caja_mail']);

$consulta = "
select arrastre_caja_suc from sucursales where idsucu = $idsucursal limit 1
";
$rssucar = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if ($rssucar->fields['arrastre_caja_suc'] != "DEF") {
    if ($rssucar->fields['arrastre_caja_suc'] == "ACT") {
        $arrastre_saldo_anterior = 'S';
    }
    if ($rssucar->fields['arrastre_caja_suc'] == "INA") {
        $arrastre_saldo_anterior = 'N';
    }
}

//echo $arrastre_saldo_anterior;exit;
if ($arrastre_saldo_anterior == 'S') {
    // estirar saldo de la ultima caja cerrada de esa sucursal que aun no se haya utilizado
    $consulta = "
	select 
	idcaja, monto_cierre ,total_efectivo
	from caja_super 
	where 
	estado_caja = 3 
	and sucursal = $idsucursal 
	and idcaja not in (select idcaja_arrastre from caja_super where idcaja_arrastre is not null)
	and cajero not in (select idusu from usuarios where soporte =1) 
	order by idcaja desc 
	limit 1
	";
    $rscajant = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcaja_ant = intval($rscajant->fields['idcaja']);
    //con este id de caja traemos solo el efectivo

    $consulta = "
	select formas_pago.descripcion as formapago, sum(monto) as total
	from caja_arqueo_fpagos
	inner join formas_pago on formas_pago.idforma = caja_arqueo_fpagos.idformapago
	where
	caja_arqueo_fpagos.idcaja = $idcaja_ant
	and caja_arqueo_fpagos.estado <> 6 
	and formas_pago.idforma=1
	group by formas_pago.descripcion
	order by formas_pago.descripcion asc
	";
    $rsarq = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    //echo $consulta;exit;

    $monto_cierre_ant = floatval($rsarq->fields['total']);
}

if ($usar_turnos_caja == 'S') {
    $parametros_array_tur = [
        'hora_actual' => date("H:i:s"),
        'idsucursal' => $idsucursal
    ];
    $res_turno = obtener_turno($parametros_array_tur);
    $idturno = intval($res_turno['idturno']);
    if ($idturno == 0) {
        echo "No se puede abrir la caja por que no existen turnos registrados para el horario actual en esta sucursal.";
        exit;
    }
}

$script_impresora = $impresor;
$impresor = strtolower($impresor);
if ($impresor == '') {
    $impresor = 'http://localhost/impresorweb/ladocliente.php';
}
if ($hab_monto_fijo_chica == 'S' or $hab_monto_fijo_recau == 'S') {
    // montos de caja fijos
    $consulta = "
	SELECT *
	FROM usuarios
	where
	estado = 1
	and idempresa = $idempresa
	and usuarios.idusu = $idusu
	";
    $rsus = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $monto_fijo_chica = intval($rsus->fields['monto_fijo_chica']);
    $monto_fijo_recau = intval($rsus->fields['monto_fijo_recau']);
}
$buscar = "Select * from impresoratk where idsucursal=$idsucursal and idempresa=$idempresa limit 1";
$rsprint = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tipoimpre = trim($rsprint->fields['tipo_impresora']);
if ($tipoimpre == 'COC') {
    $enlace = 'impresora_selecciona.php';
} else {
    $enlace = 'impresora_selecciona_caja.php';
}
/*---------------------------------------------------------------------------------------------------*/



/*--------------------------------------------------POSTS---------------------------------------------*/
if (isset($_POST['occierrecaja'])) {
    $vcontrol = intval($_POST['occierrecaja']);
    if ($vcontrol > 0) {
        if (isset($_POST['MM_cierre']) && $_POST['MM_cierre'] == 'form_cierre') {
            require_once("caja_cerrar_nuevo.php");
        }
    }

}
//Deliverys no rendidos, aun algunos deben usar
$idpago = intval($_REQUEST['idpago']);
if ($idpago > 0) {

    $ahora = date("Y-m-d H:i:s");


    $consulta = "
		update gest_pagos
		set
			rendido='S',
			fec_rendido='$ahora'
		where
			cajero=$idusu  
			and estado=1 
			and idcaja=$idcaja 
			and rendido ='N'
			and idempresa = $idempresa
			and idpago = $idpago
		";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


}



/*
if(isset($_POST)){
    print_r($_POST);exit;
}*/
if (isset($_POST['montoaper']) && intval($_POST['montoaper']) >= 0) {
    //Verificamos si el parametro de arrstre esta activo, de estarlo, n puede haber una caja abierta.
    $buscar = "Select * from preferencias_caja limit 1";
    $rscajbal2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $continua = 'S';
    $arrastre_saldo_anterior = trim($rscajbal2->fields['arrastre_saldo_anterior']);
    $consulta = "
	select arrastre_caja_suc from sucursales where idsucu = $idsucursal limit 1
	";
    $rssucar = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rssucar->fields['arrastre_caja_suc'] != "DEF") {
        if ($rssucar->fields['arrastre_caja_suc'] == "ACT") {
            $arrastre_saldo_anterior = 'S';
        }
        if ($rssucar->fields['arrastre_caja_suc'] == "INA") {
            $arrastre_saldo_anterior = 'N';
        }
    }

    if ($arrastre_saldo_anterior == 'S') {
        $buscar = "
		Select * from caja_super 
		where 
		estado_caja <>6 
		and estado_caja <>3 
		and sucursal=$idsucursal 
		and cajero not in (select idusu from usuarios where soporte =1) 
		limit 1
		";

        $rscontrol = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        //echo $buscar;exit;
        if ($rscontrol->fields['idcaja'] != '') {
            $continua = 'N';
        }
    }
    if ($continua == 'S') {
        require_once("caja_abrir.php");
    } else {

        $errores .= "Ya existe una caja abierta (#".$rscontrol->fields['idcaja'].") en la sucursal actual, solo puede abrir una por local cuando esta activo el arrastre de saldos.";

    }

}





/*----------------------------------------------------------------------*/
//vemos si tiene permitido entregar o recibir plata
$buscar = "Select * from usuarios_autorizaciones where idusu=$idusu and estado =1 order by pkffresgs desc  limit 1";
$rsl = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$imprimetk = trim($rsl->fields['imprimetk']);
if ($imprimetk == 'S') {
    $valorcheck = 1;
} else {
    $valorcheck = 0;
}
//Billetes del sistema
$buscar = "Select * from gest_billetes order by idbillete asc";
$bille = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

//Tipos de moneda
$buscar = "Select * from tipo_moneda where estado=1 order by descripcion asc";
$moneda = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

//Lista de proveedores
$buscar = "Select * from proveedores where estado=1 and idempresa=$idempresa order by nombre asc";
$rspr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

//Formas de pago
$buscar = "select * from formas_pago where estado=1  and idforma > 1 order by descripcion asc";
$rsfp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


//Formas de pago
$buscar = "select * from gest_bancos where estado=1 order by descripcion asc";
$rsb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


//ultimos movimientos de la caja
$datos = [];
//Retiros(entrega de plata)desde el cajero al supervisor
$buscar = "Select regserialretira,monto_retirado,fecha_retiro,adm,
		(select usuario from usuarios where idusu=caja_retiros.retirado_por) as autorizacion
		from caja_retiros
		where idcaja=$idcaja and cajero=$idusu and estado=1";
$rsretiros = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//echo $buscar;
$tretiros = $rsretiros->RecordCount();
//echo 'llega';exit;
if ($tretiros > 0) {
    $i = 0;
    while (!$rsretiros->EOF) {

        $datos[$i] = [
        "regunico" => $rsretiros->fields['regserialretira'],
        "fecha" => $rsretiros->fields['fecha_retiro'],
        "monto" => $rsretiros->fields['monto_retirado'],
        "autorizado" => $rsretiros->fields['autorizacion'],
        "tipo" => "RETIRO VALORES",
        "clase" => 1,
        'adm' => $rsretiros->fields['adm']
        ];
        $i = $i + 1;

        $rsretiros->MoveNext();
    }



}

//Reposiciones de Dinero (desde el tesorero al cajero
$buscar = "Select  monto_recibido,regserialentrega,fecha_reposicion,adm,
(select usuario from usuarios where idusu=caja_reposiciones.entregado_por) as autorizacion
 from caja_reposiciones where idcaja=$idcaja and cajero=$idusu and estado=1";
$rsrepo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//echo $buscar;exit;
$trepo = $rsrepo->RecordCount();
if ($trepo > 0) {
    while (!$rsrepo->EOF) {
        $i = $i + 1;
        $datos[$i] = [
        "regunico" => $rsrepo->fields['regserialentrega'],
        "fecha" => $rsrepo->fields['fecha_reposicion'],
        "monto" => $rsrepo->fields['monto_recibido'],
        "autorizado" => $rsrepo->fields['autorizacion'],
        "tipo" => "RECEPCION VALORES",
         "clase" => 2,
         'adm' => $rsrepo->fields['adm']
        ];


        $rsrepo->MoveNext();
    }
}
//Pagos x caja
$buscar = "Select estado,unis,fecha,concepto,monto_abonado,(select nombre from proveedores 
where idempresa=$idempresa and idproveedor=pagos_extra.idprov)as provee,factura,anulado_el
,(select usuario from usuarios where idusu=pagos_extra.anulado_por) as quien
from pagos_extra where idusu=$idusu and idcaja=$idcaja and estado <> 6 order by fecha asc";
$rst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$td = $rst->RecordCount();
if ($td > 0) {
    while (!$rst->EOF) {
        $i = $i + 1;
        $datos[$i] = [
        "regunico" => $rst->fields['unis'],
         "fecha" => $rst->fields['fecha'],
        "monto" => $rst->fields['monto_abonado'],
        "autorizado" => $rst->fields['provee']." FC: ".$rst->fields['factura'],
        "tipo" => "PAGO X CAJA",
        "clase" => 3
        ];



        $rst->MoveNext();
    }

}



?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
<script>
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
function registrar_pagocaja(){
	var parametros = {
	  "idcaja"           : <?php echo $idcaja;  ?>,
	  "minip"            : $("#minip").val(),
	  "montopagoxcaja"   : $("#montopagoxcaja").val(),
	  "nfactu"           : $("#nfactu").val(),
	  "obspago"          : $("#obspago").val(),
	};
	$.ajax({		  
		data:  parametros,
		url:   'caja_new_registra_pago.php',
		type:  'post',
		cache: false,
		timeout: 5000,  // I chose 3 secs for kicks: 5000
		crossDomain: true,
		beforeSend: function () {
			//$("#monto_abonar").val('Cargando...');				
		},
		success:  function (response) {
			if(IsJsonString(response)){
				var obj = jQuery.parseJSON(response);
				if(obj.valido == 'S'){
					var unis = obj.unis;
					// imprimir
					document.location.href='inf_pagosxcaja_imp.php?redir=3&id='+unis;
				}else{
					//alert('Errores: '+obj.errores);	
					$("#error_box_pagocaj_msg").html(nl2br(obj.errores));
					$("#error_box_pagocaj").show();
				}
			}else{
				alert(response);	
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
		$idcaja=floatval($_POST['ocidcaja']);
		$dp=floatval($_POST['md']);
		$codigo=md5($_POST['codigoau']);
        $codigo=antisqlinyeccion($codigo,'clave');
		$obs=antisqlinyeccion($_POST['obs'],'text');
		$montoentrega=floatval($_POST['montogs']);   
		$copias=intval($_POST['canticopias']);
		
		


function registrar_retiro(){
	var parametros = {
	  "idcaja"            : <?php echo $idcaja;  ?>,
	  "montogs"           : $("#montogs").val(),
	  "obs"               : $("#obs").val(),
	  "prender"           : $("#prender").val(),
	  "canticopias"       : $("#canticopias").val(),
	  "codigoau"          : $("#codigoau").val(),
	};
	$.ajax({		  
		data:  parametros,
		url:   'caja_new_registra_retiro.php',
		type:  'post',
		cache: false,
		timeout: 5000,  // I chose 3 secs for kicks: 5000
		crossDomain: true,
		beforeSend: function () {
			//$("#monto_abonar").val('Cargando...');	
			$("#btn_recep").hide();
			$("#btn_retiro").hide();
		},
		success:  function (response) {
			if(IsJsonString(response)){
				var obj = jQuery.parseJSON(response);
				if(obj.valido == 'S'){
					var idretiro =  obj.idretiro;
					// imprimir tipo 1 es retiro r 2 es caja nueva
					document.location.href='caja_retiros_cajero_rei.php?tipo=1&r=2&regser='+idretiro;
				}else{
					//alert('Errores: '+obj.errores);	
					$("#error_box_retrep_msg").html(nl2br(obj.errores));
					$("#error_box_retrep").show();
					$("#btn_recep").show();
					$("#btn_retiro").show();
				}
			}else{
				alert(response);	
				$("#btn_recep").show();
				$("#btn_retiro").show();
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
function cargar_boletas_abrir(){
	var parametros = {
	  "idcaja"            : <?php echo $idcaja;  ?>
	};
	$.ajax({		  
		data:  parametros,
		url:   'caja_new_registra_boleta.php',
		type:  'post',
		cache: false,
		timeout: 5000,  // I chose 3 secs for kicks: 5000
		crossDomain: true,
		beforeSend: function () {
			$("#modal_cuerpov2").html("");
		},
		success:  function (response) {
			$("#modal_cuerpov2").html(response);
			$("#modpopv2").modal("show");
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
function eliminarunicop(idunico){
	var parametros = {
	  "chau"            : idunico
	};
	$.ajax({		  
		data:  parametros,
		url:   'caja_new_registra_boleta.php',
		type:  'post',
		cache: false,
		timeout: 5000,  // I chose 3 secs for kicks: 5000
		crossDomain: true,
		beforeSend: function () {
			$("#modal_cuerpov2").html("");
		},
		success:  function (response) {
			$("#modal_cuerpov2").html(response);
			$("#modpopv2").modal("show");
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
function agregarfpagoboleta(){
	var numboleta=$("#nboleta").val();
	var monto=$("#mglobal").val();
	var fpago=$("#formapagoli").val();
	var montofpago=$("#mfpago").val();
	var obs=$("#obs_local").val();
	var parametros = {
	  "idcaja"            : <?php echo $idcaja;  ?>,
	  "numboleta"         : numboleta,
	  "monto"             : monto,
	  "fpago"             : fpago,
	  "montofpago"        : montofpago,
	  "obs"          	  : obs,
	  "registrar"	      : 1
	};
	$.ajax({		  
		data:  parametros,
		url:   'caja_new_registra_boleta.php',
		type:  'post',
		cache: false,
		timeout: 5000,  // I chose 3 secs for kicks: 5000
		crossDomain: true,
		beforeSend: function () {
			$("#modal_cuerpov2").html("");			
		},
		success:  function (response) {
			$("#modal_cuerpov2").html(response);
			
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
function registrar_recepcion(){
	var parametros = {
	  "idcaja"            : <?php echo $idcaja;  ?>,
	  "montogs"           : $("#montogs").val(),
	  "obs"               : $("#obs").val(),
	  "prender"           : $("#prender").val(),
	  "canticopias"       : $("#canticopias").val(),
	  "codigoau"          : $("#codigoau").val(),
	};
	$.ajax({		  
		data:  parametros,
		url:   'caja_new_registra_recepcion.php',
		type:  'post',
		cache: false,
		timeout: 5000,  // I chose 3 secs for kicks: 5000
		crossDomain: true,
		beforeSend: function () {
			$("#btn_recep").hide();
			$("#btn_retiro").hide();			
		},
		success:  function (response) {
			if(IsJsonString(response)){
				var obj = jQuery.parseJSON(response);
				if(obj.valido == 'S'){
					var idrecepcion =  obj.idrecepcion;
					// imprimir tipo 2 es recepcion r 2 es caja nueva
					document.location.href='caja_retiros_cajero_rei.php?tipo=2&r=2&regser='+idrecepcion;
				}else{
					//alert('Errores: '+obj.errores);	
					$("#error_box_retrep_msg").html(nl2br(obj.errores));
					$("#error_box_retrep").show();
					$("#btn_recep").show();
					$("#btn_retiro").show();	
				}
			}else{
				alert(response);
				$("#btn_recep").show();
				$("#btn_retiro").show();	
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
function nl2br (str, is_xhtml) {
  var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>'; 

  return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}
</script>
  </head>

  <body class="nav-md" onLoad="<?php if ($retirado == 1) {?>imprime_retiro();<?php } ?>">
    <div class="container body">
      <div class="main_container">
        <?php require_once("includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("includes/lic_gen.php");?>
             <!-- SECCION -->
			 <?php if ($idcaja == 0) { ?>
				<div class="row">
				  <div class="col-md-12 col-sm-12 col-xs-12">
					<div class="x_panel">
						<div class="x_title">
							<span style="text-align:center; color:#000000;"><h2>Hola <span class="fa fa-user"></span>&nbsp;<?php echo $cajero; ?>, est&aacute;s administrando tu caja de gesti&oacute;n</h2></span>
							<ul class="nav navbar-right panel_toolbox">
							<li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
							</li>
							</ul>
						<div class="clearfix"></div>
						</div>
						<div class="x_content">
<?php if ($rsco->fields['caja_nueva'] != 'S') { ?>
<div class="alert alert-warning alert-dismissible fade in" role="alert">
<strong>Deseas ver el dise&ntilde;o antiguo?</strong><br />
<a href="gest_administrar_caja.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Modulo Antiguo</a><br />
Este aviso solo estara disponible por unos dias, luego ya no podras utilizar el dise&ntilde;o antiguo.<br />
</div>
<?php } ?>
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
								<h2>Tu caja está cerrada, por favor, indica los montos y realiza tu apertura.</h2>
								<form id="form1" name="form1" method="post" action="">
								<hr />
								
								<div class="col-md-12 col-sm-12 form-group" style="text-align:center;">
										
											<h1><span class="fa fa-calendar"></span>&nbsp;<?php echo date("d/m/Y", strtotime($fechahoy)); ?></h1>                   
										
								</div>
<?php
// preferencias
$consulta = "SELECT * FROM preferencias WHERE  idempresa = $idempresa ";
			     $rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
			     $obligaprov = trim($rspref->fields['obligaprov']);
			     $impresor = trim($rspref->fields['script_ticket']);
			     $hab_monto_fijo_chica = trim($rspref->fields['hab_monto_fijo_chica']);
			     $hab_monto_fijo_recau = trim($rspref->fields['hab_monto_fijo_recau']);
			     $muestraventasciega = trim($rspref->fields['muestra_ventas_ciega']);
			     $usacajachica = trim($rspref->fields['usa_cajachica']);

			     if ($hab_monto_fijo_chica == 'S' or $hab_monto_fijo_recau == 'S') {
			         // montos de caja fijos
			         $consulta = "
	SELECT *
	FROM usuarios
	where
	estado = 1
	and usuarios.idusu = $idusu
	";
			         $rsus = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
			         $monto_fijo_chica = intval($rsus->fields['monto_fijo_chica']);
			         $monto_fijo_recau = intval($rsus->fields['monto_fijo_recau']);
			     }

			     if ($arrastre_saldo_anterior == 'S') {
			         $monto_fijo_recau = $monto_cierre_ant;
			         $hab_monto_fijo_recau = 'S';
			     }

			     ?>
								<div class="clearfix"></div>
								
								<?php
			                                     if ($arrastre_saldo == 'S') {
			                                         if ($tipo_arrastre == 1) {
			                                             // es de caja simple o la ultima siempre
			                                             $buscar = "Select total_global_gs from caja_super where estado_caja=3 order by idcaja desc limit 1 ";
			                                             $rstcaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
			                                             $tanteriorcaja = floatval($rstcaja->fields['total_global_gs']);

			                                         }
			                                         if ($tipo_arrastre == 2) {
			                                             // es de caja multiple, mostramos los anteriores para seleccionar
			                                             $buscar = "Select total_global_gs from caja_super where estado_caja=3 order by idcaja desc limit 1 ";
			                                             $rstcaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
			                                         }
			                                     }
			     ?>
								
								
								<div class="col-md-6 col-sm-12 form-group">
									<label class="control-label col-md-3 col-sm-3 col-xs-12">Monto Apertura (Recaudaci&oacute;n)</label>
									<div class="col-md-9 col-sm-9 col-xs-12">
									<input type="text" id="montoaper" name="montoaper" class="form-control" required="required" onkeypress="return validar(event,'numero')"  onchange="this.value = get_numbers(this.value)" value="<?php echo $monto_fijo_recau; ?>" <?php if ($hab_monto_fijo_recau == 'S') { ?>readonly="readonly" style="display:none;"<?php } ?>
									value="<?php echo $tanteriorcaja ?>" /><?php
                      if ($hab_monto_fijo_recau == 'S') {
                          echo formatomoneda($monto_fijo_recau, 2, 'N')."<br /> (Monto bloqueado por la Administracion)";
                      }
			     ?>             
									</div>
								</div>
									 <?php if ($usacajachica == 'S') { ?>
									<div class="col-md-6 col-sm-12 form-group">
										<label class="control-label col-md-3 col-sm-3 col-xs-12">Monto Apetura (Caja Chica)</label>
										<div class="col-md-9 col-sm-9 col-xs-12">
											<input type="text" name="recauda" id="recauda" value="<?php echo $monto_fijo_chica; ?>" required="required" placeholder="" class="form-control" <?php if ($hab_monto_fijo_chica == 'S') { ?>readonly="readonly" style="display:none;"<?php } ?>><?php
			             if ($hab_monto_fijo_chica == 'S') {
			                 echo formatomoneda($monto_fijo_chica, 2, 'N')."<br /> (Monto bloqueado por la Administracion)";
			             }
									     ?>  
										</div>
									</div>
									 <?php } ?>
									
									
									<?php if ($caja_compartida == 'S') { ?>
										<div class="col-md-6 col-sm-6 form-group">
											<label class="control-label col-md-3 col-sm-3 col-xs-12">Caja Compartida </label>
											<div class="col-md-9 col-sm-9 col-xs-12">
											<?php
									                   $autosel_1registro = "N";
									    if ($playa == 'S') {
									        $whereadd_compart = "
												and caja_super.idsalon_caja =  $idsalon_usu
												";
									        $autosel_1registro = "S";
									    }
									    // consulta
									    $consulta = "
											SELECT idcaja, CONCAT('Idcaja: ',idcaja,' | Fecha: ',date_format(fecha,\"%d/%m/%Y\"),' | Cajero: ',(select usuario from usuarios where idusu = caja_super.cajero),' [',cajero,']') as nombre
											FROM caja_super
											where
											estado_caja = 1
											and sucursal = $idsucursal
											and idcaja_compartida is null
											$whereadd_compart
											order by nombre asc
											 ";

									    // valor seleccionado
									    if (isset($_POST['idcaja_compartida'])) {
									        $value_selected = htmlentities($_POST['idcaja_compartida']);
									    } else {
									        $value_selected = "";
									    }

									    // parametros
									    $parametros_array = [
									        'nombre_campo' => 'idcaja_compartida',
									        'id_campo' => 'idcaja_compartida',

									        'nombre_campo_bd' => 'nombre',
									        'id_campo_bd' => 'idcaja',

									        'value_selected' => $value_selected,

									        'pricampo_name' => 'Seleccionar...',
									        'pricampo_value' => '',
									        'style_input' => 'class="form-control"',
									        'acciones' => '  ',
									        'autosel_1registro' => $autosel_1registro

									    ];

									    // construye campo
									    echo campo_select($consulta, $parametros_array);
									    ?>
											</div>
										</div>


									<?php } ?>
									<?php if ($usar_turnos_caja == 'S') { ?>
									<div class="col-md-6 col-sm-6 form-group">
											<label class="control-label col-md-3 col-sm-3 col-xs-12">Turno</label>
											<div class="col-md-9 col-sm-9 col-xs-12">
											<?php

                                            $parametros_array_tur = [
									       'hora_actual' => date("H:i:s"),
									       'idsucursal' => $idsucursal
                                            ];
									    $res_turno = obtener_turno($parametros_array_tur);
									    $idturno = intval($res_turno['idturno']);
									    $whereaddtur = '';
									    $autosel_1registro = 'N';
									    if ($turno_automatico_caja == 'S') {
									        $whereaddtur = " and idturno = ".$idturno;
									        $autosel_1registro = 'S';
									    }
									    // consulta
									    $consulta = "
											SELECT idturno,CONCAT(descripcion,' -> De : ',hora_desde,' | A: ',hora_hasta) as descripcion
											FROM turnos
											where
											estado = 1
											and idsucursal = $idsucursal
											$whereaddtur
											order by descripcion asc
											 ";

									    // valor seleccionado
									    if (isset($_POST['idturno'])) {
									        $value_selected = htmlentities($_POST['idturno']);
									    } else {
									        $value_selected = $idturno;
									    }

									    // parametros
									    $parametros_array = [
									        'nombre_campo' => 'idturno',
									        'id_campo' => 'idturno',

									        'nombre_campo_bd' => 'descripcion',
									        'id_campo_bd' => 'idturno',

									        'value_selected' => $value_selected,

									        'pricampo_name' => 'Seleccionar...',
									        'pricampo_value' => '',
									        'style_input' => 'class="form-control"',
									        'acciones' => '  ',
									        'autosel_1registro' => $autosel_1registro

									    ];

									    // construye campo
									    echo campo_select($consulta, $parametros_array);
									    ?>
											</div>
										</div>
									
									<?php } ?>
									<div class="clearfix"></div>
									<br>

										<div class="form-group">
											<div class="col-md-12 col-xs-12" style="text-align:center">
											
										   <!--<button type="submit" class="btn btn-success"><span class="fa fa-check-square-o"></span> Registrar</button>-->
										   <?php
									       //21/05/2022:  CONTROL DE PICOS TEMPORAL: HASTA HACER CAJA PARA PLAYA

									               if ($usar_turnos_caja == 'S') {
									                   //verificamos si los  picos ya estan abiertos
									                   if ($playa == 'S') {
									                       $buscar = "select count(idpico) as total from combustibles_numeradores where estado=1";
									                       $rs1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
									                       $totalreg = intval($rs1->fields['total']);
									                       // busca hay numeradores abiertos
									                       $consulta = "
																select * 
																from combustibles_numeradores_cab
																where
																idsucursal = $idsucursal
																and estado = 1
																";
									                       $rsnumtand = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
									                       $idnumeradorcab = intval($rsnumtand->fields['idnumeradorcab']);


									                       //si no existen picos abiertos,nopuede abrir
									                       if ($idnumeradorcab == 0) {
									                           ?>
																	<div class="alert alert-danger alert-dismissible " role="alert"><span class="warning">Los numeradores no est&aacute;n cargados, la caja no podr&aacute; ser abierta.</span></div>
																	<?php
									                       } else {
									                           ?>
																	<button type="submit" class="btn btn-success"><span class="fa fa-check-square-o"></span> Registrar</button>
																	<?php
									                       }
									                   } else {
									                       ?>
																<button type="submit" class="btn btn-success"><span class="fa fa-check-square-o"></span> Registrar</button>
																<?php
									                   }
									               } else {

									                   ?>
															<button type="submit" class="btn btn-success"><span class="fa fa-check-square-o"></span> Registrar</button>
															<?php
									               }



			     //21/05/2022:  CONTROL DE PICOS TEMPORAL: HASTA HACER CAJA PARA PLAYA-----------*//
			     ?>
										   <?php //echo $errorapertura?>
										   </div>
										   
										   
										   
											
										</div>

									  <input type="hidden" name="MM_insert" value="form1">
											  <input type="hidden" name="abrir" id="abrir" value="1" />
										<input type="hidden" name="selefe" id="selefe" value="<?php echo $fechahoy ?>" />	
									<br>
									</form>
									<div class="clearfix"></div>
									<br><br>
			 
						</div>
				  </div>
				</div>
			 
			 </div>
			 <?php } else { ?>
				<div class="row"><!--------------style="width:65%;"-------->
				 
					  <div class="col-md-12 col-sm-12 col-xs-12" >
						<div class="x_panel">
							<div class="x_title">
								<span style="text-align:center; color:#000000;"><h2>Hola <span class="fa fa-user"></span>&nbsp;<?php echo $cajero; ?>, est&aacute;s administrando tu caja de gesti&oacute;n. Id : <?php echo $idcaja ?></h2></span>
								<ul class="nav navbar-right panel_toolbox">
								<li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
								</li>
								</ul>
							<div class="clearfix"></div>
							</div>
							<div class="x_content">
<?php if ($rsco->fields['caja_nueva'] != 'S') { ?>
<div class="alert alert-warning alert-dismissible fade in" role="alert">
<strong>Deseas ver el dise&ntilde;o antiguo?</strong><br />
<a href="gest_administrar_caja.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Modulo Antiguo</a><br />
Este aviso solo estara disponible por unos dias, luego ya no podras utilizar el dise&ntilde;o antiguo.<br />
</div>
<?php } ?>
<?php if (intval($idcaja_compartida) == 0) { ?>


                            
                            
								<div class="col-md-4 col-sm-12 col-xs-12">
											<div class="x_panel">
												<div class="x_title">
													<h2>Arqueo : Moneda Nacional <small></small></h2>
													<ul class="nav navbar-right panel_toolbox">
														<li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
													
													</ul>
													<div class="clearfix"></div>
												</div>
												<div class="x_content">
												<div class="alert alert-danger alert-dismissible fade in" id="alertamn" style="display:none" role="alert">
														<strong><span class="fa fa-warning"></span>&nbsp;Errores encontrados:</strong><br />
														 <span id="textomo"></span>
													</div>
													<div class="col-md-12 col-sm-12  form-group has-feedback">
														<select name="tipobillete" id="tipobillete" class="form-control">
															<option value="">Billete</option>
															 <?php while (!$bille->EOF) {?>
															   <option value="<?php echo $bille->fields['idbillete']; ?>"><?php echo formatomoneda($bille->fields['valor']);
															     if (trim($bille->fields['leyenda_billete']) != '') {
															         echo ' ('.antixss($bille->fields['leyenda_billete']).')';
															     }  ?></option>
															   <?php $bille->MoveNext();
															 }?>
																													 
														</select>
													</div>
													<div class="col-md-12 col-sm-12  form-group has-feedback">
														<input type="text" class="form-control has-feedback-left" name="cantidadbilletes" id="cantidadbilletes" placeholder="Cantidad Billetes/Monedas" required>
														<span class="fa fa-asterisk form-control-feedback left" aria-hidden="true"></span>
													</div>
													<div class="clearfix"></div>
													<div class="col-md-12 col-sm-12  form-group has-feedback">
														<input type="text" class="form-control has-feedback-left" name="obsbille" id="obsbille" placeholder="Comentario/obs" required>
														<span class="fa fa-comment form-control-feedback left" aria-hidden="true"></span>
													</div>
													<div class="clearfix"></div>
													<div class="col-md-12 col-sm-12 col-xs-12 text-center">
														<button type="button" onClick="agregabb();" class="btn btn-default"><span class="fa fa-plus"></span> Agregar</button>
														
													</div>
													
												</div>
												<!-------------------X CONTENT---------->
											</div>
											<!-------------------X PANEL---------->
								</div>
								<div class="col-md-4 col-sm-12 col-xs-12">
											<div class="x_panel">
												<div class="x_title">
													<h2>Arqueo : Moneda Extranjera<small></small></h2>
													<ul class="nav navbar-right panel_toolbox">
														<li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
													
													</ul>
													<div class="clearfix"></div>
												</div>
												<div class="x_content">
													<div class="alert alert-danger alert-dismissible fade in" id="alertaex" style="display:none" role="alert">
														<strong><span class="fa fa-warning"></span>&nbsp;Errores encontrados:</strong><br />
														 <span id="textoex"></span>
													</div>
														<div class="col-md-12 col-sm-12  form-group has-feedback">
															<select name="moneda" id="moneda" class="form-control" onchange="carga_cotizacion(this.value);">
															<option value="" selected="selected">Seleccionar</option>
															<?php while (!$moneda->EOF) {?>
															<option value="<?php echo $moneda->fields['idtipo'] ?>"><?php echo $moneda->fields['descripcion'] ?></option>
															<?php $moneda->MoveNext();
															}?>
															</select>
														</div>
														<div class="col-md-12 col-sm-12  form-group has-feedback">
															<input type="text" class="form-control has-feedback-left" name="cantidadmonedaex" id="cantidadmonedaex" placeholder="Monto" required>
															<span class="fa fa-money form-control-feedback left" aria-hidden="true"></span>
														</div>
														<div class="clearfix"></div>
														<div class="col-md-12 col-sm-12  form-group has-feedback" id="recargacotizacion">
															<?php require_once('mini_carga_coti_ven.php'); ?>
														</div>
														<div class="clearfix"></div>
													<div class="col-md-12 col-sm-12 col-xs-12 text-center">
														<button type="button" onclick="agregabbm();" class="btn btn-default"><span class="fa fa-plus"></span> Agregar</button>
														
													</div>
												</div>
												<!-------------------X CONTENT---------->
											</div>
											<!-------------------X PANEL---------->
								</div>
								<div class="col-md-4 col-sm-12 col-xs-12">
											<div class="x_panel">
												<div class="x_title">
													<h2>Arqueo : Otros Valores <small></small></h2>
													<ul class="nav navbar-right panel_toolbox">
														<li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
													
													</ul>
													<div class="clearfix"></div>
												</div>
												<div class="x_content">
												<div class="alert alert-danger alert-dismissible fade in" id="alertaotv" style="display:none" role="alert">
														<strong><span class="fa fa-warning"></span>&nbsp;Errores encontrados:</strong><br />
														 <span id="textootros"></span>
													</div>
														<div class="col-md-12 col-sm-12  form-group has-feedback">
															<select name="fpago" id="fpago" class="form-control" onchange="">
															<option value="" selected="selected">Medio Pago</option>
															<?php while (!$rsfp->EOF) {?>
															<option value="<?php echo $rsfp->fields['idforma'] ?>"><?php echo $rsfp->fields['descripcion'] ?></option>
															<?php $rsfp->MoveNext();
															}?>
															</select>
														</div>
														<div class="col-md-12 col-sm-12  form-group has-feedback">
															<input type="text" class="form-control has-feedback-left" name="otv" id="otv" placeholder="Monto" required>
															<span class="fa fa-money form-control-feedback left" aria-hidden="true"></span>
														</div>
														<div class="clearfix"></div>
														<div class="col-md-12 col-sm-12  form-group has-feedback">
															<select name="selectbanco" id="selectbanco" class="form-control" onchange="">
															<option value="0" selected="selected">Banco</option>
															<?php while (!$rsb->EOF) {?>
															<option value="<?php echo $rsb->fields['banco'] ?>"><?php echo $rsb->fields['descripcion'] ?></option>
															<?php $rsb->MoveNext();
															}?>
															</select>
														</div>
														<div class="col-md-12 col-sm-12  form-group has-feedback">
															<input type="text" class="form-control has-feedback-left" name="numcompro" id="numcompro" placeholder="Otros" required>
															<span class="fa fa-edit form-control-feedback left" aria-hidden="true"></span>
														</div>
														<div class="clearfix"></div>
													<div class="col-md-12 col-sm-12 col-xs-12 text-center">
														<button type="button" onclick="otrosvalores();" class="btn btn-default"><span class="fa fa-plus"></span> Agregar</button>
														
													</div>
												</div>
												<!-------------------X CONTENT---------->
											</div>
											<!-------------------X PANEL---------->
								</div>
								<?php } // if(intval($idcaja_compartida) == 0){?>
							</div>
						</div>
					  </div>
				  
					 
				 
				</div>
<?php if (intval($idcaja_compartida) == 0) { ?>
				<div class="row">
				  <div class="col-md-12 col-sm-12 col-xs-12">
					<div class="x_panel">
					  <div class="x_title">
						<span style="text-align:center; color:#000000;"><h2><span class="fa fa-line-chart"></span>&nbsp; Caja Recaudaci&oacute;n: Resumen Arqueo</h2></span>
						<ul class="nav navbar-right panel_toolbox">
						  <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
						  </li>
						</ul>
						<div class="clearfix"></div>
					  </div>
					  <div class="x_content">
						
					  
						   <div class="col-md-12 col-sm-12 col-xs-12" id="resumendearqueos">
									
									<?php require_once("caja_mini_arqueo.php"); ?>
								
								
									
						  </div>
						  
					  </div>
					</div>
				  </div>
				</div>
				  
				<!-- SECCION -->
				<div class="row">
				  <div class="col-md-12 col-sm-12 col-xs-12">
					<div class="x_panel">
					  <div class="x_title">
						<span style="text-align:center; color:#000000;"><h2> <span class="fa fa-money"></span>&nbsp;Operaciones sobre valores.</h2></span>
						<ul class="nav navbar-right panel_toolbox">
						  <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
						  </li>
						</ul>
						<div class="clearfix"></div>
					  </div>
					  <div class="x_content">

							
							<?php if ($idcaja > 0) { ?>
								<div class="row">
									<div class="col-md-6">
										<div class="x_panel">
											<div class="x_title">
												<h2>Retirar / recibir dinero </h2>

												<div class="clearfix"></div>
											</div>
											<div class="x_content">

    <div class="alert alert-danger alert-dismissible fade in" role="alert" id="error_box_retrep" style="display:none;">
    <strong>Errores:</strong><br /><span id="error_box_retrep_msg"><?php echo $errores; ?></span>
    </div>



												<div class="col-md-12 col-sm-12  form-group has-feedback">
													<input type="text" class="form-control has-feedback-left" id="montogs" name="montogs" required placeholder="Monto">
													<span class="fa fa-money form-control-feedback left" aria-hidden="true"></span>
												</div>

												<div class="col-md-12 col-sm-12  form-group has-feedback">
													<input type="text" class="form-control has-feedback-left" id="obs" name="obs"  placeholder="Observaciones">
													<span class="fa fa-comment form-control-feedback left" aria-hidden="true"></span>
												</div>
												<?php if ($imprimetk == 'S') { ?>
                          <div class="">
                            <label>
                              <input name="prender" id="prender" type="checkbox" value="<?php echo $valorcheck ?>" class="js-switch form-control" <?php if ($valorcheck == 1) { ?>checked="checked" <?php } ?>> &nbsp;Imprimir
                            </label>
                          </div>
                                                
        <div class="col-md-12 col-sm-12 form-group">
            <label class="control-label col-md-5 col-sm-5 col-xs-12">Cant. Copias:</label>
            <div class="col-md-7 col-sm-7 col-xs-12">
            <input type="number" value="1" class="form-control " id="canticopias" name="canticopias" placeholder="Copias" max="3" min="1">                    
            </div>
        </div>

													<div class="col-md-12 col-sm-12  form-group has-feedback">
															
													</div>

												<?php } ?>
												<div class="col-md-12 col-sm-12  form-group has-feedback">
													<input type="password" class="form-control has-feedback-left" id="codigoau" name="codigoau" placeholder="Codigo autorizacion">
													<span class="fa fa-key form-control-feedback left" aria-hidden="true"></span>
												</div>
												<div class="clearfix"></div>
												<div class="col-md-12 col-sm-12 col-xs-12 text-center">
													<button type="button" onclick="registrar_retiro();" id="btn_retiro" class="btn btn-default"><span class="fa fa-level-up"></span> Retirar </button>
													<button type="button" onclick="registrar_recepcion();" id="btn_recep" class="btn btn-default"><span class="fa fa-level-down"></span> Recibir </button>
												</div>

											</div>
											<!-------------------X CONTENT---------->
										</div>
										<!-------------------X PANEL---------->
									</div>
									<!-------------------COL---------->
									<!-----------------------------------------------ROW-------------------------->
									<div class="col-md-6">
										<div class="x_panel">
											<div class="x_title">
												<h2>Pagos por caja <small></small></h2>
	
												<div class="clearfix"></div>
											</div>
											<div class="x_content">

    <div class="alert alert-danger alert-dismissible fade in" role="alert" id="error_box_pagocaj" style="display:none;">
    <strong>Errores:</strong><br /><span id="error_box_pagocaj_msg"><?php echo $errores; ?></span>
    </div>

											 <input type="hidden" name="ocidcajac" id="ocidcajac" value="<?php echo $idcaja?>" />
											  <input type="hidden" name="cual_pago" id="cual_pago" value="5" />
											  <input type="hidden" name="mdc" id="mdc" value="<?php echo $dispo?>" />
											 <?php  if ($pagoxcajarec == 'S' or $pagoxcajachica == 'S') { ?>
												<div class="col-md-12 col-sm-12  form-group has-feedback">
													<input type="text" class="form-control has-feedback-left" name="montopagoxcaja" id="montopagoxcaja" placeholder="Monto" required>
													<span class="fa fa-money form-control-feedback left" aria-hidden="true"></span>
												</div>
												<div class="col-md-12 col-sm-12  form-group has-feedback">
													<select name="minip" id="minip" class="form-control">
														<option value="">Proveedor</option>
														<?php while (!$rspr->EOF) {?>
														 <option value="<?php echo $rspr->fields['idproveedor']?>"><?php echo $rspr->fields['nombre']?></option>
														<?php $rspr->MoveNext();
														}?>
													</select>
												</div>
												<div class="col-md-12 col-sm-12  form-group has-feedback">
													<input type="text" name="nfactu" id="nfactu" class="form-control has-feedback-left" placeholder="Factura num">
													<span class="fa fa-asterisk form-control-feedback left" aria-hidden="true"></span>
												</div>
												<div class="col-md-12 col-sm-12  form-group has-feedback">
													<input type="text" class="form-control has-feedback-left" name="obspago" id="obspago" placeholder="Observaciones">
													<span class="fa fa-comment form-control-feedback left" aria-hidden="true"></span>
												</div>
												
												<div class="clearfix"></div>
												<div class="col-md-12 col-sm-12 col-xs-12 text-center">
													<button type="button" class="btn btn-default" onMouseUp="registrar_pagocaja();"><span class="fa fa-plus"></span> Agregar</button>
													
												</div>
											 <?php } ?>
											
											</div>
											<!-------------------X CONTENT---------->
										</div>
										<!-------------------X PANEL---------->
									</div>
								</div>
                                
                                <a name="ultcajamov"></a>
                                <div class="row">
                                  <div class="col-md-12 col-sm-12 col-xs-12">
                                    <div class="x_panel">
                                      <div class="x_title">
                                        <h2>Ultimos movimientos de la caja</h2>
                                        <ul class="nav navbar-right panel_toolbox">
                                          <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                          </li>
                                        </ul>
                                        <div class="clearfix"></div>
                                      </div>
                                      <div class="x_content" id="ultmovcaj_box">
                                            <?php require_once("caja_ultmov.php");?>
                                            
                                       </div>
                                    </div>
                                  </div>
                                </div>
							
							
							
							
							
							<?php }//de caja >0?>
					  </div>
					</div>
				  </div>
				</div>

                
				<div class="row">
				  <div class="col-md-12 col-sm-12 col-xs-12">
					<div class="x_panel">
                      <div class="x_title">
                        <h2>Verificar y cerrar la Caja</h2>
                        <ul class="nav navbar-right panel_toolbox">
                          <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                          </li>
                        </ul>
                        <div class="clearfix"></div>
                      </div>
					  <div class="x_content" id="contenidobalance">
							<?php require_once("caja_balance_final.php"); ?>
							
					   </div>
					</div>
				  </div>
				</div>
				<!-- SECCION --> 
			  
<?php } else { //if(intval($idcaja_compartida) == 0){?>
				<!-- SECCION -->   
				<div class="row">
				  <div class="col-md-12 col-sm-12 col-xs-12">
					<div class="x_panel">
                      <div class="x_title">
                        <h2>Caja Compartida</h2>
                        <div class="clearfix"></div>
                      </div>
					  <div class="x_content" id="contenidobalance">
						Estas usando Caja compartida, tu caja se cerrara automaticamente cuando la principal se cierre, pero si la abriste por error puedes cerrarla aqui.
						  
						  <br />  <br />  <br />
					   <button type="cierreform" class="btn btn-success" onclick="mostrarcuadro(2);"><span class="fa fa-sign-out"></span> Cerrar Caja</button>

							<form id="cierreform" action="" method="post">
								<input type="hidden" value="<?php echo $idcaja; ?>" name="occierrecaja" id="occierrecaja" />
								<input type="hidden" name="MM_cierre" value="form_cierre" />
							</form>
						  
						  
					   </div>
					</div>
				  </div>
				</div>
				<!-- SECCION -->   
<?php } ?>
<?php } ?>
				  <!-- /POPUP -->  
            <div class="modal fade" id="modpop"  role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered" role="document">
					<div class="modal-content">
						<div class="modal-header"><!-- Modal Header -->
							<div class="alert alert-danger alert-dismissible fade in" role="alert" id="errorescod" style="display: none">
								<strong>Errores:</strong><br /><span id="errorescodcuerpo"></span>
							</div>
							<span  id="modal_titulo" style="font-weight:bold;"></span>
							<button type="button" class="close" data-dismiss="modal">&times;</button>
							<input type="hidden" name="octipo" id="octipo" value="" />
							<input type="hidden" name="ocvalor" id="ocvalor" value="" />
						</div>

						<!-- Modal body style="height: 500px; overflow-y: scroll"-->
						<div class="modal-body" id="modal_cuerpo" >
							<div align="center">
								<span id="cuerpodinamico"></span>
							</div>
						</div>
						<!-- Modal footer -->
						<div class="modal-footer">
							<div id="conj" style="display:display;">
								<button type="button" class="btn btn-default" onclick="continuar(2);" data-dismiss="modal">Mejor no</button>
								<button type="button" class="btn btn-danger" onclick="continuar(1);">SI, POR FAVOR</button>
								<span  id="controlcito" style="display: none"></span>
							</div>
							<div id="conj2" style="display:none;">
								<button type="button" class="btn btn-default" onclick="cerrar(2);" data-dismiss="modal">Mejor no</button>
								<button type="button" class="btn btn-danger" onclick="cerrar(1);">SI, POR FAVOR</button>
								<span  id="controlcito" style="display: none"></span>
							</div>
						</div>
					</div>
				</div>
			</div>
			 <div class="modal fade" id="modpopv2"  role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered" role="document">
					<div class="modal-content">
						<div class="modal-header"><!-- Modal Header -->
							<div class="alert alert-danger alert-dismissible fade in" role="alert" id="errorescodv2" style="display: none">
								<strong>Errores:</strong><br /><span id="errorescodcuerpo"></span>
							</div>
							<span  id="modal_titulov2" style="font-weight:bold;"></span>
							
						</div>

						<!-- Modal body style="height: 500px; overflow-y: scroll"-->
						<div class="modal-body" id="modal_cuerpov2" >
							<div align="center">
								
							</div>
						</div>
						<!-- Modal footer -->
						<div class="modal-footer">
							
							<div id="conj2v2" style="display:none;">
								<button type="button" class="btn btn-default" onclick="cerrarv2(2);" data-dismiss="modal">Mejor no</button>
								<button type="button" class="btn btn-danger" onclick="cerrarv2(1);">SI, POR FAVOR</button>
								<span  id="controlcito" style="display: none"></span>
							</div>
						</div>
					</div>
				</div>
			</div>
			 <!-- /impresor de ticketes - contenedor -->
            <div  id="impresion_box" hidden="hidden"><textarea readonly id="texto" style="display:none; width:310px; height:500px;" ><?php echo $texto; ?></textarea></div><br />
			 <!-- /para almacenar u borrar los billetes y valores de arqueo -->
			  <div  id="recarga_local" hidden="hidden"></div>
			 
			 
		  </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("includes/pie_gen.php"); ?>
		<script>
		
		//---------------------------------------------Funciones para arqueo--------------------------//
		function agregabb(){//Agregar  los billetes
			$("#alertamn").hide();
			var errores="";
			var billete=$("#tipobillete").val();
			
			if (billete==""){
				errores=errores+"Debe indicar tipo de billete.<br />";
			}
			
			var cantidad=$("#cantidadbilletes").val();
			if (cantidad=='' || cantidad=='0'){
				errores=errores+"Debe indicar cantidad de monedas/billetes.<br />";
			}
			var sidc=<?php echo $idcaja?>;
			
			if (errores==''){
			
				var parametros = {
					"billete" : billete,
					"cantidad" : cantidad,
					"idcaja"	: sidc,
					"tipo"		: 1
				};
				 $.ajax({
						data:  parametros,
						url:   'caja_mini_arqueo.php',
						type:  'post',
						dataType: 'html',
						beforeSend: function () {
							$("#resumendearqueos").html("Registrando...");
						},
						crossDomain: true,
						success:  function (response) {
							$("#resumendearqueos").html(response);		
							setTimeout(function(){ refrescar_balance(sidc); }, 500);
						}
					});
			}else {
				$("#textomo").html(errores);
				$("#alertamn").show();
				
			}
		}
		
		function agregabbm(){//Agregar Monedas Extranjeras
			$("#alertaex").hide();
			var errores="";
			var moneda=$("#moneda").val();
			
			if (moneda==""){
				errores=errores+"Debe indicar tipo de moneda extranjera.<br />";
			}
			var cantidad=$("#cantidadmonedaex").val();
			if (cantidad=='' || cantidad=='0'){
				errores=errores+"Debe indicar cantidad de monedas/billetes.<br />";
			}
			var cotizacion=$("#cotiza").val();
			if (cotizacion==""){
				errores=errores+"Debe indicar cotizacion del dia.<br />";
			}
			var sidc=<?php echo $idcaja?>;
			if (errores==''){
				var parametros = {
					"moneda" : moneda,
					"cantidad" : cantidad,
					"idcaja"	: sidc,
					"cotizacion" : cotizacion,
					"tipo"		: 2
				};
				 $.ajax({
					data:  parametros,
					url:   'caja_mini_arqueo.php',
					type:  'post',
					dataType: 'html',
					beforeSend: function () {
						$("#resumendearqueos").html("Registrando...");
					},
					crossDomain: true,
					success:  function (response) {
						$("#resumendearqueos").html(response);		
						setTimeout(function(){ refrescar_balance(sidc); }, 500);
					}
				});
			} else {
				$("#textoex").html(errores);
				$("#alertaex").show();
				
			}
		}
		
		function otrosvalores(){ //Agregar Formas de Pago
			$("#alertaotv").hide();
			var errores="";
			var formapago=$("#fpago").val();
			
			if (formapago==''){
				errores=errores+"Debe indicar forma de pago.<br />";	
			}
			var sidc=<?php echo $idcaja?>;
			var montovalor=$("#otv").val();
			
			if (montovalor==''){
				errores=errores+"Debe indicar monto asociado.<br />";	
			}
			var idbanco=$("#selectbanco").val();
			
			var comenadicional=$("#numcompro").val();
			
			if (errores==''){
				var parametros = {
						"formapago" : formapago,
						"monto" 	: montovalor,
						"idcaja"	: sidc,
						"idbanco" 	: idbanco,
						"adicional"	: comenadicional,
						"tipo"		: 3
				};
				$.ajax({
					data:  parametros,
					url:   'caja_mini_arqueo.php',
					type:  'post',
					dataType: 'html',
					beforeSend: function () {
						$("#resumendearqueos").html("Registrando...");
					},
					crossDomain: true,
					success:  function (response) {
						$("#resumendearqueos").html(response);	
						setTimeout(function(){ refrescar_balance(sidc); }, 500);						
					}
				});
			
			} else {
				$("#textootros").html(errores);
				$("#alertaotv").show();
				
			}
			
		}
		function eliminar_valor(cual,valor){//Eliminar 
			var sidc=<?php echo $idcaja?>;
			var parametros = {
						"idcaja": sidc,
						"cual" 	: cual,
						"tipo"	: 6,
						"idserial": valor
			};
			$.ajax({
				data:  parametros,
				url:   'caja_mini_arqueo.php',
				type:  'post',
				dataType: 'html',
				beforeSend: function () {
					//$("#resumendearqueos").html("Registrando...");
				},
				success:  function (response) {
					$("#resumendearqueos").html(response);	
					setTimeout(function(){ refrescar_balance(sidc); }, 500);
				}
			});  
		}
		 
		function refrescar_balance(idcaja){
			var parametros = {
						"idcaja": idcaja
			};
			$.ajax({
				data:  parametros,
				url:   'caja_balance_final.php',
				type:  'post',
				dataType: 'html',
				beforeSend: function () {
					//$("#resumendearqueos").html("Registrando...");
				},
				success:  function (response) {
					$("#contenidobalance").html(response);	
					
				}
			});  
			
		}
		//---------------------------------------------------------------//
			function continuar(cual){
				//Confirma o cancela el borrado de movimientos de valores
				var tipo=$("#octipo").val();
				var unico=$("#ocvalor").val();
				
				if(cual==2){
					$("#modpop").modal("hide");
				}
				if (cual==1){
					document.location.href='caja_anular_movimientos.php?tipo='+tipo+"&regunico="+unico;
				}	
			}
			function confirmar(valorunico,tipo){
				//abre popup de confirmacion para continuar anulando o cancelar
				var clase='';var url='';
				url="caja_anular_movimientos.php?tipo="+tipo+"&valorunico="+valorunico;
				if (tipo==1){
					clase=" retiro efectivo?."

				}
				if (tipo==2){
					clase=" reposicion efectivo?."
					
				}
				if (tipo==3){
					clase=" el pago por caja?."
				}
				$("#octipo").val(tipo);
				$("#ocvalor").val(valorunico);
				$("#cuerpodinamico").html("<h1><span class='fa fa-warning'></span><br />Está seguro que desea anular "+clase+"</h1>");
				$("#modpop").modal('show');
			}
			function envio(cual){
				//envia formulario para recibir o retirar
				$("#cual").val(cual);
				$("#entregaval").submit();
			}
			<?php if ($retirado == 1) { ?>
			function imprime_retiro(){
				//imprime el tickete del retiro si esta parametrizado
				var copias=<?php echo $copias ?>;
				var texto = document.getElementById("texto").value;
				var parametros = {
					"tk" : texto
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
						$("#impresion_box").html(response);		
						<?php if ($copias > 1) { ?>
							for (let i = 1; i < copias; i++) {
								setTimeout(function(){ reimprimirtk(1,texto); }, 1500);
							}
						<?php } ?>
						}
					});
				
				
			}
			function reimprimirtk(numero,texto){
				//reimprime segun la cantidad de copias seleccionada
				if (numero >0){
						
						//var texto = document.getElementById("texto").value;
						var parametros = {
							"tk" : texto
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
									$("#impresion_box").html(response);		
							}
						});
					
				} else {
					
				}
			}
			<?php } ?>
		function mostrarcuadro(cual){
			if(cual==2){
				$("#modal_titulo").html("Cerrando caja");
				$("#cuerpodinamico").html("Esta seguro que desea cerrar?");
				$("#conj").hide();
				$("#conj2").show();
			}
			if(cual==1){
				$("#modal_titulo").html("");
				$("#cuerpodinamico").html("");
				$("#conj").show();
				$("#conj2").hide();
			}
			$("#modpop").modal("show");
		}
		function cerrar(cual){
			if (cual==2){
				
				
			}
			if (cual==1){
				
				$("#cierreform").submit();
				
			}
		}
		function carga_cotizacion(idmoneda){
			var parametros = {
				"idmoneda": idmoneda
			};
			$.ajax({
				data:  parametros,
				url:   'mini_carga_coti_ven.php',
				type:  'post',
				dataType: 'html',
				beforeSend: function () {
					//$("#resumendearqueos").html("Registrando...");
				},
				success:  function (response) {
					$("#recargacotizacion").html(response);	
					
				}
			});  
			
		
		
		
		}

		</script>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
<link href="vendors/switchery/dist/switchery.min.css" rel="stylesheet">
<script src="vendors/switchery/dist/switchery.min.js" type="text/javascript"></script>
  </body>
</html>
