 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "11";
$submodulo = "143";
require_once("includes/rsusuario.php");

ini_set('memory_limit', '128M');

$historico = "N";
if (strtolower($_GET['his']) == 's') {
    $historico = "S";
}


if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    //$desde=date("Y-m-").'01';
    $desde = date("Y-m-d");
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}

if ($_GET['idgrupoinsu'] > 0) {
    $grupo = intval($_GET['idgrupoinsu']);
    $whereadd .= " and insumos_lista.idgrupoinsu = $grupo ";
    $whereadd2 .= " and insumos_lista.idgrupoinsu = $grupo ";
}
if ($_GET['iddeposito'] > 0) {
    $deposito = intval($_GET['iddeposito']);
    $whereadd .= " and stock_movimientos.iddeposito = $deposito ";
    $whereadd2 .= " and stock_movimientos_bak.iddeposito = $deposito ";
}
if ($_GET['idtipomov'] > 0) {
    $idtipomov = intval($_GET['idtipomov']);
    $whereadd .= " and stock_movimientos.tipomov = $idtipomov ";
    $whereadd2 .= " and stock_movimientos_bak.tipomov = $idtipomov ";
}
if ($_GET['idinsumo'] > 0) {
    $idinsumo = intval($_GET['idinsumo']);
    $whereadd .= " and stock_movimientos.idinsumo = $idinsumo ";
    $whereadd2 .= " and stock_movimientos_bak.idinsumo = $idinsumo ";
}

