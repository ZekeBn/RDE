 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

if ($facturador_electronico == 'S') {
    echo "Esta operacion no esta permitida para facturadores electronicos.";
    exit;
}

$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal and tipocaja = 1 order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$consulta = "
select ruc, razon_social from cliente where borrable = 'N' and estado<>6 order by idcliente asc limit 1
";
$rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$razon_social_pred = strtoupper(trim($rscli->fields['razon_social']));
$ruc_pred = $rscli->fields['ruc'];

$idcaja = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);

if ($idcaja == 0) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>"     ;
    exit;
}
if ($estadocaja == 3) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>"     ;
    exit;
}


$idventa = intval($_GET['vta']);

$buscar = "Select factura,ventas.idventa,recibo,ventas.razon_social,ruchacienda,dv,idpedido,idtandatimbrado,
(
select tipoimpreso from facturas where idtanda = ventas.idtandatimbrado
) as tipoimpreso, ventas.ruc
from ventas
inner join cliente on cliente.idcliente=ventas.idcliente
where 
cliente.idempresa=$idempresa 
and ventas.idempresa=$idempresa 
and ventas.idcaja=$idcaja
and ventas.idventa = $idventa


order by fecha desc
limit 10
";
$rsvv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idventa = intval($rsvv->fields['idventa']);
$tipoimpreso = trim($rsvv->fields['tipoimpreso']);
$ruc_old = trim($rsvv->fields['ruc']);
$tipo_venta = intval($rsvv->fields['tipo_venta']);
$tdata = $rsvv->RecordCount();
//echo $ruc_old;exit;
if ($tipoimpreso == 'AUT' && $ruc_old != $ruc_pred) {
    echo "No se puede editar una factura que pertenece aun timbrado autoimpresor.";
    exit;
}
if ($idventa == 0) {
    header("location: gest_impresiones.php");
    exit;
}

// rellenar retroactivo
$consulta = "
insert into sucursal_cliente
(idcliente, sucursal, direccion, telefono, mail, estado, registrado_por, registrado_el, `borrado_por`, `borrado_el` )
SELECT 
idcliente, 'CASA MATRIZ', cliente.direccion, NULL, NULL, cliente.estado, 1, '$ahora', NULL, NULL 
FROM `cliente` 
where 
idcliente not in (select sucursal_cliente.idcliente from sucursal_cliente)
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

