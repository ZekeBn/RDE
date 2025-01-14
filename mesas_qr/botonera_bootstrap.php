<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("./mesas_qr_funciones.php");
require_once("./mesas_preferencias.php");


$errores = "";
// echo "hola";exit;
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";

$dirsup = "S";



if (!isset($_SESSION)) {
    session_start();
}

// Ejemplo de uso
$id_mesa = intval($_SESSION['id_mesa']);
if ($id_mesa == 0) {
    header("Location: ./mesas_qr.php");
}

$parametros_array = [
"id_mesa" => $id_mesa
];
$rs_mesas_atc = buscar_mesa_atc($parametros_array);
$idatc = intval($rs_mesas_atc['idatc']);
if ($idatc == 0) {
    header("Location: ./mesas_qr.php");
    exit;
}
$parametros_array = [
"id_mesa" => $id_mesa
];

$rs_mesas_respuesta = verificar_pedidos_pendientes($parametros_array);
$idpedido_mesero = $rs_mesas_respuesta['idpedido_mesero'];
$idpedido_cuenta = $rs_mesas_respuesta['idpedido_cuenta'];
$idpedido_ver = 0;

$consulta = "
select mesas.*, salon.nombre as salon
from mesas 
inner join salon on salon.idsalon = mesas.idsalon
where 
idmesa = $id_mesa
";
$rsmesa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>
<!-- BOTONERA INICIA -->

    <!--<div   class="boton-container white_text;">-->
		<div   class="rounded col-md-12 col-sm-12 col-xs-12">
        <div  class="boton-body" style="background-color:#000;color:#FFF;">
			Mesa: <?php echo antixss($rsmesa->fields['numero_mesa']); ?> - 
			Salon: <?php echo antixss($rsmesa->fields['salon']); ?>
        </div>
    </div>
<div class="clearfix"></div>
<br />


<div class="cards">


    <div  id="mesero_box" class="boton-container <?php if ($idpedido_mesero > 0) {
        echo "white_text";
    } else {
        echo "black_text";
    } ?>">
        <div id="mesero" onclick="mesero()" class="<?php if ($idpedido_mesero > 0) {
            echo "activo";
        }?> boton-body">
          <i class="fa fa-bell "></i>
          <?php if ($idpedido_mesero > 0) { ?>
              <h3 id="mesero_titulo" class="">Cancelar Mesero</h3>
              <p id="mesero_texto" class="">Presiona el Boton y cancelaras el llamado.</p>
            <?php } else { ?>
              <h3 id="mesero_titulo" class="">Solicitar un mesero </h3>
              <p id="mesero_texto" class="">Presiona el Boton y un mesero se acercara en la brevedad.</p>
            <?php } ?>
          
        </div>
    </div>

    <div id="pedir_cuenta_box" class="boton-container <?php if ($idpedido_cuenta > 0) {
        echo "white_text";
    } else {
        echo "black_text";
    }?>">
        <div id="pedir_cuenta" onclick="pedir_cuenta_toggle()" class="<?php if ($idpedido_cuenta > 0) {
            echo "activo";
        }?> boton-body">
            <i class="fas fa-tags text-4xl text-blue-500 mb-4"></i>
            

            <?php if ($idpedido_cuenta > 0) { ?>
              <h3 id="pedir_cuenta_titulo" class="text-xl font-bold text-gray-800 mb-2">Cancelar Pedir Cuenta  </h3>
              <p  id="pedir_cuenta_texto" class="text-gray-600">Presiona el Boton y cancelara la solicitud de cuenta.</p>
            <?php } else { ?>
              <h3 id="pedir_cuenta_titulo" class="text-xl font-bold text-gray-800 mb-2">Pedir Cuenta </h3>
              <p  id="pedir_cuenta_texto" class="text-gray-600">
              Presiona el Boton y un mesero se acercara en la brevedad posible, le traera la cuenta de la mesa y podra proporcionarle su ruc y razon social para la factura.
              </p>
            <?php } ?>

        </div>
    </div>

    
    <div class="boton-container">
        <div id="box_ver_pedidos" ><?php require_once("./mesas_qr_pedidos_grilla_bootstrap.php");?></div>
    </div>



    <div   class="boton-container white_text;" onclick="alert('proximamente!');">
        <div  class="boton-body" >
          <i class="fa fa-shopping-cart "></i>
          <h3 id="realizar_pedido_titulo" class="">Hacer Pedido </h3>
          <p id="realizar_pedido_texto" class="">Solicitar productos para la mesa.</p>
        </div>
    </div>
  
    <div   class="boton-container white_text;" onclick="alert('proximamente!');">
        <div  class="boton-body" >
          <i class="fa fa-credit-card "></i>
          <h3 id="pagar_cuenta_titulo" class="">Pagar Cuenta </h3>
          <p id="pagar_cuenta_texto" class="">Pagar la cuenta de la mesa online.</p>
        </div>
    </div>
  
</div>
<hr />
<p align="center">Powered by: <br /><a href="https://restaurante.com.py/" target="_blank"><img src="../img/logo_carrito.png" height="30px;" /></a></p>

<br /><br />
<!-- BOTONERA FIN  -->
