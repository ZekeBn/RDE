<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");





?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <?php
  if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

      // validaciones basicas
      $valido = "S";
      $errores = "";

      // control de formularios, seguridad para evitar doble envio y ataques via bots
      if ($_SESSION['form_control'] != $_POST['form_control']) {
          $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
          $valido = "N";
      }
      if (trim($_POST['form_control']) == '') {
          $errores .= "- Control del formularios no activado.<br />";
          $valido = "N";
      }
      $_SESSION['form_control'] = md5(rand());
      // control de formularios, seguridad para evitar doble envio y ataques via bots


      // recibe parametros
      $categoria = antisqlinyeccion($_POST['categoria'], "int");
      $idsubcategoria = antisqlinyeccion($_POST['idsubcategoria'], "int");
      $estado = 1;
      // $registrado_por=$idusu;
      // $registrado_el=antisqlinyeccion($ahora,"text");




      if (trim($_POST['id_categoria']) == '') {
          $valido = "N";
          $errores .= " - El campo categoria no puede estar vacio.<br />";
      }
      if (trim($_POST['idsubcategorias']) == "") {
          $valido = "N";
          $errores .= " - El campo idsubcategoria no puede estar vacio.<br />";
      }
      // si todo es correcto inserta

      if ($valido == "S") {
          //no realiza la consulta porque no es necesario para la prueba
          // $consulta="
          // insert into motoristas
          // (motorista, idusu_asignado, estado, registrado_por, registrado_el)
          // values
          // ($motorista, $idusu_asignado, $estado, $registrado_por, $registrado_el)
          // ";
          // $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

          // header("location: categorias_ajax1.php");
          // exit;

      }

  }

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

?>
  <script>

      function IsJsonString(str) {
          try {
              JSON.parse(str);
          } catch (e) {
              return false;
          }
          return true;
      }
      function send_categoria(idcategoria){
        var parametros = {
          "idcategoria"   : idcategoria 
        };
        $.ajax({		  
          data:  parametros,
          url:   'sub_categorias_select.php',
          type:  'post',
          cache: false,
          timeout: 3000,  // I chose 3 secs for kicks: 5000
          crossDomain: true,
          beforeSend: function () {
            $("#subcat_box").html('Cargando...');				
          },
          success:  function (response) {
            $("#subcat_box").html(response);
          },
          error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
          }
        }).fail( function( jqXHR, textStatus, errorThrown ) {
          errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
        });
      }
      function nl2br (str, is_xhtml) {
        // http://kevin.vanzonneveld.net
        // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +   improved by: Philip Peterson
        // +   improved by: Onno Marsman
        // +   improved by: Atli Þór
        // +   bugfixed by: Onno Marsman
        // +      input by: Brett Zamir (http://brett-zamir.me)
        // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +   improved by: Brett Zamir (http://brett-zamir.me)
        // +   improved by: Maximusya
        // *     example 1: nl2br('Kevin\nvan\nZonneveld');
        // *     returns 1: 'Kevin<br />\nvan<br />\nZonneveld'
        // *     example 2: nl2br("\nOne\nTwo\n\nThree\n", false);
        // *     returns 2: '<br>\nOne<br>\nTwo<br>\n<br>\nThree<br>\n'
        // *     example 3: nl2br("\nOne\nTwo\n\nThree\n", true);
        // *     returns 3: '<br />\nOne<br />\nTwo<br />\n<br />\nThree<br />\n'
        var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>'; // Adjust comment to avoid issue on phpjs.org display
  
        return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
      }
      function errores_ajax_manejador(jqXHR, textStatus, errorThrown, tipo){
        // error
        if(tipo == 'error'){
          if(jqXHR.status == 404){
            alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
          }else if(jqXHR.status == 0){
            alert('Se ha rechazado la conexión.');
          }else{
            alert(jqXHR.status+' '+errorThrown);
          }
        // fail
        }else{
          if (jqXHR.status === 0) {
            alert('No conectado: verifique la red.');
          } else if (jqXHR.status == 404) {
            alert('Pagina no encontrada [404]');
          } else if (jqXHR.status == 500) {
            alert('Internal Server Error [500].');
          } else if (textStatus === 'parsererror') {
            alert('Requested JSON parse failed.');
          } else if (textStatus === 'timeout') {
            alert('Tiempo de espera agotado, time out error.');
          } else if (textStatus === 'abort') {
            alert('Solicitud ajax abortada.'); // Ajax request aborted.
          } else {
            alert('Uncaught Error: ' + jqXHR.responseText);
          }
        }
      }
      // ("#categoria").on("change",function(e){
      //   categoria(e.value);
      // });
      // document.getElementById("categoria").addEventListener("change", (event) => {
      //   categoria(event.value);
      // });
   
