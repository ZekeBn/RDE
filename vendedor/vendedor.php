<?php
/*-------------------------------------
26/05/2023 Se habilita sucursal (filtro)
29/06/2023 Se habilita por preferencia Pines de SELF y WEB
--------------------------------------*/
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "180";
require_once("../includes/rsusuario.php");
$limite = " limit 100";

$order = " order by idvendedor desc ";
$whereadd = ""; // Inicializar la variable para las condiciones WHERE

// Validación y construcción para el campo codigo_vendedor
if (isset($_GET['codigo_vendedor']) && trim($_GET['codigo_vendedor']) !== '') {
    $codigo_vendedor = antisqlinyeccion(trim($_GET['codigo_vendedor']), "like");
    $whereadd .= " AND vendedor.codigo_vendedor LIKE '%$codigo_vendedor%'";
}

// Validación y construcción para el campo nrodoc
if (isset($_GET['nrodoc']) && trim($_GET['nrodoc']) !== '') {
    $nrodoc = antisqlinyeccion(trim($_GET['nrodoc']), "like");
    $whereadd .= " AND vendedor.nrodoc LIKE '%$nrodoc%'";
}

// Validación y construcción para el campo nombres
if (isset($_GET['nombres']) && trim($_GET['nombres']) !== '') {
    $nombres = antisqlinyeccion(trim($_GET['nombres']), "like");
    $whereadd .= " AND vendedor.nombres LIKE '%$nombres%'";
}

// Validación y construcción para el campo apellidos
if (isset($_GET['apellidos']) && trim($_GET['apellidos']) !== '') {
    $apellidos = antisqlinyeccion(trim($_GET['apellidos']), "like");
    $whereadd .= " AND vendedor.apellidos LIKE '%$apellidos%'";
}
// echo $codigo_vendedor, "\n";
// echo $nrodoc, "\n";
// echo $nombres, "\n";
// echo $apellidos, "\n";
// echo $whereadd, "\n"; exit;
// Construir la consulta SQL
$consulta = "
    SELECT *,
    (SELECT usuario FROM usuarios WHERE vendedor.registrado_por = usuarios.idusu) AS registrado_por,
    (SELECT descripcion FROM tipo_vendedor WHERE tipo_vendedor.idtipovendedor = vendedor.tipovendedor) AS tipovendedor,
    (SELECT descripcion FROM zona_vendedor WHERE zona_vendedor.codigo_zona = vendedor.codigo_zona) AS zona
    FROM vendedor
    WHERE estado = 'A' $whereadd $order $limite
";

// Ejecutar la consulta SQL
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$treg = $rs->RecordCount();

$buscar = "select * from preferencias";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$habilita_self = trim($rspref->fields['habilita_self']);

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
                    <h2>Vendedores</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">



<p>
<a href="vendedor_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a> 
</p>
<hr />

<form id="form1" name="form1" method="get" action="">


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Cod. Vendedor </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="codigo_vendedor" id="codigo_vendedor" value="<?php  if (isset($_GET['codigo_vendedor'])) {
	    echo htmlentities(strtoupper($_GET['codigo_vendedor']));
	}?>" placeholder="Codigo del Vendedor" class="form-control"  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Documento </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nrodoc" id="nrodoc" value="<?php  if (isset($_GET['nrodoc'])) {
	    echo htmlentities($_GET['nrodoc']);
	}?>" placeholder="Documento" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombres" id="nombres" value="<?php  if (isset($_GET['nombres'])) {
	    echo htmlentities($_GET['nombres']);
	} ?>" placeholder="Nombre" class="form-control"  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Apellido </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="apellidos" id="apellidos" value="<?php  if (isset($_GET['apellidos'])) {
	    echo htmlentities(strtoupper($_GET['apellidos']));
	}?>" placeholder="Apellido" class="form-control"  />                    
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
<h3>Viendo <?php echo $treg ?>&nbsp; <a href="clientes_listado_xls.php?ids=<?php echo intval($sucu) ?>" class="btn btn-sm btn-default" style="display: none"><span class="fa fa-file-excel-o" ></span> Descargar Clientes</a> <a href="clientes_listado_suc_xls.php?ids=<?php echo intval($sucu) ?>" class="btn btn-sm btn-default" style="display: none"><span class="fa fa-file-excel-o"></span> Descargar Clientes Suc</a></h3>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">IdVendedor</th>
			<th align="center">TipoVendedor</th>
			<th align="center">Cod. Vendedor</th>
			<th align="center">Nombre</th>
			<th align="center">Apellido</th>
			<th align="center">Zona</th>
			<th align="center">Registrado por</th>
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
					<a href="vendedor_del.php?id=<?php echo $rs->fields['idvendedor']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idvendedor']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['tipovendedor']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['codigo_vendedor']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['nombres']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['apellidos']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['zona']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
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
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
