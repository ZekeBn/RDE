<?php

if ($dirsup == "S") {
    $dirini = '../';
} elseif ($dirsup_sec == "S") {
    $dirini = '../../';
} else {
    $dirini = '';
}

$consulta = "Select idmodulo,descripcion,modulo as nombre from modulo 
where
idmodulo
		 in 
		 (
		 select distinct idmodulo 
		 from modulo_empresa 
		 where 
		 idempresa=$idempresa 
		 and estado=1 
		 and idmodulo in (select distinct idmodulo from modulo_usuario where idusu=$idusu and modulo_usuario.submodulo <> 2)
		 ) 
order by nombre asc
";
$rsmodulos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// crea imagen
$img = $dirini."gfx/empresas/emp_".$idempresa.".png";
if (!file_exists($img)) {
    $img = $dirini."gfx/empresas/emp_0.png";
}



?>
        <div class="col-md-3 left_col">
          <div class="left_col scroll-view">
            <div class="navbar nav_title" style="border: 0;  text-align:center;">
            <a href="<?php echo $dirini; ?>index.php"><img src="<?php echo $img; ?>" height="50" style="margin:0px auto; margin-top:5px; width:50px;background-color:#FFF;" border="0" alt="<?php echo $nombreempresa; ?>" title="<?php echo $nombreempresa; ?>" /></a>
            <!--  <a href="index.php" class="site_title"> <span><?php echo $nombreempresa; ?></span></a>-->
            </div>

            <div class="clearfix"></div>

            <!-- menu profile quick info -->
            <div class="profile clearfix">
              <div class="profile_pic">
                <img src="<?php echo $dirini; ?>gfx/usuarios/img.jpg" alt="..." class="img-circle profile_img">
              </div>
              <div class="profile_info">
                <span>Bienvenid@, </span>
                <h2><?php echo strtoupper($cajero); ?></h2>
              </div>
              <div class="clearfix"></div>
            </div>
            <!-- /menu profile quick info -->

            <br />

            <!-- sidebar menu -->
            <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
              <div class="menu_section">
                <h3>Modulos</h3>
                <ul class="nav side-menu">
                  <li><a href="<?php echo $dirini; ?>index.php"><i class="fa fa-home"></i> Inicio </a></li>
                  <!--<li><a href="#"><input class="form-control" name="" type="text" value="" placeholder="Busqueda..."></a></li>-->
<?php
while (!$rsmodulos->EOF) {
    $nm = capitalizar(trim($rsmodulos->fields['nombre']));
    $idmodulo = $rsmodulos->fields['idmodulo'];

    ?>
                  <li><a><i class="fa fa-clone"></i> <?php echo $nm ?> <span class="fa fa-chevron-down"></span></a>
                  	<ul class="nav child_menu">
                  <?php

        $elemento = capitalizar((utf8_decode($rsmodulos->fields['nombre'])));

    $elementodes = utf8_decode($rsmodulos->fields['descripcion']);
    $idmodppal = intval($rsmodulos->fields['idmodulo']);



    $consulta = "
Select nombresub,descripcion,pagina,idsubmod,target_blank,
registrado_el 
from modulo_detalle 
inner join modulo_usuario on modulo_usuario.submodulo=modulo_detalle.idsubmod
where 
modulo_usuario.idmodulo=$idmodppal
and modulo_usuario.submodulo <> 2
and modulo_usuario.estado=1 
and  modulo_detalle.mostrar = 1 
and modulo_usuario.idusu=$idusu
and modulo_detalle.mostrar_nav = 'S'
order by nombresub asc
";
    $rssubmodulos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    //echo $consulta;
    while (!$rssubmodulos->EOF) { ?>
                      <li><a href="<?php echo $dirini; ?><?php echo trim($rssubmodulos->fields['pagina']); ?><?php if ($rssubmodulos->fields['target_blank'] != 'S') { ?>#<?php echo $rssubmodulos->fields['idsubmod'] ?><?php } ?>" <?php if ($rssubmodulos->fields['target_blank'] == 'S') { ?> target="_blank"<?php } ?>><?php echo Capitalizar(trim($rssubmodulos->fields['nombresub'])); ?></a></li>
				<?php  $rssubmodulos->MoveNext();
    } ?> 
               		 </ul>
                 </li>
<?php  $rsmodulos->MoveNext();
} ?>
                </ul>
              </div>
              

            </div>
            <!-- /sidebar menu -->

            <!-- /menu footer buttons -->
            <div class="sidebar-footer hidden-small">
              <a data-toggle="tooltip" data-placement="top" title="Preferencias" href="preferencias.php">
                <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
              </a>
              <a data-toggle="tooltip" data-placement="top" data-original-title="" title="Video Tutoriales" href="ayuda.php">
                <span class="glyphicon glyphicon-facetime-video" aria-hidden="true"></span>
                </a>
              <!--<a data-toggle="tooltip" data-placement="top" data-original-title="" title="">
                <span class="glyphicon glyphicon-option-horizontal" aria-hidden="true"></span>
              </a>-->
              <a data-toggle="tooltip" data-placement="top" data-original-title="" title="">
                <span class="glyphicon glyphicon-option-horizontal" aria-hidden="true"></span>
              </a>
              <a data-toggle="tooltip" data-placement="top" title="Salir del Sistema" href="logout.php">
                <span class="glyphicon glyphicon-off" aria-hidden="true"></span>
              </a>
            </div>
            <!-- /menu footer buttons -->
            
         
          </div> 

        </div>
        