</script>
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
                  <!-- error MOSTRAR -->
                  <?php if (trim($errores) != "") { ?>
                  <div class="alert alert-danger alert-dismissible fade in" role="alert">
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
                  </button>
                  <strong>Errores:</strong><br /><?php echo $errores; ?>
                  </div>
                  <?php } ?>
                  <!-- FIN DE ERROR -->
                  <br>
                  <!-- COMIENZA EL FORM -->
                  <form id="form1" name="form1" method="post" action="">
                  <div >
                    <?php
                    // consulta
                    $consulta = "
                      SELECT id_categoria, nombre
                      FROM categorias
                      where
                      estado = 1
                      order by nombre asc
                      ";

// valor seleccionado
if (isset($_POST['id_categoria'])) {
    $value_selected = htmlentities($_POST['id_categoria']);
} else {
    $value_selected = htmlentities($rs->fields['id_categoria']);
}


// parametros
$parametros_array = [
  'nombre_campo' => 'id_categoria',
  'id_campo' => 'id_categoria',

  'nombre_campo_bd' => 'nombre',
  'id_campo_bd' => 'id_categoria',

  'value_selected' => $value_selected,

  'pricampo_name' => 'Seleccionar...',
  'pricampo_value' => '',
  'style_input' => 'class="form-control"',
  'acciones' => '   onchange="send_categoria(this.value)"',
  'autosel_1registro' => 'S'

];
// construye campo
echo campo_select($consulta, $parametros_array);
?>
                  </div>
                  <br>
                  <div id="subcat_box">
                    <?php require("sub_categorias_select.php"); ?>
                  </div>
                  <div class="clearfix"></div> 
                  <br>
                  <div class="form-group">
                    <div class="col-md-12 col-sm-12 col-xs-12 text-center">
                      <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
                    </div>
                  </div>
                  <input type="hidden" name="MM_insert" value="form1" />
                  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
                  <br />
                  </form>
                  <div class="clearfix"></div>
                  <!-- TERMINA EL FORM -->
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

        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
<script>
  // $( document ).ready(function() {
  //   function setCookie(cname, cvalue, exdays) {
  //   const d = new Date();
  //   d.setTime(d.getTime() + (exdays*24*60*60*1000));
  //   let expires = "expires="+ d.toUTCString();
  //   document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
  //   }
    
  //   function getCookie(cname) {
  //     let name = cname + "=";
  //     let decodedCookie = decodeURIComponent(document.cookie);
  //     let ca = decodedCookie.split(';');
  //     for(let i = 0; i <ca.length; i++) {
  //       let c = ca[i];
  //       while (c.charAt(0) == ' ') {
  //         c = c.substring(1);
  //       }
  //       if (c.indexOf(name) == 0) {
  //         return c.substring(name.length, c.length);
  //       }
  //     }
  //     return "";
  //   }
  //   categoria = $("#categoria").on( "change", function() {
  //     setCookie("categoria",categoria.val(),0.001); //0.001 es igual a 24 minutos
  //   });
    
  //   subCategoria = $("#idsubcategorias").on( "change", function() {
  //     setCookie("subCategoria",subCategoria.val(),0.001); //0.001 es igual a 24 minutos
  //   });
    
  //   if(getCookie("categoria")){
  //     categoria.val(getCookie("categoria"));
  //   }
  //   if(getCookie("subCategoria")){
  //     subCategoria.val(getCookie("subCategoria"));
  //   }
  // });
</script>
  </body>
</html>
