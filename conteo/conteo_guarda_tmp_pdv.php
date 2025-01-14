<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "162";
$dirsup = "S";

//print_r($_REQUEST);

// debe estar antes del sesion_start que esta en rsusuario
// primera linea para mantener session, luego se hace tambien por ajax por si el servidor no admite
//ini_set('session.gc_maxlifetime',86400); // segundos // 86400 = 24 horas

// no mover de aqui, debe estar despues del session.gc_maxlifetime
require_once("../includes/rsusuario.php");

//require_once("../includes/funciones_stock.php");

//print_r($_POST);
//exit;
$idconteo = intval($_POST['id']);
if (intval($idconteo) == 0) {
    echo "No envio id";
    exit;
}

$consulta = "
select *
from conteo
where
estado <> 6
and (estado = 1 or estado = 2)
and idconteo = $idconteo
and afecta_stock = 'N'
and fecha_final is null
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito = intval($rs->fields['iddeposito']);
$idsucursal = intval($rs->fields['idsucursal']);
if (intval($rs->fields['idconteo']) == 0) {
    echo "Conteo inexistente o finalizado";
    exit;
}


if (isset($_POST['accion']) && ($_POST['accion'] == 1)) {
    $accion = intval($_POST['accion']);
    $sumavent = strtoupper(substr(trim($_POST['sumavent']), 0, 1));
    //echo "accion:".$accion."<br />";

    // validaciones basicas
    $valido = "S";
    $errores = "";


    // recorrer y validar datos
    $totprodenv = 0;
    $totprodenv_ex = 0;
    //foreach($_POST as $key => $value){
    //$idproducto=intval(str_replace("cont_","",$key));
    //$cantidad=$value;
    $idproducto = intval($_POST['idprod']);
    $cantidad = floatval($_POST['cant']);
    $cantidad_contada = $cantidad;
    if (trim($cantidad) != '' && $idproducto > 0) {
        // busca que exista el insumo
        $idproducto = antisqlinyeccion($idproducto, 'int');
        $buscar = "Select idinsumo as idprod_serial, descripcion, estado from insumos_lista where idinsumo=$idproducto";
        //echo $buscar;
        //exit;
        $rsin = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idproducto_ex = $rsin->fields['idprod_serial'];
        $descripcion = antisqlinyeccion($rsin->fields['descripcion'], "text");
        $estado_prod = $rsin->fields['estado'];
        // si el producto esta activo
        if ($estado_prod == 'A') {
            $totprodenv++;

            // si el producto fue borrado
        } else {
            // si la accion es diferente a continuar mas tarde
            if ($accion != 1) {
                if ($idproducto_ex > 0) {
                    $errores .= "- El producto $descripcion con id: $idproducto, fue borrado.<br />";
                    $valido = "N";
                } else {
                    $errores .= "- El producto $descripcion con id: $idproducto, no existe.<br />";
                    $valido = "N";
                }
            }

        } // if($estado == 1){

    } // if(trim($cantidad) != '' && $idproducto > 0){

    //} // foreach($_POST as $key => $value){

    // si la accion es diferente a continuar mas tarde
    if ($accion != 1) {
        if (intval($totprodenv) == 0) {
            $errores .= "- No completaste ninguna cantidad.<br />";
            $valido = "N";
        }
    }


    if ($valido == 'S') {

        //foreach($_POST as $key => $value){
        /*$idproducto=intval(str_replace("cont_","",$key));
        $cantidad=$value;*/
        $idproducto = intval($_POST['idprod']);
        $cantidad = floatval($_POST['cant']);
        $cantidad_contada = $cantidad;

        //echo $accion;
        //exit;

        if (trim($_POST['cant']) != '' && $idproducto > 0) {

            // por si hay texto en el campo cantidad
            $cantidad = floatval($cantidad);
            $cantidad_contada = $cantidad;



            // stock disponible
            $consulta = "
				select sum(disponible) as total_stock,
				/*(
				select sum(venta_receta.cantidad) as venta
				from venta_receta 
				inner join ventas on ventas.idventa = venta_receta.idventa
				where 
				venta_receta.idproducto = gest_depositos_stock_gral.idproducto  
				and ventas.fecha >= '$fecha_inicio'
				and (select iddeposito from deposito where idsucursal = ventas.sucursal and tiposala = 2) = $iddeposito
				and ventas.estado <> 6
				) as venta,*/
				(
				select productos_sucursales.precio 
				from productos_sucursales
				where 
				productos_sucursales.idproducto = $idproducto
				and productos_sucursales.idsucursal = $idsucursal
				) as pventa
				from gest_depositos_stock_gral 
				where 
				gest_depositos_stock_gral.idproducto = $idproducto
				and gest_depositos_stock_gral.iddeposito = $iddeposito
				";
            $rsdisp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $disponible = floatval($rsdisp->fields['total_stock']);
            $pventa = floatval($rsdisp->fields['pventa']);
            $pcosto = 0;
            $cantidad_sistema = $disponible;

            // busca si existe ese producto en detalle para este conteo
            $consulta = "
				select * 
				from conteo_detalles 
				where 
				idconteo = $idconteo
				and idinsumo = $idproducto
				and idconteo in (select idconteo from conteo where idconteo = conteo_detalles.idconteo )
				";
            //echo $consulta;
            $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            //calculos
            $venta = floatval($rsdisp->fields['venta']);
            $cantidad_contada = $cantidad;
            $cantidad_teorica = floatval($disponible);
            $cantidad_teorica_cv = $cantidad_teorica + $venta;// venta es cero aca no se vende quizas
            //si se usa el mismo al alterar stock
            $diferencia = $cantidad_contada - $cantidad_teorica;
            $diferencia_cv = $cantidad_contada - $cantidad_teorica_cv;
            $cantidad_venta = "0";
            if ($sumavent == 'S') {
                $diferencia = $diferencia_cv;
                $cantidad_venta = $venta;
            }
            $precio_venta = $pventa;
            $precio_costo = $pcosto;
            $diferencia_pv = $diferencia * $precio_venta;
            $diferencia_pc = $diferencia * $precio_costo;

            //echo $diferencia;
            //if($diferencia > 0){
            //	$cantidad_aumentar=$diferencia;
            //	echo "<br />Aum:".$cantidad_aumentar;
            //}
            //if($diferencia < 0){
            //	$cantidad_descontar=$diferencia*-1;
            //	echo "<br />Desc:".$cantidad_descontar;
            //}

            //exit;



            // si no existe inserta
            if (intval($rsex->fields['idinsumo']) == 0) {
                $consulta = "
					insert into conteo_detalles
					(idconteo, idinsumo,  cantidad_contada,  cantidad_sistema, cantidad_venta, precio_venta, precio_costo, diferencia, diferencia_pv, diferencia_pc, descripcion, idusu, ubicacion)
					values
					($idconteo, $idproducto,  $cantidad_contada, $cantidad_sistema, $cantidad_venta, $precio_venta, $precio_costo, $diferencia, $diferencia_pv, $diferencia_pc, $descripcion, $idusu, $iddeposito)
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            } else {
                // si existe actualiza
                $consulta = "
					update conteo_detalles
					set
						cantidad_contada=$cantidad_contada,
						cantidad_sistema=$cantidad_sistema,
						cantidad_venta=$cantidad_venta,
						precio_venta=$precio_venta, 
						precio_costo=$precio_costo,
						diferencia=$diferencia, 
						diferencia_pv=$diferencia_pv,
						diferencia_pc=$diferencia_pc,
						idusu=$idusu,
						ubicacion=$iddeposito
					where
						idinsumo=$idproducto
						and idconteo=$idconteo
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }

            // Guardar
            if ($accion == 1) {

                // estado guardado
                $consulta = "
					update conteo 
					set 
					estado = 2,
					ult_modif = '$ahora',
					sumoventa = '$sumavent'
					where
					idconteo = $idconteo
					
					";
                //$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

            } // if($accion == 1){



        } // if(trim($cantidad) != '' && $idproducto > 0){

        if (trim($_POST['cant']) == '' && $idproducto > 0) {

            $consulta = "
				delete from conteo_detalles					
				where
				idinsumo=$idproducto
				and idconteo=$idconteo
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $diferencia = "";
        }

        //} // foreach($_POST as $key => $value){



        // redireccionar
        //header("location: conteo.php");
        //echo $diferencia;
        echo "Guardado: ".floatval($_POST['cant']);
        exit;

    } // if($valido == 'S'){
}
