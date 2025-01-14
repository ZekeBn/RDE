<?php
//Modulo para vincular documentos al cliente, lee los datos de tipos_documentos
/*--------------------------------------------------------
UR: 28/04/2023

-----------------------------------------------------*/
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "180";
require_once("../includes/rsusuario.php");
require_once("../includes/upload.php");
#require_once("../includes/funcion_upload.php");



$idcliente = intval($_REQUEST['id']);
if ($idcliente == 0) {
    echo 'Error obteniendo idcliente. No se continua';
    exit;
}



$consulta = "
select razon_social,nombre,apellido,documento,ruc from cliente where idcliente=$idcliente";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


if (isset($_FILES['archivo'])) {
    $clasedocu = intval($_POST['tipodocumento']);
    //print_r($_POST);exit;
    if (is_dir("documentos")) {

    } else {
        //creamos
        mkdir("documentos", "0777");

    }
    $source_file = $_FILES['archivo']['tmp_name'];
    $extension_archivo = end(explode('.', $_FILES['archivo']['name']));
    $nombre_archivo = 'leg_'.$clasedocu.'_'.date("YmdHis").'.'.$extension_archivo;
    $dest_file = "documentos/clientes/$idcliente/".$nombre_archivo;
    $directorio = "documentos/clientes/$idcliente";
    $nombre_archivo_ant = antisqlinyeccion($_FILES['archivo']['name'], "text");
    $comentario = antisqlinyeccion($_POST['comentario'], "text");
    $idsoportegastopla = antisqlinyeccion($_POST['idsoportegastopla'], "text");
    // si envio un gasto vinculado
    if (intval($_POST['idsoportegastopla']) > 0) {
        $consulta = "
		select * 
		from soportes_gastos_pla
		where
		idsoportegastopla = $idsoportegastopla
		and estado = 1
		";
        $rssop = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcliente_sop = intval($rssop->fields['idcliente']);
        // valida que este activo
        if (intval($rssop->fields['idsoportegastopla']) == 0) {
            echo  "El idsoportegastopla proporcionado no existe o fue borrado.";
            exit;
        }

        //valida que corresponda al mismo cliente
        if ($idcliente_sop != $idcliente) {
            echo  "El cliente del soporte no es el mismo que el del legajo.";
            exit;
        }
        // valida que no este asignado a otro legajo
        $consulta = "
		select unsf, idcliente, 
		(select ruc from cliente where idcliente = cliente_legajo.idcliente) as ruc
		from cliente_legajo 
		where 
		idsoportegastopla = $idsoportegastopla
		and estado = 1
		";
        $rslegex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $unsf_ex = intval($rslegex->fields['unsf']);
        $idcliente_ex = intval($rslegex->fields['idcliente']);
        $ruc_ex = antixss($rslegex->fields['ruc']);
        //valida que no fue asignado a otro legajo
        if ($unsf_ex > 0) {
            echo  "El idsoportegastopla ya fue asignado a otro legajo [$unsf_ex] del cliente <a href='cliente_legajo.php?id=$idcliente_ex' target='_blank'>[$idcliente_ex]</a> ruc: $ruc_ex.";
            exit;
        }


    }


    //comprobamos si existe el directorio
    $seguir = verificardirectorio($directorio);
    //echo $seguir;exit;
    if ($seguir == 'S') {
        $tipo = $_FILES['archivo']['type'];
        //$extension = substr($_FILES['archivo']['name'], -3);
        $extension = end(explode('.', $_FILES['archivo']['name']));


        $documento = $_FILES['archivo']['name'];
        $logmostrar = "";
        //if ($clasedocu==1){

        //PDF
        if ($_FILES['archivo']['type'] == "application/pdf" or $extension == "jpg" or $extension == "jpeg") {

            if (file_exists($dest_file)) {
                echo "El archivo ya existe";
                exit;
            } else {
                //echo 'MOVER';exit;
                move_uploaded_file($source_file, $dest_file) or die("Error!!");
                //echo "paso";exit;
                if ($_FILES['archivo']['error'] == 0) {
                    $logmostrar .= "Cargado correctamente - Detalles : </u></b><br/>";
                    $logmostrar .= "Nombre: ".$nombre_archivo."<br.>"."<br/>";
                    $logmostrar .= "Tamanho : ".htmlentities($_FILES['archivo']['size'])." bytes"."<br/>";
                    $logmostrar .= "Ubicacion : ".$dest_file."<br/>";
                    // SUBSTRING_INDEX(archivo, '/', -1) explode mysql
                    $insertar = "insert into cliente_legajo 
						(idcliente,idtipodocumento,archivo,estado,registrado_por,registrado_el,
						nombre_antiguo_arch,comentario,idsoportegastopla
						)
							values
						($idcliente,$clasedocu,'$dest_file',1,$idusu,'$ahora',
						$nombre_archivo_ant,$comentario,$idsoportegastopla)";
                    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

                    header("location: cliente_legajo.php?id=".$idcliente);
                    exit;
                }
            }
        } else {
            echo "ENT2";
            exit;

            exit;
            $fupload = new Upload();
            $fupload->setPath($directorio);
            $num = rand();
            $extension = substr($_FILES['archivo']['name'], -3);
            //echo $extension;
            //exit;
            $tiempo = date("YmdHis");
            $documento = $_FILES['archivo']['name'];

            $nombrearchivo2 = $num.'_'.$tiempo;

            $nombrearchivo = $nombrearchivo2.'.'.$extension;

            move_uploaded_file($source_file, $dest_file) or die("Error!!");





            $fupload->setFile("archivo", $nombrearchivo);
            $fupload->setMaxWidth(5000);
            if (strtolower($extension) != 'pdf') {
                $fupload->isImage(true);
            } else {
                $fupload->isPdf(true);
            }
            $fupload->save();

            $cargado = $fupload->isupload;
            $status = $fupload->message;
            echo $status;
            exit;
            if ($cargado) {
                $insertar = "insert into cliente_legajo 
						(idcliente,idtipodocumento,archivo,estado,registrado_por,registrado_el,
						nombre_antiguo_arch,comentario,idsoportegastopla
						)
							values
						($idcliente,$clasedocu,'$dest_file',1,$idusu,'$ahora',
						$nombre_archivo_ant,$comentario,$idsoportegastopla)";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

                //header("location: cliente_legajo.php?id=".$idcliente);
                exit;


            }











        }
        //} else {
        //subir otro
        //}
    }
}
?>