if (trim($_GET['desde']) != '') {
    $consulta = "
    SELECT  
    stock_movimientos.idstockmov as idstockmov_pk,
    gest_depositos.descripcion as deposito,
    stock_tipomov.tipomov,  
    insumos_lista.idinsumo, 
    insumos_lista.descripcion,
    (select barcode from productos where idprod_serial = insumos_lista.idproducto) as codbar,
    stock_movimientos.cantidad_sistema_ant,
    stock_movimientos.sumaoresta,
    stock_movimientos.cantidad,
    stock_movimientos.cantidad_sistema,
    insumos_lista.descripcion as insumo, 
    gest_depositos.descripcion as deposito, 
    stock_movimientos.fechahora as fechahoramov,
    productos.barcode as codbar,
    usuarios.usuario as usuario,
    stock_movimientos.fecha_comprobante,
    stock_movimientos.codrefer
    FROM stock_movimientos
    inner join stock_tipomov on stock_tipomov.idtipomov = stock_movimientos.tipomov
    inner join insumos_lista on insumos_lista.idinsumo = stock_movimientos.idinsumo
    inner join gest_depositos on gest_depositos.iddeposito = stock_movimientos.iddeposito
    inner join usuarios on usuarios.idusu = stock_movimientos.idusu
    left join productos on productos.idprod_serial = insumos_lista.idproducto
    WHERE
    date(stock_movimientos.fechahora) >= '$desde'
    and date(stock_movimientos.fechahora) <= '$hasta'
    and insumos_lista.hab_invent = 1
    $whereadd
    
    ";

    if ($historico == 'S') {

        $consulta .= "
        UNION ALL 
        
        SELECT
        stock_movimientos_bak.idstockmov as idstockmov_pk,
        gest_depositos.descripcion as deposito,
        stock_tipomov.tipomov,  
        insumos_lista.idinsumo, 
        insumos_lista.descripcion,
        (select barcode from productos where idprod_serial = insumos_lista.idproducto) as codbar,
        stock_movimientos_bak.cantidad_sistema_ant,
        stock_movimientos_bak.sumaoresta,
        stock_movimientos_bak.cantidad,
        stock_movimientos_bak.cantidad_sistema,
        insumos_lista.descripcion as insumo, 
        gest_depositos.descripcion as deposito, 
        stock_movimientos_bak.fechahora as fechahoramov,
        productos.barcode as codbar,
        usuarios.usuario as usuario,
        stock_movimientos_bak.fecha_comprobante,
        stock_movimientos_bak.codrefer
        FROM stock_movimientos_bak
        inner join stock_tipomov on stock_tipomov.idtipomov = stock_movimientos_bak.tipomov
        inner join insumos_lista on insumos_lista.idinsumo = stock_movimientos_bak.idinsumo
        inner join gest_depositos on gest_depositos.iddeposito = stock_movimientos_bak.iddeposito
        inner join usuarios on usuarios.idusu = stock_movimientos_bak.idusu
        left join productos on productos.idprod_serial = insumos_lista.idproducto
        WHERE
        date(stock_movimientos_bak.fechahora) >= '$desde'
        and date(stock_movimientos_bak.fechahora) <= '$hasta'
        and insumos_lista.hab_invent = 1
        $whereadd2
        ";

    }

    $consulta .= "
    ORDER BY 
        fechahoramov DESC, 
        idstockmov_pk DESC 
    limit 100000
    ";
    //echo $consulta;exit;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}



?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script>
function buscar_insumo_ventana(){
    var direccionurl='busqueda_producto.php';        
    var parametros = {
      "dest" : 'idinsumo'          
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $('#modal_ventana').modal('show');
            $("#modal_titulo").html('Buscar Articulo');
            $("#modal_cuerpo").html('Cargando...');        
            $("#idinsumo").val('');            
        },
        success:  function (response) {
            $("#modal_cuerpo").html(response);
        }
    });
}
function prod_busq(dest){
    var direccionurl='busqueda_producto.php';        
    var parametros = {
      "dest" : dest          
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $('#cuadro_pop').modal('show');
            $("#myModalLabel").html('Busqueda de Producto de Origen');
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response) {
            $("#modal_cuerpo").html(response);
        }
    });
    
}
function buscar_producto(destino_resultado){
    var busqueda = $("#producto").val();
    var direccionurl='busqueda_producto_inf_movstock.php';        
    var parametros = {
      "prod"   : busqueda,
      "dest"   : destino_resultado
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $("#busqueda_prod").html('Cargando...');                
        },
        success:  function (response) {
            $("#busqueda_prod").html(response);
        }
    });
}
function buscar_producto_codbar(e,destino_resultado){
    
    var codbar = $("#codbar").val();
    tecla = (document.all) ? e.keyCode : e.which;
    // tecla enter
      if (tecla==13){
        busca_producto_codbar_res(codbar,destino_resultado);
    }
    
}
function busca_producto_codbar_res(codbar,destino_resultado){
    //alert(destino_resultado);
    var parametros = {
      "codbar"   : codbar,
      "dest"   : destino_resultado
    };
    $.ajax({          
        data:  parametros,
        url:   'busqueda_producto_inf_movstock.php',
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#busqueda_prod").html('Cargando...');                
        },
        success:  function (response) {
            $("#busqueda_prod").html(response);
            //alert(response);
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
function seleccionar_item(idproducto,idinput,descipcion){
    $("#"+idinput).val(idproducto+' - '+descipcion);
    //$('#modal').modal().hide();
    $('#modal_ventana').modal('hide');
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
                    <h2>Informe de Movimientos de Stock</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">



        
<form id="form1" name="form1" method="get" action="<?php echo antixss($_SERVER['PHP_SELF']);?>">

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Desde *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="desde" id="desde" value="<?php echo $desde; ?>" placeholder="Desde" class="form-control" required="required" />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Hasta *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="hasta" id="hasta" value="<?php echo $hasta; ?>" placeholder="Hasta" class="form-control" required="required" />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Deposito </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT iddeposito, descripcion
FROM gest_depositos
where
estado = 1
order by descripcion asc
 ";

// valor seleccionado
if (isset($_GET['iddeposito'])) {
    $value_selected = htmlentities($_GET['iddeposito']);
} else {
    //$value_selected=htmlentities($rs->fields['idgrupoinsu']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'iddeposito',
    'id_campo' => 'iddeposito',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'iddeposito',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>                 
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Movimiento </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idtipomov, tipomov
FROM stock_tipomov

order by tipomov asc
 ";

// valor seleccionado
if (isset($_GET['idtipomov'])) {
    $value_selected = htmlentities($_GET['idtipomov']);
} else {
    //$value_selected=htmlentities($rs->fields['idgrupoinsu']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtipomov',
    'id_campo' => 'idtipomov',

    'nombre_campo_bd' => 'tipomov',
    'id_campo_bd' => 'idtipomov',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>                 
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Articulo </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="idinsumo" id="idinsumo" value="<?php  if (intval($_GET['idinsumo']) > 0) {
        echo antixss($_GET['idinsumo']);
    } ?>" placeholder="Click para Buscar Articulo..." class="form-control" readonly onMouseUp="buscar_insumo_ventana();"  /> 
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Grupo Stock </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idgrupoinsu, nombre
FROM grupo_insumos
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_GET['idgrupoinsu'])) {
    $value_selected = htmlentities($_GET['idgrupoinsu']);
} else {
    //$value_selected=htmlentities($rs->fields['idgrupoinsu']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idgrupoinsu',
    'id_campo' => 'idgrupoinsu',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idgrupoinsu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>                 
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Historico </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    
<?php
// valor seleccionado
if (isset($_GET['his'])) {
    $value_selected = htmlentities($_GET['his']);
} else {
    $value_selected = 'n';
}
// opciones
$opciones = [

    'DATOS ACTUALES (RECOMENDADO)' => 'n',
    'DATOS ANTIGUOS (Mas Lento, solo hasta 100.000 registros por tanda, Mas de 1 A&ntilde;o de antiguedad)' => 's'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'his',
    'id_campo' => 'his',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);

?>
    </div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Generar Reporte</button>

        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>


<?php if (trim($_GET['desde']) != '') {?>
                      
<p><a href="inf_stock_mov_csv.php<?php echo parametros_url(); ?>" class="btn btn-sm btn-default"><span class="fa fa-file-excel-o"></span> Descargar CSV</a></p>
                      
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
    <tr>
      <th>Deposito</th>
      <th>Movimiento</th>
      <th>Cod Art.</th>
      <th>Cod Barra</th>
      <th>Articulo</th>
      <th>Cantidad Anterior</th>
      <th>Cantidad Mov</th>
      <th>Cantidad Posterior</th>
      <th>Fecha/Hora MOV</th>
      <th>Fecha/Hora Comp.</th>
      <th>Codigo Comp.</th>
      <th>Usuario</th>
    </tr>
    </thead>
    <tbody>
<?php while (!$rs->EOF) {
    if ($rs->fields['sumaoresta'] == '+') {
        $cantidad = $rs->fields['cantidad'];
    } else {
        $cantidad = $rs->fields['cantidad'] * -1;
    }
    $cant_mov_acum += $cantidad;

    ?>
    <tr>
      <td align="center"><?php echo $rs->fields['deposito']; ?></td>
      <td align="center"><?php echo $rs->fields['tipomov']; ?></td>
      <td><?php echo $rs->fields['idinsumo']; ?></td>
      <td><?php echo $rs->fields['codbar']; ?></td>
      <td><a href="inf_stock_mov.php<?php echo parametros_url(); ?>"><?php echo $rs->fields['insumo']; ?></a></td>
      <td align="right"   
><?php if ($rs->fields['cantidad_sistema_ant'] != '') {
    echo formatomoneda($rs->fields['cantidad_sistema_ant'], 4, 'N');
} ?></td>
      <td align="right" 
<?php
if ($rs->fields['sumaoresta'] == '+') {
    $color = "#069600";
} else {
    $color = "#FF0000";
}
    ?>   
style="color:<?php echo $color; ?>;"><?php echo $rs->fields['sumaoresta']; ?><?php echo formatomoneda($rs->fields['cantidad'], 4, 'N'); ?></td>
      <td align="right"   
><?php if ($rs->fields['cantidad_sistema'] != '') {
    echo formatomoneda($rs->fields['cantidad_sistema'], 4, 'N');
} ?></td>
      <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['fechahoramov'])); ?></td>
      <td align="center"><?php if ($rs->fields['fecha_comprobante'] != '') {
          echo date("d/m/Y", strtotime($rs->fields['fecha_comprobante']));
      } ?></td>
      <td align="center"><?php echo $rs->fields['codrefer']; ?></td>
      <td align="center"><?php echo $rs->fields['usuario']; ?></td>
    </tr>
<?php $rs->MoveNext();
} ?>
    </tbody>
    <tfoot>
    <tr style="font-weight:bold; background-color:#CCC;">
      <td align="center">Total</td>
      <td align="center"></td>
      <td></td>
      <td></td>
      <td></td>
      <td align="right"   ></td>
      <td align="right" ><?php echo $cant_mov_acum; ?></td>
      <td align="right"   ></td>
      <td align="center"></td>
      <td align="center"></td>
      <td align="center"></td>
      <td align="center"></td>
    </tr>
    </tfoot>
  
</table>
</div>
<br />
<?php } ?>




                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->
        
        <!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
                   <h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
                Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        
        </div>
    </div>
</div>
        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
