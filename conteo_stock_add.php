 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "134";
require_once("includes/rsusuario.php");


$consulta = "
select tipo_conteo from preferencias_inventario limit 1
";
$rsprefinv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tipo_conteo = $rsprefinv->fields['tipo_conteo'];


if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

    // recibe parametros
    $fecha_inicio = antisqlinyeccion($ahora, "text");
    $iniciado_por = antisqlinyeccion($idusu, "int");
    $finalizado_por = antisqlinyeccion('', "int");
    $inicio_registrado_el = antisqlinyeccion($ahora, "text");
    $final_registrado_el = antisqlinyeccion('', "text");
    $estado = antisqlinyeccion(1, "int");
    $afecta_stock = antisqlinyeccion('N', "text");
    $fecha_final = antisqlinyeccion('', "text");
    $observaciones = antisqlinyeccion(' ', "text");
    $iddeposito = antisqlinyeccion($_POST['iddeposito'], "int");
    $totinsu = intval($_POST['totinsu']);

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // para evitar hack que colapse el servidor
    if (intval($_POST['totinsu']) > 1000) {
        $valido = "N";
        $errores .= " - La cantidad de grupos marcados supera el maximo permitido.<br />--> Intento de Hack Registrado ;)<br />";
        $totinsu = 1000;
    }
    if (intval($_POST['iddeposito']) == 0) {
        $valido = "N";
        $errores .= " - Debes seleccionar el deposito.<br />";
    }
    // buscamos que exista el deposito y su sucursal
    $consulta = "
    select * from gest_depositos
    where 
    idempresa = $idempresa
    and estado = 1
    and iddeposito = $iddeposito
    ";
    $rsdep = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $iddeposito = intval($rsdep->fields['iddeposito']);
    $idsucu = intval($rsdep->fields['idsucursal']);
    if ($iddeposito == 0) {
        $valido = "N";
        $errores .= " - Deposito inexistente.<br />";
    }




    //$valido="N";
    // validaciones especificas

    // no se puede iniciar un conteo por que este deposito ya tiene activo otro con el mismo grupo de insumos



    // validar grupo de insumos que al menos 1 este marcado
    $totinsu_valor = 0;
    for ($i = 0;$i <= $totinsu;$i++) {
        $idgrupoinsu = intval($_POST['grupo_'.$i]);
        if ($idgrupoinsu > 0) {
            // busca si existe en la bd y si le pertenece
            $consulta = " SELECT * FROM grupo_insumos where idempresa = $idempresa and idgrupoinsu = $idgrupoinsu and estado = 1 ";
            $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // si existe cuenta
            if ($rsex->fields['idgrupoinsu'] > 0) {
                $totinsu_valor++;
                $grupo_enc[$totinsu_valor] = $idgrupoinsu;
            }
        }

    }
    // si no selecciono ningun insumo
    if ($totinsu_valor == 0) {
        $valido = "N";
        $errores .= " - Debes marcar al menos 1 grupo de insumos.<br />";
    }

    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
        insert into conteo
        (fecha_inicio, iniciado_por, finalizado_por, estado, afecta_stock, fecha_final, observaciones,  idsucursal, idempresa, iddeposito, inicio_registrado_el, final_registrado_el)
        values
        ($fecha_inicio, $iniciado_por, $finalizado_por, $estado, $afecta_stock, $fecha_final, $observaciones,  $idsucu, $idempresa, $iddeposito, $inicio_registrado_el, $final_registrado_el)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
        select idconteo from conteo where idempresa = $idempresa and iniciado_por = $iniciado_por order by idconteo desc
        ";
        $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idconteo = intval($rsmax->fields['idconteo']);


        // inserta en conteo grupo
        foreach ($grupo_enc as $idgrupo) {
            $idgrupoinsu = $idgrupo;
            $consulta = "
            insert into conteo_grupos
            (idgrupoinsu, idconteo, idempresa)
            values
            ($idgrupoinsu, $idconteo, $idempresa)
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }


        if ($tipo_conteo == 'C') {
            $pag = "conteo_stock_contar_codbar.php?id=$idconteo";
        } else {
            $pag = "conteo_stock_contar.php?id=$idconteo";
        }

        header("location: $pag");
        exit;

    }

}

$consulta = "
select * from grupo_insumos
where
idempresa = $idempresa
and estado = 1
order by nombre desc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script>
function envia_form(){
    $("#button_cont").hide();
    setTimeout(function(){ $("#form1").submit(); }, 1000);
}
function marcar(source){
    checkboxes=document.getElementsByTagName('input'); //obtenemos todos los controles del tipo Input
    for(i=0;i<checkboxes.length;i++) { //recoremos todos los controles
        if(checkboxes[i].type == "checkbox"){ //solo si es un checkbox entramos
            checkboxes[i].checked=source.checked; //si es un checkbox le damos el valor del checkbox que lo llamÃ³ (Marcar/Desmarcar Todos)
        }
    }
}
</script>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
            <?php require_once("includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Agregar Conteo</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<form id="form1" name="form1" method="post" action="">
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<strong>Seleccione los Grupos de Stock:</strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
  <tr >
    <th align="center" style="text-align:center;"><div style="margin:0px auto; width:30px;"><input type="checkbox" name="checkbox" id="checkbox" onclick="marcar(this);" /></div></th>
    <th><strong>Grupo</strong></th>
  </tr>
  </thead>
  <tbody>
<?php
$i = 1;
while (!$rs->EOF) { ?>
  <tr class="tablaconborde">
    <td align="center" ><input type="checkbox" name="grupo_<?php echo $i; ?>" <?php if (intval($_POST['grupo_'.$i]) > 0) { ?>checked="checked"<?php } ?> value="<?php echo $rs->fields['idgrupoinsu']; ?>" /></td>
    <td><?php echo $rs->fields['nombre']; ?></td>
  </tr>
<?php $i++;
    $rs->MoveNext();
} ?>
  <tbody>
  </tbody>
</table>
</div>
<br /><br />

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Deposito *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
      
   <?php
// consulta
$consulta = "
Select iddeposito,descripcion,tiposala,color,direccion,usuario
from gest_depositos 
inner join usuarios on usuarios.idusu=gest_depositos.idencargado 
where 
gest_depositos.tiposala <> 3
order by descripcion ASC  
 ";

// valor seleccionado
if (isset($_POST['iddeposito'])) {
    $value_selected = htmlentities($_POST['iddeposito']);
} else {
    $value_selected = "";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'iddeposito',
    'id_campo' => 'iddeposito',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'iddeposito',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>         
    </div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
       <button type="button" class="btn btn-success" id="button_cont" onmouseup="envia_form();"><span class="fa fa-check-square-o"></span> Guardar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='conteo_stock.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" /><input type="hidden" name="totinsu" id="totinsu" value="<?php echo $i; ?>" />


</form>

<br /><br />

                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            

            
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
