 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "430";
require_once("includes/rsusuario.php");

if (intval($_POST['idcliente']) > 0) {
    $idcliente = intval($_POST['idcliente']);
}

if ($idcliente == 0) {
    echo "No se indico el cliente.";
    exit;
}

$consulta = "
select *,
(select usuario from usuarios where tmp_carrito_anticipos_fpag.registrado_por = usuarios.idusu) as registrado_por,
(select descripcion from formas_pago where idforma = tmp_carrito_anticipos_fpag.idformapago) as formapago,
(select nombre from bancos where idbanco = tmp_carrito_anticipos_fpag.idbanco) as banco,
(select nombre from bancos where idbanco = tmp_carrito_anticipos_fpag.idbanco_propio) as banco_propio
from tmp_carrito_anticipos_fpag 
where 
 registrado_por = $idusu
 and idcliente = $idcliente 
order by idcarritoanticiposfpag asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



?><div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Forma de pago</th>
            <th align="center">Datos Extra</th>
            <th align="center">Monto pago</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) {
    $monto_pago_acum += $rs->fields['monto_pago'];


    $banco = $rs->fields['banco'];
    $banco_propio = $rs->fields['banco_propio'];
    $transfer_numero = $rs->fields['transfer_numero'];
    $tarjeta_boleta = $rs->fields['tarjeta_boleta'];
    $cheque_numero = $rs->fields['cheque_numero'];
    $boleta_deposito = $rs->fields['boleta_deposito'];
    $retencion_numero = $rs->fields['retencion_numero'];


    $datos_extra = "";
    if (trim($banco) != '') {
        $datos_extra .= "Banco Cliente: $banco<br />";
    }
    if (trim($banco_propio) != '') {
        $datos_extra .= "Banco Destino: $banco_propio<br />";
    }
    if (trim($transfer_numero) != '') {
        $datos_extra .= "Transfer Nro.: $transfer_numero<br />";
    }
    if (trim($tarjeta_boleta) != '') {
        $datos_extra .= "Voucher Tarj Nro.: $tarjeta_boleta<br />";
    }
    if (trim($cheque_numero) != '') {
        $datos_extra .= "Cheque Nro.: $cheque_numero<br />";
    }
    if (trim($boleta_deposito) != '') {
        $datos_extra .= "Boleta Dep. Nro.: $boleta_deposito<br />";
    }
    if (trim($retencion_numero) != '') {
        $datos_extra .= "Retencion Nro.: $retencion_numero<br />";
    }
    ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="pagos_afavor_adh_car_fpag_del.php?id=<?php echo $rs->fields['idcarritoanticiposfpag']; ?>&idevento=<?php echo intval($_GET['idevento']); ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                </div>

            </td>
            <td align="center"><?php echo antixss($rs->fields['formapago']); ?>
            
            </td>
            <td align="center"><?php echo $datos_extra; ?>
            </td>
            <td align="center"><?php echo formatomoneda($rs->fields['monto_pago'], 4, 'N'); ?></td>
        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
        <tr style="background-color:#CCC; font-weight:bold;">
            <td>
Totales
            </td>
            <td align="center"></td>
            <td align="center"></td>
            <td align="center"><?php echo formatomoneda($monto_pago_acum, 4, 'N'); ?></td>
        </tr>
      </tbody>
    </table>
</div>
<br />
