<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$dirsup = "S";
$submodulo = "134";
require_once("../includes/rsusuario.php");


//print_r($_POST);
$idalms = ($_POST['idalms']);
$idinsumo = intval($_POST['idinsumo']);
$iddeposito = intval($_POST['iddeposito']);
$idconteo_ref = intval($_POST['idconteo']);

// recibe parametros
$fecha_inicio = antisqlinyeccion($ahora, "text");
$iniciado_por = antisqlinyeccion($idusu, "int");
$finalizado_por = antisqlinyeccion('', "int");
$inicio_registrado_el = antisqlinyeccion($ahora, "text");
$final_registrado_el = antisqlinyeccion('', "text");
$estado = antisqlinyeccion(2, "int");
$afecta_stock = antisqlinyeccion('N', "text");
$fecha_final = antisqlinyeccion('', "text");
$observaciones = antisqlinyeccion(' ', "text");
$iddeposito = antisqlinyeccion($iddeposito, "int");
$totinsu = intval($_POST['totinsu']);

$tipo_conteo = antisqlinyeccion(2, "int");

if ($idinsumo > 0) {
    $tipo_contep = 2;
}
// validaciones basicas
$valido = "S";
$errores = "";


if ($iddeposito == 0) {
    $valido = "N";
    $errores .= " - Debes seleccionar el deposito.<br />";
}
// buscamos que exista el deposito y su sucursal
$consulta = "
	select * from gest_depositos
	where 
	idempresa = $idempresa
	and estado = 1
	and iddeposito = $iddeposito
	";
$rsdep = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito = intval($rsdep->fields['iddeposito']);
$idsucu = intval($rsdep->fields['idsucursal']);
if ($iddeposito == 0) {
    $valido = "N";
    $errores .= " - Deposito inexistente.<br />";
}




//$valido="N";
// validaciones especificas

// no se puede iniciar un conteo por que este deposito ya tiene activo otro con el mismo grupo de insumos






