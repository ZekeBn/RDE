<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "613";

$dirsup = "S";
require_once("../includes/rsusuario.php");

// un vendedor tiene clientes que devolvieron articulos y en que cantidad de que factura
// Obtener la URL actual
$pagina_actual = $_SERVER['REQUEST_URI'];
$iddeposito = $_GET['id'];

$irrecuperable = 0;
$averiado = 0;
$otros = 0;

$consulta = "SELECT gest_depositos.iddeposito FROM gest_depositos WHERE UPPER(gest_depositos.descripcion) like UPPER(\"averiado\") ";
$rs_averiados = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idaveriado = $rs_averiados->fields['iddeposito'];

$consulta = "SELECT gest_depositos.iddeposito FROM gest_depositos WHERE UPPER(gest_depositos.descripcion) like UPPER(\"IRECUPERABLE\")";
$rs_irrecuperables = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idirrecuperable = $rs_irrecuperables->fields['iddeposito'];
$urlParts = parse_url($pagina_actual);

// Verificar si hay parámetros GET
if (isset($urlParts['query'])) {
    // Convertir los parámetros GET en un arreglo asociativo
    parse_str($urlParts['query'], $queryParams);

    // Eliminar el parámetro 'pag' (si existe)
    unset($queryParams['pag']);

    // Reconstruir los parámetros GET sin 'pag'
    $newQuery = http_build_query($queryParams);
    // Reconstruir la URL completa
    if (isset($newQuery) == false || empty($newQuery)) {
        $newUrl = $urlParts['path'].'?' ;
    } else {
        $newUrl = $urlParts['path'] . '?' . $newQuery .'&';
    }



    $pagina_actual = $newUrl;
} else {
    $pagina_actual = $urlParts['path'].'?' ;
}





function formatearNumero($numero)
{
    // Convertir el número a una cadena
    $numero = strval($numero);

    // Dividir la cadena en partes
    $parte1 = substr($numero, 0, 3);
    $parte2 = substr($numero, 3, 3);
    $parte3 = substr($numero, 6);

    // Unir las partes con guiones
    $numeroFormateado = $parte1 . '-' . $parte2 . '-' . $parte3;

    return $numeroFormateado;
}
$consulta_button_group = "";
$button_group = "";
$whereadd = " ";
if (trim($_GET['filter']) != '') {
    $button_group = $_GET['filter'];
    if ($button_group == 1) {
        $consulta_button_group = "
      GROUP BY devolucion_det.idproducto ";
    }
    if ($button_group == 2) {
        $consulta_button_group = "
      GROUP BY cliente.idcliente ";
    }
    if ($button_group == 3) {
        $consulta_button_group = "
      group by vendedor.idvendedor ";
    }
}

if (trim($_GET['idcliente']) != '') {
    $idcliente = $_GET['idcliente'];
    $whereadd .= " and c.idcliente = $idcliente ";
}
if (trim($_GET['num_factura']) != '') {
    $num_factura = $_GET['num_factura'];
    $whereadd .= " and v.factura = $num_factura ";
}
// echo $whereadd;exit;
$limit = "";
$consulta_numero_filas = "
select 
count(*) as filas from ventas 
";
$rs_filas = $conexion->Execute($consulta_numero_filas) or die(errorpg($conexion, $consulta_numero_filas));
$num_filas = $rs_filas->fields['filas'];
$filas_por_pagina = 50;
$paginas_num_max = ceil($num_filas / $filas_por_pagina);

$limit = "  LIMIT $filas_por_pagina";


$num_pag = intval($_GET['pag']);
$offset = null;
if (($_GET['pag']) > 0) {
    $numero = (intval($_GET['pag']) - 1) * $filas_por_pagina;
    $offset = " offset $numero";
} else {
    $offset = " ";
    $num_pag = 1;
}



$consulta = "SELECT 
cliente.idcliente, 
vendedor.idvendedor, 
devolucion_det.iddevolucion, 
devolucion_det.idproducto, 
devolucion_det.iddeposito, 
(devolucion_det.cantidad) as cantidad_sumada, 
cliente.razon_social as rs_cliente, 
( Select gest_depositos.descripcion from gest_depositos where gest_depositos.iddeposito = devolucion_det.iddeposito ) as deposito,
CONCAT(
  COALESCE(vendedor.nombres, '--'), 
  ' ', 
  COALESCE(vendedor.apellidos, '--')
) as nombre_vendedor, 
productos.descripcion, 
ventas.factura as factura,
devolucion.registrado_el as fecha
from 
devolucion_det 
INNER JOIN devolucion ON devolucion.iddevolucion = devolucion_det.iddevolucion 
INNER JOIN ventas ON ventas.idventa = devolucion.idventa 
INNER JOIN cliente ON cliente.idcliente = ventas.idcliente 
INNER JOIN vendedor ON cliente.idvendedor = vendedor.idvendedor 
INNER JOIN productos ON productos.idprod = devolucion_det.idproducto 
INNER JOIN productos_sucursales on productos_sucursales.idproducto = devolucion_det.idproducto 
WHERE
productos.borrado = 'N' 
and productos_sucursales.idsucursal = $idsucursal 
and productos_sucursales.activo_suc = 1 
and devolucion_det.iddeposito = $iddeposito
ORDER BY 
cliente.idcliente ASC $limit $offset
";

