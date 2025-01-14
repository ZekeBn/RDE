<?php
/*-------------------------------------
26/05/2023 Se habilita sucursal (filtro)
29/06/2023 Se habilita por preferencia Pines de SELF y WEB
--------------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "180";
require_once("includes/rsusuario.php");
$limite = " limit 100";

$order = " order by idcliente desc ";
/*if(trim($_GET['razon_social']) != ''){
    $razon_social=antisqlinyeccion(trim($_GET['razon_social']),"like");
    $whereadd.=" and cliente.razon_social like '%$razon_social%'";
}*/
if (trim($_GET['ruc']) != '') {
    $ruc = antisqlinyeccion(trim($_GET['ruc']), "text");
    $whereadd .= " and cliente.ruc = $ruc";
}
if (intval($_GET['documento']) > 0) {
    $documento = antisqlinyeccion(trim($_GET['documento']), "int");
    $whereadd .= " and cliente.documento = $documento";
}

$v_rz = trim($_GET['razon_social']);
if ($v_rz != '') {
    $ra = antisqlinyeccion($v_rz, 'text');
    $ra = str_replace("'", "", $ra);
    $len = strlen($ra);
    // armar varios likes por cada palabra
    $v_rz_ar = explode(" ", $v_rz);
    foreach ($v_rz_ar as $palabra) {
        $whereadd .= " and razon_social like '%$palabra%' ";
    }
    $order = "
	order by 
	CASE WHEN
		substring(razon_social from 1 for $len) = '$ra'
	THEN
		0
	ELSE
		1
	END asc, 
	razon_social asc
	";
}

$v_fantasia = trim($_GET['fantasia']);
if ($v_fantasia != '') {
    $fa = antisqlinyeccion($v_fantasia, 'text');
    $fa = str_replace("'", "", $fa);
    $len = strlen($fa);
    // armar varios likes por cada palabra
    $v_fa_ar = explode(" ", $v_fantasia);
    foreach ($v_fa_ar as $palabra) {
        $whereadd .= " and fantasia like '%$palabra%' ";
    }
    $order = "
	order by 
	CASE WHEN
		substring(fantasia from 1 for $len) = '$fa'
	THEN
		0
	ELSE
		1
	END asc, 
	fantasia asc
	";
}
$sucu = intval($_REQUEST['sucursal']);
if ($sucu > 0) {
    $addsucu = " and cliente.sucursal=$sucu ";
    $limite = " ";
}

$consulta = "
select *,
(select usuario from usuarios where cliente.registrado_por = usuarios.idusu) as registrado_por,
(select clientetipo from cliente_tipo where cliente_tipo.idclientetipo = cliente.tipocliente) as tipocliente
from cliente 
where 
 estado = 1 
 $whereadd $addsucu
 $order
$limite 
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$treg = $rs->RecordCount();

$buscar = "select * from preferencias";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$habilita_self = trim($rspref->fields['habilita_self']);

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
                    <h2>Clientes Administracion</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">



<p>
<a href="cliente_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a> 
<a href="cliente_borrado.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Clientes Borrados</a>

	<a href="cliente_importar.php" class="btn btn-sm btn-default"><span class="fa fa-upload"></span> Carga Masiva</a>
	<?php if ($rspref->fields['usa_adherente'] == 'S') { ?>
	<a href="cliente_adherente_importar.php" class="btn btn-sm btn-default"><span class="fa fa-upload"></span> Carga Masiva Adherentes</a>
	<?php } ?>
</p>
<hr />

<form id="form1" name="form1" method="get" action="">


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Razon social </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="razon_social" id="razon_social" value="<?php  if (isset($_GET['razon_social'])) {
	    echo htmlentities(strtoupper($_GET['razon_social']));
	}?>" placeholder="Razon social" class="form-control"  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Documento </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="documento" id="documento" value="<?php  if (isset($_GET['documento'])) {
	    echo htmlentities($_GET['documento']);
	}?>" placeholder="Documento" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Ruc </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="ruc" id="ruc" value="<?php  if (isset($_GET['ruc'])) {
	    echo htmlentities($_GET['ruc']);
	} ?>" placeholder="Ruc" class="form-control"  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Fantasia </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="fantasia" id="fantasia" value="<?php  if (isset($_GET['fantasia'])) {
	    echo htmlentities(strtoupper($_GET['fantasia']));
	}?>" placeholder="Fantasia" class="form-control"  />                    
	</div>
