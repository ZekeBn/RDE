 <?php
/*----------------------
01/11/2023: SOLO REV.
---------------------*/
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
require_once("../includes/rsusuario.php");

$idatc = intval($_POST['idatc']);
$idmesa = intval($_POST['idmesa']);


if ($idmesa == 0 or $idatc == 0) {
    echo "Error al obtener la mesa o el atc.";
    exit;
}

// consulta a la tabla
$consulta = "
select *
from lista_precios_venta 
where 
idlistaprecio in
(

    select idlistaprecio
    from lista_precios_venta_perm
     where 
    idusuario = $idusu
    
    and estado = 1
) 
and estado = 1
and borrable = 'S'
order by lista_precio asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tr = $rs->RecordCount();

//Vemos si existe una lista de precios alicada para el atc
$buscar = "
    select idventatmp,idlistaprecio,idatc 
    from tmp_ventares 
     inner join tmp_ventares_cab on tmp_ventares_cab.idtmpventares_cab=tmp_ventares.idtmpventares_cab
     where 
    tmp_ventares_cab.idatc=$idatc 
    and tmp_ventares.borrado='N' 
    and tmp_ventares.finalizado='S'
    and tmp_ventares.idlistaprecio is NOT NULL
  ";
$rsvl = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idlista = intval($rsvl->fields['idlistaprecio']);
if ($tr > 0) {
    ?>
<div id="aplicalista"></div>
    <div class="col-md-12 col-xs-12">
    <h4>Seleccione lista a ser aplicada</h4>
        <table width="100%" class="table table-bordered jambo_table bulk_action">
        <thead>
            <tr>
              <th>Aplicada</th>
              <th>Lista precio</th>
              <th>Accion</th>
              
              </tr>
        </thead>
        <tbody>
            <?php while (!$rs->EOF) {
                if ($idlista == $rs->fields['idlistaprecio']) {
                    $aplicado = "<span style='color:black;background-color:yellow;'>LISTA APLICADA</span>";
                } else {
                    $aplicado = "";
                }

                ?>
                <tr>
                    <td><?php echo $aplicado; ?></td>
                    <td><?php echo $rs->fields['lista_precio'] ?>&nbsp;[<?php echo $rs->fields['idlistaprecio'] ?>]</td>
                    <td>
                        <?php if ($aplicado == '') { ?>
                        <a href="javascript:void(0);" onclick="aplicar_lista(<?php echo $rs->fields['idlistaprecio'] ?>,<?php echo $idmesa ?>,<?php echo $idatc ?>)" class="btn btn-sm btn-default"><span class="fa fa-gear"></span>&nbsp; Aplicar Lista</a></td>
                        <?php } else { ?>
                        <a href="javascript:void(0);" onclick="revertir_lista(<?php echo $rs->fields['idlistaprecio'] ?>,<?php echo $idmesa ?>,<?php echo $idatc ?>)" class="btn btn-sm btn-default"><span class="fa fa-recycle"></span>&nbsp; Revertir Lista</a></td>
                        <?php } ?>
                </tr>
            
            <?php $rs->MoveNext();
            } ?>
        </tbody>

    </div>


<?php
} else {
    ?>
<div class="col-md-12">
<h3>Ud no posee autorizacion para utilizar lista de precios. Debe solicitar la activacion a su administrador en gestion->lista precios permisos</h3>
</div>

<?php } ?>

