<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "129";
require_once("includes/rsusuario.php");

$telefono_g = '0'.intval($_GET['tel']);
$idclientedel = intval($_GET['id']);


$consulta = "
	select * from cliente where borrable = 'N' and idempresa = $idempresa order by idcliente asc limit 1
	";
$rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$razon_social_pred = strtoupper(trim($rscli->fields['razon_social']));
$ruc_pred = $rscli->fields['ruc'];
//echo $razon_social_pred;
//exit;


if (isset($_POST['oclcidel'])) {
    $idcliedel = intval($_POST['oclcidel']);
    $buscar = "Select * from cliente_delivery where idclientedel=$idcliedel and estado=1";
    $rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    //echo $buscar;
    $idcliente = intval($rsf->fields['idcliente']);
    //echo $idcliente;exit;
    if ($idcliente > 0) {

        $update = "update cliente_delivery set estado=6,anulado_por=$idusu,anulado_el=current_timestamp where idclientedel=$idcliedel and idcliente=$idcliente";
        $conexion->Execute($update) or die(errorpg($conexion, $update));

        $update = "update cliente set estado=6,anulado_por=$idusu,anulado_el=current_timestamp where  idcliente=$idcliente";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        header("Location: delivery_pedidos.php");
        exit;

    }




}


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {



    // recibe parametros
    $nombres = antisqlinyeccion($_POST['nombres'], "text");
    $apellidos = antisqlinyeccion($_POST['apellidos'], "text");
    $iddomicilio = antisqlinyeccion($_POST['iddomicilio'], "int");
    $telefono = antisqlinyeccion($_POST['telefono'], "int");

    // recibe parametros dom
    /*$iddomicilio=antisqlinyeccion($_POST['iddomicilio'],"int");
    $direccion=antisqlinyeccion($_POST['direccion'],"text");
    $referencia=antisqlinyeccion($_POST['referencia'],"text");
    $nombre_domicilio=antisqlinyeccion($_POST['nombre_domicilio'],"text");*/
    //$idclientedel=antisqlinyeccion($_POST['idclientedel'],"int");
    $ruc = antisqlinyeccion($_POST['ruc'], "text");
    $razon_social = antisqlinyeccion($_POST['razon_social'], "text");


    // validaciones basicas
    $valido = "S";
    $errores = "";


    if (trim($_POST['nombres']) == '') {
        $valido = "N";
        $errores .= " - El campo nombres no puede estar vacio.<br />";
    }
    if (trim($_POST['apellidos']) == '') {
        $valido = "N";
        $errores .= " - El campo apellidos no puede estar vacio.<br />";
    }
    if (intval($_POST['telefono']) == 0) {
        $valido = "N";
        $errores .= " - El campo telefono no puede ser cero o nulo.<br />";
    }



    // validaciones dom
    /*if(trim($_POST['direccion']) == ''){
        $valido="N";
        $errores.=" - El campo direccion no puede estar vacio.<br />";
    }
    if(trim($_POST['nombre_domicilio']) == ''){
        $valido="N";
        $errores.=" - El campo nombre_domicilio no puede estar vacio.<br />";
    }*/


    // validaciones si completo ruc
    if (trim($_POST['ruc']) != '') {



        // validar digito verificador del ruc
        $rucar = trim($_POST['ruc']);
        $ruc_array = explode("-", $rucar);
        $ruc_pri = $ruc_array[0];
        $ruc_dv = $ruc_array[1];
        $dv_correcto = calcular_ruc($ruc_pri);

        /*if($ruc_pri <= 0){
    			$errores.="- El ruc no puede ser cero o menor.<br />";
    			$valido="N";
    		}
    		if(strlen($ruc_dv) <> 1){
    			$errores.="- El digito verificador del ruc no puede tener 2 numeros.<br />";
    			$valido="N";
    		}
    		if(calcular_ruc($ruc_pri) <> $ruc_dv){
    			$digitocor=calcular_ruc($ruc_pri);
    			$errores.="- El digito verificador del ruc no corresponde a la cedula el digito debia ser $digitocor para la cedula $ruc_pri.<br />";
    			$valido="N";
    		}*/
        if (trim($_POST['ruc']) == $ruc_pred && trim(strtoupper($_POST['razon_social'])) <> $razon_social_pred) {
            $errores .= "- La Razon Social debe ser $razon_social_pred si el RUC es $ruc_pred.<br />";
            $valido = "N";
        }
        if (trim($_POST['ruc']) <> $ruc_pred && trim(strtoupper($_POST['razon_social'])) == $razon_social_pred) {
            $errores .= "- El RUC debe ser $ruc_pred si la Razon Social es $razon_social_pred.<br />";
            $valido = "N";
        }

    }

    /*$consulta="
    select *
    from cliente_delivery
    where
    idempresa = $idempresa
    and telefono = $telefono
    ";
    $rsdelcliex=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    if(intval($rsdelcliex->fields['idclientedel']) > 0){
        $errores.="- El telefono ya existe, edite el cliente con este telefono.<br />";
        $valido="N";
    }*/


    // conversiones
    if (trim($_POST['ruc']) == '') {
        $idcliente = "NULL";
    }

    // si envio ruc
    if (trim($_POST['ruc']) != '') {

        // busca en clientes
        $consulta = "
		select * from cliente where ruc = $ruc  and estado = 1 order by idcliente asc limit 1
		";
        $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcliente = intval($rscli->fields['idcliente']);
        if ($idcliente == 0) {
            $parametros_array = [
                'idclientetipo' => 1,
                'ruc' => $_POST['ruc'],
                'razon_social' => $_POST['razon_social'],
                'documento' => $_POST['dc'],
                'fantasia' => $_POST['fantasia'],
                'nombre' => $_POST['nombres'],
                'apellido' => $_POST['apellidos'],


                'idvendedor' => '',
                'sexo' => '',

                'nombre_corto' => $_POST['nombre_corto'],
                'idtipdoc' => $_POST['idtipdoc'],

                'telefono' => $_POST['telefono'],
                'celular' => $_POST['celular'],
                'email' => $_POST['email'],
                'direccion' => $_POST['direccion'],
                'comentario' => $_POST['comentario'],
                'fechanac' => $_POST['fechanac'],

                'ruc_especial' => $_POST['ruc_especial'],
                'idsucursal' => $idsucursal,
                'idusu' => $idusu,

            ];


            $res = validar_cliente($parametros_array);
            if ($res['valido'] != 'S') {
                $valido = $res['valido'];
                $errores .= nl2br($res['errores']);
            }
        }

    }



    // si todo es correcto inserta
    if ($valido == "S") {


        // si envio ruc
        if (trim($_POST['ruc']) != '') {
            // busca en clientes
            $consulta = "
			select * from cliente where ruc = $ruc  and estado = 1 order by idcliente asc limit 1
			";
            $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idcliente = intval($rscli->fields['idcliente']);
            // sino existe inserta
            if ($idcliente == 0) {
                /*$consulta="
                Insert into cliente
                (idempresa,nombre,apellido,ruc,documento,direccion,celular,razon_social)
                values
                ($idempresa,$nombres,$apellidos,$ruc,NULL,NULL,$telefono,$razon_social)
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
                // busca en clientes el que acabamos de insertar
                $consulta="
                select * from cliente where ruc = $ruc and idempresa = $idempresa order by idcliente desc limit 1
                ";
                $rscli=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
                $idcliente=intval($rscli->fields['idcliente']);*/
                $res = registrar_cliente($parametros_array);
                $idcliente = $res['idcliente'];
                $consulta = "
				select * from cliente where idcliente = $idcliente
				";
                $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                // si existe actualiza
            } else {
                $consulta = "
				update cliente
				set
				razon_social = $razon_social,
				nombre = $nombres,
				apellido = $apellidos,
				actualizado_el = '$ahora',
				actualizado_por = $idusu
				where
				ruc = $ruc
				and idcliente = $idcliente
				and idempresa = $idempresa
				and borrable = 'S'
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }
        }

        // cliente delivery
        $consulta = "
		update cliente_delivery
		set
		nombres = $nombres,
		apellidos = $apellidos,
		telefono = $telefono,
		fec_ultactualizacion = '$ahora',
		editado_por = $idusu,
		idcliente = $idcliente
		where
		idclientedel = $idclientedel
		and idempresa = $idempresa
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        //echo $consulta;
        //exit;





        header("location: delivery_pedidos_dir.php?id=".$idclientedel);
        exit;

    }

}


