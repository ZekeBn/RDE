 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "29";
$submodulo = "347";
require_once("includes/rsusuario.php");

?>
<div class="col-md-12">
    <div class="col-md-6 col-sm-6 form-group" >
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Descripci&oacute;n </label>
        <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="describetipoev" id="describetipoev" value="" class="form-control">                    
        </div>
    </div>
    <div class="col-md-6 col-sm-6 form-group">
            <button type="button"  class="btn btn-primary go-class" onClick="registrar();"><span class="fa fa-check-square-o"></span> Registrar</button>
    </div>
</div>
