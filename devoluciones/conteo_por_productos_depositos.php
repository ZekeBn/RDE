<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$dirsup = "S";
$modulo = "1";
$submodulo = "613";
require_once("../includes/rsusuario.php");

$errores = "";
if (isset($_POST['iddevolucion'])) {
    $iddevolucion = $_POST['iddevolucion'];
}
$agregar = intval($_POST['agregar']);
$borrar = intval($_POST['borrar']);
$editar = intval($_POST['editar']);
$idinsumo = intval($_GET['idinsumo']);
if ($idinsumo == 0) {
    $idinsumo = intval($_POST['idinsumo']);
}
if ($agregar == 1) {
    $idinsumo = intval($_POST['idinsumo']);
    $idproducto = intval($_POST['idproducto']);
    $cantidad = floatval($_POST['cantidad']);
    $iddevolucion = intval($_POST['iddevolucion']);
    $lote = antisqlinyeccion($_POST['lote'], "text");
    $comentario = antisqlinyeccion($_POST['comentario'], "text");
    $vencimiento = antisqlinyeccion($_POST['vencimiento'], 'date');
    $iddeposito = intval($_POST['iddeposito']);
    $idmedida = intval($_POST['idmedida']);
    $valido = "S";

    if ($iddevolucion == 0) {
        $valido = "N";
        $errores .= "El id de devolucion no puede ser 0 o nulo.<br>";
    }
    if ($idinsumo == 0) {
        $valido = "N";
        $errores .= "El id del insumo no puede ser 0 o nulo.<br>";
    }



    $consultas = "SELECT * 
    FROM devolucion_det
    WHERE 
    devolucion_det.iddevolucion = $iddevolucion
    and devolucion_det.idproducto = $idproducto
    and (devolucion_det.lote = $lote or devolucion_det.lote is NULL  )
    and ( DATE_FORMAT(devolucion_det.vencimiento, '%Y-%m-%d') = $vencimiento or devolucion_det.vencimiento is NULL) 
    ";

    $rs_verificar = $conexion->Execute($consultas) or die(errorpg($conexion, $consultas));
    $iddevolucion_verificar = intval($rs_verificar->fields['iddevolucion_det']);

    if ($iddevolucion_verificar > 0) {
        $valido = "N";
        $errores .= "Ya existe un articulo identico cargado, favor editelo o eliminelo.<br>";
    }





    if ($valido == 'S') {

        $iddevolucion_det = select_max_id_suma_uno("devolucion_det", "iddevolucion_det")["iddevolucion_det"];

        $consulta = "
            insert into devolucion_det
            (iddevolucion_det, iddevolucion, idproducto, iddeposito, cantidad, comentario, lote, vencimiento, idmedida)
            values
            ($iddevolucion_det, $iddevolucion, $idproducto, $iddeposito, $cantidad, $comentario, $lote, $vencimiento, $idmedida)
            ";
        // echo $consulta;exit;
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }


}
if ($borrar == 1) {
    $iddevolucion_det = $_POST['iddevolucion_det'];
    $consulta = "DELETE FROM devolucion_det WHERE iddevolucion_det=$iddevolucion_det";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}
if ($editar == 1) {
    $idproducto = intval($_POST['idproducto']);
    $iddeposito = intval($_POST['iddeposito']);
    $idmedida = intval($_POST['idmedida']);
    $iddevolucion_det = intval($_POST['iddevolucion_det']);
    $cantidad = intval($_POST['cantidad']);
    $lote = antisqlinyeccion(($_POST['lote']), 'text');
    $comentario = antisqlinyeccion(($_POST['comentario']), 'text');
    $vencimiento = antisqlinyeccion(($_POST['vencimiento']), 'date');

    //////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////VERIFICACIONES ////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////

    // validaciones basicas
    $valido = "S";

    // verifica si existe en la devolucion

    // encontrando iddevolucion
    $consultas = "SELECT devolucion_det.iddevolucion 
    FROM devolucion_det
    WHERE 
    devolucion_det.iddevolucion_det = $iddevolucion_det
    ";

    $rs_devolucion = $conexion->Execute($consultas) or die(errorpg($conexion, $consultas));
    $iddevolucion = intval($rs_devolucion->fields['iddevolucion']);

    $consultas = "SELECT * 
    FROM devolucion_det
    WHERE 
    devolucion_det.iddevolucion = $iddevolucion
    and devolucion_det.idproducto = $idproducto
    and (devolucion_det.lote = $lote or devolucion_det.lote is NULL  )
    and ( DATE_FORMAT(devolucion_det.vencimiento, '%Y-%m-%d') = $vencimiento or devolucion_det.vencimiento is NULL) 
    ";

    $rs_verificar = $conexion->Execute($consultas) or die(errorpg($conexion, $consultas));
    $iddevolucion_verificar = intval($rs_verificar->fields['iddevolucion_det']);

    if ($iddevolucion_verificar > 0 && $iddevolucion_verificar != $iddevolucion_det) {
        $valido = "N";
        $errores .= "Ya existe un articulo identico cargado, favor editelo o eliminelo.<br>";
    }


    if ($valido == 'S') {

        if ($idproducto > 0) {

            //actualiza
            $consulta = "UPDATE 
                        devolucion_det
                    set
                        idproducto = $idproducto,
                        iddeposito = $iddeposito,
                        cantidad = $cantidad,
                        comentario = $comentario, 
                        lote = $lote,
                        vencimiento = $vencimiento, 
                        idmedida = $idmedida
                    where
                        iddevolucion_det=$iddevolucion_det
                    ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }
    }
}


