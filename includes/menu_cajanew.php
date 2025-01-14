

<div class="btn-group">
<button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='informe_caja_new.php'"><span class="fa fa-reply"></span>&nbsp;Volver a Cajas</button>

<button class="btn btn-sm btn-<?php if ($refemenu == 'res') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='informe_caja_arq_new.php?id=<?php echo $idcaja; ?>'"><span class="fa fa-search"></span>&nbsp;Resumen</button>
<button class="btn btn-sm btn-<?php if ($refemenu == 'det') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='informe_caja_det_new.php?id=<?php echo $idcaja; ?>'"><span class="fa fa-search"></span>&nbsp;Detalle</button>


<?php if ($rsco->fields['eventos_infcaja'] == 'S') { ?>    
<button class="btn btn-sm btn-<?php if ($refemenu == 'eve') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='informe_caja_det_eventos.php?id=<?php echo $idcaja; ?>'"><span class="fa fa-search"></span>&nbsp;Eventos</button>    
<?php } ?>    
<?php
$consulta = "SELECT * FROM preferencias_caja WHERE  idempresa = $idempresa ";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>    
<?php if ($rsprefcaj->fields['mail_cierrecaja'] == 'S') { ?>    
<a href="informe_caja_arq_new_mail.php?id=<?php echo $idcaja; ?>" target="_blank" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-paper-plane"></span>&nbsp;Enviar Correo</a>
<?php } ?>
    
<a href="informe_caja_arq_new_pdf.php?id=<?php echo $idcaja; ?>" target="_blank" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-file-pdf-o"></span>&nbsp;Descargar PDF</a>
<?php if ($rs->fields['estado_caja'] == 3) { ?>
<a href="caja_cerrar_imprime_rei_adm.php?idcaja=<?php echo $idcaja; ?>" target="_blank" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-print"></span>&nbsp;Ticket Cierre</a>
<?php } ?>
<?php if ($rs->fields['rendido'] != 'S') {?>
<button class="btn btn-sm btn-<?php if ($refemenu == 'rend') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='informe_caja_arq_new.php?id=<?php echo $idcaja; ?>&r=s'" style="background-color:#F00; color:#FFF;"><span class="fa fa-times-circle"></span>&nbsp;No Rendido</button>
<?php } else { ?>
<button class="btn btn-sm btn-<?php if ($refemenu == 'rend') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='informe_caja_arq_new.php?id=<?php echo $idcaja; ?>&r=n'"  style="background-color:#090; color:#FFF;"><span class="fa fa-check-circle"></span>&nbsp;Rendido</button>
<?php } ?>



</div>

<br /> 