<!DOCTYPE html>
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
                    <h2>Subir imagenes /  documentos al cliente<br />
					</h2>
					<br />
					
					
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
					<?php echo $logmostrar; ?>
					<form enctype="multipart/form-data" action="" method="post">
						<table class="table table-bordered">
						<thead>
							<tr>
								<th colspan="3" align="center"><h2><span class="fa fa-user"></span>&nbsp;&nbsp;<?php echo $rs->fields['razon_social']; ?> &nbsp; &nbsp; 
									<a href="cliente_legajo_rep.php" class="btn btn-sm btn-default" title="Reporte de Documentos" data-toggle="tooltip" data-placement="right"  data-original-title="Reporte de Documentos"><span class="fa fa-search"></span> Reporte de Documentos</a>
									
									<a href="cliente.php"><span class="fa fa-home"></span>&nbsp;&nbsp;REGRESAR</a></h2></th>
							
							</tr>
			
						</thead>
			
						</table>

						
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Archivo *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="file" name="archivo" id="archivo" class="form-control" />
	</div>
</div>
						
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Documentacion *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idtipodoc, descripcion
FROM tipos_documentos
where
estado = 1
order by descripcion asc
 ";

// valor seleccionado
if (isset($_POST['idtipodoc'])) {
    $value_selected = htmlentities($_POST['idtipodoc']);
} else {
    $value_selected = "";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'tipodocumento',
    'id_campo' => 'tipodocumento',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idtipodoc',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>		
	</div>
</div>
						
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Comentario </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="comentario" id="comentario" value="<?php  if (isset($_POST['comentario'])) {
	    echo htmlentities($_POST['comentario']);
	} else {
	    echo htmlentities($rs->fields['comentario']);
	}?>" placeholder="Comentario" class="form-control"  />                    
	</div>
</div>
						
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Cod Servicio </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idsoportegastopla" id="idsoportegastopla" value="<?php  if (isset($_POST['idsoportegastopla'])) {
	    echo intval($_POST['idsoportegastopla']);
	} else {
	    echo intval($_GET['idsoportegastopla']);
	}?>" placeholder="idsoportegastopla" class="form-control"  />                    
	</div>
</div>
						
	<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='cliente.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />	<br /><br />	
						
					</form>



                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
              <?php
                $buscar = "Select archivo,descripcion,(select usuario from usuarios where idusu=cliente_legajo.registrado_por) as quien,cliente_legajo.registrado_el,unsf
				from cliente_legajo 
				inner join tipos_documentos on tipos_documentos.idtipodoc =cliente_legajo.idtipodocumento 
				where idcliente=$idcliente and cliente_legajo.estado=1 order by cliente_legajo.registrado_el desc";
$rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//echo $buscar;
$tr = $rsf->RecordCount();
if ($tr > 0) {
    ?> 
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Documentos encontrados</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

					
						<table width="100%" class="table table-bordered jambo_table bulk_action">
						<thead>
							
							<tr>
								<th></th>
								<th>Id</th>
								<th>Tipo Documento</th>
								<th>Archivo / Imagen</th>
								<th>Nombre Antiguo</th>
								<th>Comentario</th>
								<th>Registrado el</th>
								<th>Registrado por</th>
								
							</tr>
						</thead>
						<tbody>
						<?php while (!$rsf->EOF) {
						    $ids = $rsf->fields['unsf'];

						    ?>
						<tr>
							<td>

								<div class="btn-group">
									<a href="cliente_legajo_visor.php?unico=<?php echo $ids ?>" target="_blank" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
									<a href="cliente_legajo_edit.php?id=<?php echo $ids ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
									<a href="cliente_legajo_del.php?id=<?php echo $ids ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
								</div>

							</td>
							<td scope="row"><?php echo $ids ?></td>
							<td scope="row"><?php echo $rsf->fields['descripcion']; ?></td>
							
							<td><?php echo antixss($rsf->fields['archivo']); ?></td>
							<td><?php echo antixss($rsf->fields['nombre_antiguo_arch']); ?></td>
							<td><?php echo antixss($rsf->fields['comentario']); ?></td>
							<td><?php echo date("d/m/Y H:i:s", strtotime($rsf->fields['registrado_el'])); ?></td>
							<td><?php echo antixss($rsf->fields['quien']); ?></td>

						</tr>
						<?php $rsf->MoveNext();
						} ?>
						</tbody>
						</table>
					</form>



                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
				<?php } ?>
            
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
