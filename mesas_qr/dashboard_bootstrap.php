<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("./mesas_qr_funciones.php");
require_once("./mesas_preferencias.php");
require_once("../includes/funciones_carrito.php");


// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";

$dirsup = "S";

if (!isset($_SESSION)) {
    session_start();
}

$idatc = intval($_SESSION['idatc']);

/*$id_mesa = intval($_SESSION['id_mesa']);
if( $id_mesa ==0 ){
  header("Location: ./mesas_qr.php");
}*/

$parametros_array = [
  "idatc" => $idatc
];
$rs_mesas_atc = buscar_idmesa_atc($parametros_array);
//$idatc = intval($rs_mesas_atc['idatc']);
$id_mesa = intval($rs_mesas_atc['idmesa']);
//print_r($rs_mesas_atc);exit;
if ($id_mesa == 0) {
    header("Location: ./mesas_qr.php");
    exit;
}




$logo = "../gfx/empresas/emp_0.png";



$buscar = "SELECT empresa FROM empresas WHERE 1 limit 1";
$rsn = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$nombreempresa = $rsn->fields['empresa'];



?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen_mesas.php"); ?>
  <script>
   
    function mesero(){
      var direccion="";
      var estado = 0;
      if($('#mesero').hasClass('activo')){
        var direccion="mesas_qr_eliminar_pedido.php";
        estado = 1;
      }else{
        var direccion="mesas_qr_crear_pedido.php";
        estado = 0;
      }
      var parametros = {
            "id_mesa"   : <?php echo $id_mesa ;?>,
            "tipo_pedido" : 1
      };
      // parametros = JSON.stringify(parametros);
      $.ajax({
                data:  parametros,
                url:   direccion,
                type:  'post',
                timeout: 5000,  // I chose 3 secs for kicks: 3000
                beforeSend: function () {
                  // $("#hola").html("cargando");
                },
                success:  function (response) {
                  var response = JSON.stringify(response);
                  if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
                    if(obj.success == true){
                      if (estado == 1){
                        $("#mesero").removeClass("activo");
                        $("#mesero_box").removeClass("white_text");
                        $("#mesero_box").addClass("black_text");
                        $("#mesero_titulo").html("Solicitar un mesero");
                        $("#mesero_texto").html("Presiona el Boton y un mesero se acercara en la brevedad.");
                      }else{
                        $("#mesero").addClass("activo");
                        $("#mesero_box").addClass("white_text");
                        $("#mesero_box").removeClass("black_text");
                        $("#mesero_titulo").html("Mesero Solicitado!");
                        $("#mesero_texto").html("Presiona el Boton y cancelaras el llamado.");

                      }
                    }else{

                      if(obj.logout == false) {
                        if (estado == 1){
                          $("#mesero").removeClass("activo");
                          $("#mesero_box").removeClass("white_text");
                          $("#mesero_box").addClass("black_text");
                          $("#mesero_titulo").html("Solicitar un mesero");
                          $("#mesero_texto").html("Presiona el Boton y un mesero se acercara en la brevedad.");
                        }else{
                          $("#mesero").addClass("activo");
                          $("#mesero_box").addClass("white_text");
                          $("#mesero_box").removeClass("black_text");
                          $("#mesero_titulo").html("Mesero Solicitado!");
                          $("#mesero_texto").html("Presiona el Boton y cancelaras el llamado.");
                        }
                      }else{
                        document.location.href='mesas_qr.php?id_mesa=<?php echo $id_mesa; ?>';
                      }
                    }
                  }else{
                    alert(response);
                  }

                  
                }
        });	
    }
   
    function ver_pedido(){
	    var html_btn = $("#pedidos").html();
      var direccion="mesas_qr_pedidos_grilla_bootstrap.php";
      var parametros = {
        "ver_pedido"   : 1
      };
      // parametros = JSON.stringify(parametros);
      $.ajax({ 
                data:  parametros,
                url:   direccion,
                type:  'post',
                timeout: 5000,  // I chose 3 secs for kicks: 3000
                beforeSend: function () {
					      $("#pedidos").html('Cargando...');
                },
                success:  function (response) {
                  if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
                    if(obj.logout == true){
                      document.location.href='mesas_qr.php?id_mesa=<?php echo $id_mesa; ?>';
                    }
                  }else{
                    $("#pedidos").html(html_btn);
                    $("#box_grilla_pedidos").html(response);
                    $("#box_grilla_pedidos").css("display","block");
                    $("#box_grilla_btn").css("display","none");
                    $(window).scrollTop(0);
                  }
                }
        });	

    }
    function cerrar_pedido(){
      $("#box_grilla_pedidos").css("display","none");
      $("#box_grilla_btn").css("display","block");

    }
    function pedir_cuenta_toggle(){
      var direccion="";
      var estado = 0;
      if($('#pedir_cuenta').hasClass('activo')){
        var direccion="mesas_qr_eliminar_pedido.php";
        estado = 1;
      }else{
        var direccion="mesas_qr_crear_pedido.php";
        estado = 0;
      }
      var parametros = {
            "id_mesa"   : <?php echo $id_mesa ;?>,
            "tipo_pedido" : 2
        };
      // parametros = JSON.stringify(parametros);
      $.ajax({
                data:  parametros,
                url:   direccion,
                type:  'post',
                timeout: 5000,  // I chose 3 secs for kicks: 3000
                beforeSend: function () {
                },
                success:  function (response) {
                  var response = JSON.stringify(response);
                  if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
                    if(obj.success == true){
                      if (estado == 1){
                        $("#pedir_cuenta").removeClass("activo");
                        $("#pedir_cuenta_box").removeClass("white_text");
                        $("#pedir_cuenta_box").addClass("black_text");
                        $("#pedir_cuenta_titulo").html("Pedir Cuenta ");
                        $("#pedir_cuenta_texto").html("Presiona el Boton y un mesero se acercara en la brevedad posible, le traera la cuenta de la mesa y podra proporcionarle su ruc y razon social para la factura.");

                      }else{
                        $("#pedir_cuenta").addClass("activo");
                        $("#pedir_cuenta_box").addClass("white_text");
                        $("#pedir_cuenta_box").removeClass("black_text");
                        $("#pedir_cuenta_titulo").html("Cuenta Solicitada!");
                        $("#pedir_cuenta_texto").html("Presiona el Boton y cancelara la solicitud de cuenta.");

                      }
                    }else{
                      if(obj.logout == false) {
                        if (estado == 1){
                          $("#pedir_cuenta").removeClass("activo");
                          $("#pedir_cuenta_box").removeClass("white_text");
                          $("#pedir_cuenta_box").addClass("black_text");
                        }else{
                          $("#pedir_cuenta").addClass("activo");
                          $("#pedir_cuenta_box").addClass("white_text");
                          $("#pedir_cuenta_box").removeClass("black_text");
                        }
                      }else{
                        document.location.href='mesas_qr.php?id_mesa=<?php echo $id_mesa; ?>';
                      }
                    }
                  }else{
                    alert(response);
                  }

                }
        });	

    }
    
    function agregarSombra(tipo) {
      if (tipo === 1) {
        solicitar_mesero();
      } else if (tipo === 2) {
        ver_pedido();
      } else if (tipo === 3) {
        pedir_cuenta();
      } else if (tipo === 4) {
        cancelar_pedido_mesero();
      } else if (tipo === 5) {
        cerrar_pedido();
      } else if (tipo === 6) {
        cancelar_pedir_cuenta();
      } else {
        console.log("Opción no válida");
      }
              
    }
    function salir(){
      var direccion="mesas_qr_logout.php?id_mesa=<?php echo $id_mesa; ?>";
      var parametros = {
            "reload" : 1
      };
      // parametros = JSON.stringify(parametros);
      var a;
      $.ajax({
                data:  parametros,
                url:   direccion,
                type:  'post',
                timeout: 5000,  // I chose 3 secs for kicks: 3000
                beforeSend: function () {
                },
                success:  function (response) {
					document.location.href='mesas_qr.php?id_mesa=<?php echo $id_mesa; ?>';
                 
                }
        });	
    }
    function IsJsonString(str) {
        try {
            JSON.parse(str);
        } catch (e) {
            return false;
        }
        return true;
    }

