<?php
if ($dirsup == "S") {
    $dirini = '../';
} elseif ($dirsup_sec == "S") {
    $dirini = '../../';
} else {
    $dirini = '';
}
?>
		<div class="top_nav">
          <div class="nav_menu">
            <nav>
              <div class="nav toggle">
                <a id="menu_toggle"><i class="fa fa-bars"></i></a>
              </div>

              <ul class="nav navbar-nav navbar-right">
                <li class="">
                  <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    <img src="<?php echo $dirini; ?>gfx/usuarios/img.jpg" alt=""><?php echo strtoupper($cajero); ?>
                    <span class=" fa fa-angle-down"></span>
                  </a>
                  <ul class="dropdown-menu dropdown-usermenu pull-right">
                    <li><a href="<?php echo $dirini; ?>preferencias.php"><i class="fa fa-eye pull-right"></i> 
                    <span>Mis accesos</span>
                    <?php /*if($hayfotoperf != 'S'){ ?><span class="badge bg-red pull-right">Sin Foto</span><?php }*/ ?>
                    </a>
                     
                    </li>
                    <li>
                      <a href="<?php echo $dirini; ?>cambiar_clave.php"><i class="fa fa-lock pull-right"></i>
                        <span>Cambiar Contrase&ntilde;a</span>
                      </a>
                    </li>
                    <li>
                      <a href="<?php echo $dirini; ?>soporte.php"><i class="fa fa-phone pull-right"></i>
                        <span>Soporte</span>
                      </a>
                    </li>
                    <li>
                      <a href="<?php echo $dirini; ?>ayuda.php"><i class="fa fa-video-camera pull-right"></i>
                        <span>Video Tutoriales</span>
                      </a>
                    </li>
                    <li><a href="#" onclick="cierra_sesion();"><i class="fa fa-sign-out pull-right"></i> Salir del Sistema</a></li>
                  </ul>
                </li>
				<?php /*?>
                <!-- INICIO NOTIFICACIONES -->
                <li role="presentation" class="dropdown">
                  <a href="javascript:;" class="dropdown-toggle info-number" data-toggle="dropdown" aria-expanded="false">
                    <i class="fa fa-envelope-o"></i>
                    <?php if($totalnotiflei > 0){  ?><span class="badge bg-green"><?php echo $totalnotiflei; ?></span><?php } ?>
                  </a>

                  <ul id="menu1" class="dropdown-menu list-unstyled msg_list" role="menu">
<?php
if($row_notileidet->fields['idnotificacion'] > 0){
while(!$row_notileidet->EOF){

    $idusu_noti=$row_notileidet->fields['enviado_por'];
    $sexo_noti=$row_notileidet->fields['enviado_por_sexo'];
    if($sexo_noti == 'F'){
        $imgperfil_mini_noti="gfx/usuarios/img_fem.jpg";
    }else{
        $imgperfil_mini_noti="gfx/usuarios/img.jpg";
    }
    // si existe foto de perfil
    if(file_exists("gfx/usuarios/us_mini_".$idusu_noti.".jpg")){
        $imgperfil_mini_noti="gfx/usuarios/us_mini_".$idusu_noti.".jpg";
    }
    $imgperfil_mini_noti.='?'.date("YmdHis");

     ?>
                    <li>
                      <a href="notificaciones_usuario.php?id=<?php echo $row_notileidet->fields['idnotificacion'] ?>#noticontent">
                        <span class="image"><img src="<?php echo $imgperfil_mini_noti; ?>" alt="Profile Image" /></span>
                        <span>
                          <span><?php echo trim(substr(trim(capitalizar($row_notileidet->fields['operador_envia'])),0,18)); ?></span>
                          <span class="time"><?php echo date("d/m/Y H:i",strtotime($row_notileidet->fields['fechahora'])); ?></span>
                        </span>
                        <span class="message">
                          <?php echo trim(substr(trim($row_notileidet->fields['titulo']).' '.trim($row_notileidet->fields['texto']),0,100)); ?>...
                        </span>
                      </a>
                    </li>
<?php $row_notileidet->MoveNext(); }
}
?>

                    <li>
                      <div class="text-center">
                        <a href="notificaciones_usuario.php">
                          <strong>Ver todas las alertas</strong>
                          <i class="fa fa-angle-right"></i>
                        </a>
                      </div>
                    </li>
                  </ul>
                </li>
                <!-- FIN NOTIFICACIONES -->
               <?php */?> 
                
              </ul>
            </nav>
          </div>
        </div>