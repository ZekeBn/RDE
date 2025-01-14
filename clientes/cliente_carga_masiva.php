<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require("../../clases/PHPExcel.php");

// archivos
require_once("../includes/upload.php");
require_once("../includes/funcion_upload.php");
require_once("../proveedores/preferencias_proveedores.php");

set_time_limit(0);

function limpia_csv_externo($texto)
{
    $texto = utf8_encode($texto);
    return $texto;
}




$array_fallados = [];
$array_datos = [];
$status = "";
$msg = urlencode("Archivo Cargado Exitosamente!");
if (isset($_POST["MM_upload"]) && ($_POST["MM_upload"] == "form1")) {

    set_time_limit(0);

    $archivo = $_FILES['archivo'];
    if ($archivo['name'] != "") {

        $archivoExcel = $archivo['tmp_name'];
        $excel = PHPExcel_IOFactory::load($archivoExcel);

        // Seleccionar la primera hoja del archivo
        $hoja = $excel->getActiveSheet();// marcas
        $numFilas_marcas = $hoja->getHighestRow();
        $numColumnas_marcas = PHPExcel_Cell::columnIndexFromString($hoja->getHighestColumn());


        // Recorrer las filas y acceder a los valores
        $valido = "S";
        $errores = "";
        for ($fila = 2; $fila <= $numFilas_marcas; $fila++) {

            /* * */		$razon_social = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(0, $fila)->getValue()), "text");
            $nombre_fantasia = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(1, $fila)->getValue()), "text");
            /* * */		$ruc = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(2, $fila)->getValue()), "text");
            $direccion = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(3, $fila)->getValue()), "text");
            $comentario = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(4, $fila)->getValue()), "text");
            $web = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(5, $fila)->getValue()), "text");
            $telefono = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(6, $fila)->getValue()), "text");
            $email = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(7, $fila)->getValue()), "text");
            $contacto = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(8, $fila)->getValue()), "text");
            $area = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(9, $fila)->getValue()), "text");
            $email_contacto = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(10, $fila)->getValue()), "text");
            $dias_vence = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(11, $fila)->getValue()), "float");
            $plazo_entrega = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(12, $fila)->getValue()), "float");
            /* * */		$tipo_persona = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(13, $fila)->getValue()), "text");
            /* * */		$pais = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(14, $fila)->getValue()), "text");
            /* * */		$moneda = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(15, $fila)->getValue()), "text");
            /* * */		$retentor = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(16, $fila)->getValue()), "text");
            $tipo_servicio = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(17, $fila)->getValue()), "text");
            $origen = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(18, $fila)->getValue()), "text");
            $tipo_compra = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(19, $fila)->getValue()), "text");
            $cuenta_corriente_mercaderia = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(20, $fila)->getValue()), "text");
            $cuenta_corriente_deuda = antisqlinyeccion(trim($hoja->getCellByColumnAndRow(21, $fila)->getValue()), "text");
            // echo "Razón Social: " . $razon_social . " " .
            // "Nombre Fantasía: " . $nombre_fantasia . " " .
            // "RUC: " . $ruc . " " .
            // "Dirección: " . $direccion . " " .
            // "Comentario: " . $comentario . " " .
            // "Web: " . $web . " " .
            // "Teléfono: " . $telefono . " " .
            // "Email: " . $email . " " .
            // "Contacto: " . $contacto . " " .
            // "Área: " . $area . " " .
            // "Email de Contacto: " . $email_contacto . " " .
            // "Días que Vence: " . $dias_vence . " " .
            // "Plazo de Entrega: " . $plazo_entrega . " " .
            // "Tipo de Persona: " . $tipo_persona . " " .
            // "País: " . $pais . " " .
            // "Moneda: " . $moneda . " " .
            // "Retentor: " . $retentor . " " .
            // "Tipo de Servicio: " . $tipo_servicio . " " .
            // "Origen: " . $origen . " " .
            // "Tipo de Compra: " . $tipo_compra . " " .
            // "Cuenta Corriente Mercadería: " . $cuenta_corriente_mercaderia . " " .
            // "Cuenta Corriente Deuda: " . $cuenta_corriente_deuda . "<br><br>";

            // if($estado !=""){
            // 	if($estado == "A"){
            // 		$estado = 1;
            // 	}else{
            // 		$estado = 6;
            // 	};
            // }

            // $estado=1;
            $valido = "S";

            if ($tipo_persona == "NULL") {
                $valido = "N";
                $errores .= "ruc nulo";
            } else {
                if (strtoupper($tipo_persona) == "FISICA") {
                    $tipo_persona = 1;
                } else {
                    $tipo_persona = 2;
                }
            }
            $idpais = "NULL";
            if ($pais != "NULL") {
                $consulta = "SELECT idpais 
					from paises_propio
					where
					UPPER(nombre) = UPPER($pais)
					";
                $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idpais = intval($rs->fields["idpais"]);
            } else {
                $consulta = "SELECT idpais FROM paises_propio WHERE defecto=1 ";
                $rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idpais = $rs_guarani->fields["idpais"];
            }
            if ($moneda != "NULL") {
                $consulta = "SELECT idtipo 
					from tipo_moneda
					where
					UPPER(descripcion) = UPPER($moneda)
					";
                $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idmoneda = intval($rs->fields["idtipo"]);
            } else {
                $consulta = "SELECT idtipo FROM `tipo_moneda` WHERE nacional='S' ";
                $rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idmoneda = $rs_guarani->fields["idtipo"];
            }
            if ($origen != "NULL") {
                $consulta = "SELECT idtipo_origen 
					from tipo_origen
					where
					UPPER(tipo) = UPPER($origen)
					";
                $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idorigen = intval($rs->fields["idtipo_origen"]);
            }
            if ($tipo_servicio != "NULL") {


                $consulta = "SELECT idtipo_servicio 
					from tipo_servicio
					where
					UPPER(tipo) = UPPER($tipo_servicio)
					";
                $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idtipo_servicio = intval($rs->fields["idtipo_servicio"]);
                if ($idtipo_servicio == 0) {
                    $idtipo_servicio = select_max_id_suma_uno("tipo_servicio", "idtipo_servicio")["idtipo_servicio"];
                    $consulta = "insert into tipo_servicio 
						(idtipo_servicio, tipo, registrado_por, registrado_el , estado, idempresa) VALUES 
						($idtipo_servicio, $tipo_servicio,$idusu,'$ahora',1,$idempresa)
						";
                    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                }

            }
            $idtipo_compra = "NULL";
            if ($tipo_compra != "NULL") {

                $consulta = "SELECT idtipocompra 
					from tipocompra
					where
					UPPER(tipocompra) = UPPER($tipo_compra)
					";
                $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idtipo_compra = intval($rs->fields["idtipocompra"]);
            }

            //ATENCION OBLIGA A FACTURA SIEMPRE POR RAMIREZ FALTA PREFERENCIA
            $estado = 1;
            $incrementa = "'N'";
            $borrable = antisqlinyeccion('S', "text");
            $acuerdo_comercial = antisqlinyeccion('N', "text");
            $idproveedor = select_max_id_suma_uno("proveedores", "idproveedor")["idproveedor"];
            $registrado_el = antisqlinyeccion($ahora, "text");
            $sucursal = antisqlinyeccion(1, "float");
            $parametros_array = [
                "idproveedor" => $idproveedor,
                "idempresa" => $idempresa,
                "ruc" => $ruc,
                "nombre" => $razon_social,
                "fantasia" => $nombre_fantasia,
                "direccion" => $direccion,
                "sucursal" => $sucursal,
                "comentarios" => $comentario,
                "web" => $web,
                "telefono" => $telefono,
                "estado" => $estado,
                "email" => $email,
                "contacto" => $contacto,
                "area" => $area,
                "email_conta" => $email_contacto,
                "borrable" => $borrable,
                "diasvence" => $dias_vence,
                "dias_entrega" => $plazo_entrega,
                "incrementa" => $incrementa,
                "acuerdo_comercial" => $acuerdo_comercial,
                "acuerdo_comercial_coment" => "NULL",
                "archivo_acuerdo_comercial" => ["name" => "NULL"],
                "acuerdo_comercial_desde" => "NULL",
                "acuerdo_comercial_hasta" => "NULL",
                "persona" => $tipo_persona,
                "idpais" => $idpais, // ya esta
                "idmoneda" => $idmoneda,
                "agente_retencion" => $retentor,
                "idtipo_servicio" => $idtipo_servicio,
                "idtipo_origen" => $idorigen, //ya esta
                "idtipocompra" => $idtipo_compra,
                "cuenta_cte_mercaderia" => $cuenta_corriente_mercaderia,
                "cuenta_cte_deuda" => $cuenta_corriente_deuda,
                "registrado_por" => $idusu,
                "registrado_el" => $registrado_el,
                "form_completo" => 1,
            ];
            $array_datos[$nombre_fantasia][] = $fila  ;
            $res = validar_proveedor($parametros_array);
            // si todo es correcto inserta
            if ($res["valido"] == "S" && $valido == "S") {

                $res = agregar_proveedor($parametros_array);//idproveedor
            } else {
                $errores = $res["errores"];
                $bandera_proveedor_repetido = $res["bandera_proveedor_repetido"];
                if ($bandera_proveedor_repetido == 1) {

                    $array_fallados[] = ["fila" => json_encode($array_datos[$nombre_fantasia]), "nombre" => $nombre_fantasia, "estado" => $estado, "error" => $errores];

                } else {
                    $array_fallados[] = ["fila" => $fila, "nombre" => $nombre_fantasia, "estado" => $estado, "error" => $errores];

                }

            }





        }




        // exit;
        ///////////////////////////////////////////////////////////////////////////////////////////////////
    }

}




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
                    <h2>Importar Proveedores</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p>
