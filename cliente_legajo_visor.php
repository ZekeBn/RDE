<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "180";
require_once("includes/rsusuario.php");


$unico = intval($_REQUEST['unico']);
if ($unico == 0) {
    echo 'Error obteniendo idcliente. No se continua';
    exit;
}
$buscar = "Select archivo,descripcion,(select usuario from usuarios where idusu=cliente_legajo.registrado_por) as quien,cliente_legajo.registrado_el,unsf
from cliente_legajo 
inner join tipos_documentos on tipos_documentos.idtipodoc =cliente_legajo.idtipodocumento 
where unsf=$unico  and cliente_legajo.estado=1 order by cliente_legajo.registrado_el desc";
$rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$ar = trim($rsf->fields['archivo']);
$mi_archivo = fopen("$ar", "r");
if (!$mi_archivo) {
    echo "<p>No puedo abrir el archivo para lectura</p>";
    exit;
}
$extension = strtolower(end(explode('.', $ar)));
if ($extension == 'jpg') {
    header('Content-type: image/jpg');
    header("Content-Length: " . filesize($mi_archivo));
}
if ($extension == 'jpeg') {
    header('Content-type: image/jpeg');
    header("Content-Length: " . filesize($mi_archivo));
}
if ($extension == 'pdf') {
    header('Content-type: application/pdf');
    header("Content-Length: " . filesize($mi_archivo));
}
// envía las cabeceras correctas
//header("Content-Type: image/png");
//header("Content-Length: " . filesize($nombre));

fpassthru($mi_archivo);
fclose($mi_archivo);
exit;
