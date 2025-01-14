 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";
require_once("includes/rsusuario.php");

$id = intval($_GET['id']);
if ($id == 0) {
    echo "No se envio el id";
    exit;
}
$buscar = "
    Select *, (select idgrupoinsu from insumos_lista where idproducto = productos.idprod_serial) as idgrupoinsu,
    (select idinsumo from insumos_lista where idproducto = productos.idprod_serial) as idinsumo,
    (select nombre from medidas where id_medida=productos.idmedida) as medida,
    (select idinsumo from insumos_lista where idproducto = productos.idprod_serial) as idinsumo,
    (select idtipoiva from insumos_lista where idproducto = productos.idprod_serial) as idtipoiva_compra
    from productos 
    where 
    idprod_serial=$id 
    and borrado = 'N'  
    and idempresa = $idempresa
    ";
$rsminip = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idproducto = intval($rsminip->fields['idprod_serial']);
$descripcion = $rsminip->fields['descripcion'];
if ($idproducto == 0) {
    echo "Producto inexistente o fue borrado";
    exit;
}




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
function asigna_deposito(iddeposito,idsucursal){
    var direccionurl='productos_depo_suc_registra_suc.php';    
    //alert(direccion);
    var parametros = {
      "iddeposito" : iddeposito,
      "idproducto"  : '<?php echo $idproducto; ?>',
      "idsucursal"  : idsucursal
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
                $("#box_"+idsucursal).html('Cargando...');    
        },
        success:  function (response, textStatus, xhr) {
            $("#box_"+idsucursal).html(response);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
    
}
function borra_deposito(idsucursal){
    var direccionurl='productos_depo_suc_registra_suc_del.php';    
    //alert(direccion);
    var parametros = {
      "idproducto"  : '<?php echo $idproducto; ?>',
      "idsucursal"  : idsucursal
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
                $("#box_"+idsucursal).html('Cargando...');    
        },
        success:  function (response, textStatus, xhr) {
            $("#box_"+idsucursal).html(response);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
    
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
                    <h2>Asignar Deposito de descuento stock para <?php echo antixss($descripcion); ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<p>
    <a href="gest_editar_productos_new.php?id=<?php echo $idproducto; ?>" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
          
</p>
<div class="alert alert-warning alert-dismissible fade in" role="alert">
<strong>AVISO:</strong><br />Esta configuracion no se aplicara a menos que este activada en la configuracion <strong>'Comportamiento Deposito Ventas'</strong> la opcion <strong style="color:#870002;">'Deposito Sucursal Producto'</strong> en <a href="sucursales.php" target="_blank">Gestion > sucursales</a>. <br />
Si no se asigna ningun deposito especifico se descontara el stock del deposito de ventas de cada sucursal.
                      
                      
</div>
<hr />
<strong>Producto: <?php echo antixss($descripcion); ?></strong><br />
<?php
$consulta = "
select *
from sucursales 
where 
 estado = 1 
order by nombre asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
                      
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="left">Sucursal</th>
            <th align="center">Deposito descuento stock</th>

        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) {
    $idsucursal_imp = $rs->fields['idsucu'];


    ?>
        <tr>
            <td align="left"><?php echo antixss($rs->fields['nombre']); ?> [<?php echo $idsucursal_imp?>]</td>
            <td align="left" id="box_<?php echo $idsucursal_imp; ?>">
                <?php require("productos_depo_suc_asignar_suc_mini.php"); ?>
            </td>
        </tr>
<?php

    $rs->MoveNext();
} //$rs->MoveFirst();?>
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