</script>
    <style>
        body{
            background: #E5E7EB;
        }
        .activo{
          background: #70AE6E !important;
        }
        .white_text i,
        .white_text h3,
        .white_text p {
          color: white !important;
        }

        .black_text i{
          color: #4B5563;

        }
        .black_text h3{
          color: #4B5563;

        }
        .black_text p {
          color: #4B5563;
        }
        .resized-image{
          height: 2rem;
          width: 2rem;
        }
        .boton-container{
          text-align: center;
          max-width: 350px;
          cursor: pointer;
        }
        .boton-body{
          background: white;
          border-radius: 0.675rem;
          padding: 2.5rem;
          text-align: center;
          box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
        }
        .boton-body i{
          font-size: 3.35rem;
          font-weight: bold;
          margin-top: 1rem;
          color: #4B5563;
        }
        .boton-body h3{
          color: #4B5563;
          font-size: large;
          font-weight: bold;
        }
        .boton-body p{
          color: #4B5563;
           
        }
        .cards{
          max-width: 350px;
          margin: 0 auto;
          display: grid;

          gap: 1rem;
        }
        .logo_image{
          width: 5rem;
          height: 5rem;
        }
        .header_name{
          display:inline;
          width: 70vw;
          text-align: center;
          font-weight:bold;
        }
        @media (min-width: 600px) {
          .cards { 
            max-width: 90vw;
            grid-template-columns: repeat(2, 1fr); }
            .logo_image{
            width: 5rem;
            height: 5rem;
          }
          
        }
        @media (min-width: 900px) {
          .cards { 
            max-width: 90vw;
            grid-template-columns: repeat(3, 1fr); }
            .logo_image{
            width: 5rem;
            height: 5rem;
          }
          
        }
        @media (max-width: 300px) {
          .logo_image{
            width: 3rem;
            height: 3rem;
          }
        }
        @media (max-width: 600px )  {
          .header_name{
            width:50vw;
          }
        }
    </style>
    <!-- Font Awesome -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title><?php echo $nombreempresa; ?></title>
  </head>

  <body class="nav-md">
    <nav class="navbar navbar-default">
      <div class="">
        <div class="navbar-header" style="display: flex;align-items: center;justify-content: space-evenly;">
          <a class="navbar-brand" style="padding:0;width: 15vw;min-width: 20px;display: flex;justify-content: center;align-items: center;height: auto;padding-bottom: 1rem;padding-top: 1rem;" href="#">
          <img class="resized-image img-responsive logo_image" src="<?php echo $logo;?>"  alt="Pay background" />
          </a>
          <div class="header_name" ><?php echo $nombreempresa; ?></div>
          <div style="width: 15vw;display: flex;justify-content: space-around;" onclick="salir()" style="cursor: pointer;"> <small class="fa fa-sign-out " style="cursor: pointer;"></small></div>
        </div>
        <div class="clearfix"></div>
      </div>
    </nav>
    <div class="container body">
      <div class="main_container">



        <!-- BOTONERA INICIA -->
        <div id="box_grilla_btn">
          <?php require_once("botonera_bootstrap.php");?>
        </div>
        <div id="box_grilla_pedidos">
        </div>

        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
