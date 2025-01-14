 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "16";
$submodulo = "76";
require_once("includes/rsusuario.php");

$valido = "S";
$errores = "";

$id = intval($_GET['id']);
$idusu_get = $id;

$where_omite = '';
// si es el mismo usuario que esta logueado no puede autosacarse permisos a este modulo, se omite
if ($idusu == $idusu_get) {
    if ($_GET['accion'] == 'b') {
        $where_omite = " and modulo_detalle.idsubmod <> $submodulo ";
    }
}

$modulomaster = "N";
if (($idusu == 2 or $idusu == 3) && $idempresa == 1 && $superus == 'S') {
    $modulomaster = "S";
}
//echo $superus;
if ($modulomaster != "S") {
    $whereadd = "    and modulo_detalle.idmodulo <> 19 ";
}

// si el usuario no es de una master franquicia
$consulta = "
select * from usuarios where idusu = $idusu 
";
$rsusfranq = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$franq_m = $rsusfranq->fields['franq_m'];
// si el usuario actual no es master franq ni super filtra
if ($franq_m != 'S' && $superus != 'S') {
    $whereaddsup = "
    and franq_m = 'N'
    ";
}

// si no es un super usuario debe filtrar usuarios por este campo
if ($superus != 'S') {
    $whereaddsup .= "
    and super = 'N'
    ";
}



$consulta = "
SELECT *, (SELECT fechahora FROM usuarios_accesos where idusuario = usuarios.idusu order by fechahora desc limit 1) as ultacceso
FROM usuarios
where
estado = 1
and idempresa = $idempresa
and usuarios.idusu = $id
$whereaddsup
";
//echo $consulta;exit;
$rsus = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

if (intval($rsus->fields['idusu']) == 0) {
    echo "Usuario Inexistente o Inactivo!";
    exit;
}


$consulta = "
SELECT modulo_detalle.descripcion as desmoduppal,nombresub,pagina,
modulo,modulo_detalle.idsubmod,modulo_empresa.asignado_el,modulo_detalle.idmodulo,
        (
        select modulo_usuario.submodulo 
        from modulo_usuario 
        where 
        modulo_usuario.idmodulo = modulo_detalle.idmodulo 
        and modulo_usuario.submodulo = modulo_detalle.idsubmod 
        and modulo_usuario.idempresa = modulo_empresa.idempresa
        and modulo_usuario.idusu = $idusu_get
        limit 1
        ) as asignado
FROM modulo_detalle
INNER JOIN modulo on modulo.idmodulo = modulo_detalle.idmodulo
INNER JOIN modulo_empresa on modulo_empresa.idsubmod = modulo_detalle.idsubmod and modulo_empresa.idmodulo = modulo_detalle.idmodulo
where
modulo_detalle.mostrar = 1
and modulo_detalle.estado = 1
and modulo_detalle.idmodulo <> 19 
$whereadd
$where_omite
order by modulo asc, nombresub asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// RECORRE LOS MODULOS ACTIVOS
while (!$rs->EOF) {
    $idmodulo = $rs->fields['idmodulo'];
    $idsubmodulo = $rs->fields['idsubmod'];
    $asignado = intval($rs->fields['asignado']);

    // agregar permiso
    if ($_GET['accion'] == 'a') {
        if ($asignado == 0) {
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

        // sacar permiso
    } else {
        // si esta asignado
        if ($asignado > 0) {
            // si no es el modulo inicial
            if ($idsubmodulo != 2) {
                // borrar permiso
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
            }
        }
    }

    $rs->MoveNext();
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

header("location: usuarios_permisos.php?id=$idusu_get");
exit;


?>
