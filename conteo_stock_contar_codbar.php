 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "134";
require_once("includes/rsusuario.php");

require_once("includes/funciones_stock.php");

$idconteo = intval($_GET['id']);
if (intval($idconteo) == 0) {
    header("location: conteo_stock.php");
    exit;
}

$consulta = "
select *,
(select descripcion from gest_depositos where iddeposito = conteo.iddeposito)  as deposito,
(select estadoconteo from estado_conteo where idestadoconteo = conteo.estado ) as estadoconteo,
(select usuario from usuarios where idusu = conteo.iniciado_por) as usuario
from conteo
where
estado <> 6
and (estado = 1 or estado = 2)
and idconteo = $idconteo
and afecta_stock = 'N'
and fecha_final is null
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito = intval($rs->fields['iddeposito']);
if (intval($rs->fields['idconteo']) == 0) {
    header("location: conteo_stock.php");
    exit;
}
//$fecha_inicio=date("Y-m-d");




?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script>
function calcular_dif(id){
    conteo_guarda_tmp(id);
}
function calcular_dif_cbar(id){
    conteo_guarda_tmp_cbar(id);
}
function accionbtn(cod){
    $("#accion").val(cod);    
    $("#form1").submit();
    $("#submit_1").hide();
    $("#submit_2").hide();
    $("#submit_3").hide();
}
function conteo_guarda_tmp(id){
    var campo_cant = "cont_"+id;
    var campo_name = $('#'+campo_cant).attr('name');
    var idprod_s = campo_name.split("_");
    var idprod = idprod_s[1];
    //alert(idprod);
    
    var cantidad = $("#"+campo_cant).val();
        var parametros = {
                    "accion" : 1,
                    "cant" : cantidad,
                    "idprod" : idprod,
                    "id" : <?php echo $idconteo; ?>
                    
           };
          $.ajax({
                    data:  parametros,
                    url:   'conteo_guarda_tmp.php',
                    type:  'post',
                    beforeSend: function () {
                        $("#dif_"+id).html('Guardando...');
                    },
                    success:  function (response) {
                        $("#dif_"+id).html(response);
                        if(cantidad == ''){
                         actualiza_listado();
                        }
                    }
            });    

}
function conteo_guarda_tmp_cbar(id){
    var campo_cant = "cont_bus_"+id;
    var campo_name = $('#'+campo_cant).attr('name');
    var idprod_s = campo_name.split("_");
    var idprod = idprod_s[1];
    //alert(idprod);
    
    var cantidad = $("#"+campo_cant).val();
        var parametros = {
                    "accion" : 1,
                    "cant" : cantidad,
                    "idprod" : idprod,
                    "id" : <?php echo $idconteo; ?>
                    
           };
          $.ajax({
                    data:  parametros,
                    url:   'conteo_guarda_tmp.php',
                    type:  'post',
                    beforeSend: function () {
                        $("#dif_"+id).html('Guardando...');
                    },
                    success:  function (response) {
                        $("#dif_"+id).html(response);
                        $("#filtroprod").html('');    
                        actualiza_listado();
                    }
            });    

}
function actualiza_listado(){
    var direccionurl='conteo_stock_filtra_cbar.php';        
    var parametros = {
        "id"       : <?php echo $idconteo; ?>
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $("#filtroprod").html('Cargando...');                
        },
        success:  function (response) {
            $("#filtroprod").html(response);
            $("#insumo_box").html('');
            $("#codigobarra").focus();
        }
    });
}
function mantiene_session(){
    var f=new Date();
    cad=f.getHours()+":"+f.getMinutes()+":"+f.getSeconds(); 
    var parametros = {
                "ses" : cad,
       };
      $.ajax({
                data:  parametros,
                url:   'mantiene_session.php',
                type:  'post',
                beforeSend: function () {
                },
                success:  function (response) {
                    //alert(response);
                }
        });    
}
function buscar_producto_codbar(e){
    
    // que tecla presiono
    tecla = (document.all) ? e.keyCode : e.which;
    if (tecla==13){
        var codbar = $("#codbar").val();
        var direccionurl='conteo_stock_filtra.php';        
        var parametros = {
          "codbar"   : codbar,
          "id"       : <?php echo $idconteo; ?>
        };
        $.ajax({          
            data:  parametros,
            url:   direccionurl,
            type:  'post',
            beforeSend: function () {
                $("#filtroprod").html('Cargando...');                
            },
            success:  function (response) {
                $("#filtroprod").html(response);
                if (tecla==13){
                    $("#cont_1").focus();
                }
            }
        });
    }
}
function busca_insumo_cbar(e){
    var codbar = $("#codigobarra").val();
    tecla = (document.all) ? e.keyCode : e.which;
    // tecla enter
      if (tecla==13){
        var valor = $("#codigobarra").val();
        $("#codigop").val(''); 
        var parametros = {
          "codigobarra" : valor,
          "id" : <?php echo $idconteo; ?>
        };
        $.ajax({
            data:  parametros,
            url:   'conteo_stock_cbar.php',
            type:  'post',
            beforeSend: function () {
                    $("#insumo_box").html("Cargando...");
            },
            success:  function (response) {
                    $("#insumo_box").html(response);
                    $("#codigobarra").val('');
                    $("#cont_bus_1").focus();
            }
        });    
    }
}
</script>
<style>
.mt-1{
    margin-top: 20px; !important;
}
</style>
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
                    <h2>Conteo #<?php echo $rs->fields['idconteo']; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p>