<a href="gest_proveedores.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
</p>
<hr />

<hr />

<?php if (count($array_fallados) > 0) { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
<strong>Errores:</strong><br />
	<div>
		<ul class="list-group">
			<?php if (count($array_fallados) > 0) { ?>
				<li class="list-group-item list-group-item-danger">
					<strong>Total Errores:</strong> <?php  echo count($array_fallados); ?><br>
					<?php foreach ($array_fallados as $key => $value) {?>
						<strong>Fila:</strong> <?php  echo $value["fila"]; ?>&nbsp;&nbsp;&nbsp; <strong>Marca:</strong> <?php  echo $value["nombre"]; ?> <strong>Error:</strong> <?php  echo $value["error"]; ?><br>
					<?php } ?>
				</li>
			<?php } ?>
		</ul>
	</div>
</div>
<?php } ?>



<form action="" method="post" enctype="multipart/form-data" name="form1" id="form1">



<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Archivo xlsx *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
    <input type="file" name="archivo" id="archivo"  class="form-control" accept=".xlsx"  />
	</div>
</div>


<div class="clearfix"></div>
<br />





<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-upload"></span> Cargar Archivo</button>
        </div>
    </div>

	<input type="hidden" name="MM_upload" id="MM_upload" value="form1" /></td>
 </form>

<p>&nbsp;</p>
<hr />
<h2>Instrucciones:</h2><br />
<br />
<strong>Paso 1:</strong><br />
<a class="btn btn-sm btn-default" type="button" 
href='../gfx/formatos_arch/proveedor.xlsx' download><span class="fa fa-download" ></span> Descargar Formato XLSX Ejemplo</a>
<br />
<br />
<strong>Paso 2:</strong><br />
Cargar aqui el archivo excel con las nuevas cantidades.
<br />
 </form>

<p>&nbsp;</p>
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
