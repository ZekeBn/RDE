 <?php
    require_once("includes/conexion.php");
require_once("includes/funciones.php");

// nombre del modulo al que pertenece este archivo
$modulo = "29";
$submodulo = "354";
require_once("includes/rsusuario.php");
$consulta = "
    select *
    from tmp_detalles_eventos_factu 
    where 
    estado <> 6 and ualta = $idusu
    ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rs->fields['idevento']) > 0) {
    ?>
    <div class="table-responsive">
        <table width="100%" class="table table-bordered jambo_table bulk_action">
        <thead>
            <tr>
                <th></th>
                <th align="center">Evento a Facturar</th>
                <th align="center">Monto Pedido</th>
                <th align="center">Monto Abonado</th>
                <th align="center">Saldo</th>
            </tr>
        </thead>
        <tbody>
    <?php while (!$rs->EOF) {
        $monto_saldo_acum += $rs->fields['saldo'];
        $monto_tot_acum += $rs->fields['monto_pedido'];
        $monto_cobrado_acum += $rs->fields['cobrado_pedido'];
        ?>
            <tr>
                <td>
                    <div class="btn-group"> 
                        <a onclick ="borrar_carrito_evento(<?php echo $rs->fields['iddet']; ?>)" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                    </div>
                </td>
                <td align="center"><?php echo antixss($rs->fields['nombre_evento']); ?></td>
                <td align="center"><?php echo formatomoneda($rs->fields['monto_pedido'], 4, 'N'); ?></td>
                <td align="center"><?php echo formatomoneda($rs->fields['cobrado_pedido'], 4, 'N'); ?></td>
                <td align="center"><?php echo formatomoneda($rs->fields['saldo'], 4, 'N'); ?></td>
            
            </tr>
    <?php $rs->MoveNext();
    } //$rs->MoveFirst();?>
            <tr >
                <td>
                    Totales
                </td>
                <td align="center"></td>
                <td align="center"><?php echo formatomoneda($monto_tot_acum, 4, 'N'); ?></td>
                <td align="center"><?php echo formatomoneda($monto_cobrado_acum, 4, 'N'); ?></td>
                <td align="center"><?php echo formatomoneda($monto_saldo_acum, 4, 'N'); ?></td>
            </tr>
        </tbody>
        </table>
    </div>
    <br />

                        <?php }?>    
                        <div class="clearfix"></div>
