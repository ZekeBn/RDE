<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "30";
$submodulo = "632";
$dirsup = "S";
require_once("../includes/rsusuario.php");



$consulta = "
select  mesas.numero_mesa, mesas.idmesa, salon.nombre as salon
from mesas 
inner join salon on salon.idsalon = mesas.idsalon
where 
 estadoex = 1 
order by mesas.numero_mesa asc, salon.nombre asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


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
                    <h2>Mesas Smart</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<div class="row">
<div class="col-md-6 col-sm-6">

	<strong>Activando esta funcionalidad modulo tus clientes podran desde su telefono:</strong><br />
	1) Llamar al mozo. <br />
	2) Pedir la cuenta.<br />
	3) Ver la precuenta. <br />
	4) Ver la carta del local. <br />
	5) Abonar la cuenta de la mesa desde su telefono y le llegara la factura por mail. (Proximamente)<br />
	6) Pedir productos para la mesa. (Proximamente)<br />
	7) Ver promociones del local. (Proximamente)<br />
	<hr />
	<strong>Como Funciona:</strong><br />
	1) El cliente escanea un QR ubicado en su mesa.<br />
	2) El cliente ya puede realizar todas las acciones desde su propio celular.<br />
<hr />
	<strong>Como lo Activo:</strong><br />
	1) Llama al soporte y te guiamos en todo el proceso.<br />
	2) Genera los códigos QR: Genera los códigos QR únicos para cada mesa o ubicación dentro de tu restaurante. Puedes imprimirlos y colocarlos en los manteles, tarjetas de mesa o en cualquier lugar visible para tus clientes.<br />
	<a href="qr_mesas.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Generar QR</a>
	<a href="https://www.servidor.com.py/paquetes_instalar/notificador.zip" target="_blank" class="btn btn-sm btn-default"><span class="fa fa-download"></span> Descargar Notificador</a>

<br /><br />
</div>
<div class="col-md-6 col-sm-6">
	<p align="center">
	<img src="pedirypagarmesa.jpg" height="300" style="margin:5px;"   alt="QR Menu Digital" title="QR Menu Digital" />
	</p>
</div>
</div>


<strong>Beneficios para tu restaurante y tus clientes</strong><br />
Al implementar la tecnología QR para pedir y pagar en tu restaurante, disfrutarás de una serie de beneficios tanto para tu negocio como para tus comensales. Algunos de ellos son los siguientes:<br />
<br />
<strong>Mayor eficiencia y rapidez:</strong> La tecnología QR permite que tus clientes realicen pedidos y pagos de forma rápida y sencilla, sin tener que esperar a un camarero. Esto agiliza la atención y reduce los tiempos de espera, mejorando la satisfacción del cliente.<br />
<strong>Reducción de errores en los pedidos:</strong> Al permitir que tus clientes realicen los pedidos directamente desde sus dispositivos móviles, se minimizan los errores de comunicación entre el cliente y el personal del restaurante. Esto asegura que los pedidos se tomen de manera precisa y se sirvan correctamente.<br />
<strong>Incremento del ticket medio:</strong> Al facilitar a tus clientes la visualización del menú digital y ofrecer opciones de personalización, la tecnología QR puede aumentar el ticket medio de tu restaurante. Tus comensales podrán explorar y descubrir nuevos platos y añadir complementos o bebidas especiales, lo que se traduce en mayores ingresos para tu negocio.<br />
<strong>Diferenciación frente a la competencia:</strong> En un mercado cada vez más competitivo, ofrecer una experiencia única a tus clientes puede marcar la diferencia para atraerlos y retenerlos.<br />
	
<hr />

<p align="center">
	<img src="mesa_smart_display.png" height="500" style="margin:5px;"   alt="QR Menu Digital" title="QR Menu Digital" />
	</p>

	<p align="center">
	<img src="mesa_smart.png" height="500" style="margin:5px;"   alt="QR Menu Digital" title="QR Menu Digital" />
	</p>


<br />
<br />  
					  

                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
           
            
            
            
          </div>
        </div>
        <!-- /page content -->
		

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
