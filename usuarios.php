<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "16";
$submodulo = "76";
require_once("includes/rsusuario.php");

// otorga permisos al modulo inicial
$consulta = "
INSERT  INTO `modulo_usuario`(`idusu`, `idmodulo`, `idempresa`, `estado`, `submodulo`, `registrado_el`, `registrado_por`, `sucursal`) 
select usuarios.idusu, 1, 1, 1, 2, NOW(), 0, 1 
from usuarios where usuarios.estado = 1 and idusu not in (select idusu from modulo_usuario where estado = 1 and submodulo = 2)
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// si el usuario no es de una master franquicia
$consulta = "
select * from usuarios where idusu = $idusu 
";
$rsusfranq = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$franq_m = $rsusfranq->fields['franq_m'];
// si el usuario actual no es master franq ni super filtra
if ($franq_m != 'S' && $superus != 'S') {
    $whereadd = "
    and franq_m = 'N'
    ";
}

// si no es un super usuario debe filtrar usuarios por este campo
if ($superus != 'S') {
    $whereadd .= "
    and super = 'N'
    ";
}
// si no es soporte ni super debe filtra por este campo
if ($soporte != 1) {
    $whereadd .= "
  and soporte = 0
  ";
}


// mensajes de licencia
$consulta = "
update usuarios 
set 
usuarios.adm = 'S' 
WHERE 
idusu in (select idusu from modulo_usuario where submodulo = 76 and estado <> 6)
and (select oculta_lic from preferencias limit 1) = 'N'
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// oculta mensaje para clientes vip tipo micaela
$consulta = "
update usuarios set adm = 'N' where (select oculta_lic from preferencias limit 1) = 'S'
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



$consulta = "
SELECT *, (SELECT fechahora FROM usuarios_accesos where idusuario = usuarios.idusu order by fechahora desc limit 1) as ultacceso,
(select nombre from sucursales where idsucu=usuarios.sucursal and idempresa=$idempresa) as sucuchar,
(SELECT idusu FROM modulo_usuario WHERE submodulo = 76 and idempresa = $idempresa and estado = 1 and usuarios.idusu = modulo_usuario.idusu limit 1) as modusu
FROM usuarios
where
estado = 1
and idempresa = $idempresa
$whereadd
order by bloqueado desc, usuario asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


$consulta = "
SELECT *, 
(SELECT fechahora FROM usuarios_accesos where idusuario = usuarios.idusu order by fechahora desc limit 1) as ultacceso,
 (select us.usuario from usuarios us where us.idusu = usuarios.usu_borrado_por) as usu_borrado_por,
usu_borrado_el
FROM usuarios
where
(estado = 6 OR estado is null)
and idempresa = $idempresa
$whereadd
order by usu_borrado_el desc
limit 10
";
$rs2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));






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
                    <h2>Usuarios Activos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p>
