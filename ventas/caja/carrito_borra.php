<?php

require_once("../../includes/conexion.php");
require_once("../../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
$dirsup_sec = "S";

require_once("../../includes/rsusuario.php");

// variables
$usuario = $idusu;
$idsucursal = $idsucursal;
$idempresa = $idempresa;
$producto = antisqlinyeccion($_POST['prod'], "int");
$todo = substr(strtoupper(trim($_REQUEST['todo'])), 0, 1);
$idventatmp = intval($_POST['idventatmp']);



if ($idventatmp > 0) {
    $whereadd = "
	idventatmp = $idventatmp
	";
} else {
    $whereadd = "
	idproducto = $producto
	and (
		select tmp_ventares_agregado.idventatmp 
		from tmp_ventares_agregado 
		WHERE 
		tmp_ventares_agregado.idventatmp = tmp_ventares.idventatmp
		limit 1
	) is null
	and (
		select tmp_ventares_sacado.idventatmp 
		from tmp_ventares_sacado 
		WHERE 
		tmp_ventares_sacado.idventatmp = tmp_ventares.idventatmp
		limit 1
	) is null
	";

}

// borra todo
if ($todo == 'S') {
    $consulta = "
	select * 
	from tmp_ventares 
	where
	usuario = $usuario
	and finalizado = 'N'
	and registrado = 'N'
	and idsucursal = $idsucursal
	and idempresa = $idempresa
	and borrado = 'N'
	;
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // borra producto especifico
} else {
    // trae todos los detalles a borrar
    $consulta = "
	select * 
	from tmp_ventares 
	where
	$whereadd
	and usuario = $usuario
	and finalizado = 'N'
	and registrado = 'N'
	and idsucursal = $idsucursal
	and idempresa = $idempresa
	and borrado = 'N'
	;
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}


// recorre borra
while (!$rs->EOF) {

    $idventatmp = $rs->fields['idventatmp'];
    $idtmpventaresagregado = intval($rs->fields['idtmpventaresagregado']);

    // borra los detalles que contienen ese producto
    $consulta = "
	update tmp_ventares
	set borrado = 'S'
	where
	idventatmp = $idventatmp
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // borra los agregados relacionados al idventatmp principal
    $consulta = "
	update tmp_ventares
	set 
	borrado = 'S'
	where
	idventatmp_princ_delagregado = $idventatmp
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // borra los agregados de la tabla de agregados
    $consulta = "
	delete from tmp_ventares_agregado
	where
	idventatmp = $idventatmp
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    if ($idtmpventaresagregado > 0) {
        $consulta = "
		delete from tmp_ventares_agregado
		where
		idtmpventaresagregado = $idtmpventaresagregado
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }


    $rs->MoveNext();
}


if ($_GET['redir'] == 'ven') {
    header("location: gest_ventas_resto_caja.php");
    exit;
}


// redirecciona
header("location: gest_ventas_resto_carrito.php");
exit;
