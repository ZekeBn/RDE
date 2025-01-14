 <?php
/*------------------------------------------

Formulario de busqueda para clientes
UR: 10/11/2021

--------------------------------------------
*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "29";
$submodulo = "347";
require_once("includes/rsusuario.php");

?>
<div class="clearfix"></div>
<div  class="col-md-12">
    <div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Documento </label>
        <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="dcum" id="dcum" class="form-control" onKeyUp="bcliente(this.value,1);"> 
        </div>
    </div>
    <div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">RUC </label>
        <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="rucc" id="rucc"  class="form-control" onKeyUp="bcliente(this.value,3);"> 
        </div>
    </div>    
    <div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre</label>
        <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="ncc" id="ncc"  class="form-control" onKeyUp="bcliente(this.value,2);"> 
        </div>
    </div>

    
</div>
<div class="clearfix"></div>
<div class="col-md-12" id="cuerpoclientebusca">
    


</div>

    

