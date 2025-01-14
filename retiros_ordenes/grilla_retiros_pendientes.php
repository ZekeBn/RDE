<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "613";

$dirsup = "S";
require_once("../includes/rsusuario.php");
$editar_estado = intval($_POST['editar_estado']);

//////////get
$idvendedor = $_GET['idvendedor'];
$idcliente = $_GET['idcliente'];


$whereadd = "";
if (trim($idvendedor) != '') {
    $whereadd .= " and cliente.idvendedor  = $idvendedor ";
}
if (trim($idcliente) != '') {
    $whereadd .= " and cliente.idcliente  = $idcliente ";
}
if (trim($estado_filtro) != '') {
    $whereadd .= " and retiros_ordenes.estado  = $estado_filtro ";
}

// Obtener la URL actual
$pagina_actual = $_SERVER['REQUEST_URI'];
$pagina_actual = str_replace('grilla_retiros_pendientes', 'retiros_ordenes', $pagina_actual);

$urlParts = parse_url($pagina_actual);
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


$limit = "";
$consulta_numero_filas = "
select 
count(*) as filas from retiros_ordenes 
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

if ($editar_estado == 1) {
    $idorden_retiro = intval($_POST['idorden_retiro']);
    $estado = intval($_POST['estado']);


    $consulta = "Update retiros_ordenes set estado = $estado, modificado_el = '$ahora'
     where idorden_retiro = $idorden_retiro
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}
$consulta = "
select retiros_ordenes.*, cliente.razon_social, cliente.idcliente, cliente.idvendedor
from retiros_ordenes
inner join devolucion on devolucion.iddevolucion = retiros_ordenes.iddevolucion 
inner join ventas on ventas.idventa = devolucion.idventa
inner join cliente on ventas.idcliente = cliente.idcliente
where 
retiros_ordenes.estado != 6 
$whereadd
order by idorden_retiro, retiros_ordenes.estado asc
";
// echo $consulta;exit;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?><div class="table-responsive">
    <h2>Retiros</h2>
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idorden retiro</th>
			<th align="center">Cliente</th>
			<th align="center">Estado</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				<div class="btn-group">
				<?php if ($rs->fields['estado'] != 3) { ?>
          <a href="retiros_ordenes_det.php?id=<?php echo $rs->fields['idorden_retiro']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-archive"></span> Finalizar Devoluci&oacute;n</a>
          <?php } ?>	
					
          <a href="javascript:void(0);" onclick="orden_retiro_articulos(<?php echo intval($rs->fields['idorden_retiro']); ?>)" class="btn btn-sm btn-default" title="Articulos" data-toggle="tooltip" data-placement="right"  data-original-title="Articulos"><span class="fa fa-list"></span></a>
				</div>
			</td>
			<td align="center"><?php echo intval($rs->fields['idorden_retiro']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
			<td align="center">
        <?php if ($rs->fields['estado'] != 3) { ?>
          <div class=" form-group" >
            <?php

              // valor seleccionado
              if (isset($_POST['estado'])) {
                  $value_selected = htmlentities($_POST['estado']);
              } else {
                  $value_selected = $rs->fields['estado'];
              }
            // opciones
            $opciones = [
              'Pendiente' => 1,
              'Transito' => 2
            ];
            // parametros
            $idorden_retiro = $rs->fields['idorden_retiro'];
            $acciones = " onchange='cambiar_estado_retiro(this.value,$idorden_retiro)' ";
            $parametros_array = [
              'nombre_campo' => 'estado',
              'id_campo' => 'estado',

              'value_selected' => $value_selected,

              'pricampo_name' => 'Seleccionar...',
              'pricampo_value' => '',
              'style_input' => 'class="form-control"',
              'acciones' => $acciones,
              'autosel_1registro' => 'S',
              'opciones' => $opciones

            ];
            // construye campo
            echo campo_select_sinbd($parametros_array);
            ?>
          </div>
          <?php } else { ?>
            Finalizado
            <?php } ?>


      </td>
		</tr>
<?php

$rs->MoveNext();
} //$rs->MoveFirst();?>
<tr>
                <td align="center" colspan="8">
                    <div class="btn-group">
                        <?php
                        $last_index = 0;
if ($num_pag + 10 > $paginas_num_max) {
    $last_index = $paginas_num_max;
} else {
    $last_index = $num_pag + 10;
}
if ($num_pag != 1) { ?>
                            <a href="<?php echo $pagina_actual ?>pag=<?php echo($num_pag - 1);?>" class="btn btn-sm btn-default" title="<?php echo($num_pag - 1);?>"  data-placement="right"  data-original-title="<?php echo($num_pag - 1);?>"><span class="fa fa-arrow-left"></span></a>
                        <?php }
$inicio_pag = 0;
if ($num_pag != 1 && $num_pag - 5 > 0) {
    $inicio_pag = $num_pag - 5;
} else {
    $inicio_pag = 1;
}
for ($i = $inicio_pag; $i <= $last_index; $i++) {
    ?>
                            <a href="<?php echo $pagina_actual ?>pag=<?php echo($i);?>" class="btn btn-sm btn-default <?php echo $i == $num_pag ? " selected_pag " : "" ?>" title="<?php echo($i);?>"  data-placement="right"  data-original-title="<?php echo($i);?>"><?php echo($i);?></a>
                            <?php if ($i == $last_index && ($num_pag + 1 < $paginas_num_max)) {?>
                                <a href="<?php echo $pagina_actual ?>pag=<?php echo($num_pag + 1);?>" class="btn btn-sm btn-default" title="<?php echo($num_pag + 1);?>"  data-placement="right"  data-original-title="<?php echo($num_pag + 1);?>"><span class="fa fa-arrow-right"></span></a>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </td>
            </tr>


	  </tbody>
	  
    </table>
</div>