$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


$consulta = "SELECT gest_depositos.*, gest_depositos_tiposala.tipo_sala,
(select usuario from usuarios where gest_depositos.idencargado = usuarios.idusu) as encargado
FROM gest_depositos
INNER JOIN gest_depositos_tiposala on gest_depositos_tiposala.idtiposala = gest_depositos.tiposala
WHERE gest_depositos.iddeposito = $iddeposito
and gest_depositos.estado = 1
";

$rs_deposito_detalle = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <script>
        function alerta_modal(titulo,mensaje){
        $('#modal_ventana').modal('show');
        $("#modal_titulo").html(titulo);
        $("#modal_cuerpo").html(mensaje);
        }

    </script>

<style>
    tbody tr:hover td{
     color : #73879C !important;
    }
        .div_leyenda{
            width: 100%;
            display: flex;
            align-items: center;
            /* justify-content: center; */
        }
        .averiado{
            background: #FFC857;
            font-weight: bold;
            color: white;
        }
        .irrecuperable{
            background: #DBA8AC;
            font-weight: bold;
            color: white;
        }
        .otros{
            background: #9BC1BC;
            font-weight: bold;
            color: white;

        }
        .averiado:hover,.irrecuperable:hover,.otros:hover{
            color:#3F5367;
        }
        .leyenda_alerta{
            width: 10px;
            height: 10px;
            background: #EA6153;
            display: inline-block;
            margin: 10px;
        }
        .leyenda_otros{
            width: 10px;
            height: 10px;
            background: #9BC1BC;
            display: inline-block;
            margin: 10px;
        }
        .leyenda_irrecuperable{
            width: 10px;
            height: 10px;
            background: #DBA8AC;
            display: inline-block;
            margin: 10px;
        }
        .leyenda_averiada{
            width: 10px;
            height: 10px;
            background: #FFC857;
            display: inline-block;
            margin: 10px;
        }
</style>
  
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
                    <h2>Ranking Devoluciones</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	

  

                  
<p>
  <a href="devolucion_ranking.php?filter=4" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Regresar</a>
</p>

<hr />

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
		<tr>
			<th align="center">iddeposito</th>
			<td align="center"><?php echo antixss($rs_deposito_detalle->fields['iddeposito']); ?></td>
		</tr>
        <tr>
			<th align="center">Nombres</th>
			<td align="center"><?php echo antixss($rs_deposito_detalle->fields['descripcion']); ?></td>
		</tr>
        <tr>
			<th align="center">Tipo sala</th>
			<td align="center"><?php echo antixss($rs_deposito_detalle->fields['tipo_sala']); ?></td>
		</tr>
        <tr>
			<th align="center">Encargado</th>
			<td align="center"><?php echo antixss($rs_deposito_detalle->fields['encargado']); ?></td>
		</tr>
    </table>
</div>
<br />
<?php
$id = 0;
?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
        <?php while (!$rs->EOF) {
            $clasecss = "";
            if (intval($rs->fields['iddeposito']) == intval($idaveriado)) {
                $clasecss = "averiado";
                $averiado++;
            }
            if (intval($rs->fields['iddeposito']) == intval($idirrecuperable)) {
                $clasecss = "irrecuperable";
                $irrecuperable++;
            }
            if (intval($rs->fields['iddeposito']) != intval($idirrecuperable) && intval($rs->fields['iddeposito']) != intval($idaveriado)) {
                $clasecss = "otros";
                $otros++;
            }
            if ($id != $rs->fields['idcliente']) {
                $id = $rs->fields['idcliente'];

                ?>
                <thead>
                    <tr>
                        <th align="center">cliente</th>
                        <th align="center" colspan="6"><?php echo $rs->fields['rs_cliente']; ?></th>
                    </tr>
                </thead>

                <thead>
                    <tr>
                        <th align="center">idarticulo</th>
                        <th align="center">articulo</th>
                        <th align="center">cantidad</th>
                        <th align="center">iddeposito</th>
                        <th align="center">deposito</th>
                        <th align="center">factura</th>
                        <th align="center">fecha</th>
                    </tr>
                </thead>

                <?php
            }
            ?>
        
        <tbody>
                <tr>
                <td align="center"><?php echo antixss($rs->fields['idproducto']); ?></td>
                <td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
                <td align="center"><?php echo antixss($rs->fields['cantidad_sumada']); ?></td>
                <td align="center"><?php echo antixss($rs->fields['iddeposito']); ?></td>
                <td align="center" class="<?php echo $clasecss; ?>" ><?php echo antixss($rs->fields['deposito']); ?></td>
                <td align="center"><?php echo antixss($rs->fields['factura']); ?></td>
                <td align="center"><?php echo antixss($rs->fields['fecha']); ?></td>
                </tr>
            <?php $rs->MoveNext();
        } //$rs->MoveFirst();?>
        </tbody>
    </table>
</div>
<br />

<div class="div_leyenda"> 
    <div class="leyenda_irrecuperable"></div><small>Deposito Irrecuperable: <?php echo $irrecuperable; ?></small>
    <div class="leyenda_averiada"></div><small>Deposito Averiado: <?php echo $averiado; ?></small>
    <div class="leyenda_otros"></div><small>Deposito Otros: <?php echo $otros; ?></small>
</div>



                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            

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
