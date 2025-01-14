 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";

require_once("includes/rsusuario.php");



$consulta = "
SELECT *, insumos_lista.idproducto as prodins, recetas_detalles.idprod as prodrec, insumos_lista.costo,
medidas.nombre as medida
FROM recetas_detalles
inner join ingredientes on recetas_detalles.ingrediente = ingredientes.idingrediente
inner join insumos_lista on insumos_lista.idinsumo = ingredientes.idinsumo
inner join medidas on medidas.id_medida = insumos_lista.idmedida
where
recetas_detalles.idprod = $id
";
$rsrec = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>


<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
        <thead>
        <tr>
            <th></th>
              <th ><strong>Codigo</strong></th>
            <th ><strong>Ingrediente</strong></th>
            <th ><strong>Cantidad</strong></th>
            <th ><strong>Medida</strong></th>
            <th ><strong>Costo Unitario</strong></th>
            <th ><strong>Costo Total</strong></th>
            <th ><strong>Permite Sacar</strong></th>

      </tr>
      </thead>
      <tbody>
        <?php while (!$rsrec->EOF) {

            $cant_acum += $rsrec->fields['cantidad'];
            $costo_acum += $rsrec->fields['costo'];
            $costo_tot_acum += $rsrec->fields['costo'] * $rsrec->fields['cantidad'];

            ?>
        <tr <?php if ($rsrec->fields['prodins'] == $rsrec->fields['prodrec']) { ?>bgcolor="#CCC"<?php } ?>>
            <td>
                <?php if ($rsrec->fields['prodins'] != $rsrec->fields['prodrec']) { ?>
                <div class="btn-group">
                    <a href="javascript:editar_receta(<?php echo $rsrec->fields['idreceta_detalle']; ?>);void(0);" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                    <a href="javascript:void(0);" onClick="eliminar(<?php echo $rsrec->fields['idreceta_detalle']?>,'<?php echo str_replace("'", "\'", $rsrec->fields['descripcion']); ?>')" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                </div>
                <?php } ?>

            </td>
              <td align="left"><?php echo $rsrec->fields['idinsumo']?></td>
            <td height="34" align="left"><?php echo $rsrec->fields['descripcion']?></td>
            <td align="center"><?php echo formatomoneda($rsrec->fields['cantidad'], 4, 'N'); ?></td>
            <td align="center"><?php echo $rsrec->fields['medida']?></td>
            <td align="center"><?php echo formatomoneda($rsrec->fields['costo'], 4, 'N'); ?></td>
            <td align="center"><?php echo formatomoneda($rsrec->fields['costo'] * $rsrec->fields['cantidad'], 4, 'N'); ?></td>
            <td align="center"><?php echo siono($rsrec->fields['sacar']); ?></td>

        </tr>
        
        <?php $rsrec->MoveNext();
        }?>
        </tbody>
         <tfoot>
      <tr >
          <td align="left"><strong>Totales</strong></td>
            <td  align="left"></td>
            <td  align="left"></td>
            <td align="center"><strong><?php echo formatomoneda($cant_acum, 4, 'N'); ?></strong></td>
            <td align="center"></td>
            <td align="center"></td>
            <td align="center"><strong><?php echo formatomoneda($costo_tot_acum, 4, 'N'); ?></strong></td>
            <td align="center"></td>


        </tr>
         </tfoot>
    </table>
</div><br />