$consulta = " 
SELECT devolucion_det.cantidad, devolucion_det.iddevolucion_det, 
devolucion_det.lote, devolucion_det.vencimiento, devolucion_det.comentario, 
insumos_lista.descripcion, devolucion_det.iddeposito,devolucion_det.idproducto,
(SELECT nombre
        FROM medidas
        WHERE medidas.id_medida = devolucion_det.idmedida ) AS medida
FROM devolucion_det
INNER JOIN insumos_lista on insumos_lista.idproducto = devolucion_det.idproducto
INNER JOIN devolucion on devolucion.iddevolucion = devolucion_det.iddevolucion
WHERE 
devolucion_det.iddevolucion = $iddevolucion
and devolucion.estado = 1 
and devolucion.idempresa = $idempresa
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));










?>
<script>
  function cerrar_error_guardar(event){
        event.preventDefault();
        $('#boxErroresArticulosGuardar').removeClass('show');
        $('#boxErroresArticulosGuardar').addClass('hide');
    }
    
</script>
<?php if ($errores != "") { ?>
  <div class="alert alert-danger alert-dismissible fade in " role="alert" id="boxErroresArticulosGuardar">
            <button type="button" class="close" onclick="cerrar_error_guardar(event)" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
            <strong>Errores:</strong><br /><p id="erroresArticulosModal"><?php echo $errores;?></p>
        </div>
<?php } ?>


<!-- require tabla de productos a devolver-->
<div id="buscador_conteo">
  <?php require_once("./buscador_conteo_deposito.php"); ?>
</div>
<br>
<h2>Articulos a Devolver</h2>

<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th></th>
                <th>idproducto</th>
                <th>Producto</th>
                <th>Cantidad(Unidades)</th>
                <th>Vencimiento</th>
                <th>Comentario</th>
                <th>Medida_ref</th>
            </tr>
        </thead>
        <tbody>
            <?php
             if ($rs->RecordCount() > 0) {
                 while (!$rs->EOF) {
                     $iddevolucion_det = $rs->fields['iddevolucion_det'];
                     ?>
            <tr>
                <td align="center">
                    <a href="javascript:void(0);" onclick="editar_articulo(<?php echo $iddevolucion_det ?>);" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Editar"><span class="fa fa-edit"></span></a>
                    <a href="javascript:void(0);" onclick="eliminar_articulo(<?php echo $iddevolucion_det ?>);" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                </td>
                <td><?php echo intval($rs->fields['idproducto']); ?></td>
                <td><?php echo antixss($rs->fields['descripcion']); ?></td>
                <td align="center"><?php echo formatomoneda($rs->fields['cantidad'], 2, 'N'); ?></td>
                <td> Vencimiento: <?php echo $rs->fields['vencimiento'] ? date("d/m/Y", strtotime($rs->fields['vencimiento'])) : "--" ?> <br> Lote: <?php echo ($rs->fields['lote'])   ?> </td>
                <td><?php echo antixss($rs->fields['comentario']); ?></td>
                <td><?php echo antixss($rs->fields['medida']); ?></td>
                
            </tr>

            <?php
                     $rs->MoveNext();
                 }
             }
?>
           
        </tbody>
    </table>
</div>
