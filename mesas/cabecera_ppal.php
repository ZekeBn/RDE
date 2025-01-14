 <?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
require_once("../includes/rsusuario.php");


?>

<!---
<div class="col-md-12 col-xs-12">
    <div style="text-align:center;">
         <a href="http://www.restaurante.com.py/" target="_blank"><img src="../img/logoekaru_email.jpg" width="200" height="80" alt=""/></a>
    </div>
</div>
<div class="col-md-12 col-xs-12">
    <div style="text-align:center;  background:#E8E8E8 ;
  border-radius:10px;
  height:auto;
  width:100%;float:left;margin-left:auto;margin-right:auto;">
    <?php
        //Traemos los salones disponibles
    $buscar = "Select * from salon where idsucursal=$idsucursal and estado_salon <> 6 order by preferido,nombre asc";
$rssalones = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tsala = $rssalones->RecordCount();
if ($tsala > 0) {
    while (!$rssalones->EOF) {
        ?>
    <button type="button" class="btn btn-info btn-sm" onClick="cargar(<?php echo $rssalones->fields['idsalon']?>)"><?php echo $rssalones->fields['nombre']?></button>

    <?php $rssalones->MoveNext();
    }
    ?>   
    <?php }?>
    </div>
</div>
---->
<div class="col-md-12 col-xs-12">
    <?php
    // crea imagen
    $img = "../gfx/empresas/emp_".$idempresa.".png";
if (!file_exists($img)) {
    $img = "../gfx/empresas/emp_0.png";
}
?>
    <div class="col-md-4" id="logoempresa">
        <div class="pull-left"><img src="<?php echo $img ?>" style="width: 120px;" height="100px;"></div>
    </div>
    <div class="col-md-4" style="align:center">
        <div style="text-align:center;margin-top:0%;">
                <a href="../index.php"><i class="fa fa-home fa-2x">&nbsp;Regresar</i></a>
        </div>
        <div style="text-align:center;margin-top:2%;">

    
            <button type="button" class="btn btn-warning btn-sm" onClick="cargar(0)" data-toggle="modal" data-target="#myModal">Todos</button>
            <?php
          //Traemos los salones disponibles
          $buscar = "Select * from salon where idsucursal=$idsucursal and estado_salon <> 6 order by preferido,nombre asc";
$rssalones = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tsala = $rssalones->RecordCount();
if ($tsala > 0) {
    while (!$rssalones->EOF) {
        ?>
                <button type="button" class="btn btn-info btn-sm" onClick="cargar(<?php echo $rssalones->fields['idsalon']?>)"><?php echo $rssalones->fields['nombre']?></button>

            <?php $rssalones->MoveNext();
    }?>   
            <?php }?>
        </div>
    </div> 
      <div class="col-md-4" id="logoekaru">
          <div class="pull-right">
          <a href="http://www.restaurante.com.py/" target="_blank"><img src="../img/logoekaru_email.jpg" width="200" height="80" alt=""/></a>
          </div>    
      </div>            
</div>
