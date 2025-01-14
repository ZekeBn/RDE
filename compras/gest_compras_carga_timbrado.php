<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");

// recibe parametros
$prov = intval($_POST['prov']);

// inicializa variable
$res = "";

// actualiza numeracion proveedor
$consulta = "
	update facturas_proveedores 
	set 
	fact_num = CAST(substring(factura_numero from 7 for 9) as UNSIGNED)
	where 
	fact_num is null
	and id_proveedor=$prov ;
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

if (intval($prov) > 0) {

    // convertir a factura completa
    $facompra = antisqlinyeccion(agregacero($suc, 3).agregacero($pex, 3).agregacero($fa, 7), "text");

    // buscar en la base el timbrado
    $consulta = "
	Select * 
	from compras 
	where 
	idproveedor=$prov 
	and idempresa = $idempresa 
	and estado=1 
	order by fechacompra desc 
	limit 1
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    if (intval($rs->fields['timbrado']) > 0) {
        $res = $rs->fields['timbrado'].','.$rs->fields['vto_timbrado'].',';
    } else {
        $res = ',,';
    }

    // busca si el proveedor es incremental
    $consulta = "
	select incrementa from proveedores where idproveedor = $prov and idempresa = $idempresa
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // si es incremental
    if ($rs->fields['incrementa'] == 'S') {
        // busca el ultimo numero
        $consulta = "
		Select fact_num 
		from facturas_proveedores
		where 
		id_proveedor=$prov 
		and estado <> 6
		order by fact_num desc 
		limit 1";
        $rsinc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $prox = intval($rsinc->fields['fact_num']) + 1;
        //$res.='001001'.agregacero($prox,7);
        $res .= $prox;
    }


    echo $res;

}