<a href="conteo_stock.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
<a href="conteo_importar.php?id=<?php echo $idconteo;  ?>" class="btn btn-sm btn-default"><span class="fa fa-upload"></span> Carga Masiva</a>
</p>
<hr />

<div class="table-responsive">
<table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
    <tr>
      <th><strong># Conteo</strong></th>
      <th><strong>Deposito</strong></th>
      <th><strong>Iniciado Por</strong></th>
      <th><strong>Estado</strong></th>
      </tr>
       </thead>
        </tbody>
    <tr>
      <td align="center"><?php echo $rs->fields['idconteo']; ?></td>
      <td align="center"><?php echo $rs->fields['deposito']; ?></td>
      <td align="center"><?php echo $rs->fields['usuario']; ?></td>
      <td align="center"><?php echo $rs->fields['estadoconteo']; ?></td>
      </tr>
  </tbody>
</table>
</div>



<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo de Barras *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="codigobarra" id="codigobarra" value="" placeholder="Codigo de Barras" class="form-control" required="required" onkeyup="busca_insumo_cbar(event);" />                    
    </div>
</div>

<div class="clearfix"></div>
<br />

<div id="insumo_box">
</div>

<div id="filtroprod">
<?php require_once("conteo_stock_filtra_cbar.php"); ?>
</div>

<div class="clearfix"></div>
<br />

<div class="form-group">
    <div class="col-md-12 col-sm-12 col-xs-12 text-center">
    
        <div class="col-md-4 col-sm-4 col-xs-12 text-center mt-1">
       <button type="button" name="submit" id="submit1" class="btn btn-default" onmouseup="accionbtn(1);"><span class="fa fa-clock-o"></span> Continuar mas tarde</button>
       </div>
       
       <div class="col-md-4 col-sm-4 col-xs-12 text-center mt-1">
       <button type="button" name="submit2" id="submit2" class="btn btn-success" onmouseup="accionbtn(2);"><span class="fa fa-ban"></span> Finalizar sin afectar stock</button>
       </div>
       
       <div class="col-md-4 col-sm-4 col-xs-12 text-center mt-1">
       <button type="button" name="submit3" id="submit3" class="btn btn-success" onmouseup="accionbtn(3);"><span class="fa fa-check-square-o"></span> Finalizar y afectar stock</button>
       </div>
       
    </div>
</div>


<div class="clearfix"></div>
<br />
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