<a href="usuarios_agregar.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
<a href="usuarios_modulos.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Vista por Modulo</a>
<a href="usuarios_log.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Log de Permisos</a>
<a href="usuarios_copiarperm.php" class="btn btn-sm btn-default"><span class="fa fa-files-o"></span> Copiar Permisos</a>
<a href="accesos_sistema.php" class="btn btn-sm btn-default"><span class="fa fa-eye"></span> Accesos</a>
<a href="intentos_fallidos.php" class="btn btn-sm btn-default"><span class="fa fa-eye"></span> Intentos Fallidos</a>
<!--<a href="usuarios_logs.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Logs</a>-->
</p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
         <th></th>
          <th>ID Usuario</th>
        <th>Usuario</th>
        <th>Nombre y Apellido</th>
        <th>Documento</th>
        <th>Sucursal</th>
        <th>Ultimo Acceso</th>
        <th>Bloq</th>

      </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
      <tr>
        <td width="165" align="center">
        <div class="btn-group">
        <?php if ($rs->fields['bloqueado'] != 'S') { ?>
        <a href="usuarios_editar.php?id=<?php echo $rs->fields['idusu']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
        <a href="usuarios_cambioclave.php?id=<?php echo $rs->fields['idusu']; ?>" class="btn btn-sm btn-default" title="Cambiar Clave" data-toggle="tooltip" data-placement="right"  data-original-title="Cambiar Clave"><span class="fa fa-key"></span></a>
        <a href="usuarios_permisos.php?id=<?php echo $rs->fields['idusu']; ?>" class="btn btn-sm btn-default" title="Permisos" data-toggle="tooltip" data-placement="right"  data-original-title="Permisos"><span class="fa fa-shield"></span></a>
        
        
        
        <?php } else { ?>
        <a href="usuarios_desbloquear.php?id=<?php echo $rs->fields['idusu']; ?>" class="btn btn-sm btn-default" title="Desbloquear" data-toggle="tooltip" data-placement="right"  data-original-title="Desbloquear"><span class="fa fa-unlock"></span></a>
        <?php } ?>
          <a href="usuarios_borrar.php?id=<?php echo $rs->fields['idusu']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
        </div>
        </td>
      <td align="center"><?php echo $rs->fields['idusu']; ?></td>
        <td align="center"><?php if ($rs->fields['modusu'] > 0) { ?>(*) <?php } ?><?php echo $rs->fields['usuario']; ?></td>
        <td align="center"><?php echo $rs->fields['nombres']; ?> <?php echo $rs->fields['apellidos']; ?></td>
        <td align="center"><?php echo $rs->fields['documento']; ?></td>
        <td align="center"><?php echo $rs->fields['sucuchar']; ?></td>
        <td align="center"><?php if ($rs->fields['ultacceso'] != '') {
            echo date("d/m/Y H:i:s", strtotime($rs->fields['ultacceso']));
        } ?></td>
        <td align="center"><?php if ($rs->fields['bloqueado'] == 'S') { ?><strong style="color:#FF0000;">SI</strong><?php } else { ?>NO<?php } ?></td>
    </tr>
<?php $rs->MoveNext();
} ?>
    </tbody>
  </table>
 
</div>
 <br /> <br /> <br />



                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Usuarios Borrados (Ultimos 10)</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p><a href="usuarios_borrados.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Lista Completa</a></p>
<hr />
<div class="table-responsive">
<table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
        <tr>
            <th>Acciones</th>
            <th>Usuario</th>
            <th>Nombre y Apellido</th>
            <th>Documento</th>
            <th>Ultimo Acceso</th>
            <th>Borrado por</th>
            <th>Borrado el</th>

          </tr>
    </thead>
    <tbody>
        <?php while (!$rs2->EOF) { ?>
          <tr>
            <td  align="center">
            <a href="usuarios_reactivar.php?id=<?php echo $rs2->fields['idusu']; ?>" class="btn btn-sm btn-default"><span class="fa fa-recycle"></span> Reactivar</a></td>
            <td align="center"><?php echo $rs2->fields['usuario']; ?></td>
            <td align="center"><?php echo $rs2->fields['nombres']; ?> <?php echo $rs2->fields['apellidos']; ?></td>
            <td align="center"><?php echo $rs2->fields['documento']; ?></td>
            <td align="center"><?php if ($rs2->fields['ultacceso'] != '') {
                echo date("d/m/Y H:i:s", strtotime($rs2->fields['ultacceso']));
            } ?></td>
            <td align="center"><?php echo $rs2->fields['usu_borrado_por']; ?></td>
            <td  align="center"><?php if ($rs2->fields['usu_borrado_el'] != '') {
                echo date("d/m/Y H:i:s", strtotime($rs2->fields['usu_borrado_el']));
            } ?></td>
          </tr>
        <?php $rs2->MoveNext();
        } ?>
    </tbody>
</table>
</div>



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
