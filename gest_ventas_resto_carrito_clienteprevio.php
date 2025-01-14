 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");




if (trim($_GET['razon_social']) != '') {
    $razon_social = antisqlinyeccion(trim($_GET['razon_social']), "like");
    $whereadd .= " and cliente.razon_social like '%$razon_social%'";
}
if (trim($_GET['ruc']) != '') {
    $ruc = antisqlinyeccion(trim($_GET['ruc']), "text");
    $whereadd .= " and cliente.ruc = $ruc";
}
if (intval($_GET['documento']) > 0) {
    $documento = antisqlinyeccion(trim($_GET['documento']), "int");
    $whereadd .= " and cliente.documento = $documento";
}

$consulta = "
select *,
(select usuario from usuarios where cliente.registrado_por = usuarios.idusu) as registrado_por,
(select clientetipo from cliente_tipo where cliente_tipo.idclientetipo = cliente.tipocliente) as tipocliente,
(select canal_venta from canal_venta where idcanalventa = cliente.idcanalventacli) as canal,
(select concat(nombres,' ',apellidos) from vendedor where idvendedor = cliente.idvendedor) as vendedor
from cliente 
where 
 estado = 1 
 and idcanalventacli is not null
 $whereadd
order by razon_social asc
limit 100
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
                    <h2>Aplicar Cliente</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


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




<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Buscar</button>

        </div>
    </div>

</form>
<hr />

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
            <th align="center">Canal</th>
            <th align="center">Vendedor</th>

        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="gest_ventas_resto_carrito_clienteprevio_aplica.php?id=<?php echo $rs->fields['idcliente']; ?>" class="btn btn-sm btn-default" title="Aplicar Cliente" data-toggle="tooltip" data-placement="right"  data-original-title="Aplicar Cliente"><span class="fa fa-check"></span></a>

                </div>

            </td>
            <td align="center"><?php echo intval($rs->fields['idcliente']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['tipocliente']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['documento']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['canal']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['vendedor']); ?></td>
        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />

<br /><br /><br /><br /><br />

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
