<?php
require_once("../../includes/conexion.php");
require_once("../../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
$pag = "index";
$asig_pag = 'S';
$dirsup_sec = "S";
require_once("../../includes/rsusuario.php");



if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";


    $csucursal = antisqlinyeccion($_POST['sucursal'], "int");
    $cfactura_suc = antisqlinyeccion($_POST['factura_suc'], "int");
    $cfactura_pexp = antisqlinyeccion($_POST['factura_pexp'], "int");
    $cidsalon_usu = antisqlinyeccion($_POST['idsalon_usu'], "int");
    $cidterminal_usu = antisqlinyeccion($_POST['idterminal_usu'], "int");


    if (intval($_POST['sucursal']) <= 0) {
        $valido = "N";
        $errores .= " - Debe indicar el local de venta.<br />";
    }

    if (intval($_POST['factura_suc']) <= 0) {
        $valido = "N";
        $errores .= " - Debe indicar la sucursal de la factura.<br />";
    }

    if (intval($_POST['factura_pexp']) <= 0) {
        $valido = "N";
        $errores .= " - Debe indicar el punto de expedicion de la factura.<br />";
    }


    if (intval($_POST['sucursal']) > 0) {
        $sucursal = intval($_POST['sucursal']);
        $consulta = "
		select idsucu from sucursales where idsucu = $sucursal and estado = 1 limit 1
		";
        $rssucex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if (intval($rssucex->fields['idsucu']) == 0) {
            $valido = "N";
            $errores .= " - Sucursal Inexistente.<br />";
            header("location: asigna_sucursal_pc.php?error=sucursal+inactiva");
            exit;
        }
    }


    // si todo es correcto actualiza
    if ($valido == "S") {


        $consulta = "
		update usuarios
		set
			sucursal = $csucursal,
			factura_suc = $cfactura_suc,
			factura_pexp = $cfactura_pexp,
			idsalon_usu = $idsalon_usu,
			idterminal_usu = $idterminal_usu
		where
			idusu = $idusu
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // para el log
        $idsucursal_ant = $idsucursal;
        $idsucursal_asig = $csucursal;
        $factura_suc_ant = intval($factura_suc);
        $factura_suc_asig = $cfactura_suc;
        $factura_pexp_ant = intval($factura_pexp);
        $factura_pexp_asig = $cfactura_pexp;
        $idsalon_usu_ant = antisqlinyeccion($idsalon_usu, "int");
        $idsalon_usu_asig = $cidsalon_usu;
        $idterminal_usu_ant = antisqlinyeccion($idterminal_usu, "int");
        $idterminal_usu_asig = $cidterminal_usu;


        // registra cambio
        $consulta = "
		INSERT INTO asignasucu_auto
		(idusu, idsucursal_ant, idsucursal_asig, fechahora,idempresa, factura_suc_ant, factura_suc_asig, factura_pexp_ant, factura_pexp_asig, idsalon_usu_ant, idsalon_usu_asig) 
		VALUES 
		($idusu,$idsucursal_ant,$idsucursal_asig,'$ahora',$idempresa, $factura_suc_ant, $factura_suc_asig, $factura_pexp_ant, $factura_pexp_asig, $idsalon_usu_ant, $idsalon_usu_asig)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        //echo $consulta;

        setcookie("csucursal", intval($idsucursal_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años
        setcookie("cfactura_suc", intval($factura_suc_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años
        setcookie("cfactura_pexp", intval($factura_pexp_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años
        setcookie("cidsalon_usu", intval($idsalon_usu_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años
        setcookie("cidterminal_usu", intval($idterminal_usu_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años

        $_SESSION['idterminal_usu'] = $idterminal_usu_asig;


        header("location: asigna_sucursal_pc_auto.php?ok=s");
        exit;


    }



}



// busca los parametros locales y actualiza usuario si son diferentes
$csucursal = antisqlinyeccion($_COOKIE['csucursal'], 'int');
$cfactura_suc = antisqlinyeccion($_COOKIE['cfactura_suc'], 'int');
$cfactura_pexp = antisqlinyeccion($_COOKIE['cfactura_pexp'], 'int');
$cidsalon_usu = antisqlinyeccion($_COOKIE['cidsalon_usu'], 'int');
$cidterminal_usu = antisqlinyeccion($_COOKIE['cidterminal_usu'], 'int');

if ($csucursal > 0) {

    $diferente = "N";

    // conversioens
    if (intval($cfactura_suc) == 0) {
        $cfactura_suc = 1;
    }
    if (intval($cfactura_pexp) == 0) {
        $cfactura_pexp = 1;
    }

    // busca si son diferentes al del usuario
    if ($idsucursal != $csucursal) {
        $diferente = "S";
    }
    if ($factura_suc != $cfactura_suc) {
        $diferente = "S";
    }
    if ($factura_pexp != $cfactura_pexp) {
        $diferente = "S";
    }
    if (intval($idsalon_usu) != intval($cidsalon_usu)) {
        $diferente = "S";
    }
    if (intval($idterminal_usu) != intval($cidterminal_usu)) {
        $diferente = "S";
    }

    if ($diferente == 'S') {
        // asignar a usuario la sucursal
        $consulta = "
		UPDATE usuarios 
		SET
			sucursal=$csucursal,
			factura_suc = $cfactura_suc,
			factura_pexp = $cfactura_pexp,
			idsalon_usu = $cidsalon_usu,
			idterminal_usu = $cidterminal_usu
		WHERE
			idempresa=$idempresa
			and idusu=$idusu
			and estado=1
		";
        //echo $consulta;exit;
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // tambien en la variable de sesion
        $_SESSION['idsucursal'] = $csucursal;


        // para el log
        $idsucursal_ant = $idsucursal;
        $idsucursal_asig = $csucursal;
        $factura_suc_ant = $factura_suc;
        $factura_suc_asig = $cfactura_suc;
        $factura_pexp_ant = $factura_pexp;
        $factura_pexp_asig = $cfactura_pexp;
        $idsalon_usu_ant = antisqlinyeccion($idsalon_usu, "int");
        $idsalon_usu_asig = $cidsalon_usu;
        $idterminal_usu_ant = antisqlinyeccion($idterminal_usu, "int");
        $idterminal_usu_asig = $cidterminal_usu;


        // registra cambio
        $consulta = "
		INSERT INTO asignasucu_auto
		(idusu, idsucursal_ant, idsucursal_asig, fechahora,idempresa, factura_suc_ant, factura_suc_asig, factura_pexp_ant, factura_pexp_asig, idsalon_usu_ant, idsalon_usu_asig) 
		VALUES 
		($idusu,$idsucursal_ant,$idsucursal_asig,'$ahora',$idempresa, $factura_suc_ant, $factura_suc_asig, $factura_pexp_ant, $factura_pexp_asig, $idsalon_usu_ant, $idsalon_usu_asig)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        // asigna cookie
        setcookie("csucursal", intval($idsucursal_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años
        setcookie("cfactura_suc", intval($factura_suc_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años
        setcookie("cfactura_pexp", intval($factura_pexp_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años
        setcookie("cidsalon_usu", intval($idsalon_usu_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años
        setcookie("cidterminal_usu", intval($idterminal_usu_asig), time() + (10 * 365 * 24 * 60 * 60)); // 10 años

        $_SESSION['idterminal_usu'] = $idterminal_usu_asig;

        //header("location: index.php?s=ok");
        header("location: asigna_sucursal_pc_auto.php?ok=s");
        exit;

    }
}

// buscar url_local de la sucursal
$consulta = "
SELECT * 
FROM impresoratk 
where 
idsucursal = $idsucursal 
and borrado = 'N' 
and tipo_impresora='CAJ' 
order by idimpresoratk  asc 
limit 1
";
$rsimpprint = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$url_local = 'http://localhost/impresorweb/datos_locales.php';
// si existe reemplaza, caso contrario usa la de caja que trae arriba
if (trim($rsimpprint->fields['url_local']) != '') {
    $url_local = trim($rsimpprint->fields['url_local']).'datos_locales.php';
}
?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../../includes/head_gen.php"); ?>
<?php if ($_GET['ok'] == '') {?>
<script>
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
function leer_datos_locales(){
	var direccionurl = '<?php echo $url_local; ?>';
	var parametros = {
	  'accion' : 'l' // a: agregar/editar l: leer
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			$("#asignadiv").html('<h2>Asignando, aguarde por favor...</h2>');				
		},
		success:  function (response) {
			if(IsJsonString(response)){
				var obj = jQuery.parseJSON(response);
				if(obj.factura_pexp > 0){
					$("#factura_pexp").val(obj.factura_pexp);
					$("#factura_suc").val(obj.factura_suc);
					$("#sucursal").val(obj.idsucursal);
					$("#idsalon_usu").val(obj.idsalon_usu);
					$("#idterminal_usu").val(obj.idterminal_usu);
					
					
					setTimeout(function(){ document.getElementById('form1').submit(); },1000); // 1000 = 1 segundo
				}else{
					//si no hay datos en el archivo local, direccion para asignar
					document.location.href='asigna_sucursal_pc.php';
				}
			}else{
				//si no hay datos en el archivo local, direccion para asignar
				document.location.href='asigna_sucursal_pc.php';	
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Archivo local de asignaciones no existe. '+jqXHR.status+' '+errorThrown);
				document.location.href='asigna_sucursal_pc.php';
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			//alert('Pagina no encontrada [404]');
			alert('Archivo local de asignaciones no existe [404]');
			document.location.href='asigna_sucursal_pc.php';
		
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
	});
}
</script>
<?php } ?>
  </head>

  <body class="nav-md" <?php if ($_GET['ok'] == '') {?>onLoad="leer_datos_locales();"<?php } ?>>
    <div class="container body">
      <div class="main_container">
        <?php require_once("../../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("../../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2><?php if ($_GET['ok'] == '') {?>Asignando PC<?php } else { ?>PC Asignada<?php } ?></h2>
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
<?php if ($_GET['ok'] == '') {?>
<div id="asignadiv">
Asignando, favor aguarde...
</div>

<form id="form1" name="form1" method="post" action="">
<input type="hidden" name="sucursal" id="sucursal" value=""  class="form-control" required readonly />
<input type="hidden" name="factura_suc" id="factura_suc" value="" placeholder="00x" class="form-control" required  readonly />
<input type="hidden" name="factura_pexp" id="factura_pexp" value=""  placeholder="00x" class="form-control" required  readonly />
<input type="hidden" name="idsalon_usu" id="idsalon_usu" value=""  placeholder="00x" class="form-control" required  readonly />
<input type="hidden" name="idterminal_usu" id="idterminal_usu" value=""  placeholder="00x" class="form-control" required  readonly />
  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
</form>
<br /><br /><br /><br /><br /><br />
<?php } else { ?>
<div class="clearfix"></div>


            <p align="left" style="font-size:14px;">
            Parametros asignados a esta PC:<br /><br />
<?php
if (isset($_COOKIE['csucursal']) && trim($_COOKIE['csucursal']) != '') {
    $csucursal = antisqlinyeccion($_COOKIE['csucursal'], 'int');
    //echo $csucursal;
    $buscar = "select * from sucursales where idempresa=$idempresa and idsucu = $csucursal order by nombre asc";
    $rspc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $sucursal_activa = strtoupper(trim($rspc->fields['nombre']));


}
    if (isset($_COOKIE['cidsalon_usu']) && trim($_COOKIE['cidsalon_usu']) != '') {
        $cidsalon_usu = antisqlinyeccion($_COOKIE['cidsalon_usu'], 'int');
        //echo $csucursal;
        $buscar = "select * from salon where idsalon = $idsalon_usu order by nombre asc";
        $rssal = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $salon_activo = strtoupper(trim($rssal->fields['nombre']));


    }
    if (isset($_COOKIE['cidterminal_usu']) && trim($_COOKIE['cidterminal_usu']) != '') {
        $cidterminal_usu = antisqlinyeccion($_COOKIE['cidterminal_usu'], 'int');
        //echo $csucursal;
        $buscar = "select * from terminal where idterminal = $cidterminal_usu order by terminal asc";
        $rssal = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $terminal_activa = strtoupper(trim($rssal->fields['terminal']));


    }

    ?>
<?php if ($sucursal_activa != '') { ?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
  <tr>
    <td width="50%">Local de Venta:</td>
    <td><?php echo $sucursal_activa?></td>
  </tr>
  <tr>
    <td width="50%">Salon:</td>
    <td><?php echo $salon_activo ?></td>
  </tr>
  <tr>
    <td width="50%">Terminal:</td>
    <td><?php echo $terminal_activa ?></td>
  </tr>
  <tr>
    <td>Factura Sucursal:</td>
    <td><?php echo agregacero($cfactura_suc, 3); ?></td>
  </tr>
  <tr>
    <td>Factura Punto Expedicion:</td>
    <td><?php echo agregacero($cfactura_pexp, 3); ?></td>
  </tr>
  
<?php


    $consulta = "
SELECT * 
FROM lastcomprobantes 
where 
idsuc=$factura_suc
and pe=$factura_pexp
and idempresa=$idempresa 
order by ano desc 
limit 1
";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        ?>
  <tr>
    <td>Proxima Factura:</td>
    <td><?php echo agregacero($cfactura_suc, 3); ?>-<?php echo agregacero($cfactura_pexp, 3); ?>-<?php echo agregacero($rs->fields['numfac'] + 1, 7); ?></td>
  </tr>
</table>
</div>


<strong style="color:#FF0000">IMPORTANTE:</strong> <br />
Si los datos que muestran aqui no son correctos:<br />
1) NO REALICE NINGUNA VENTA <br />
2) NO ABRA SU CAJA<br />
3) Llame al soporte<br />
<br />
No realice ninguna accion hasta que estos datos esten correctos.<br />
<br />
Si desea corregir puede hacerlo en: <a href="asigna_sucursal_pc.php" class="btn btn-sm btn-primary" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span>Editar Datos</a>
<br />
(solamente si tiene permisos a ese modulo)<br />
<?php } else { ?>
<strong style="color:#FF0000">SIN SUCURSAL ASIGNADA</strong>
<?php } ?>
            </p>
			<br /><br /><br />



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
		<?php require_once("../../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../../includes/footer_gen.php"); ?>
  </body>
</html>
