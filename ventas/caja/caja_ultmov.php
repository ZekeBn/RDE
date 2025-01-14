

<div class="table-responsive">
<table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
    <tr>
        <th>Acci&oacute;n</th>
        <th>Tipo Mov</th>
        <th>Monto</th>
        <th>Autorizado / Obs</th>
        <th>Fecha/Hora</th>

    </tr>
    </thead>
    <tbody>
    <?php foreach ($datos as $valor) {
        $id = intval($valor['regunico']);
        $adm_reg = $valor['adm'];
        if ($adm_reg == '') {
            $adm_reg = 'N';
        }
        $url2 = "caja_anular_movimientos.php?tipo=".$valor['clase']."&unicoid=$id";
        $url2 = "javascript:void(0);";
        if ($valor['clase'] == 1) {
            $url1 = "caja_retiros_cajero_rei.php?tipo=1&regser=$id&r=2";
        }
        if ($valor['clase'] == 2) {
            $url1 = "caja_retiros_cajero_rei.php?tipo=2&regser=$id&r=2";
        }
        if ($valor['clase'] == 3) {
            $url1 = "inf_pagosxcaja_imp.php?id=$id&redir=3";
        }
        ?>
    <tr>
        <td>
			
        <a href="<?php echo $url1; ?>"  class="btn btn-sm btn-default" title="Imprimir" data-toggle="tooltip" data-placement="right"  data-original-title="Imprimir"><span class="fa fa-print"></span></a>
        <?php if ($adm_reg == 'N') {?>
<a href="<?php echo $url2; ?>" onclick="confirmar(<?php echo $id; ?>,<?php echo $valor['clase'] ?>)"  class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash"></span></a>
    <?php } else { ?>
			<a href="#" onclick="alert('Este registro lo cargo un administrador, y solo el administrador puede borrarlo.');"  class="btn btn-sm btn btn-dark" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash"></span></a>
			<?php } ?>
        
        </td>
        <td><?php echo $valor['tipo']; ?></td>
        <td><?php echo formatomoneda($valor['monto'], 4, 'N'); ?></td>
        <td><?php echo $valor['autorizado']; ?></td>
        <td><?php echo date("d/m/Y H:i:s", strtotime($valor['fecha'])); ?></td>
    </tr>
    <?php } ?>
    </tbody>
</table>
</div>

