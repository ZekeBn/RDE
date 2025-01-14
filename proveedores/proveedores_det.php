<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "24";
require_once("../includes/rsusuario.php");
require_once("preferencias_proveedores.php");




$idproveedor = intval($_GET['id']);
if ($idproveedor == 0) {
    header("location: proveedores.php");
    exit;
}

// consulta a la tabla
$consulta = "
SELECT proveedores.idproveedor, proveedores.idempresa, proveedores.idmoneda, proveedores.ac_archivo,proveedores.ac_desde,proveedores.ac_hasta,proveedores.persona,
proveedores.idtipo_origen, proveedores.idtipo_servicio, proveedores.idpais, 
proveedores.ruc, proveedores.nombre, proveedores.fantasia, proveedores.direccion, 
proveedores.sucursal, proveedores.comentarios, proveedores.web, proveedores.telefono, 
proveedores.estado, proveedores.email, proveedores.contacto, proveedores.area, proveedores.email_conta, 
proveedores.borrable, proveedores.diasvence, proveedores.incrementa, proveedores.acuerdo_comercial, 
proveedores.acuerdo_comercial_coment, proveedores.agente_retencion, (Select tipocompra from tipocompra where proveedores.tipocompra=tipocompra.idtipocompra) as tipocompra, proveedores.cuenta_cte_mercaderia, 
proveedores.cuenta_cte_deuda, tipo_moneda.descripcion as moneda,paises_propio.nombre as pais, tipo_servicio.tipo as tipo_servicio, 
tipo_origen.tipo as origen, (select usuario from usuarios where proveedores.registrado_por = usuarios.idusu) as registrado_por, 
proveedores.registrado_el,(select usuario from usuarios where proveedores.actualizado_por = usuarios.idusu) as actualizado_por,
proveedores.actualizado_el
FROM proveedores
LEFT JOIN tipo_moneda ON tipo_moneda.idtipo = proveedores.idmoneda
LEFT JOIN paises_propio ON paises_propio.idpais = proveedores.idpais
LEFT JOIN `tipo_servicio` ON tipo_servicio.idtipo_servicio = proveedores.idtipo_servicio
LEFT JOIN tipo_origen ON tipo_origen.idtipo_origen = proveedores.idtipo_origen
WHERE 
proveedores.idproveedor = $idproveedor
and proveedores.estado = 1
limit 1
";


$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idproveedor = intval($rs->fields['idproveedor']);
if ($idproveedor == 0) {
    header("location: proveedores.php");
    exit;
}

?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
	<style>
		.custom-table {
			width: 100%;
			table-layout: fixed;
		}

		.custom-table th:first-child,
		.custom-table td:first-child {
			width: 70%;
		}

		.custom-table th:last-child,
		.custom-table td:last-child {
			min-width: auto;

		}

	</style>
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
                    <h2>Detalle del Proveedor</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p><a href="gest_proveedores.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action ">
	
		<tr>
			<th align="center">Codigo</th>
			<td align="center"><?php echo intval($rs->fields['idproveedor']); ?></td>
		</tr>	
	</table>
</div>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
			<h2 >Datos Personales</h2>
		<tr>
			<th align="center">Direccion</th>
			<td align="center"><?php echo antixss($rs->fields['direccion']); ?></td>
		</tr>
		<tr>
			<th align="center">Telefono</th>
			<td align="center"><?php echo antixss($rs->fields['telefono']); ?></td>
		</tr>
		<?php if ($proveedores_importacion == "S") {?>
			<tr>
				<th align="center">Moneda</th>
				<td align="center"><?php echo antixss($rs->fields['moneda']); ?></td>
			</tr>	
			<tr>
				<th align="center">Pais</th>
				<td align="center"><?php echo antixss($rs->fields['pais']); ?></td>
			</tr>			
		<?php }?>
		<?php if ($proveedores_importacion == "S") {?>
			<tr>
				<th align="center">Origen</th>
				<td align="center"><?php echo antixss($rs->fields['origen']); ?></td>
			</tr>	
					
		<?php }?>
		<tr>
			<th align="center">Email</th>
			<td align="center"><?php echo antixss($rs->fields['email']); ?></td>
		</tr>
		<tr>
			<th align="center">Contacto</th>
			<td align="center"><?php echo antixss($rs->fields['contacto']); ?></td>
		</tr>
		<tr>
			<th align="center">Area del contacto</th>
			<td align="center"><?php echo antixss($rs->fields['area']); ?></td>
		</tr>
		<tr>
			<th align="center">Web</th>
			<td align="center"><?php echo antixss($rs->fields['web']); ?></td>
		</tr>
		<tr>
			<th align="center">Comentarios</th>
			<td align="center"><?php echo antixss($rs->fields['comentarios']); ?></td>
		</tr>
	</table>
</div>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action ">
	
			<h2 >Datos Tributarios</h2>
		
		
		<tr>
			<th align="center">Razon Social</th>
			<td align="center"><?php echo antixss($rs->fields['nombre']); ?></td>
		</tr>
		<tr>
			<th align="center">Ruc</th>
			<td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
		</tr>
		<tr>
			<th align="center">Email conta</th>
			<td align="center"><?php echo antixss($rs->fields['email_conta']); ?></td>
		</tr>
		<tr>
			<th align="center">Fantasia</th>
			<td align="center"><?php echo antixss($rs->fields['fantasia']); ?></td>
		</tr>
		<?php if ($proveedores_sin_factura == "N") { ?>
		<tr>
			<th align="center">Persona</th>
			<td align="center"><?php echo intval($rs->fields['persona']) == 1 ? "Fisica" : "Juridica"; ?></td>
		</tr>
		<?php } ?>
		<?php if ($proveedores_cta_cte == "S") {?>
			<tr>
				<th align="center">Cuenta Contable Deuda Proveedor</th>
				<td align="center"><?php echo antixss($rs->fields['cuenta_cte_deuda']); ?></td>
			</tr>
			<tr>
				<th align="center">Cuenta Contable Mercaderia</th>
				<td align="center"><?php echo antixss($rs->fields['cuenta_cte_mercaderia']); ?></td>
			</tr>	

					
		<?php }?>
		</table>
