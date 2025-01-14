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
$img = $dirini . "gfx/empresas/emp_" . $idempresa . ".png";
if (!file_exists($img)) {
    $img = $dirini . "gfx/empresas/emp_0.png";
}

?>
<script>
  function filterMenu() {
    var input, filter, ul, li, a, i, j, txtValue, subUl, subLi, subA, found;
    input = document.getElementById('menuFilter');
    filter = input.value.toUpperCase();
    ul = document.getElementsByTagName('ul')[0];
    li = ul.getElementsByTagName('li');

    for (i = 0; i < li.length; i++) {
      a = li[i].getElementsByTagName("a")[0];
      txtValue = a.textContent || a.innerText;

      if (li[i].id === 'inicio') {
        li[i].style.display = ""; // Mostrar siempre
        continue;
      }

      // Inicialmente ocultar todos los elementos
      li[i].style.display = "none";
      found = false;

      // Verifica los elementos de nivel superior
      if (txtValue.toUpperCase().indexOf(filter) > -1) {
        li[i].style.display = "";
        found = true;
      }

      // Verifica los elementos anidados
      subUl = li[i].getElementsByTagName('ul')[0];
      if (subUl) {
        subLi = subUl.getElementsByTagName('li');
        for (j = 0; j < subLi.length; j++) {
          subA = subLi[j].getElementsByTagName("a")[0];
          txtValue = subA.textContent || subA.innerText;
          if (txtValue.toUpperCase().indexOf(filter) > -1) {
            subLi[j].style.display = "";
            li[i].style.display = ""; // Mostrar el elemento principal
            subUl.style.display = "block"; // Mostrar el submenú
            found = true;
          } else {
            subLi[j].style.display = "none";
          }
        }
      }

      // Si no se encontró nada en los elementos anidados, oculta el submenú
      if (!found && subUl) {
        subUl.style.display = "none";
      }
    }
  }
  function busca_modulo(valor,idempresa,idusu){
    var n = valor.length;
    if(n > 2){
       var parametros = {
              "modulo" : valor,
              "idempresa" : idempresa,
              "idusu" : idusu
       };
       console.log(parametros);
       $.ajax({
                data:  parametros,
                url:   'includes/buscar_modulo.php',
                type:  'post',
                beforeSend: function () {
                        $("#descmodulo").html("Cargando...");
                },
                success:  function (response) {
                        $("#descmodulo").html(response);
                }
        });    
    }
  }
  function nuevapestana(event, url) {
    var openInNewTab = document.getElementById('openInNewTab').checked;
    if (openInNewTab) {
      window.location.href = window.location.href;
      window.open(url, '_blank');
      event.preventDefault();
    }
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
        <h3>Buscar Modulo</h3>
        <label>&emsp;Abrir en otra pestaña
          <input type="checkbox" id="openInNewTab" checked>
        </label>
        <input class="form-control" id="menuFilter" type="text" value="" onkeyup="filterMenu()" placeholder="Busqueda...">
        <!-- <input class="form-control" type="text" name="menuFilter" id="menuFilter" value="" placeholder="Busqueda..." onkeyup="busca_modulo(this.value,'<?php echo $idempresa; ?>','<?php echo $idusu; ?>');"/> -->
        <ul class="nav side-menu" id="descmodulo">
          <li id="inicio"><a href="<?php echo $dirini; ?>index.php"><i class="fa fa-home"></i> Inicio </a></li>

    <?php
    // require_once("includes/buscar_modulo.php");
?>
              <?php
      while (!$rsmodulos->EOF) {
          $nm = capitalizar(trim($rsmodulos->fields['nombre']));
          $idmodulo = $rsmodulos->fields['idmodulo'];

          ?>
            <li><a><i class="fa fa-clone"></i> <?php echo $nm ?> <span class="fa fa-chevron-down"></span></a>
              <ul class="nav child_menu">
                <?php
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
$add
order by nombresub asc
";

          $rssubmodulos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
          //echo $consulta;
          while (!$rssubmodulos->EOF) {
              $paginam = $dirini . trim($rssubmodulos->fields['pagina']);
              if ($rssubmodulos->fields['target_blank'] != 'S') {
                  $paginam .= '#' . $rssubmodulos->fields['idsubmod'];
              }
              ?>
                  <li><a href="<?php echo $paginam; ?>" onclick="nuevapestana(event,'<?php echo $paginam; ?>')" <?php if ($rssubmodulos->fields['target_blank'] == 'S') { ?> target="_blank" <?php } ?>><?php echo Capitalizar(trim($rssubmodulos->fields['nombresub'])); ?></a></li>
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