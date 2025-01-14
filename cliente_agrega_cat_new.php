<?php
/*--------------------------------------------
01/08/2022

-------------------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "29";
$submodulo = "347";
require_once("includes/rsusuario.php");


$idcampo = htmlentities($_POST['idcampo']);
$tipo = htmlentities($_POST['idcampo']);
//print_r($_POST);
//preferencias catering
$buscar = "Select * from catering_preferencias ";
$rsprefcat = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$obliga_email = trim($rsprefcat->fields['obliga_email_registro']);

// preferencias caja
$consulta = "
SELECT 
valida_ruc, permite_ruc_duplicado
FROM preferencias_caja 
WHERE  
idempresa = 1
";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$valida_ruc = trim($rsprefcaj->fields['valida_ruc']);
$permite_ruc_duplicado = trim($rsprefcaj->fields['permite_ruc_duplicado']);


// cliente generico
$consulta = "
select ruc, razon_social
from cliente 
where 
borrable = 'N'
order by idcliente asc
limit 1
";
$rscligen = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$ruc_pred = $rscligen->fields['ruc'];
$razon_social_pred = $rscligen->fields['razon_social'];

if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

    $valido = "S";
    $errores = "";
    $parametros_array = [
        'idusu' => $idusu,
        'idvendedor' => '',
        'sexo' => '',
        'nombre' => $_POST['nombre'],
        'apellido' => $_POST['apellido'],
        'nombre_corto' => $_POST['nombre_corto'],
        'idtipdoc' => $_POST['idtipdoc'],
        'documento' => $_POST['documento'],
        'ruc' => $_POST['ruc'],
        'telefono' => $_POST['telefono'],
        'celular' => $_POST['celular'],
        'email' => $_POST['email'],
        'direccion' => $_POST['direccion'],
        'comentario' => $_POST['comentario'],
        'fechanac' => $_POST['fechanac'],
        'idclientetipo' => $_POST['idclientetipo'],
        'razon_social' => $_POST['razon_social'],
        'fantasia' => $_POST['fantasia'],
        'ruc_especial' => $_POST['ruc_especial'],
        'idsucursal' => $idsucursal,
    ];
    $res = validar_cliente($parametros_array);
    if ($res['valido'] != 'S') {
        $valido = $res['valido'];
        $errores = nl2br($res['errores']);
        $respuesta = json_encode($res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        echo $respuesta;
        exit;
    }
    // si todo es correcto inserta
    if ($valido == "S") {

        $res = registrar_cliente($parametros_array);
        $idcliente = $res['idcliente'];

        //si es tipo facturador, insertamos en el rol del cliente
        if ($tipo == 2) {
            $insertar = "Insert into cliente_rol 
			(idcliente,tipo,observaciones,estado,registrado_el,registrado_por)
			values 
			($idcliente,'F',NULL,1,'$ahora',$idusu)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

        }

        $consulta = "
		select * 
		from cliente 
		inner join sucursal_cliente on sucursal_cliente.idcliente = cliente.idcliente
		where 
		cliente.idcliente = $idcliente
		";
        $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $res = [
            'ruc' => $rscli->fields['ruc'],
            'razon_social' => $rscli->fields['razon_social'],
            'nombre_ruc' => $rscli->fields['nombre'],
            'apellido_ruc' => $rscli->fields['apellido'],
            'idcliente' => $rscli->fields['idcliente'],
            'idsucursal_clie' => $rscli->fields['idsucursal_clie'],
            'telefono' => $rscli->fields['telefono'],
            'email' => $rscli->fields['email'],
            'valido' => 'S'
        ];

        $respuesta = json_encode($res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        echo $respuesta;
        exit;

    }

}



?>
 <div  id="agrega_clie"  style="min-height: 450px; width: 100%;">


<div class="col-md-6 col-sm-6 form-group">

    <div class="btn-group" data-toggle="buttons">

    <input type="radio" name="fisi" id="r1" value="1" checked="checked"   class="flat" onClick="cambia(this.value)"> &nbsp; Persona &nbsp;

    <input type="radio" name="fisi" id="r2" value="2"  class="flat" onClick="cambia(this.value)" > Empresa

    </div>

</div>

<div class="clearfix"></div>
<div class="col-md-6 col-sm-6 form-group" id="cedula_box">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Cedula *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
		<input type="text" name="cedula" id="cedulaclie" value="" placeholder="Cedula" class="form-control"   />    
	</div>
</div>
<div class="col-md-6 col-sm-6 form-group" id="nombreclie_box">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Cliente Pedido *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="clientepedidol" id="clientepedidol" value="" placeholder="Cliente del Pedido" class="form-control"  />                    
	</div>
</div>
<div class="col-md-6 col-sm-6 form-group" id="nombreclie_box" style="display:none;">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombres *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombreclie" id="nombreclie" value="" placeholder="Nombres" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="apellidos_box" style="display:none;>
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Apellidos *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="apellidos" id="apellidosclie" value="" placeholder="Apellidos" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12"><a href="javascript:void(0); " onclick="carga_ruc_set();"><span class="fa fa-search"></span>&nbsp; RUC * </a></label>
	<div class="col-md-9 col-sm-9 col-xs-12 input-group mb-3">
        <input type="text" name="ruccliente" id="ruccliente" value="" placeholder="RUC" class="form-control" />
     
	</div>
</div>
<div class="col-md-6 col-sm-6 form-group" style="" id="rz1_box">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Razon Social *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="rz1" id="rz1" value="" placeholder="Razon Social" class="form-control"  />                    
	</div>
</div>
<div class="clearfix"></div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12" id="ph_box">Celular *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="telefono" id="telefono" value="" placeholder="telefono" class="form-control" required="required" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12" id="ph_box">Tel&eacute;fono *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="telefono" id="telefono" value="" placeholder="telefono" class="form-control" required="required" />                    
	</div>
</div>

<?php if ($obliga_email == 'S') {
    $reqemail = "required='required' ";
}
?>
<div class="col-md-6 col-sm-6 form-group" id="cedula_box">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Email <?php if ($obliga_email == 'S') {
	    echo " * ";
	} ?></label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="email" name="email" id="email" value="" placeholder="Email" class="form-control" <?php echo $reqemail; ?>  />                    
	</div>
</div>
<div class="clearfix"></div>
<div class="form-group">
    <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
	<button type="submit"  name="reg" class="btn btn-success" onClick="registrar_cliente('<?php echo $idcampo; ?>');"><span class="fa fa-check-square-o"></span> Registrar Cliente</button>
    </div>
</div>
    




