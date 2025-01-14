<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_compras.php");
require_once("../includes/funciones_proveedor.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");

if ($_POST['idinsumo'] > 0) {
    $idinsumo = intval($_POST['idinsumo']);
}
$buscar = "SELECT 
            DISTINCT(lote), 
            vencimiento, descripcion 
          FROM 
            gest_depositos_stock 
          WHERE 
            gest_depositos_stock.idproducto = $idinsumo 
          ORDER BY 
            idregseriedptostk desc 
";
$rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

?>
<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Lote</th>
                <th>Vencimiento</th>
                <th>Descripcion</th>
            </tr>
        </thead>
        <tbody>
            <?php
             if ($rs->RecordCount() > 0) {
                 while (!$rs->EOF) {
                     ?>
            <tr>
                <td align="center"><?php echo antixss($rs->fields['lote']); ?></td>
                <td align="center">  <?php echo $rs->fields['vencimiento'] ? date("d/m/Y", strtotime($rs->fields['vencimiento'])) : "--" ?> </td>                
                <td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
            </tr>

            <?php
                     $rs->MoveNext();
                 }
             }
?>
           
        </tbody>
    </table>
</div>