</div>
<?php
$buscar = "Select * from sucursales where estado <>6 order by nombre asc";
$rssucu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

?>
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
		<select name="sucursal" class="form-control" id="sucursal">
			<option value="" selected="selected">Todas</option>
			<?php while (!$rssucu->EOF) { ?>
			<option value="<?php echo $rssucu->fields['idsucu']; ?>"<?php if ($_REQUEST['sucursal'] == $rssucu->fields['idsucu']) { ?> selected="selected" <?php } ?><?php $ns = trim($rssucu->fields['nombre']);?>><?php echo $rssucu->fields['nombre']; ?></option>
			<?php $rssucu->MoveNext();
			}
?>
		</select>
	</div>
</div>



<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Buscar</button>

        </div>
    </div>

</form>
<hr />
<h3>Viendo <?php echo $treg ?>&nbsp; <a href="clientes_listado_xls.php?ids=<?php echo intval($sucu) ?>" class="btn btn-sm btn-default"><span class="fa fa-file-excel-o"></span> Descargar Clientes</a> <a href="clientes_listado_suc_xls.php?ids=<?php echo intval($sucu) ?>" class="btn btn-sm btn-default"><span class="fa fa-file-excel-o"></span> Descargar Clientes Suc</a></h3>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idcliente</th>
			<th align="center">Tipocliente</th>
			<th align="center">Razon social</th>
			<th align="center">Ruc</th>
			<th align="center">Documento</th>
			<th align="center">Nombre</th>
			<th align="center">Apellido</th>
			<th align="center">Fantasia</th>
			<?php if ($sucu > 0) { ?>
			<th>Sucursal</th>
			<?php } ?>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="cliente_edit.php?id=<?php echo $rs->fields['idcliente']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="cliente_edita_cred_linea.php?id=<?php echo $rs->fields['idcliente']; ?>&ref=ca" class="btn btn-sm btn-default" title="Linea de Credito" data-toggle="tooltip" data-placement="right"  data-original-title="Linea de Credito"><span class="fa fa-money"></span></a>
					<a href="cliente_suc.php?id=<?php echo $rs->fields['idcliente']; ?>" class="btn btn-sm btn-default" title="Sucursales" data-toggle="tooltip" data-placement="right"  data-original-title="Sucursales"><span class="fa fa-university"></span></a>
					<?php if ($rspref->fields['usa_legajo_cliente'] == 'S') { ?>
					<a href="cliente_legajo.php?id=<?php echo $rs->fields['idcliente']; ?>" class="btn btn-sm btn-default" title="Legajo" data-toggle="tooltip" data-placement="right"  data-original-title="Legajos"><span class="fa fa-user"></span></a>
					<?php } ?>
					<?php if ($rspref->fields['usa_adherente'] == 'S') { ?>
					<a href="adherentes_credito.php?id=<?php echo $rs->fields['idcliente']; ?>" class="btn btn-sm btn-default" title="Adherentes" data-toggle="tooltip" data-placement="right"  data-original-title="Adherentes"><span class="fa fa-child"></span></a>
					<?php } ?>
					<?php if ($rspref->fields['habilita_portal'] == 'S') { ?>
					<a href="gest_administrar_acceso_portal.php?idc=<?php echo $rs->fields['idcliente']; ?>" class="btn btn-sm btn-default" title="Acceso Empresas" data-toggle="tooltip" data-placement="right"  data-original-title="Acceso Empresas"><span class="fa fa-spinner"></span></a>
					<?php } ?>
					<?php if ($habilita_self == 'S') { ?>
						<a href="gest_administrar_pines.php?idc=<?php echo $rs->fields['idcliente']; ?>" class="btn btn-sm btn-default" title="Generar Pines" data-toggle="tooltip" data-placement="right"  data-original-title="Generar Pines"><span class="fa fa-key"></span></a>
					<?php } ?>
					
					
					<a href="cliente_del.php?id=<?php echo $rs->fields['idcliente']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idcliente']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['tipocliente']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['documento']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['nombre']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['apellido']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['fantasia']); ?></td>
			<?php if ($sucu > 0) { ?>
			<td><?php echo antixss($ns); ?></td>
			<?php } ?>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
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
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