if ($_POST['MM_update'] == 'form1') {
    $valido = "S";
    $errores = "";

    //$factura=antisqlinyeccion(trim($_POST['factura']),"text");
    $idcliente = antisqlinyeccion(trim($_POST['idcliente']), "int");
    $idsucursal_clie = antisqlinyeccion(trim($_POST['idsucursal_clie']), "int");

    /*if(strlen(trim($_POST['factura'])) < 13 or strlen(trim($_POST['factura'])) > 15){
        echo "La factura debe tener al menos 13 digitos.";
        exit;
    }*/
    if (intval($_POST['idcliente']) == 0) {
        $valido = "N";
        $errores .= " - Debe indicar el cliente.<br />";
    }
    if (intval($_POST['idsucursal_clie']) == 0) {
        $valido = "N";
        $errores .= " - Debe indicar la sucursal del cliente.<br />";
    }
    if ($tipoimpreso == 'AUT' && $ruc_old != $ruc_pred) {
        $valido = "N";
        $errores .= " - No se puede editar una factura que pertenece aun timbrado autoimpresor.<br />";
    }

    $consulta = "
    SELECT idcliente , idsucursal_clie
    FROM sucursal_cliente
    where 
    idcliente = $idcliente
    and idsucursal_clie = $idsucursal_clie
    ";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsex->fields['idsucursal_clie']) == 0) {
        $valido = 'N';
        $errores .= '- La sucursal del cliente no corresponde al cliente seleccionado.'.$saltolinea;
    }


    if ($valido == 'S') {
        $consulta = "
        select ruc from cliente where idcliente = $idcliente
        ";
        $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $ruc = trim($rscli->fields['ruc']);
        $ruc_array = explode("-", $ruc);
        $ruchacienda = intval($ruc_array[0]);
        $dv = intval($ruc_array[1]);
        $ruccompleto = $ruchacienda.'-'.$dv;
        $ruccompleto = antisqlinyeccion($ruccompleto, "text");

        // log datos
        $consulta = "
        INSERT INTO ventaslog
        (idventa, registrado_por, registrado_el, idcliente_ant, idcliente_new, ruc_ant, ruc_new, 
        factura_ant, factura_new, razon_social_ant, razon_social_new) 
        select 
        idventa, $idusu, '$ahora', idcliente, $idcliente, CONCAT(ruchacienda,'-',dv) as ruc, (select ruc from cliente where cliente.idcliente=$idcliente), 
        factura, factura, razon_social, (select razon_social from cliente where cliente.idcliente=$idcliente)
        from ventas
        where
        idventa = $idventa
        and idempresa = $idempresa
        and idcaja = $idcaja    
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        //$ruchacienda=antisqlinyeccion($ruc_array[0],"text");
        //$dv=antisqlinyeccion($ruc_array[1],"text");

        $consulta = "
        update ventas
        set 
        idcliente = $idcliente,
        ruchacienda = $ruchacienda,
        dv = $dv,
        ruc=(select ruc from cliente where cliente.idcliente=$idcliente),
        razon_social = (select razon_social from cliente where cliente.idcliente=$idcliente),
        idsucursal_clie = $idsucursal_clie
        where
        idventa = $idventa
        and idempresa = $idempresa
        and idcaja = $idcaja    
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if ($tipo_venta == 2) {
            $consulta = "
            update cuentas_clientes
            set 
            idcliente = $idcliente,
            idsucursal_clie = $idsucursal_clie
            where
            idventa = $idventa
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }


        header("location: gest_impresiones.php?cvta=".$idventa);
        exit;

    }

}


