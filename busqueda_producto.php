 <?php

$dest = htmlentities($_POST['dest']);

?>
<div class="form-group">
    <div class="col-md-6 col-sm-6 col-xs-12 form-group has-feedback">
        <input type="text" class="form-control" placeholder="Producto" id="producto" onkeyup="buscar_producto('<?php echo $dest ?>');">
        <span class="fa fa-search form-control-feedback right" aria-hidden="true"></span>
    </div>
    <div class="col-md-6 col-sm-6 col-xs-12 form-group has-feedback">
        <input type="text" class="form-control" placeholder="Codigo de Barras" id="codbar" onkeyup="buscar_producto_codbar(event,'<?php echo $dest ?>');">
        <span class="fa fa-barcode form-control-feedback right" aria-hidden="true"></span>
    </div>
    <div class="clearfix"></div>
</div>

<div id="busqueda_prod"></div>

<div class="clearfix"></div> 
