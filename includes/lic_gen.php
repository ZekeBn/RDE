<?php if (trim($mensajelic_rs) != '') { ?>
<?php //$atraso_rs=30; //if($pag=="index"){;?>
<?php if ($atraso_rs >= 1 && $atraso_rs < 30 && $pag == "index") { ?>
<div class="alert alert-info alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<?php echo $mensajelic_rs; ?> <button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='lic.php'"><span class="fa fa-search"></span> Mas info</button>
</div>
<?php } ?>
<?php if ($atraso_rs >= 30 && $atraso_rs < 60) { ?>
<div class="alert alert-warning alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Licencia:</strong><br /><?php echo $mensajelic_rs; ?> <button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='lic.php'"><span class="fa fa-search"></span> Mas info</button>
</div>
<?php } ?>
<?php if ($atraso_rs >= 60 && $atraso_rs < 90) { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<strong>Mensaje de Licencia:</strong><br /><?php echo $mensajelic_rs; ?> <button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='lic.php'"><span class="fa fa-search"></span> Mas info</button>
</div>
<?php } ?>
<?php if ($atraso_rs >= 90) { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<strong>MENSAJE URGENTE DE LICENCIA:</strong><br /><?php echo $mensajelic_rs; ?> <button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='lic.php'"><span class="fa fa-search"></span> Mas info</button>
</div>
<?php } ?>
<?php } ?>