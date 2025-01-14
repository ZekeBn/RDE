<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "16";
$submodulo = "76";
require_once("includes/rsusuario.php");

$valido = "S";
$errores = "";
$modulos_venta = explode(',', '83,216');

$idusu_get = intval($_POST['idusu_get']);
$idsubmodulo = intval($_POST['idsubmodulo']);

//print_r($_POST);


if ($idusu_get == 0) {
    $valido = "N";
    $errores = "-No se indico el usuario.".$saltolinea;
}
if ($idsubmodulo == 0) {
    $valido = "N";
    $errores = "-No se indico el submodulo.".$saltolinea;
}


if (in_array(intval($idsubmodulo), $modulos_venta)) {
    $hayvta = "S";
}
$modulomaster = "N";
if (($idusu == 2 or $idusu == 3) && $idempresa == 1 && $superus == 'S') {
    $modulomaster = "S";
}
//echo $superus;
if ($modulomaster != "S") {
    $whereadd = "	and modulo_detalle.idmodulo <> 19 ";
}



$consulta = "
select idmodulo from modulo_detalle where idsubmod = $idsubmodulo and estado =  1 limit 1
";
$rssub = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idmodulo = intval($rssub->fields['idmodulo']);
if ($idmodulo == 0) {
    $valido = "N";
    $errores = "-Sub modulo no existe o no esta activo.".$saltolinea;
}
$consulta = "
SELECT *
FROM usuarios
where
estado = 1
and usuarios.idusu = $idusu_get
";
$rsus = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rsus->fields['idusu']) == 0) {
    $valido = "N";
    $errores = "-Usuario Inexistente o Inactivo.".$saltolinea;
}
// si el modulo es master y el usuario tiene permitido ser master
if ($idmodulo == 19 && $rsus->fields['super'] != 'S') {
    $valido = "N";
    $errores = "-Modulo no permitido para este usuario.".$saltolinea;
}


if ($valido == 'S') {

    // busca si existe en la bd
    $consulta = "
	select * 
	from modulo_usuario 
	where 
	submodulo = $idsubmodulo 
	and idusu = $idusu_get
	limit 1
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $submodulo_ex = intval($rs->fields['submodulo']);
    // si ya existe borrar
    if ($submodulo_ex > 0) {
        $accion = "B"; //borrar
    } else {
        $accion = "A"; // agregar
    }


    // si existe borra
    if ($submodulo_ex > 0) {


        // borrar el producto para ese servicio
        $consulta = "
		delete from modulo_usuario where submodulo = $idsubmodulo  and idusu = $idusu_get
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // loguear borrado
        $consulta = "
		insert into modulo_usuario_log_new
		(idusu, submodulo, accion, registrado_por, registrado_el)
		values
		($idusu_get, $idsubmodulo, 'B', $idusu, '$ahora')
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // si no existe registra
    } else {


        // agrega
        $consulta = "
		INSERT INTO modulo_usuario
		(idusu, idmodulo,idempresa, estado, submodulo, registrado_el, registrado_por, sucursal)
		VALUES 
		($idusu_get,$idmodulo,1,1,$idsubmodulo,'$ahora',$idusu,$idsucursal)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // loguear agregado
        $consulta = "
		insert into modulo_usuario_log_new
		(idusu, submodulo, accion, registrado_por, registrado_el)
		values
		($idusu_get, $idsubmodulo, 'A', $idusu, '$ahora')
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



    }



    // si es un modulo de venta forzar permiso a submodulo 30
    if ($hayvta == 'S') {
        // busca si tiene permisos al submodulo 30
        $consulta = "
		select  * from modulo_usuario where idusu = $idusu_get and submodulo = 30 limit 1
		";
        $rsexperm = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // si no tiene aun el permiso, asigna
        if (intval($rsexperm->fields['submodulo']) == 0) {
            // insertar permiso para el modulo 1 y submodulo 30 registrar_venta
            $consulta = "
			INSERT ignore INTO modulo_usuario
			(idusu, idmodulo, idempresa, estado, submodulo, registrado_el, registrado_por, sucursal) 
			VALUES 
			($idusu_get,1,1,1,30,'$ahora',$idusu,$idsucursal)
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // log
            $consulta = "
			insert into modulo_usuario_log_new
			(idusu, submodulo, accion, registrado_por, registrado_el)
			values
			($idusu_get, 30, 'A', $idusu, '$ahora')
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }
    }

    // busca si tiene permisos al submodulo 2
    $consulta = "
	select  * from modulo_usuario where idusu = $idusu_get and submodulo = 2 limit 1
	";
    $rsexperm = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // si no tiene aun el permiso, asigna
    if (intval($rsexperm->fields['submodulo']) == 0) {
        // insertar permiso para el modulo 1 y submodulo 2 si o si
        $consulta = "
		INSERT IGNORE INTO modulo_usuario
		(idusu, idmodulo, idempresa, estado, submodulo, registrado_el, registrado_por, sucursal) 
		VALUES 
		($idusu_get,1,1,1,2,'$ahora',$idusu,$idsucursal)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // log
        $consulta = "
		insert into modulo_usuario_log_new
		(idusu, submodulo, accion, registrado_por, registrado_el)
		values
		($idusu_get, 2, 'A', $idusu, '$ahora')
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }


}





// busca en la bd el nuevo valor
$consulta = "
select idusu, submodulo 
from modulo_usuario 
where 
idusu = $idusu_get
and submodulo = $idsubmodulo
limit 1;
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$checked = "";
$permitido = "N";

if (intval($rs->fields['submodulo']) > 0) {
    $checked = "checked";
    $permitido = "S";
}
$html_checkbox = '<input name="producto" id="box_'.$idsubmodulo.'" type="checkbox" value="S" class="js-switch" onChange="registra_permiso('.$idsubmodulo.'); " '.$checked.' />';



// genera array con los datos
$arr = [
    'html_checkbox' => $html_checkbox,
    'permitido' => $permitido, // permitido si o no
    'valido' => $valido,
    'errores' => $errores
];

//print_r($arr);

// convierte a formato json
$respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// devuelve la respuesta formateada
echo $respuesta;
