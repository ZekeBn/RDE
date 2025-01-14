<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "180";
require_once("includes/rsusuario.php");

require_once("includes/funciones_articulos.php");

$idtimbradoimpcab = intval($_GET['id']);
if ($idtimbradoimpcab == 0) {
    header("location: timbrado_doc_importar.php");
    exit;
}






if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

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

    $consulta = "
	SELECT ruc, count(*) as total 
	FROM cliente_import 
	where 
	idclienteimpcab = $idclienteimpcab 
	and ruc <> '$ruc_pred'
	group by ruc
	order by count(*) desc 
	limit 1
	";
    $rsdup = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsdup->fields['total']) > 1) {
        $errores .= "- Existen ruc duplicados en el archivo cargado.<br />";
        $valido = "N";
    }
    $consulta = "
	SELECT documento, count(*) as total 
	FROM cliente_import 
	where 
	idclienteimpcab = $idclienteimpcab 
	and documento > 0
	and documento is not null
	group by documento 
	order by count(*) desc 
	limit 1
	";
    $rsdup = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsdup->fields['total']) > 1) {
        $errores .= "- Existen documentos duplicados en el archivo cargado.<br />";
        $valido = "N";
    }

    // si todo es correcto actualiza
    if ($valido == "S") {


        // vuelve a consultar pero esta vez ya existen categorias y demas
        $consulta = "
		select *,
		(select idsucu from sucursales where nombre = cliente_import.sucursal and estado <> 6) as idsucursal
		from cliente_import 
		where 
		idclienteimpcab = $idclienteimpcab
		order by idclienteimpcab asc
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // inserta en productos
        while (!$rs->EOF) {

            // fisico o juridico
            $tipo_cliente = $rs->fields['tipo_cliente'];
            if (strtoupper($tipo_cliente) == 'JURIDICO') {
                $idclientetipo = 2;
            } else {
                $idclientetipo = 1;
            }

            $linea_credito = floatval($rs->fields['linea_credito']);

            // completar parametros
            $parametros_array = [
                'idusu' => $idusu,
                'idvendedor' => '',
                'sexo' => '',
                'nombre' => $rs->fields['nombre'],
                'apellido' => $rs->fields['apellido'],
                'nombre_corto' => '',
                'idtipdoc' => 1,
                'documento' => $rs->fields['documento'],
                'ruc' => $rs->fields['ruc'],
                'telefono' => $rs->fields['telefono'],
                'celular' => '',
                'email' => $rs->fields['email'],
                'direccion' => $rs->fields['direccion'],
                'comentario' => $rs->fields['comentario'],
                'fechanac' => '',
                'idclientetipo' => $idclientetipo,
                'razon_social' => $rs->fields['razon_social'],
                'fantasia' => $rs->fields['nombre_fantasia'],
                'ruc_especial' => $rs->fields['ruc_especial'],
                'idsucursal' => $rs->fields['idsucursal'],
                'linea_credito' => $rs->fields['linea_credito'],
                'latitud' => $rs->fields['latitud'],
                'longitud' => $rs->fields['longitud'],

            ];

            //print_r($_POST);exit;

            $res = validar_cliente($parametros_array);
            // si no es valido salta la carga
            if ($res['valido'] == 'N') {
                $errores .= $res['errores'].'->> idclienteimpcab : '.intval($parametros_array['idclienteimpcab']).'<hr />';
                $valido = "N";

            } else {
                $parametros_array_acum[] = $parametros_array;
            }

            $rs->MoveNext();
        }

        // si todo fue valido
        if ($valido == 'S') {
            // recorre y agrega los productos
            foreach ($parametros_array_acum as $parametros_array) {
                //print_r($parametros_array);exit;
                $res = registrar_cliente($parametros_array);
                $idcliente = $res['idcliente'];
                $consulta = "
				update cliente set idclienteimpcab = $idclienteimpcab where idcliente = $idcliente
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                $linea_credito = floatval($parametros_array['linea_credito']);
                // si envio linea credito crea y sobre escribee la linea credito por defecto del cliente
                // si no envio linea  de credito igual creara la linea por defecto en caso que este activada la preferencia
                if ($linea_credito > 0) {
                    $consulta = "
					UPDATE cliente 
					SET 
						permite_acredito='S',
						max_mensual = 100000000,
						saldo_mensual = 100000000,
						linea_sobregiro = $linea_credito,
						saldo_sobregiro = $linea_credito

					WHERE
						idcliente=$idcliente
						and estado <> 6
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                    // registra en el log
                    $consulta = "
					INSERT INTO clientes_lineas_log
					(idcliente, permite_acredito, max_mensual, linea_sobregiro, registrado_por, registrado_el) 
					VALUES 
					($idcliente,'S', 100000000, $linea_credito, $idusu, '$ahora')
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                }

                $i++;
            } // foreach($parametros_array_acum as $parametros_array){

            // actualiza como finalizado
            $consulta = "
			update cliente_import_cab
			set
			finalizado_por = $idusu,
			finalizado_el = '$ahora',
			estado = 3
			where
			idclienteimpcab = $idclienteimpcab
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            header("location: cliente.php");
            exit;
        }




    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

$consulta = "
select *,
	
	1
	 as existe
from timbrado_import  
where 
idtimbradoimpcab = $idtimbradoimpcab
order by idtimbradoimpcab asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


/*
BORRAR DUPLICADOS, EJECUTAR VARIAS VECES HASTA QUE NO QUEDE NINGUNO
delete from cliente_import
where idclienteimp in (
select idclienteimp
        from (
            SELECT ruc, count(*) as total, max(idclienteimp) as idclienteimp
            FROM cliente_import
            where
            idclienteimpcab = 1
            and ruc <> 'X'
            group by ruc
            order by count(*) desc
        ) tt
        where
        total > 1

    )

*/



?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
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
                    <h2>Controlar Clientes del Archivo</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
			Favor verifique que los datos cargados esten correctos, una vez finalizado ya <strong style="color:#F00">NO SE PODRA DESHACER</strong> esta accion.


<hr />
<p><a href="cliente_importar.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
				  
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Valido</th>
			<th align="center">Idtimbradoimp</th>
			<th align="center">Timbrado</th>
			<th align="center">Inicio vigencia</th>
			<th align="center">Fin vigencia</th>
			<th align="center">Documento</th>
			<th align="center">Tipo documento</th>
			<th align="center">Sucursal</th>
			<th align="center">Punto expedicion</th>
			<th align="center">Numero desde</th>
			<th align="center">Numero hasta</th>
			<th align="center">Comentario</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td align="center"><?php
if (intval($rs->fields['existe']) > 0) {
    echo  '<span style="color:#F00;">DUPLICADO</span>';
    $duplicados++;
} else {
    echo "OK";
}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['idtimbradoimp']); ?></td>

			<td align="center"><?php echo antixss($rs->fields['timbrado']); ?></td>
			<td align="center"><?php if ($rs->fields['inicio_vigencia'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['inicio_vigencia']));
			} ?></td>
			<td align="center"><?php if ($rs->fields['fin_vigencia'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fin_vigencia']));
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['documento']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['tipo_documento']); ?></td>
			<td align="center"><?php echo agregacero($rs->fields['sucursal'], 3); ?></td>
			<td align="center"><?php echo agregacero($rs->fields['punto_expedicion'], 3); ?></td>
			<td align="center"><?php echo intval($rs->fields['numero_desde']); ?></td>
			<td align="center"><?php echo intval($rs->fields['numero_hasta']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['comentario']); ?></td>
		</tr>
<?php


$rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
	
    </table>
</div>
<br />
<br />
Duplicados: <?php echo formatomoneda($duplicados); ?><br />
Estos clientes duplicados no se crearan, se omitiran de la importacion. <br /> 

<form id="form1" name="form1" method="post" action="">
					  

<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Finalizar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='cliente_importar.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
	  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
					  <div class="clearfix"></div>
<br />
<br /><br /><br />

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
