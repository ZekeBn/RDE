<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$dirsup = "S";
$submodulo = "134";
require_once("../includes/rsusuario.php");

$errores = "";
if (isset($_POST['idconteo'])) {
    $idconteo = $_POST['idconteo'];
}




$consulta = "SELECT conteo_detalles.unicose,conteo_detalles.diferencia, conteo_detalles.idconteo, conteo_detalles.idinsumo,conteo_detalles.descripcion,
conteo_detalles.cantidad_contada,conteo_detalles.lote,conteo_detalles.vencimiento,
conteo_detalles.fila,conteo_detalles.columna,
gest_deposito_almcto_grl.nombre  as almacenamiento,
(
    select 
        CONCAT(nombre,' ',COALESCE(cara, '')) 
    from 
        gest_deposito_almcto 
    where 
    gest_deposito_almcto.idalm = conteo_detalles.idalm
) as tipo_almacenamiento,
(
    select 
        nombre
    from 
    gest_almcto_pasillo 
    where 
    gest_almcto_pasillo.idpasillo = conteo_detalles.idpasillo
) as pasillo,
(
    select 
        nombre
    from
        medidas
    where
        medidas.id_medida = conteo_detalles.idmedida_ref
    ) as medida_ref
from conteo_detalles
inner join gest_deposito_almcto on  gest_deposito_almcto.idalm = conteo_detalles.idalm
    inner join gest_deposito_almcto_grl on gest_deposito_almcto_grl.idalmacto = gest_deposito_almcto.idalmacto
where
idconteo = $idconteo
order by fechahora desc
limit 100
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
<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad(Unidades)</th>
                <th>Diferencia</th>
                <th>Lote</th>
                <th>Vencimiento</th>
                <th>Almacenamiento</th>
                <th>Almacenado en</th>
                <th>Fila</th>
                <th>Columna</th>
                <th>Pasillo</th>
                <th>Medida_ref</th>
            </tr>
        </thead>
        <tbody>
            <?php
             if ($rs->RecordCount() > 0) {
                 while (!$rs->EOF) {
                     $unicose_det = $rs->fields['unicose'];
                     ?>
            <tr>
                
                <td><?php echo antixss($rs->fields['descripcion']); ?></td>
                <td align="center"><?php echo formatomoneda($rs->fields['cantidad_contada'], 2, 'N'); ?></td>
                <td align="center"><?php echo formatomoneda($rs->fields['diferencia'], 2, 'N'); ?></td>
                <td><?php echo antixss($rs->fields['lote']); ?></td>
                <td> Vencimiento: <?php echo $rs->fields['vencimiento'] ? date("d/m/Y", strtotime($rs->fields['vencimiento'])) : "--" ?> <br> Lote: <?php echo ($rs->fields['lote'])   ?> </td>
                <td><?php echo antixss($rs->fields['almacenamiento']); ?></td>
                <td><?php echo antixss($rs->fields['tipo_almacenamiento']); ?></td>
                <td><?php echo antixss($rs->fields['fila']); ?></td>
                <td><?php echo antixss($rs->fields['columna']); ?></td>
                <td><?php echo antixss($rs->fields['pasillo']); ?></td>
                <td><?php echo antixss($rs->fields['medida_ref']); ?></td>
                
            </tr>

            <?php
                     $rs->MoveNext();
                 }
             }
?>
           
        </tbody>
    </table>
</div>