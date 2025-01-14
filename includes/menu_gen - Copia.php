<?php

if ($dirsup == "S") {
    $dirini = '../';
} elseif ($dirsup_sec == "S") {
    $dirini = '../../';
} else {
    $dirini = '';
}

// Obtener el protocolo (http o https)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

// Obtener el nombre del servidor (dominio)
$serverName = $_SERVER['SERVER_NAME'];

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

$buscar = "
SELECT DISTINCT modulo.modulo, modulo_detalle.nombresub, modulo_detalle.pagina FROM modulo
INNER JOIN modulo_detalle ON modulo_detalle.idmodulo = modulo.idmodulo
INNER JOIN modulo_usuario ON modulo_usuario.idmodulo = modulo_detalle.idmodulo
INNER JOIN usuarios ON usuarios.idusu = modulo_usuario.idusu
WHERE usuarios.idusu = $idusu
ORDER BY modulo.modulo, modulo_detalle.nombresub;
";
$rsbuscar = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$resultados_menu = null;
while (!$rsbuscar->EOF) {
    $modulo = $rsbuscar->fields['modulo'];
    $nombresubm = $rsbuscar->fields['nombresub'];
    $paginam = $rsbuscar->fields['pagina'];

    $resultados_menu .= "
		<a class='a_link_menu'  href='javascript:void(0);' data-hidden-value='$paginam' onclick=\"abrirpagina('$protocol','$serverName','$paginam');\">$modulo/$nombresubm</a>
		";
    $rsbuscar->MoveNext();
}

$nombresubm = "Buscar Menú";

// crea imagen
$img = $dirini . "gfx/empresas/emp_" . $idempresa . ".png";
if (!file_exists($img)) {
    $img = $dirini . "gfx/empresas/emp_0.png";
}

?>
<script>
  function buscarmenu(event) {
    event.preventDefault();
    var div, ul, li, a, i;
    div = document.getElementById("myDropdownm");
    a = div.getElementsByTagName("a");
    for (i = 0; i < a.length; i++) {
      a[i].style.display = "block";
    }

    document.getElementById("myInputm").classList.toggle("show");
    document.getElementById("myDropdownm").classList.toggle("show");
    div = document.getElementById("myDropdownm");
    $("#myInputm").focus();

    $(document).mousedown(function(event) {
      var target = $(event.target);
      var myInput = $('#myInputm');
      var myDropdown = $('#myDropdownm');
      var div = $("#lista_menu");
      var button = $("#iddepartameto");
      // Verificar si el clic ocurrió fuera del elemento #my_input
      if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdownm").length && myInput.hasClass('show')) {
        // Remover la clase "show" del elemento #my_input
        myInput.removeClass('show');
        myDropdown.removeClass('show');
      }

    });
  }

  function buscarmenu2(event) {
    event.preventDefault();
    var input, filter, ul, li, a, i;
    input = document.getElementById("myInputm");
    filter = input.value.toUpperCase();
    div = document.getElementById("myDropdownm");
    a = div.getElementsByTagName("a");
    for (i = 0; i < a.length; i++) {
      txtValue = a[i].textContent || a[i].innerText;
      paginaValue = a[i].getAttribute('data-hidden-value');
      if (txtValue.toUpperCase().indexOf(filter) > -1 || paginaValue.indexOf(filter) > -1) {
        a[i].style.display = "block";
      } else {
        a[i].style.display = "none";
      }
    }
  }

  function abrirpagina(protocol, servername, pagina) {
    var url = protocol + servername;
    if (servername === 'localhost') {
      url += '/desarrollo';
    }
    url += '/' + pagina;
    const checkbox = document.getElementById('otrapestana');
    var pestana = "_self";
    if (checkbox.checked) {
      pestana = ""; 
    }
    console.log(url);
    window.open(url, pestana);
  }
</script>
<style type="text/css">
  #lista_menu {
    width: 100%;
    color: black;
    font-weight: bold;

  }

  .a_link_menu {
    display: block;
    padding: 0.8rem;
    color: black;
    font-weight: bold;
  }

  .a_link_menu:hover {
    color: black;
    background: #73879C;
    font-weight: bold;
  }

  .dropdown_menu {
    position: absolute;
    top: 70px;
    left: 0;
    z-index: 99999;
    width: 100% !important;
    overflow: auto;
    white-space: nowrap;
    background: #fff !important;
    border: #c2c2c2 solid 1px;
    color: black;
    font-weight: bold;
  }

  .dropdown_menu_input {
    position: absolute;
    top: 37px;
    left: 0;
    z-index: 99999;
    display: none;
    width: 100% !important;
    padding: 5px !important;
    color: black;
    font-weight: bold;
  }

  .btn_proveedor_select {
    border: #c2c2c2 solid 1px;
    color: black;
    font-weight: bold;
    width: 100%;
  }
</style>

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
        <form id="form1" name="form1" method="post" action="">

          <div class=" form-group">
            <div class="col-md-12 col-sm-12 col-xl-12">
              <div class="" style="display:flex">
                <label for="abrir-otra-venta">Abrir en otra pestaña&emsp;&emsp;&emsp;</label>
                <input type="checkbox" id="otrapestana" name="otrapestana" checked>
              </div>
            </div>
            <div class="col-md-12 col-sm-12 col-xs-12">
              <div class="" style="display:flex;">
                <div class="dropdown " id="lista_menu">
                  <select onclick="buscarmenu(event)" class="form-control" id="buscarme" name="buscarme">
                    <option value="" disabled selected></option>
                    <?php if ($modulo) { ?>
                      <option value="<?php echo $modulo ?>" data-hidden-pagina="<?php echo $paginam ?>" selected><?php echo $nombresubm ?></option>
                    <?php } ?>
                  </select>
                  <input class="dropdown_menu_input col-md-12 col-sm-12 col-xs-12" type="text" placeholder="Buscar Menú" id="myInputm" onkeyup="buscarmenu2(event)">
                  <div id="myDropdownm" class="dropdown-content hide dropdown_menu links-wrapper col-md-12 col-sm-12 col-xs-12" style="max-height: 200px;overflow: auto;">
                    <?php echo $resultados_menu ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
        <ul class="nav side-menu">
          <li><a href="<?php echo $dirini; ?>index.php"><i class="fa fa-home"></i> Inicio </a></li>
          <li><a href="#"><input class="form-control" name="" type="text" value="" placeholder="Busqueda..."></a></li>
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
                  <li><a href="<?php echo $dirini; ?><?php echo trim($rssubmodulos->fields['pagina']); ?><?php if ($rssubmodulos->fields['target_blank'] != 'S') { ?>#<?php echo $rssubmodulos->fields['idsubmod'] ?><?php } ?>" <?php if ($rssubmodulos->fields['target_blank'] == 'S') { ?> target="_blank" <?php } ?>><?php echo Capitalizar(trim($rssubmodulos->fields['nombresub'])); ?></a></li>
                <?php $rssubmodulos->MoveNext();
              } ?>
              </ul>
            </li>
          <?php $rsmodulos->MoveNext();
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