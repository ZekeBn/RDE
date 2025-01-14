<?php
/*--------------------------------------------
buscando el insumo del input de compras detalles
30/5/2023
---------------------------------------------*/
//ya no se usa
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_compras.php");
require_once("../includes/funciones_iva.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");

$idinsumo = intval($_POST['insu']);
$codbar = antisqlinyeccion(trim($_POST['cbar']), "text");
//print_r($_POST);


if (trim($_POST['cbar']) != '') {
    // busca dando prioridad a solo conversion
    $buscar = "
	Select insumos_lista.descripcion, medidas.nombre , insumos_lista.idinsumo
	from insumos_lista 
	inner join medidas on insumos_lista.idmedida = medidas.id_medida
	inner join productos on productos.idprod_serial = insumos_lista.idproducto
	where 
	productos.barcode = $codbar
	and insumos_lista.estado = 'A'
	and insumos_lista.hab_compra = 1
	and insumos_lista.idempresa = $idempresa
	order by solo_conversion desc
	";
    $rscbar = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idinsumo = intval($rscbar->fields['idinsumo']);
    //echo $buscar;
    //exit;
}

if ($idinsumo > 0) {
    $buscar = "
	Select idinsumo,descripcion,
	(	select nombre
	 	from medidas 
		where id_medida=insumos_lista.idmedida and estado=1
	) as medida 
	from insumos_lista 
	where 
	estado='A' 
	and idempresa = $idempresa
	and hab_compra=1 
	and idinsumo=$idinsumo";
    // $buscar="Select  * from insumos_lista where idinsumo=$idinsumo";
    $rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $descripcion = trim(antixss($rsd->fields['descripcion']));
    $medida = trim(antixss($rsd->fields['medida']));
    $idinsumo = intval(antixss($rsd->fields['idinsumo']));
    if ($idinsumo > 0) {
        $respuesta = [
            "success" => true,
            "medida" => $medida,
            "descripcion" => $descripcion,
            "idinsumo" => $idinsumo
        ];
    } else {
        $errores .= nl2br("El idinsumo no existe .<br>");
        $respuesta = [
            "success" => false,
            "errores" => $errores
        ];

    }
    echo json_encode($respuesta);
}
