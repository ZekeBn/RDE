 <?php
$idpedido = intval($_POST['idpedido']);
?><div class="form-group">
    
<p><a href="#" class="btn btn-sm btn-default" onClick="agrega_cliente(<?php echo $idpedido; ?>);"><span class="fa fa-plus"></span> Agregar</a></p>
<hr />

    
    <div class="col-md-6 col-sm-6 col-xs-12 form-group has-feedback">
        <input type="text" class="form-control" placeholder="RUC" id="ruc_cent" onkeyup="busca_cliente_res('ruc',<?php echo $idpedido; ?>);">
        <span class="fa fa-search form-control-feedback right" aria-hidden="true"></span>
    </div>
    <div class="col-md-6 col-sm-6 col-xs-12 form-group has-feedback">
        <input type="text" class="form-control" placeholder="Razon Social" id="razon_social_cent" onkeyup="busca_cliente_res('razon_social',<?php echo $idpedido; ?>);">
        <span class="fa fa-search form-control-feedback right" aria-hidden="true"></span>
    </div>
    <div class="col-md-6 col-sm-6 col-xs-12 form-group has-feedback">
        <input type="text" class="form-control" placeholder="Nombre de Fantasia" id="fantasia_cent" onkeyup="busca_cliente_res('fantasia',<?php echo $idpedido; ?>);">
        <span class="fa fa-search form-control-feedback right" aria-hidden="true"></span>
    </div>
    
    <div class="col-md-6 col-sm-6 col-xs-12 form-group has-feedback">
        <input type="text" class="form-control" placeholder="Documento" id="documento_cent" onkeyup="busca_cliente_res('documento',<?php echo $idpedido; ?>);">
        <span class="fa fa-search form-control-feedback right" aria-hidden="true"></span>
    </div>
    
    
    <div class="clearfix"></div>
</div>

<div id="busqueda_cli"></div>

<div class="clearfix"></div> 
