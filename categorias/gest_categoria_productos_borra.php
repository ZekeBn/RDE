<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "81";
$dirsup = "S";
require_once("../includes/rsusuario.php");

$id = intval($_GET['id']);
//  verificar que no haya productos activos cargado a esa categoria para borrar
$buscar = "
	SELECT * 
	FROM productos
	where
	borrado = 'N'
	and idempresa = $idempresa
	and idcategoria = $id
	";
$rsprodcat = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
if (intval($rsprodcat->fields['idprod_serial']) > 0) {
    echo "Acceso Denegado!<br />-No se puede borrar la categoria por que existen productos asignados.<br />-Para borrar la categoria debe eliminar primero esos productos o asignarlos a otra categoria.";
    exit;
}


$buscar = "
	SELECT * 
	FROM categorias
	where
	estado = 1
	and idempresa = $idempresa
	and id_categoria = $id
	"	;
$prod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
// valida que exista la categoria
if (intval($prod->fields['id_categoria']) == 0) {
    echo "Error!<br /> -La categoria que intentas borrar no existe o ya fue borrada.";
    exit;
}

// marca como borrado
$buscar = "
	update
	categorias
	set estado = 6
	where
	estado = 1
	and idempresa = $idempresa
	and id_categoria = $id
	"	;
$conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

// marca como borrado todas sus subcategorias
$buscar = "
	update
	sub_categorias
	set estado = 6
	where
	estado = 1
	and idempresa = $idempresa
	and idcategoria = $id
	"	;
$conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

// por si quedo basura
$buscar = "
	update sub_categorias 
	set 
	estado = 6 
	where 
	estado = 1 
	and idempresa = 1 
	and idcategoria in (select categorias.id_categoria from categorias where categorias.estado = 6)
	"    ;
$conexion->Execute($buscar) or die(errorpg($conexion, $buscar));



header("location: gest_categoria_productos.php");
exit;
