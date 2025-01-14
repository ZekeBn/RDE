<?php
/*--------------------------------------------
Insertando proveedor a la db
30/5/2023
---------------------------------------------*/
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_proveedor.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");


$agregar_pais = intval($_POST["agregar_pais"]);
if ($agregar_pais > 0) {
    $valido = "S";
    $error = "";

    $idpais = $_POST["idpais"];
    $idmoneda = $_POST["idmoneda"];
    $consulta = "";

    $consulta = "
      update paises_propio
      set
            idmoneda = $idmoneda
      where
            idpais = $idpais
      ";


    //echo $consulta;
    $rsc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


}




?>
<div class="col-md-6 col-sm-6 col-xs-12 form-group">
      <label class="control-label col-md-3 col-sm-3 col-xs-12">
            Pais *
      </label>
      <div class="col-md-9 col-sm-9 col-xs-12">
            <?php

            // consulta

            $consulta = "
            SELECT p.idpais, p.nombre, p.idmoneda FROM paises_propio p
            WHERE p.estado = 1
            order by nombre asc;
            ";

// valor seleccionado
if (isset($rs->fields['idpais'])) {
    $value_selected = htmlentities($rs->fields['idpais']);
}
if (isset($_GET['idpais'])) {
    $value_selected = htmlentities($_GET['idpais']);
}
if (isset($_POST['idpais'])) {
    $value_selected = htmlentities($_POST['idpais']);
}



// parametros
$parametros_array = [
      'nombre_campo' => 'idpais',
      'id_campo' => 'idpais',

      'nombre_campo_bd' => 'nombre',
      'id_campo_bd' => 'idpais',

      'value_selected' => $value_selected,

      'pricampo_name' => 'Seleccionar...',
      'pricampo_value' => '',
      'data_hidden' => 'idmoneda',
      'style_input' => 'class="form-control"',
      'acciones' => ' required="required" onchange="verificar_pais(this)"'.$add,
      'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
      </div>
</div>