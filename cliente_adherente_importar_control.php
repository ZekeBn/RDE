<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "180";
require_once("includes/rsusuario.php");

require_once("includes/funciones_articulos.php");

$idadherenteimpcab = intval($_GET['id']);
if ($idadherenteimpcab == 0) {
    header("location: cliente_adherente_importar.php");
    exit;
}

$consulta = "
select * from cliente where borrable = 'N' and idempresa = $idempresa order by idcliente asc limit 1
";
$rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$razon_social_pred = strtoupper(trim($rscli->fields['razon_social']));
$ruc_pred = $rscli->fields['ruc'];

$consulta = "
select *,
	(
		select idadherente
		from adherentes 
		where
		estado <> 6
		and adherentes.documento = adherentes_import.cedula_adherente
		and adherentes.documento is not null
		limit  1
	) as existe
from adherentes_import 
where 
idadherenteimpcab = $idadherenteimpcab
order by idadherenteimpcab asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idadherenteimpcab = intval($rs->fields['idadherenteimpcab']);
if ($idadherenteimpcab == 0) {
    header("location: adherentes_importar.php");
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

    /*$consulta="
    SELECT cedula_titular, count(*) as total
    FROM adherentes_import
    where
    idadherenteimpcab = $idadherenteimpcab
    and cedula_titular is not null
    group by cedula_titular
    order by count(*) desc
    limit 1
    ";
    $rsdup=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    if(intval($rsdup->fields['total']) > 1){
        $errores.="- Existen documentos de titulares duplicados en el archivo cargado.<br />";
        $valido="N";
    }*/
    $consulta = "
	SELECT cedula_adherente, count(*) as total 
	FROM adherentes_import 
	where 
	idadherenteimpcab = $idadherenteimpcab 
	and cedula_adherente is not null
	group by cedula_adherente 
	order by count(*) desc 
	limit 1
	";
    $rsdup = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsdup->fields['total']) > 1) {
        $errores .= "- Existen documentos de adherentes duplicados en el archivo cargado.<br />";
        $valido = "N";
    }

    // si todo es correcto actualiza
    if ($valido == "S") {


        // vuelve a consultar pero esta vez ya existen categorias y demas
        $consulta = "
		select *
		from adherentes_import 
		where 
		idadherenteimpcab = $idadherenteimpcab
		order by idadherenteimpcab asc
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // validar adherentes
        while (!$rs->EOF) {



            $cedula_titular = antisqlinyeccion($rs->fields['cedula_titular'], "text");
            $consulta = "
			select idcliente from cliente where documento = $cedula_titular and estado <> 6 limit 1
			";
            $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idcliente = intval($rsex->fields['idcliente']);

            // completar parametros
            $parametros_array = [
                'cedula_titular' => $rs->fields['cedula_titular'],
                'cedula_adherente' => $rs->fields['cedula_adherente'],
                'nombres' => $rs->fields['nombres'],
                'apellidos' => $rs->fields['apellidos'],
                'seccion' => $rs->fields['seccion'],
                'idcliente' => $idcliente,
                'idadherenteimp' => $rs->fields['idadherenteimp'],
            ];


            if ($idcliente == 0) {
                $errores .= "- No existe el cliente titular con cedula ".antixss($rs->fields['cedula_titular']).".<br />";
                $valido = "N";
            }


            //print_r($_POST);exit;

            //$res=validar_cliente($parametros_array);
            // si no es valido salta la carga
            if ($res['valido'] == 'N') {
                $errores .= $res['errores'].'->> idadherenteimpcab : '.intval($parametros_array['idadherenteimpcab']).'<hr />';
                $valido = "N";

            } else {
                $parametros_array_acum[] = $parametros_array;
            }

            $rs->MoveNext();
        }

        //print_r($parametros_array_acum);exit;

        // si todo fue valido
        if ($valido == 'S') {


            // si no existe seccion crea
            $consulta = "
			INSERT INTO gest_secciones
			(descripcion,idempresa,estado) 
			SELECT 
			seccion, 1, 1
			FROM adherentes_import 
			where 
			seccion not in (select gest_secciones.descripcion from gest_secciones where estado = 1) 
			and idadherenteimpcab = $idadherenteimpcab
			group by seccion
			order by seccion asc
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // recorre y agrega los productos
            foreach ($parametros_array_acum as $parametros_array) {


                $cedula_titular = antisqlinyeccion($parametros_array['cedula_titular'], "text");
                $cedula_adherente = antisqlinyeccion($parametros_array['cedula_adherente'], "text");
                $nombres = antisqlinyeccion($parametros_array['nombres'], "text");
                $apellidos = antisqlinyeccion($parametros_array['apellidos'], "text");
                $seccion = antisqlinyeccion($parametros_array['seccion'], "text");
                $nomape = antisqlinyeccion($parametros_array['nombres'].' '.$parametros_array['apellidos'], "text");
                $idcliente = antisqlinyeccion($parametros_array['idcliente'], "int");
                $idadherenteimp = antisqlinyeccion($parametros_array['idadherenteimp'], "int");



                $consulta = "
				select idseccion 
				from gest_secciones 
				where 
				descripcion = $seccion 
				and estado = 1 
				order by idseccion asc 
				limit 1
				";
                $rssec = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idseccion = $rssec->fields['idseccion'];

                $consulta = "
				insert into adherentes
				(nombres, apellidos, telefono, lugar_actual, maximo_mensual, linea_sobregiro, idcliente, idempresa,nomape,disponible,adicional1,idtipoad,documento,idadherenteimp,idseccion)
				values
				($nombres, $apellidos, NULL, 0, 9999999999, 0, $idcliente,1,$nomape,0,0,0,$cedula_adherente,$idadherenteimp,$idseccion)
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $consulta = "
				select idadherente from adherentes where idadherenteimp = $idadherenteimp order by idadherente desc limit 1 
				";
                $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idadherente = $rsmax->fields['idadherente'];
                // genera linea de credito por servicio
                $consulta = "
				INSERT ignore INTO adherentes_servicioscom
				(idadherente, idserviciocom, idcliente, linea_credito, max_mensual, disponibleserv) 
				select adherentes.idadherente, servicio_comida.idserviciocom, adherentes.idcliente, 0, 9999999999, 0
				from adherentes, servicio_comida
				where
				idadherente = $idadherente
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


                $i++;
            } // foreach($parametros_array_acum as $parametros_array){

            // actualiza como finalizado
            $consulta = "
			update adherente_import_cab
			set
			finalizado_por = $idusu,
			finalizado_el = '$ahora',
			estado = 3
			where
			idadherenteimpcab = $idadherenteimpcab
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
	(
		select idadherente
		from adherentes 
		where
		estado <> 6
		and adherentes.documento = adherentes_import.cedula_adherente
		and adherentes.documento is not null
		limit  1
	) as existe
from adherentes_import  
where 
idadherenteimpcab = $idadherenteimpcab
order by idadherenteimpcab asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));






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
                    <h2>Controlar Adherentes del Archivo</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
			Favor verifique que los datos cargados esten correctos, una vez finalizado ya <strong style="color:#F00">NO SE PODRA DESHACER</strong> esta accion.


<hr />
<p><a href="cliente_adherente_importar.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
				  
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Valido</th>
			<th align="center">Idadherenteimp</th>
			<th align="center">Nombres</th>
			<th align="center">Apellidos</th>
			<th align="center">Seccion</th>
			<th align="center">Cedula titular</th>
			<th align="center">Cedula adherente</th>
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
			<td align="center"><?php echo antixss($rs->fields['idadherenteimp']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['nombres']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['apellidos']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['seccion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['cedula_titular']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['cedula_adherente']); ?></td>
		</tr>
<?php
$precio_venta_acum += $rs->fields['precio_venta'];
    $costo_acum += $rs->fields['costo'];

    $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
	
    </table>
</div>
<br />
<br />
Duplicados: <?php echo formatomoneda($duplicados); ?><br />
Estos adherentes duplicados no se crearan, se omitiran de la importacion. <br /> 

<form id="form1" name="form1" method="post" action="">
					  

<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Finalizar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='cliente_adherente_importar.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>


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
