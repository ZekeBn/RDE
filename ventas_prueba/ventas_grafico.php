<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// echo "hola";exit;
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";

$dirsup = "S";
require_once("../includes/rsusuario.php");


$pagina_actual = $_SERVER['REQUEST_URI'];
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
        $newUrl = $urlParts['path'] . '?';
    } else {
        $newUrl = $urlParts['path'] . '?' . $newQuery . '&';
    }

    $pagina_actual = $newUrl;
} else {
    $pagina_actual = $urlParts['path'] . '?';
}


// paginado del index

$limit = "";
$consulta_numero_filas = "
select 
count(*) as filas from   ventas
";
$rs_filas = $conexion->Execute($consulta_numero_filas) or die(errorpg($conexion, $consulta_numero_filas));
$num_filas = $rs_filas->fields['filas'];
$filas_por_pagina = 20;
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
////////////////////////////////
$desde = $_GET['desde'];
$hasta = $_GET['hasta'];


$consulta = "
select
	idcliente,
	razon_social,
	SUM(total_venta) AS total_ventas
from 
	ventas 
where 
	fechasola BETWEEN '" . $desde . "' AND '" . $hasta . "'
GROUP BY idcliente
ORDER BY total_ventas DESC 
LIMIT 5
";

$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
//var_dump($rs);
$top = [];
$cont = 0;
while (!$rs->EOF) {
    $top[$cont]['razon'] = $rs->fields['razon_social'];
    $top[$cont]['total'] = $rs->fields['total_ventas'];
    $cont = $cont + 1;

    $rs->MoveNext();
}


?>

<!DOCTYPE html>
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
					<?php require_once("../includes/lic_gen.php"); ?>

					<!-- SECCION -->
					<div class="row">
						<div class="col-md-12 col-sm-12 col-xs-12">
							<div class="x_panel">
								<div class="x_title">
									<h2>Datos Plantilla</h2>
									<ul class="nav navbar-right panel_toolbox">
										<li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
										</li>
									</ul>
									<div class="clearfix"></div>
								</div>
								<div class="x_content">

									<div style="width:50%;margin:auto;">
										<canvas id="myChart"></canvas>
									</div>




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
			<?php require_once("../includes/pie_gen.php"); ?>
			<!-- /footer content -->
		</div>
	</div>
	<?php require_once("../includes/footer_gen.php"); ?>
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<script>
		var dataSet= <?php echo json_encode($top); ?>;
		console.log(dataSet);
		const ctx = document.getElementById('myChart');
		var lab = [];
		var dat = [];
		for (var i=0; i<dataSet.length; i++){
		
			lab.push(dataSet[i]['razon']);
			dat.push(dataSet[i]['total']);

		}
		console.log(lab);
		console.log(dat);
		new Chart(ctx, {
			type: 'pie',
			data: {
				labels:lab,
				datasets: [{
					label: 'Total Ventas',
					data: dat,
					borderWidth: 1
				}]
			},
			options: {
				scales: {
					y: {
						beginAtZero: true
					}
				}
			}
		});
	</script>
</body>

</html>