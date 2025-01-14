<?php

require_once("conexion.php");
require_once("funciones.php");
$modulo = "1";
$submodulo = "106";
require_once("rsusuario.php");

$response = [
        'success' => false,
        'modulos' => []
    ];
if ((isset($_POST['modulo']) && ($_POST['modulo']) != '')) {
    $modulo = antisqlinyeccion($_POST['modulo'], 'text');
    $idempresa = antisqlinyeccion($_POST['idempresa'], 'text');
    $isusu = antisqlinyeccion($_POST['idusu'], 'text');
    $modulosolo = str_replace("'", "", $modulo);
    $add = "";
    if ($modulo != 'NULL') {
        $add = "and upper(modulo_detalle.nombresub) like upper(%'$modulo'%)";
        $len = strlen($modulosolo);
    }
}

$buscar = "Select idmodulo,descripcion,modulo as nombre from modulo 
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