?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script>
function agrega_cliente(){
        var direccionurl='cliente_agrega_asig.php';
        var parametros = {
              "new" : 'S',
       };
       $.ajax({
                data:  parametros,
                url:   direccionurl,
                type:  'post',
                beforeSend: function () {
                    $('#modal_ventana').modal('show');
                    $("#modal_titulo").html('Alta de Cliente');
                    $("#modal_cuerpo").html('Cargando...');
                },
                success:  function (response) {
                    $('#modal_ventana').modal('show');
                    $("#modal_titulo").html('Alta de Cliente');
                    $("#modal_cuerpo").html(response);
                    if (document.getElementById('ruccliente')){
                        document.getElementById('ruccliente').focus();
                    }
                    $("#idpedido").html(idpedido);
                }
        });    
}
function busca_cliente(){
        var direccionurl='clientesexistentes_fac.php';
        var parametros = {
              "id" : 0
       };
       $.ajax({
                data:  parametros,
                url:   direccionurl,
                type:  'post',
                beforeSend: function () {
                    $("#modal_titulo").html('Busqueda de Clientes');
                    $("#modal_cuerpo").html("Cargando...");
                    $('#modal_ventana').modal('show');
                },
                success:  function (response) {
                        $("#modal_titulo").html('Busqueda de Clientes');
                        $("#modal_cuerpo").html(response);
                        $("#blci").focus();

                }
        });    
}
function selecciona_cliente(valor){

    if(IsJsonString(valor)){
        var obj = jQuery.parseJSON(valor);
        var idcliente = obj.idcliente;
        var idsucursal_clie = obj.idsucursal_clie;
        $("#idcliente").val(idcliente);
        $("#idsucursal_clie").val(idsucursal_clie);
        $('#modal_ventana').modal('hide');
        mostrar_cliente(valor);
    }else{
        alert(valor);    
    }
    
}
function mostrar_cliente(valor){
    if(IsJsonString(valor)){
        var obj = jQuery.parseJSON(valor);
        var idcliente = obj.idcliente;
        var idsucursal_clie = obj.idsucursal_clie;
        $("#idcliente").val(idcliente);
        $("#idsucursal_clie").val(idsucursal_clie);
    }else{
        alert(valor);    
    }
    
        var parametros = {
                "id"   : idcliente,
                "idsucursal_clie"   : idsucursal_clie
                
        };
        $.ajax({
                data:  parametros,
                url:   'cliente_datos.php',
                type:  'post',
                beforeSend: function () {
                     //$("#adicio").html('Cargando datos del cliente...');  
                },
                success:  function (response) {
                    var datos = response;
                    var dato = datos.split("-/-");
                    var ruc_completo = dato[0];
                    var ruc_array = ruc_completo.split("-");
                    var ruc = ruc_array[0];
                    var ruc_dv = ruc_array[1];
                    var razon_social = dato[1];
                    //cargar de nuevo el pop4
                    //alert(response);
                    
                    //$("#razon_social_box").html(razon_social);
                    $("#razon_social").val(razon_social);
                    
        
                }
        });
        
}
function filtrar_rz(){
        var buscar=$("#blci").val();
        var parametros = {
                "bus_rz" : buscar
        };
        $.ajax({
                data:  parametros,
                url:   'cliente_filtrado.php',
                type:  'post',
                beforeSend: function () {
                      $("#clientereca").html('Filtrando...');
                      $("#blci2").val('');
                },
                success:  function (response) {
                        $("#clientereca").html(response);
                }
        });
        
        
}
function filtrar_ruc(){ 
        var buscar=$("#blci2").val();
        var parametros = {
                "bus_ruc" : buscar
        };
        $.ajax({
                data:  parametros,
                url:   'cliente_filtrado.php',
                type:  'post',
                beforeSend: function () {
                      $("#clientereca").html('Filtrando...');
                      $("#blci").val('');
                },
                success:  function (response) {
                        $("#clientereca").html(response);
                }
        });
        
        
}
function filtrar_doc(){ 
        var buscar=$("#blci3").val();
        var parametros = {
                "bus_doc" : buscar
        };
        $.ajax({
                data:  parametros,
                url:   'cliente_filtrado.php',
                type:  'post',
                beforeSend: function () {
                      $("#clientereca").html('Filtrando...');
                      $("#blci").val('');
                      $("#blci2").val('');
                },
                success:  function (response) {
                        $("#clientereca").html(response);
                }
        });
        
        
}
function carga_ruc_h(idpedido){
    var vruc = $("#ruccliente").val();
    var txtbusca="Buscando...";
    var tipocobro=$("#mediopagooc").val();
    if(txtbusca != vruc){
    var parametros = {
            "ruc" : vruc
    };
    $.ajax({
            data:  parametros,
            url:   'ruc_extrae.php',
            type:  'post',
            beforeSend: function () {
                $("#ruccliente").val('Buscando...');
            },
            success:  function (response) {
                if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
                    //alert(obj.error);
                    if(obj.error == ''){
                        var new_ruc = obj.ruc;
                        var new_rz = obj.razon_social;
                        var new_nom = obj.nombre_ruc;
                        var new_ape = obj.apellido_ruc;
                        var idcli = obj.idcliente;
                        $("#ruccliente").val(new_ruc);
                        $("#nombreclie").val(new_nom);
                        $("#apellidos").val(new_ape);
                        $("#rz1").val(new_rz);
                        if(parseInt(idcli)>0){
                            //nclie(tipocobro,idpedido);
                            var obj_json = '{"idcliente":"'+obj.idcliente+'","idsucursal_clie":"'+obj.idsucursal_clie+'"}';
                            selecciona_cliente(obj_json,tipocobro,idpedido);
                        }
                    }else{
                        $("#ruccliente").val(vruc);
                        $("#nombreclie").val('');
                        $("#apellidos").val('');
                    }
                }else{
    
                    alert(response);
            
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                if(jqXHR.status == 404){
                    alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
                }else if(jqXHR.status == 0){
                    alert('Se ha rechazado la conexiÃ³n.');
                }else{
                    alert(jqXHR.status+' '+errorThrown);
                }
            }
        }).fail( function( jqXHR, textStatus, errorThrown ) {
            if (jqXHR.status === 0) {
            
                alert('No conectado: verifique la red.');
            
            } else if (jqXHR.status == 404) {
            
                alert('Pagina no encontrada [404]');
            
            } else if (jqXHR.status == 500) {
            
                alert('Internal Server Error [500].');
            
            } else if (textStatus === 'parsererror') {
            
                alert('Requested JSON parse failed.');
            
            } else if (textStatus === 'timeout') {
            
                alert('Tiempo de espera agotado, time out error.');
            
            } else if (textStatus === 'abort') {
            
                alert('Solicitud ajax abortada.'); // Ajax request aborted.
            
            } else {
            
                alert('Uncaught Error: ' + jqXHR.responseText);
            
            }
        });
    }
}
function nclie(tipocobro,idpedido){
    var p=0;

    if($('#r1').is(':checked')) { p=1; }
    if($('#r2').is(':checked')) { p=2; }
    
    //alert(tipocobro+'-'+idpedido);
    var errores='';
    var nombres=document.getElementById('nombreclie').value;
    var razg="";
    razg=$("#rz1").val();
    var apellidos=document.getElementById('apellidos').value;
    var docu=$("#cedula").val();
    var ruc=document.getElementById('ruccliente').value;
    var direclie=document.getElementById('direccioncliente').value;
    var telfo=document.getElementById('telefonoclie').value;
    var ruc_especial = $("#ruc_especial").val();
    if (p==1){
        if (nombres==''){
            errores=errores+'Debe indicar nombres del cliente. \n';
        }
        if (apellidos==''){
            errores=errores+'Debe indicar apellidos del cliente. \n';
        }
    }
    if (p==2){
        if (razg==''){
            errores=errores+'Debe indicar razon social del cliente juridico. \n';
        }
        
    }
    if (docu==''){
        //errores=errores+'Debe indicar documento del cliente. \n';
    }
    if (ruc==''){
        errores=errores+'Debe indicar documento del cliente o ruc generico. \n';
    }
    if (errores==''){
         var html_old = $("#agrega_clie").html();
        //alert(html_old);
         var parametros = {
                    "n"     : 1,
                    "nom"   : nombres,
                    "ape"   : apellidos,
                    "rz1"    :  razg,
                    "dc"    : docu,
                    "ruc"   : ruc,
                    "dire"  : direclie,
                    "telfo" : telfo,
                     "tipocobro" : tipocobro,
                    "idpedido" : idpedido,
                    "tc"    : p,
                    "ruc_especial" : ruc_especial
            };
           $.ajax({
                    data:  parametros,
                    url:   'cliente_registra.php',
                    type:  'post',
                    beforeSend: function () {
                            $("#agrega_clie").html("<br /><br />Registrando, favor espere...<br /><br />");
                    },
                    success:  function (response) {
                        
                        if(IsJsonString(response)){
                            var obj = jQuery.parseJSON(response);
                            if(obj.valido == 'S'){
                                var obj_json = '{"idcliente":"'+obj.idcliente+'","idsucursal_clie":"'+obj.idsucursal_clie+'"}';
                                selecciona_cliente(obj_json,tipocobro,idpedido);
                            }else{
                                alertar('ATENCION:',obj.errores,'error','Lo entiendo!');
                                $("#agrega_clie").html(html_old);
                                $("#nombreclie").val(nombres);
                                $("#apellidos").val(apellidos);
                                $("#ruccliente").val(ruc);
                                $("#direccioncliente").val(direclie);
                                $("#telefonoclie").val(telfo);
                                $("#cedula").val(docu);
                                $("#rz1").val(razg);
                                if(p == 1){
                                    $("#r1").prop("checked", true); 
                                    $("#r2").prop("checked", false); 
                                }else{
                                    $("#r1").prop("checked", false); 
                                    $("#r2").prop("checked", true); 
                                }
                            }
                        }else{
                            alert(response);
                            $("#agrega_clie").html(html_old);
                            $("#nombreclie").val(nombres);
                            $("#apellidos").val(apellidos);
                            $("#ruccliente").val(ruc);
                            $("#direccioncliente").val(direclie);
                            $("#telefonoclie").val(telfo);    
                            $("#cedula").val(docu);
                            $("#rz1").val(razg);
                            if(p == 1){
                                $("#r1").prop("checked", true); 
                                $("#r2").prop("checked", false); 
                            }else{
                                $("#r1").prop("checked", false); 
                                $("#r2").prop("checked", true); 
                            }
                        }
                        

                        //$("#agrega_clie").html(response);

                    }
            });
    } else {
        alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
        
    }
    
}
function alertar(titulo,error,tipo,boton){
    swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
}
function alertar_redir(titulo,error,tipo,boton,redir){
    swal({
      title: titulo,
      text: error,
      type: tipo,
      /*showCancelButton: true,*/
      confirmButtonClass: "btn-danger",
      confirmButtonText: boton,
     /* cancelButtonText: "No, cancel plx!",*/
      closeOnConfirm: false,
     /* closeOnCancel: false*/
    },
    function(isConfirm) {
      if (isConfirm) {
        //swal("Deleted!", "Your imaginary file has been deleted.", "success");
          document.location.href=redir;
      } else {
        //swal("Cancelled", "Your imaginary file is safe :)", "error");
          document.location.href=redir;
      }
    });
    
}
</script>
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
                    <h2>Editar cliente en Factura</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<p><a href="gest_impresiones.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />


