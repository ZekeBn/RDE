 <?php
/*--------------------------
01/08/2021

----------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");
//



?>
<div class="col-md-12 col-sm-12 form-group has-feedback">
    <input type="text" class="form-control has-feedback-left" id="busquedatxt" autofocus name="busquedatxt" placeholder="Busqueda de productos" onkeyup="esteproducto(this.value,event)">
    <span class="fa fa-user form-control-feedback left" aria-hidden="true"></span>
</div>
<div class="row"></div>
<div id="filtradoprodli" class="col-md-12 col-sm-12 " style="height: 280px;overflow-y:scroll">


</div>
