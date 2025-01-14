<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");

$consulta2 = "
select * 
from usuarios 
where 
estado = 1
";
$rs2 = $conexion->Execute($consulta2) or die(errorpg($conexion, $consulta2));



?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Categoria Modulo ajax</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                  <select class="form-control" name="idusu_asignado_select" id="idusu_asignado_select">
                  <?php
                    foreach ($rs2 as $value) {
                        if (intval($value["idusu"]) == intval($rs->fields['idusu_asignado'])) {
                            echo '<option value="'. intval($value["idusu"]) .'" selected>'.$value["nombres"].'</option>';
                        } else {
                            echo '<option value="'. intval($value["idusu"]) .'">'.$value["nombres"].'</option>';
                        }
                    }
?>
                  </select>
                  <br>
                  <select class="form-control" name="idcategorias" id="idcategorias">
                  </select>
                  <br>
                  <select class="form-control" name="idsubcategorias" id="idsubcategorias">
                  </select>
                  <br>
             

                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            

            
            
            
          </div>
        </div>
        <!-- /page content -->
		  
        <!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        
            <div class="modal-header">
            	<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
           		<h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
            	Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
            	<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script>
//   $.ajax({
//     type: "POST"
//     , url: "http://localhost/ekaru/pruebas/categorias_endpoint.php"
//     , data: {
//         "idusu": "hola ramon"
//     } // or {"on": true} 
// }).done(function (data, textStatus, jqXHR) {
//     console.log(data);
// });


// optionValue = "a";
// optionText = "batman";
// categorias.append(`<option value="${optionValue}">
//                                        ${optionText}
//                                   </option>`);
// categorias.append(`<option value="b">
//       Batman2
// </option>`);



$( document ).ready(function() {
  categorias=$("#idcategorias");
  sub_categoria=$("#idsubcategorias");
  $.ajax({
      type: "GET"
      , url: "http://localhost/ekaru/pruebas/categorias_endpoint.php"
      , data: {
          
      }  
  }).done(function (data, textStatus, jqXHR) {
      console.log((JSON.parse(data)).length);
      var datos = (JSON.parse(data));
      for(var i=0;i<=datos.length; i++){
        if ((datos[i])){
          categorias.append(`<option value="${datos[i]["id_categoria"]}">
                                          ${datos[i]["nombre"]}
                                      </option>`);

        }
      }

  });


  categorias.on( "change", function() {
  $.ajax({
    type: "GET"
    , url: "http://localhost/ekaru/pruebas/sub_categorias_endpoint.php"
    , data: {
        "idcategoria":categorias.val()
    }  
  }).done(function (data, textStatus, jqXHR) {
      console.log((JSON.parse(data))[0]);
      var datos = JSON.parse(data);
      sub_categoria.find("option").remove();
      for(var i=0;i<=datos.length; i++){
        if((datos[i])){
          sub_categoria.append(`<option value="${datos[i]["idsubcate"]}">
                                          ${datos[i]["descripcion"]}
                                      </option>`);

        }
      }
      
  });
  });

});
</script>
        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
