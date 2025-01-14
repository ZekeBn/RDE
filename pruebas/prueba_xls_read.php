<?php

require("../clases/PHPExcel.php");
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "2";
require_once("../includes/rsusuario.php");
$archivoExcel = 'proveedores_fob.xlsx';

// Crear un objeto PHPExcel
$excel = PHPExcel_IOFactory::load($archivoExcel);

// Seleccionar la primera hoja del archivo
$hoja = $excel->getActiveSheet();

// Obtener el número de filas y columnas
$numFilas = $hoja->getHighestRow();
$numColumnas = PHPExcel_Cell::columnIndexFromString($hoja->getHighestColumn());
$idproveedor = $hoja->getCellByColumnAndRow(1, 1)->getValue();
echo "El proveedor tiene id:".$idproveedor."<br>";
// Recorrer las filas y acceder a los valores
$valido = "S";
$error = "";
for ($fila = 3; $fila <= $numFilas; $fila++) {

    $codigo = trim($hoja->getCellByColumnAndRow(0, $fila)->getValue());
    $precio = trim($hoja->getCellByColumnAndRow(1, $fila)->getValue());
    $fechaCell = $hoja->getCellByColumnAndRow(2, $fila);
    $fechaValue = trim($fechaCell->getValue());

    $fechaFormatted = PHPExcel_Style_NumberFormat::toFormattedString($fechaValue, 'YYYY-DD-MM');
    echo "Codigo: $codigo, PrecioGS: $precio, Fecha: $fechaFormatted<br>";
    if ($codigo == "" && $precio == "" && $fechaValue == "") {
    } else {

        if ($codigo == "") {
            $valido = "N";
            $error .= "Fila $fila el codigo no puede ser nulo.<br>";
        }
        if ($precio == "") {
            $valido = "N";
            $error .= "Fila $fila el precio no puede ser nulo.<br>";
        }
        if ($fechaValue == "") {
            $valido = "N";
            $error .= "Fila $fila la fecha no puede ser nula.<br>";
        }
    }
}
if ($valido == "S") {

    for ($fila = 3; $fila <= $numFilas; $fila++) {

        $codigo_articulo = trim($hoja->getCellByColumnAndRow(0, $fila)->getValue());
        $precio = trim($hoja->getCellByColumnAndRow(1, $fila)->getValue());
        $fechaCell = $hoja->getCellByColumnAndRow(2, $fila);
        $fecha = trim($fechaCell->getValue());
        $fecha = PHPExcel_Style_NumberFormat::toFormattedString($fecha, 'DD-MM-YYYY');
        if ($codigo_articulo != "" && $precio != "" && $fecha != "") {
            $registrado_por = $idusu;
            $registrado_el = antisqlinyeccion($ahora, "text");
            $fecha = date("Y-m-d", strtotime($fecha));

            /////busca si no existe
            $consulta = "SELECT idfob from proveedores_fob
            where idproveedor = $idproveedor and codigo_articulo = '$codigo_articulo' and estado = 1
            ";
            $rs_fob = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            ////////inserta
            if (intval($rs_fob->fields['idfob']) > 0) {
                $idfob = intval($rs_fob->fields['idfob']);
                $consulta = "
                update proveedores_fob 
                set
                 precio=$precio,
                 fecha='$fecha',
                 registrado_el=$registrado_el,
                 registrado_por=$registrado_por
                where
                 idfob=$idfob
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            } else {
                $idfob = select_max_id_suma_uno("proveedores_fob", "idfob")["idfob"];
                $consulta = "
                insert into proveedores_fob
                (idfob, idproveedor, codigo_articulo,precio,fecha,registrado_el, registrado_por, estado)
                values
                ($idfob, $idproveedor, '$codigo_articulo', $precio, '$fecha', $registrado_el, $registrado_por, 1
                )
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }
        }
    }

} else {
    echo $error;
}
