<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$dirsup = "S";
$submodulo = "2";
require_once("../includes/rsusuario.php");
require_once("../includes/funciones_stock_fefo.php");


// estado guardado
$setadd = "";
$guardar_sub_conteo = intval($_POST['guardar_sub_conteo']);
$consolidar_conteo = intval($_POST['consolidar_conteo']);
if ($guardar_sub_conteo == 1) {
    $idconteo = intval($_POST['idconteo']);

    $consulta = "UPDATE 
                    conteo 
                set 
                    estado = 2,
                    ult_modif = '$ahora'
                where
                    idconteo = $idconteo
                    and idempresa = $idempresa
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    echo json_encode(["success" => true]);
    exit;
}
// $consolidar_conteo=1;
if ($consolidar_conteo == 1) {
    $mueve_stock = intval($_POST['mueve_stock']);

    $setadd = "";

    if ($mueve_stock == 1) {
        $setadd = " ,afecta_stock='S' ";
    } else {
        $setadd = " ,afecta_stock='N' ";
    }
    $idconteo = intval($_POST['idconteo']);

    // $idconteo=2;
    // $mueve_stock=1;

    //fecha de inicio del conteo
    $consulta = "SELECT inicio_registrado_el, iniciado_por
                from  conteo 
               where
                    idconteo = $idconteo
                    and idempresa = $idempresa
    ";

    $rs_fecha_inicio = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $fecha_inicio = $rs_fecha_inicio -> fields['inicio_registrado_el'];
    $iniciado_por = $rs_fecha_inicio -> fields['iniciado_por'];





    if ($mueve_stock == 1) {

        //PREPARANDO PARA MOVER STOCK
        //////////////////////////
        ///////////////////////

        // ver prueba fefo
        //encontrar detalles de  conteo
        $consulta = "SELECT conteo_detalles.*, insumos_lista.maneja_lote, conteo.iddeposito 
        FROM conteo_detalles
        INNER JOIN conteo ON conteo.idconteo = conteo_detalles.idconteo
        INNER JOIN insumos_lista ON insumos_lista.idinsumo = conteo_detalles.idinsumo 
        where 
        conteo.idconteo_ref=$idconteo
        and conteo.estado=2
        ";
        $rs_conteo_detalles = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        while (!$rs_conteo_detalles->EOF) {
            $diferencia = $rs_conteo_detalles->fields['diferencia'];
            $diferencia_inicial = $diferencia;
            $idinsumo = $rs_conteo_detalles->fields['idinsumo'];
            $idmedida_ref = $rs_conteo_detalles->fields['idmedida_ref'];
            $fila = $rs_conteo_detalles->fields['fila'];
            $columna = $rs_conteo_detalles->fields['columna'];
            $idalm = $rs_conteo_detalles->fields['idalm'];
            $lote = intval($rs_conteo_detalles->fields['lote']);
            $vencimiento = antisqlinyeccion($rs_conteo_detalles->fields['vencimiento'], 'date');
            $maneja_lote = $rs_conteo_detalles->fields['maneja_lote'];
            $iddeposito = $rs_conteo_detalles->fields['iddeposito'];
            $idpasillo = $rs_conteo_detalles->fields['idpasillo'];
            if ($diferencia < 0) {
                $cantidad = $diferencia_inicial * (-1);
                $tipomov = 6; // stock_tipomov es esto
                $sumaoresta = '-';

                $parametros_array = [
                    'idinsumo' => $idinsumo,
                    'idconteo' => $idconteo,
                    'lote' => $lote,
                    'vencimiento' => $vencimiento,
                    'fila' => $fila,
                    'columna' => $columna,
                    'idalm' => $idalm,
                    'iddeposito' => $iddeposito,
                    'diferencia' => $diferencia,
                    'diferencia_inicial' => $diferencia_inicial,
                    'cantidad' => $cantidad,
                    'tipomov' => 6,
                    'sumaoresta' => $sumaoresta

                ];
                disminuir_stockfefo($parametros_array);
                movimientos_stock($parametros_array);
            }


            if ($diferencia > 0) {

                $cantidad = $diferencia_inicial * (-1);
                $tipomov = 6; // stock_tipomov es esto
                $sumaoresta = '+';

                $parametros_array = [
                    'idinsumo' => $idinsumo,
                    'idconteo' => $idconteo,
                    'lote' => $lote,
                    'vencimiento' => $vencimiento,
                    'fila' => $fila,
                    'columna' => $columna,
                    'idalm' => $idalm,
                    'iddeposito' => $iddeposito,
                    'diferencia' => $diferencia,
                    'diferencia_inicial' => $diferencia_inicial,
                    'cantidad' => $cantidad,
                    'idmedida_ref' => $idmedida_ref,
                    'tipomov' => 6,
                    'sumaoresta' => $sumaoresta,
                    'fecha_inicio' => $fecha_inicio,
                    'iniciado_por' => $iniciado_por,
                    'idpasillo' => $idpasillo

                ];
                aumentar_stockfefo($parametros_array);
                movimientos_stock($parametros_array);
            }


            // if($diferencia > 0){
            //     $cantidad_aumentar=$diferencia;
            //     aumentar_stock_general($idinsumo,$cantidad_aumentar,$iddeposito);
            //     aumentar_stock($idinsumo,$cantidad_aumentar,$ult_costo,$iddeposito);
            //     movimientos_stock($idinsumo,$cantidad_aumentar,$iddeposito,6,'+',$idconteo,$fecha_inicio);
            // }
            $rs_conteo_detalles->MoveNext();
        }

    }

    $consulta = "UPDATE 
        conteo 
    set 
        estado = 3,
        ult_modif = '$ahora'
        $setadd
    where
        idconteo = $idconteo
        and idempresa = $idempresa
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    echo json_encode(["success" => true]);

}