$consulta = "
	select *, 
	(select razon_social from cliente where idcliente = cliente_delivery.idcliente and cliente.idempresa = $idempresa) as razon_social,
	(select ruc from cliente where idcliente = cliente_delivery.idcliente and cliente.idempresa = $idempresa) as ruc
	from cliente_delivery
	where
	idclientedel is not null
	and idclientedel = $idclientedel
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
                    <h2>Editar cliente de Delivery</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Telefono *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="telefono" id="telefono" value="<?php  if (isset($_POST['telefono'])) {
	    echo htmlentities($_POST['telefono']);
	} else {
	    echo htmlentities('0'.$rs->fields['telefono']);
	}?>" placeholder="telefono" required class="form-control" />
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombres *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombres" id="nombres" value="<?php  if (isset($_POST['nombres'])) {
	    echo htmlentities($_POST['nombres']);
	} else {
	    echo htmlentities($rs->fields['nombres']);
	}?>" placeholder="nombres" required class="form-control" />
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Apellidos *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="apellidos" id="apellidos" value="<?php  if (isset($_POST['apellidos'])) {
	    echo htmlentities($_POST['apellidos']);
	} else {
	    echo htmlentities($rs->fields['apellidos']);
	}?>" placeholder="apellidos" required class="form-control" />
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">RUC *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="ruc" id="ruc" value="<?php  if (isset($_POST['ruc'])) {
	    echo htmlentities($_POST['ruc']);
	} elseif ($rs->fields['ruc'] != '') {
	    echo htmlentities($rs->fields['ruc']);
	} else {
	    echo htmlentities('44444401-7');
	}?>" placeholder="ruc" required class="form-control" />
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Razon Social *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="razon_social" id="razon_social" value="<?php  if (isset($_POST['razon_social'])) {
	    echo htmlentities($_POST['razon_social']);
	} elseif ($rs->fields['razon_social'] != '') {
	    echo htmlentities($rs->fields['razon_social']);
	} else {
	    echo htmlentities('Consumidor Final');
	}?>" placeholder="razon_social" required class="form-control" />
	</div>
</div>




<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='delivery_pedidos_dir.php?id=<?php echo $rs->fields['idclientedel']; ?>'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />





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
