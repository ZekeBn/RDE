<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_proveedor.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "24";

require_once("../includes/rsusuario.php");
require_once("preferencias_proveedores.php");

// si envio bandera para agregar
if ($_POST['add'] == 'S') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // recibe parametros
    $diacredito = antisqlinyeccion($_POST['diacredito'], "text");
    if (trim($_POST['diacredito']) == '') {
        $valido = 'N';
        $errores .= " - El campo dia credito no puede estar vacio.<br />";
    }

    $consulta = "
    select * from tipo_credito where dias_credito = $diacredito and estado = 1 limit 1
    ";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsex->fields['idcredito'] > 0) {
        $valido = 'N';
        $errores .= " - El dia credito ya existe.<br />";
    }

    // si todo es correcto inserta
    if ($valido == "S") {
        $consulta = "
        select max(idcredito) as proxid from tipo_credito
        ";
        $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcredito = intval($rsex->fields['proxid']) + 1;
        $descripcion = "CREDITO A ".$diacredito." DIAS";
        $descripcion = str_replace("'", "", $descripcion);
        $consulta = "
        insert into tipo_credito
        (idcredito, descripcion, dias_credito, tolerancia, estado)
        values
        ($idcredito, '$descripcion', $diacredito, 5, 1)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $res = [
            'valido' => $valido,
            'errores' => $errores,
            'idcredito' => $idcredito
        ];

        // convierte a formato json
        $respuesta = json_encode($res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

        // devuelve la respuesta formateada
        echo $respuesta;
        exit;
    }
}

$buscar = "
select * from tipo_credito where estado = 1 order by dias_credito
";
$rsdc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
?>

<h2>Agregar Días de Créditos</h2>
<a href="#" onmouseup="recargar_tipo_credito(0);" class="btn btn-sm-btn-default"><span class="fa fa-refresh"></span> Actualizar Formulario</a>
<br/>
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">x</span></button>
    <strong>Errores:</strong><br/><?php echo $errores; ?>
</div>
<?php } ?>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Días de Créditos </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="number" name="diacredito" id="diacredito" value="" placeholder="Días de Créditos" class="form-control" autofocus />
        <br />
        <button type="button" class="btn btn-success" onmouseup="agregar_tipo_credito();" ><span class="fa fa-check-square-o"></span> Agregar</button>
    </div>
</div>

<input type="hidden" name="MM_insert" value="form1" />
<input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />

<div class="clearfix"></div>

<hr />
<strong>Ultimas 10 Agregadas:</strong>
<div class="table-responsive">
    <table class="table table-bordered jambo-table bulk_action">
        <thead>
        <tr>
            <th align="center">Días</th>
            <th align="center">Descripción</th>
        </tr>
        </thead>
        <tbody>
            <?php while (!$rsdc->EOF) { ?>
                <tr>
                    <td align="center"><?php echo trim($rsdc->fields['dias_credito']) ?></td>
                    <td align="center"><?php echo trim($rsdc->fields['descripcion'])?></td>
                </tr>

            <?php $rsdc->MoveNext();
            } ?>
        </tbody>
    </table>
</div>