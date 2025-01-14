<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "16";
$submodulo = "76";
require_once("includes/rsusuario.php");

$modulomaster = "N";
if (($idusu == 2 or $idusu == 3) && $idempresa == 1 && $superus == 'S') {
    $modulomaster = "S";
}
//echo $superus;
if ($modulomaster != "S") {
    $whereadd = "	and modulo_detalle.idmodulo <> 19 ";
}

// si el usuario no es de una master franquicia
$consulta = "
select * from usuarios where idusu = $idusu 
";
$rsusfranq = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$franq_m = $rsusfranq->fields['franq_m'];
// si el usuario actual no es master franq ni super filtra
if ($franq_m != 'S' && $superus != 'S') {
    $whereaddsup = "
	and franq_m = 'N'
	";
}

// si no es un super usuario debe filtrar usuarios por este campo
if ($superus != 'S') {
    $whereaddsup .= "
	and super = 'N'
	";
}

$id = intval($_GET['id']);
$idusu_get = $id;

$consulta = "
SELECT *, (SELECT fechahora FROM usuarios_accesos where idusuario = usuarios.idusu order by fechahora desc limit 1) as ultacceso
FROM usuarios
where
estado = 1
and idempresa = $idempresa
/*and sucursal = $idsucursal*/
and usuarios.idusu = $id
$whereaddsup
";
//echo $consulta;exit;
$rsus = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

if (intval($rsus->fields['idusu']) == 0) {
    echo "Usuario Inexistente o Inactivo!";
    exit;
}

// busca si tiene permisos al submodulo 2
$consulta = "
select  * from modulo_usuario where idusu = $idusu_get and submodulo = 2 limit 1
";
$rsexperm = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// si no tiene aun el permiso, asigna
if (intval($rsexperm->fields['submodulo']) == 0) {
    // insertar permiso para el modulo 1 y submodulo 2 si o si
    $consulta = "
	INSERT IGNORE INTO modulo_usuario
	(idusu, idmodulo, idempresa, estado, submodulo, registrado_el, registrado_por, sucursal) 
	VALUES 
	($idusu_get,1,1,1,2,'$ahora',$idusu,$idsucursal)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // log
    $consulta = "
	insert into modulo_usuario_log_new
	(idusu, submodulo, accion, registrado_por, registrado_el)
	values
	($idusu_get, 2, 'A', $idusu, '$ahora')
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}

$consulta = "
SELECT modulo_detalle.descripcion as desmoduppal,nombresub,pagina,
modulo,modulo_detalle.idsubmod,modulo_empresa.asignado_el,modulo_detalle.idmodulo,
		(
		select modulo_usuario.submodulo 
		from modulo_usuario 
		where 
		modulo_usuario.idmodulo = modulo_detalle.idmodulo 
		and modulo_usuario.submodulo = modulo_detalle.idsubmod 
		and modulo_usuario.idempresa = modulo_empresa.idempresa
		and modulo_usuario.idusu = $id
		limit 1
		) as asignado
FROM modulo_detalle
INNER JOIN modulo on modulo.idmodulo = modulo_detalle.idmodulo
INNER JOIN modulo_empresa on modulo_empresa.idsubmod = modulo_detalle.idsubmod and modulo_empresa.idmodulo = modulo_detalle.idmodulo
where
idempresa = $idempresa
and modulo_detalle.mostrar = 1
and modulo_detalle.estado = 1
and modulo_detalle.idmodulo in (Select idmodulo from modulo where estado_modulo=1)
$whereadd
order by modulo asc, nombresub asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
<script>
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
function registra_permiso(idsubmodulo){
	var direccionurl='usuarios_perm_registra.php';	
	//alert(direccion);
	var parametros = {
	  "idusu_get"   : '<?php echo $idusu_get; ?>',
	  "idsubmodulo"  : idsubmodulo,
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
				$("#box_td_"+idsubmodulo).html('Cargando...');	
		},
		success:  function (response, textStatus, xhr) {
			if(IsJsonString(response)){
				var obj = jQuery.parseJSON(response);
				if(obj.valido == 'S'){
					$("#box_td_"+idsubmodulo).html(obj.html_checkbox);
					var permitido = obj.permitido;
					var elem = $("#box_"+idsubmodulo);
					//alert(elem);
					var idbox = "entrante";
					switchery_reactivar_uno(idsubmodulo);
				}else{
					alert(obj.errores);
				}
			}else{
				alert(response);	
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
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
		
			alert('Pagina no encontrada [404]');
		
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
function switchery_reactivar(){
	var elems = Array.prototype.slice.call(document.querySelectorAll('.js-switch'));
	elems.forEach(function(html) {
		var switchery = new Switchery(html);
	});
}
function switchery_reactivar_uno(idsubmodulo){
		var elems = document.querySelector('#box_'+idsubmodulo);
		var switchery = new Switchery(elems);

}
</script>
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
                    <h2>Asignar Permisos a usuario</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<p>
	<a href="usuarios.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
	<a href="usuarios_log.php?idusu_get=<?php echo $idusu_get; ?>" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Log</a>				  
</p>
<hr />
 <p><strong>Usuario: </strong><?php echo antixss($rsus->fields['usuario']); ?></p>

<hr />
<a href="usuarios_perm_registra_all.php?id=<?php echo $idusu_get; ?>&accion=a" class="btn btn-sm btn-default"><span class="fa fa-toggle-on"></span> Marcar Todos</a>
<a href="usuarios_perm_registra_all.php?id=<?php echo $idusu_get; ?>&accion=b" class="btn btn-sm btn-default"><span class="fa fa-toggle-off"></span> Desmarcar Todos</a>
					  
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
      <tr>
        <td ></td>
          <th >M&oacute;dulo</th>
          
          <th >Sub-m&oacute;dulo</th>
          <th>Qu&eacute; Hace?</th>
        </tr>
		  </thead>
		<tbody>
      <?php $i = 0;
while (!$rs->EOF) {




    $idsubmodulo = $rs->fields['idsubmod'];
    ?>
      <tr>
        <td id="box_td_<?php echo $idsubmodulo; ?>">		
			<input name="producto" id="box_<?php echo $idsubmodulo; ?>" type="checkbox" value="S" class="js-switch" onChange="registra_permiso(<?php echo $idsubmodulo; ?>);" <?php if (intval($rs->fields['asignado']) > 0) {
			    echo "checked";
			} ?>   >
		</td>
		<td><?php echo $rs->fields['modulo']; ?></td>
		<td><?php echo $rs->fields['nombresub']; ?></td>
		<td><?php echo $rs->fields['desmoduppal']; ?></td>
	  </tr>
      <?php $i++;
    $rs->MoveNext();
} ?>
			</tbody>
</table>
</div>			  
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
<link href="vendors/switchery/dist/switchery.min.css" rel="stylesheet">
<script src="vendors/switchery/dist/switchery.min.js" type="text/javascript"></script>
  </body>
</html>