<form id="form1" name="form1" method="post" action="">


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Idventa *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="app" id="app" value="<?php  if (isset($_POST['idventa'])) {
        echo htmlentities($_POST['idventa']);
    } else {
        echo htmlentities($rsvv->fields['idventa']);
    }?>" placeholder="App" class="form-control" required readonly />                    
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12"> Cliente * </label>
    <div class="col-md-9 col-sm-9 col-xs-12 input-group mb-3">
        <input type="text" name="razon_social" id="razon_social" value="<?php echo $rsvv->fields['razon_social']; ?>" placeholder="razon_social" class="form-control". style="width:80%" readonly onMouseUp="busca_cliente();"  />
        <div class="input-group-append">
            <button class="btn btn-outline-secondary" type="button" onMouseUp="busca_cliente();" title="Buscar" data-toggle="tooltip" data-placement="right"  data-original-title="Buscar"><span class="fa fa-search"></span></button>
            <input type="hidden" name="idcliente" id="idcliente" value="" />
            <input type="hidden" name="idsucursal_clie" id="idsucursal_clie" value="" />
        </div>        
    </div>
</div>



<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_impresiones.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

<input name="MM_update" type="hidden" value="form1" />
</form>
<p><br />
</p>
<?php
$consulta = "
select *,
(select usuario from usuarios where ventaslog.registrado_por = usuarios.idusu) as registrado_por
from ventaslog 
where 
idventa = $idventa
order by idventalog desc
limit 100
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>
<br /><hr /><br />
<strong>Ultimos Cambios:</strong>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>

            <th align="center">Idventa</th>
          <th align="center">Registrado por</th>
          <th align="center">Registrado el</th>
          <th align="center">Idcliente ant</th>
          <th align="center">Idcliente new</th>
          <th align="center">Ruc ant</th>
          <th align="center">Ruc new</th>
          <th align="center">Razon social ant</th>
          <th align="center">Razon social new</th>
          <th align="center">Factura ant</th>
          <th align="center">Factura new</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>

            <td align="center"><?php echo antixss($rs->fields['idventa']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
            <td align="center"><?php if ($rs->fields['registrado_el'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
            }  ?></td>
            <td align="center"><?php echo intval($rs->fields['idcliente_ant']); ?></td>
            <td align="center"><?php echo intval($rs->fields['idcliente_new']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['ruc_ant']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['ruc_new']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['razon_social_ant']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['razon_social_new']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['factura_ant']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['factura_new']); ?></td>
        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />



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
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