</div>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action ">
		<h2 >Acuerdos Comerciales</h2>

		<tr>
			<th align="center">Acuerdo comercial</th>
			<td align="center"><?php echo siono($rs->fields['acuerdo_comercial']); ?></td>
		</tr>
		<tr>
			<th align="center">Acuerdo comercial descripcion</th>
			<td align="center"><?php echo antixss($rs->fields['acuerdo_comercial_coment']); ?></td>
		</tr>
		<?php if ($proveedores_acuerdos_comerciales_archivo == "S") { ?>
			<tr>
				<th align="center" >Acuerdo comercial archivo</th>
				<td align="center">
					<?php if ($rs->fields['ac_archivo'] != null and $rs->fields['ac_archivo'] != "" and isset($rs->fields['ac_archivo'])) {?>
					<a class="btn btn-sm btn-default " href="<?php echo($rs->fields['ac_archivo']); ?>" download>Descargar archivo</a>
					<?php } else { ?>
						Sin Archivos
					<?php }?>
				</td>
			</tr>
			<tr>
				<th align="center">Desde</th>
				<td align="center"><?php echo antixss($rs->fields['ac_desde']); ?></td>
			</tr>
			<tr>
				<th align="center">Hasta</th>
				<td align="center"><?php echo antixss($rs->fields['ac_hasta']); ?></td>
			</tr>
		<?php } ?>

		
		
	</table>
</div>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action ">
			<h2 >Datos Compra</h2>
		<?php if ($proveedores_acuerdos_comerciales_archivo == "S") { ?>
		<tr>
			<th align="center">Tipo Compra</th>
			<td align="center"><?php echo antixss($rs->fields['tipocompra']); ?></td>
		</tr>
		<?php } ?>
		<tr>
			<th align="center">Dias Credito</th>
			<td align="center"><?php echo intval($rs->fields['diasvence']); ?></td>
		</tr>
		<tr>
			<th align="center">Sin Factura</th>
			<td align="center"><?php echo siono($rs->fields['incrementa']); ?></td>
		</tr>
		
        
		
		
		
		<?php if ($proveedores_agente_retencion == "S") {?>
			<tr>
				<th align="center">Agente de Retencion</th>
				<td align="center"><?php echo antixss($rs->fields['agente_retencion']); ?></td>
			</tr>	
					
		<?php }?>
		
		<?php if ($tipo_servicio == "S") {?>
			<tr>
				<th align="center">Servicio</th>
				<td align="center"><?php echo antixss($rs->fields['tipo_servicio']); ?></td>
			</tr>	
					
		<?php }?>

		
		
		
		
		<tr>
			<th align="center">Registrado por</th>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
		</tr>	
		<tr>
			<th align="center">Registrado el</th>
			<td align="center"><?php echo antixss($rs->fields['registrado_el']); ?></td>
		</tr>	
		<tr>
			<th align="center">Actualizado por</th>
			<td align="center"><?php echo antixss($rs->fields['actualizado_por']); ?></td>
		</tr>	
		<tr>
			<th align="center">Actualizado el</th>
			<td align="center"><?php echo antixss($rs->fields['actualizado_el']); ?></td>
		</tr>	
		
</table>
 </div>
<br />
<?php
if ($proveedores_acuerdos_comerciales_archivo == "S") {
    $path = "../gfx/proveedores/acuerdos_comercial/$idproveedor";
    // $directorio = __DIR__ .$path;
    // $directorio=str_replace("/","\\",$directorio);

    // Leer los nombres de los directorios
    // echo $path;exit;
    $archivos = scandir($path);

    // echo json_encode($archivos);exit;
    $actual = $rs->fields['ac_archivo'];
    $actual = str_replace("\\", "/", $actual);
    // echo ($actual);exit;
    $pattern = "/\/([^\/]+)$/"; // Expresión regular para obtener el nombre del archivo

    if (preg_match($pattern, $actual, $matches)) {
        $nombreArchivo = $matches[1];

    }
    // echo $nombreArchivo;
    $archivos = array_slice($archivos, 2);
    // echo json_encode($archivos);
    if (count($archivos) > 1) {
        ?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action ">
		<h2>Acuerdos Comerciales Historico</h2>
		<?php

                    // echo json_encode(scandir("../gfx/proveedores/acuerdos_comercial/$idproveedor"));
                    // echo json_encode($archivos);exit;

                    // Recorrer los archivos y excluir los directorios "." y ".."
                    foreach ($archivos as $archivo) {
                        if ($archivo != "." && $archivo != "..") {
                            // Verificar si no es un directorio
                            // echo ($path . '/' . $archivo);
                            // echo "<br>";
                            // echo $rs->fields['ac_archivo'];
                            // exit;
                            if (!is_dir($path . '/' . $archivo) && ($path . '/' . $archivo) != $actual) {
                                $tiempo = filemtime($path . '/' . $archivo);
                                $tiempo = date("d/m/Y H:i", $tiempo);
                                ?>
			<tr>
				<th align="center"> <?php echo  $tiempo; ?> </th>
				<td align="center"><a class="btn btn-sm btn-default " href="<?php echo $path."/".$archivo ; ?>" download>Descargar archivo</a></td>
			</tr>
		<?php
                            }
                        }
                    }

        ?>
		
	<table>
		<?php }?>
</div>
<?php } ?>



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