// si todo es correcto inserta
if ($valido == "S") {
    $idconteo = select_max_id_suma_uno("conteo", "idconteo")["idconteo"];

    $consulta = "
		insert into conteo
		(idconteo, fecha_inicio, iniciado_por, finalizado_por, estado, afecta_stock, fecha_final, observaciones,  idsucursal, idempresa, iddeposito, inicio_registrado_el, final_registrado_el, tipo_conteo, idinsumo, idconteo_ref)
		values
		($idconteo, $fecha_inicio, $iniciado_por, $finalizado_por, $estado, $afecta_stock, $fecha_final, $observaciones,  $idsucu, $idempresa, $iddeposito, $inicio_registrado_el, $final_registrado_el, $tipo_conteo, $idinsumo, $idconteo_ref)
		";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // hacer el update de los almacenes que faltaron y cerarlos
    foreach ($idalms as $key => $value) {
        $consulta = "SELECT gest_depositos_stock_almacto.disponible, gest_depositos_stock_almacto.idalm,
      gest_depositos_stock_almacto.fila, gest_depositos_stock.lote, gest_depositos_stock.vencimiento, 
      gest_depositos_stock_almacto.columna, gest_depositos_stock_almacto.idpasillo,
      gest_depositos_stock_almacto.disponible,medidas.nombre as medida_ref, medidas.id_medida as idmedida, gest_deposito_almcto_grl.nombre as almacenamiento,
      CONCAT(gest_deposito_almcto.nombre,' ',COALESCE(gest_deposito_almcto.cara, ''))  as tipo_almacenamiento, 
      gest_almcto_pasillo.nombre as pasillo,insumos_lista.descripcion as insumo
      FROM gest_depositos_stock_almacto
      LEFT JOIN gest_almcto_pasillo on gest_almcto_pasillo.idpasillo = gest_depositos_stock_almacto.idpasillo
      INNER JOIN gest_deposito_almcto on gest_deposito_almcto.idalm = gest_depositos_stock_almacto.idalm
      INNER JOIN gest_deposito_almcto_grl on gest_deposito_almcto_grl.idalmacto = gest_deposito_almcto.idalmacto
      INNER JOIN gest_depositos_stock ON gest_depositos_stock.idregseriedptostk = gest_depositos_stock_almacto.idregseriedptostk
      INNER JOIN  insumos_lista ON insumos_lista.idinsumo = gest_depositos_stock.idproducto
      INNER JOIN medidas on medidas.id_medida = gest_depositos_stock_almacto.idmedida
      where 
      gest_depositos_stock_almacto.idalm = $value 
      and gest_depositos_stock_almacto.disponible > 0
      and gest_depositos_stock_almacto.estado = 1
      and gest_depositos_stock.idproducto = $idinsumo
      ";

        $rs_stock_faltante = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        while (!$rs_stock_faltante->EOF) {

            ////////////////////////////////////////////////////////////////////////////
            $cantidad = floatval($rs_stock_faltante->fields['disponible']);
            $lote = antisqlinyeccion($rs_stock_faltante->fields['lote'], "text");
            $vencimiento = antisqlinyeccion($rs_stock_faltante->fields['vencimiento'], 'date');
            $tipo_almacenamiento = intval($rs_stock_faltante->fields['tipo_almacenamiento']);
            $idmedida = intval($rs_stock_faltante->fields['idmedida']);
            $idalm = $value;
            $fila = intval($rs_stock_faltante->fields['fila']);
            $columna = intval($rs_stock_faltante->fields['columna']);
            $idpasillo = intval($rs_stock_faltante->fields['idpasillo']);

            $valido = "S";
            // idalma
            // fila
            // columna
            // estado == 1 o estado == 2  finalizado es 3  anulado es 4



            if ($idconteo == 0) {
                $location = "conteo_por_producto_detalle.php?id=".$iddeposito."&idinsumo=".$idinsumo;
                header("location: $location");
                exit;
            }
            if ($idinsumo == 0) {
                $location = "conteo_stock_detalle.php?id=".$iddeposito;
                header("location: $location");
                exit;
            }

            //verifica si existe el conteo
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

            $idsucursal = intval($rs->fields['idsucursal']);
            if (intval($rs->fields['idconteo']) == 0) {
                $valido = "N";
                $errores .= "Conteo inexistente o finalizado";
            }

            // //verifica si existe el conteo
            // $consultas="SELECT conteo_detalles.idconteo, gest_deposito_almcto_grl.nombre as almacenamiento, CONCAT(gest_deposito_almcto.nombre,' ',COALESCE(gest_deposito_almcto.cara, ''))  as tipo_almacenamiento
            // FROM conteo_detalles
            // INNER JOIN conteo on conteo.idconteo=conteo_detalles.idconteo
            // INNER JOIN gest_deposito_almcto on gest_deposito_almcto.idalm = conteo_detalles.idalm
            // INNER JOIN gest_deposito_almcto_grl on gest_deposito_almcto_grl.idalmacto = gest_deposito_almcto.idalmacto
            // where
            // (conteo.estado =1 or conteo.estado=2)
            // and conteo.conteo_consolidado != 1
            // and conteo.idinsumo = $idinsumo
            // and conteo.iddeposito = $iddeposito
            // and conteo_detalles.idalm = $idalm
            // and conteo.tipo_conteo = 2
            // and conteo.idconteo != $idconteo
            // ";

            // $rs_verificar=$conexion->Execute($consultas) or die(errorpg($conexion,$consultas));
            // $idconteo_verificar=intval($rs_verificar->fields['idconteo']);
            // $almacenamiento=($rs_verificar->fields['almacenamiento']);
            // $tipo_almacenamiento_verificar=($rs_verificar->fields['tipo_almacenamiento']);
            // $nombre_almacenamiento="$almacenamiento $tipo_almacenamiento_verificar";
            // if($idconteo_verificar > 0){
            //     $valido="N";
            //     $errores .= "Ya existe un conteo activo para el almacenamiento $nombre_almacenamiento elija otro tipo Almacenamiento o verifique el conteo id:$idconteo_verificar";
            // }

            //verificar si en el detalle de este conteo no existe nadie en ese mismo lugar





            // validaciones basicas

            // recorrer y validar datos
            $totprodenv = 0;
            $totprodenv_ex = 0;
            $idproducto = $idinsumo;
            $cantidad_contada = $cantidad;
            if (trim($cantidad) != '' && $idproducto > 0) {
                // busca que exista el insumo
                $idproducto = antisqlinyeccion($idproducto, 'int');
                $buscar = "Select idinsumo as idprod_serial, descripcion, estado, idproducto from insumos_lista where idinsumo=$idproducto";
                //echo $buscar;
                //exit;
                $rsin = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $idproducto_ex = $rsin->fields['idprod_serial'];
                $idproducto = $rsin->fields['idproducto'];
                $descripcion = antisqlinyeccion($rsin->fields['descripcion'], "text");
                $estado_prod = $rsin->fields['estado'];
                // si el producto esta activo
                if ($estado_prod == 'A') {
                    $totprodenv++;

                    // si el producto fue borrado
                } else {


                } // if($estado == 1){

            } // if(trim($cantidad) != '' && $idproducto > 0){

            // if($tipo_almacenamiento == 1){

            //   $consulta="SELECT conteo_detalles.unicose, gest_deposito_almcto.tipo_almacenado,
            //              gest_deposito_almcto.cara, gest_deposito_almcto.nombre
            //              FROM conteo_detalles
            //              INNER JOIN conteo ON conteo.idconteo = conteo_detalles.idconteo
            //              INNER JOIN gest_deposito_almcto on gest_deposito_almcto.idalm = conteo_detalles.idalm
            //              where
            //              conteo.estado <> 6
            //              and (conteo.estado = 1 or conteo.estado = 2)
            //              and conteo.afecta_stock = 'N'
            //              and conteo.fecha_final is null
            //              and conteo_detalles.fila = $fila
            //              and conteo_detalles.columna = $columna
            //              and conteo_detalles.idpasillo=0
            //              and conteo_detalles.idalm = $idalm
            //   ";
            //   $rs_articulo_duplicado=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
            //   $unicose_conteo_duplicado = intval($rs_articulo_duplicado->fields['unicose']);
            //   $nombre_estante=$rs_articulo_duplicado->fields['nombre'];
            //   $cara_estante=$rs_articulo_duplicado->fields['cara'];
            //   if($unicose_conteo_duplicado > 0){
            //     $valido="N";
            //     $errores.="- Ya existe un conteo Guardado o Pendiente para el artículo en la Fila:$fila, Columna:$columna del Estante $nombre_estante $cara_estante.<br>";
            //   }

            // }

            if ($valido == 'S') {

                // stock disponible por lote
                $consulta = "SELECT 
                  productos_sucursales.precio  as pventa
                from 
                  productos_sucursales 
                where 
                  productos_sucursales.idproducto = $idproducto
                  and productos_sucursales.idsucursal = $idsucursal
              ";
                $rsdisp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $pventa = floatval($rsdisp->fields['pventa']);
                $pcosto = 0;

                $precio_venta = $pventa;
                $precio_costo = $pcosto;
                $diferencia_pv = $cantidad_contada * $pventa * (-1);
                $diferencia_pc = 0;

                //TODO Ver CERAR
                $consulta = "
                            insert into conteo_detalles
                            (idconteo, idinsumo,  cantidad_contada,  cantidad_sistema, cantidad_venta, precio_venta, precio_costo, diferencia, diferencia_pv, diferencia_pc, descripcion, idusu, ubicacion, lote, vencimiento, idpasillo, fila, columna, idalm, idmedida_ref)
                            values
                            ($idconteo, $idproducto_ex,  0, $cantidad_contada, 0, $precio_venta, $precio_costo, -$cantidad_contada, $diferencia_pv, $diferencia_pc, $descripcion, $idusu, $iddeposito, $lote, $vencimiento, $idpasillo, $fila, $columna, $idalm, $idmedida)
                            ";
                // echo $consulta;
                // exit;
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                // if( $idproducto > 0){
                //     $whereadd="";
                //     // si es que tiene lote lo agrega
                //     if($lote != "NULL" ){
                //         $whereadd=" and gest_depositos_stock.lote = $lote
                //         and gest_depositos_stock.vencimiento = $vencimiento ";
                //     }else{
                //         $whereadd="  and gest_depositos_stock.lote is NULL
                //         and gest_depositos_stock.vencimiento is NULL ";
                //     }

                //     // stock disponible por lote
                //     $consulta="SELECT
                //     gest_depositos_stock_almacto.disponible,
                //     (
                //       select
                //         productos_sucursales.precio
                //       from
                //         productos_sucursales
                //       where
                //         productos_sucursales.idproducto = $idproducto
                //         and productos_sucursales.idsucursal = $idsucursal
                //     ) as pventa
                //     FROM
                //         gest_depositos_stock_almacto
                //         INNER JOIN gest_depositos_stock ON gest_depositos_stock.idregseriedptostk = gest_depositos_stock_almacto.idregseriedptostk
                //     where
                //         gest_depositos_stock_almacto.fila = $fila
                //         and gest_depositos_stock_almacto.columna = $columna
                //         $whereadd
                //         and gest_depositos_stock_almacto.idpasillo = $idpasillo
                //         and gest_depositos_stock.idproducto = $idproducto
                //         and gest_depositos_stock_almacto.idalm = $idalm
                //         and gest_depositos_stock.iddeposito = $iddeposito
                //         and gest_depositos_stock_almacto.disponible > 0
                //         and gest_depositos_stock_almacto.estado = 1
                //     ";
                //     $rsdisp=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
                //     $disponible=floatval($rsdisp->fields['disponible']);
                //     $pventa=floatval($rsdisp->fields['pventa']);
                //     $pcosto=0;
                //     $cantidad_sistema=$disponible;

                //     // busca si existe ese producto en detalle para este conteo
                //     if($lote != "NULL" ){
                //         $whereadd=" and lote = $lote
                //         and vencimiento = $vencimiento ";
                //     }else{
                //         $whereadd="  and lote is NULL
                //         and vencimiento is NULL ";
                //     }

                //     $consulta="
                //     select *
                //     from conteo_detalles
                //     where
                //     idconteo = $idconteo
                //     and idinsumo = $idproducto
                //     $whereadd
                //     and fila = $fila
                //     and columna = $columna
                //     and idpasillo = $idpasillo
                //     and idconteo in (select idconteo from conteo where idconteo = conteo_detalles.idconteo )
                //     ";


                //     $rsex=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

                //     //calculos
                //     $venta=floatval($rsdisp->fields['venta']);
                //     $cantidad_contada=$cantidad;
                //     $cantidad_teorica=floatval($disponible);
                //     $cantidad_teorica_cv=$cantidad_teorica+$venta;// venta es cero aca no se vende quizas
                //                                                   //si se usa el mismo al alterar stock
                //     $diferencia=$cantidad_contada-$cantidad_teorica;
                //     $diferencia_cv=$cantidad_contada-$cantidad_teorica_cv;
                //     $cantidad_venta="0";
                //     // if($sumavent == 'S'){
                //     //     $diferencia=$diferencia_cv;
                //     //     $cantidad_venta=$venta;
                //     // }
                //     $precio_venta=$pventa;
                //     $precio_costo=$pcosto;
                //     $diferencia_pv=$diferencia*$precio_venta;
                //     $diferencia_pc=$diferencia*$precio_costo;
                //     $unicose = $rsex->fields['unicose'];


                //     // si no existe inserta
                //     if(intval($rsex->fields['idinsumo']) == 0){
                //         $consulta="
                //         insert into conteo_detalles
                //         (idconteo, idinsumo,  cantidad_contada,  cantidad_sistema, cantidad_venta, precio_venta, precio_costo, diferencia, diferencia_pv, diferencia_pc, descripcion, idusu, ubicacion, lote, vencimiento, idpasillo, fila, columna, idalm, idmedida_ref)
                //         values
                //         ($idconteo, $idproducto,  $cantidad_contada, $cantidad_sistema, $cantidad_venta, $precio_venta, $precio_costo, $diferencia, $diferencia_pv, $diferencia_pc, $descripcion, $idusu, $iddeposito, $lote, $vencimiento, $idpasillo, $fila, $columna, $idalm, $idmedida)
                //         ";
                //         // echo $consulta;exit;
                //         $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

                //     }




                // } // if(trim($cantidad) != '' && $idproducto > 0){



                //} // foreach($_POST as $key => $value){




            }




            ////////////////////////////////////////////////////////////////////////////
            ///////////////////////////////////////////////////////////////////////////
            /////////////////////FIN INSERT DE CONTEO ////////////////////////////////

            $rs_stock_faltante->MoveNext();
        }



        // 	if ($tipo_conteo == 2 ) {
        // 		# conteo por producto que se encuentra en un deposito pero no asi en el mismo
        // 		// alamacenamiento o tipo de almacenamiento
        // 		header("location: conteo_stock_contar_producto.php?id=$idconteo&iddeposito=$iddeposito&idinsumo=$idinsumo");
        // 	}else if ($tipo_conteo ==1){
        // 		//conteo por tipo_almacenamiento que pertenecen a un deposito
        // 		header("location: conteo_stock_contar_deposito.php?id=$idconteo&iddeposito=$iddeposito");

        // 	}else{
        // //tipo_conteo == 6 es para conteo normal pero eso es en otro modulo reutiliza la tabla por eso
        // // es el if
        // 	}

    }

}
