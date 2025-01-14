<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "180";
require_once("includes/rsusuario.php");

/*
idtipodoc	descripcion
1	CONTRATO
2	DOCUMENTO IDENTIDAD
3	RUC
4	OTROS
5	ADENDA CONTRATO
6	HOJA SERVICIO
7	RESCISION CONTRATO
8	ACUSE RECIBO CONTRATO
*/


$consulta = "
select * 
from cliente
where
estado <> 6
and idcliente not in 
	(
		select idcliente 
		from cliente_legajo 
		where 
		estado <> 6 
		and idtipodocumento = 1
	)
order by idcliente desc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$num_total_registros = $rs->RecordCount();

/******** PAGINACION **********/
$nropagina = intval($_GET["pagina"]);
//Limito la busqueda
$TAMANO_PAGINA = 100;
//examino la página a mostrar y el inicio del registro a mostrar
if ($nropagina == 0) {
    $inicio = 0;
    $nropagina = 1;
} else {
    $inicio = ($nropagina - 1) * $TAMANO_PAGINA;
}
//calculo el total de páginas
$total_paginas = ceil($num_total_registros / $TAMANO_PAGINA);

// volver a consultar pero agregar los limites
$consulta .= " 
Limit $inicio, $TAMANO_PAGINA
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

/******** PAGINACION **********/



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
                    <h2>Clientes sin contrato</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
					  

<a href="cliente_legajo_rep.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Busqueda de Legajos</a>
<a href="#" class="btn btn-sm btn-primary"><span class="fa fa-search"></span> Clientes Activos sin Legajos</a>
<hr />	
    <div class="clearfix"></div>

    <ul class="pagination">
<?php
//$rs_prod->MoveFirst();
if ($url_add != '') {
    $url = "cliente_legajo_rep_sinleg.php?sf=n".$url_add;
} else {
    $url = "cliente_legajo_rep_sinleg.php?sf=s";
}
if ($total_paginas > 1) {
    //echo '<a href="'.$url.'?pagina='.($pagina-1).'"><img src="images/izq.gif" border="0"></a>';
    if ($nropagina != 1) {
        echo '<li><a href="'.$url.'&pagina='.($nropagina - 1).'">&laquo;</a></li>';
    }
    for ($i = 1;$i <= $total_paginas;$i++) {
        if ($nropagina == $i) {
            //si muestro el índice de la página actual, no coloco enlace
            //echo $pagina;
            echo '<li class="active" style="background-color:#FE980F; color:#FFF;"><a href="'.$url.'">'.$nropagina.'</a></li>';
        } else {
            //si el índice no corresponde con la página mostrada actualmente,
            //coloco el enlace para ir a esa página
            // echo '  <a href="'.$url.'?pagina='.$i.'">'.$i.'</a>  ';
            echo '<li><a href="'.$url.'&pagina='.$i.'">'.$i.'</a></li>';
        }

    }
    if ($nropagina != $total_paginas) {
        //echo '<a href="'.$url.'?pagina='.($pagina+1).'"><img src="images/der.gif" border="0"></a>';
        echo '<li><a href="'.$url.'&pagina='.($nropagina + 1).'">&raquo;</a></li>';
    }
}
?>
    </ul>
    <div class="clearfix"></div>
    <br />
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

		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">


					<a href="cliente_legajo.php?id=<?php echo $rs->fields['idcliente']; ?>" class="btn btn-sm btn-default" title="Legajo" data-toggle="tooltip" data-placement="right"  data-original-title="Legajos"><span class="fa fa-user"></span></a>
		

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
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />


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
