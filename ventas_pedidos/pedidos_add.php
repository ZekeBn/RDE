<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");

// Module name this file belongs to
$modulo = "1";
$submodulo = "2";

$dirsup = "S";
require_once("../includes/rsusuario.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Obtenemos el idproducto
    $idProducto = isset($_POST['producto']) ? $_POST['producto'] : null;
    $idProducto = antisqlinyeccion($idProducto, "int");

    // Buscamos el producto por su id
    $cons_prod = "SELECT * FROM productos WHERE idprod = $idProducto";
    $rs_prod = $conexion->Execute($cons_prod) or die(errorpg($conexion, $cons_prod));
}

// Form control regeneration after receiving post and validation
$_SESSION['form_control'] = md5(rand());

// Valores por defecto
$tipoPorDefecto = "PED";
$seriePorDefecto = "A";
$fechaActual = date('Y-m-d');

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once("../includes/head_gen.php"); ?>
</head>

<body class="nav-md">
    <div class="container body">
        <div class="main_container">
            <?php require_once("../includes/menu_gen.php"); ?>
            <?php require_once("../includes/menu_top_gen.php"); ?>

            <div class="right_col" role="main">
                <div class="page-title">
                    <div class="title_left">
                        <h3>Registro de Pedidos</h3>
                    </div>
                </div>
                <div class="clearfix"></div>

                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="x_panel">
                            <div class="x_content">
                                <?php if (trim($errores) != "") { ?>
                                    <div class="alert alert-danger alert-dismissible fade in" role="alert">
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">×</span>
                                        </button>
                                        <strong>Errors:</strong><br /><?php echo $errores; ?>
                                    </div>
                                <?php } ?>

                                <!-- Formulario de pedido cliente  -->
                                <div class="container">
                                    <form id="formRegistro" name="formRegistro" method="POST" action="">
                                        <!-- <input type="file" id="ediFile" name="ediFile">Carga Edi</input>
                                            <div class="form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Nro Pedido Edi</label>
                                                <div class="col-md-6 col-sm-6 col-xs-12">
                                                    <input type="text" name="nro_pedido" class="form-control"
                                                    value="<?php //echo isset($_POST['nro_pedido']) ? htmlentities($_POST['nro_pedido']) : ''; 
                                                            ?>">
                                                </div>
                                            </div> -->

                                        <div class="row">
                                            <!-- Datos Pedido -->
                                            <div class="col-md-5">
                                                <div class="row">
                                                    <div class="col-md-12 col-sm-6 form-group">
                                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal</label>
                                                        <div class="col-md-8 col-sm-5 col-xs-9">
                                                            <select name="sucursal" id="sucursal" class="form-control">
                                                                <option value="">Seleccione una Sucursal</option>
                                                                <?php
                                                                $consulta = "SELECT idsucu, nombre FROM sucursales;";
                                                                $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                                                                while (!$rs->EOF) {
                                                                    $row = $rs->fields;
                                                                    $selected = (isset($_REQUEST['sucursal']) && $_REQUEST['sucursal'] == $row['idsucu']) ? 'selected' : '';
                                                                    echo "<option value=\"{$row['idsucu']}\" $selected>{$row['nombre']}</option>";
                                                                    $rs->MoveNext();
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                               <div class="row">
                                                    <div class="col-md-12 col-sm-6 form-group">
                                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo / Serie</label>
                                                        <div class="col-md-3 col-sm-5 col-xs-9">
                                                            <select name="tipo" id="tipo" class="form-control" disabled>
                                                                <option value="">Seleccione un Tipo</option>
                                                                <option value="PED" <?php echo $tipoPorDefecto === "PED" ? 'selected' : ''; ?>>PED</option>
                                                                <!-- Agrega más opciones según sea necesario -->
                                                            </select>
                                                        </div>
                                                        <div class="col-md-5 col-sm-5 col-xs-9">
                                                            <input type="text" name="nropedido" class="form-control" value="A" disabled>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-12 col-sm-6 form-group">
                                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Número</label>
                                                        <div class="col-md-8 col-sm-5 col-xs-9">
                                                            <input type="text" name="nropedido" class="form-control"
                                                                value="<?php echo isset($_POST['nropedido']) ? htmlentities($_POST['nropedido']) : ''; ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-12 col-sm-6 form-group">
                                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha</label>
                                                        <div class="col-md-8 col-sm-5 col-xs-9">
                                                            <input type="date" name="fechapedido" id="fechapedido" class="form-control" 
                                                                value="<?php echo isset($_REQUEST['fechapedido']) ? $_REQUEST['fechapedido'] : $fechaActual; ?>" />
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-12 col-sm-6 form-group">
                                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Sector</label>
                                                        <div class="col-md-2 col-sm-5 col-xs-9">
                                                            <input type="text" name="idsector" id="idsector" class="form-control" value="1" >
                                                        </div>
                                                        <div class="col-md-6 col-sm-5 col-xs-9">
                                                            <select name="sector" id="sector" class="form-control">
                                                                <option value="">Seleccione un Sector</option>
                                                                <!-- Populate options as needed -->
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-12 col-sm-6 form-group">
                                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Referencia</label>
                                                        <div class="col-md-4 col-sm-5 col-xs-9">
                                                            <select name="referencia" id="referencia" class="form-control">
                                                                <option value="">Seleccione una Referencia</option>
                                                                <!-- Populate options as needed -->
                                                                 
                                                            </select>
                                                        </div>
                                                        <div class="col-md-2 col-sm-5 col-xs-9">
                                                            <input type="text" name="ref2" id="ref2" class="form-control">
                                                            </select>
                                                        </div>
                                                        <div class="col-md-2 col-sm-5 col-xs-9">
                                                            <input type="text" name="ref3" id="ref3" class="form-control">
                                                        </div>     
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-12 col-sm-6 form-group">
                                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Venta</label>
                                                        <div class="col-md-8 col-sm-5 col-xs-9">
                                                            <select name="tipoventa" id="tipoventa" class="form-control">
                                                                <option value="">Seleccione un Tipo Venta</option>
                                                                <!-- Populate options as needed -->
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-12 col-sm-6 form-group">
                                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Entrega</label>
                                                        <div class="col-md-8 col-sm-5 col-xs-9">
                                                            <select name="entrega" id="entrega" class="form-control">
                                                                <option value="">Seleccione un Tipo Entrega</option>
                                                                <!-- Populate options as needed -->
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-12 col-sm-6 form-group">
                                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Entrega</label>
                                                        <div class="col-md-8 col-sm-5 col-xs-9">
                                                            <input type="date" name="fechaentrega" id="fechaentrega" class="form-control"
                                                                value="<?php echo $_REQUEST['fechaentrega']; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Datos Cliente -->
                                            <div class="col-md-6">
                                                <div class="row">
                                                    <div class="col-md-12 col-sm-6 form-group">
                                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Cliente</label>
                                                        <div class="col-md-2 col-sm-5 col-xs-9">
                                                            <input type="text" name="idcliente" id="idcliente" class="form-control"
                                                                value="<?php echo $_REQUEST['idcliente']; ?>" />
                                                        </div>
                                                        <div class="col-md-6 col-sm-5 col-xs-9">
                                                            <select name="cliente" id="cliente" class="form-control">
                                                                <option value="">Seleccione un Cliente</option>
                                                                <?php
                                                                $consulta = "SELECT idcliente, CONCAT(nombre, ' ', COALESCE(apellido, '')) as nom FROM cliente;";
                                                                $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                                                                while (!$rs->EOF) {
                                                                    $row = $rs->fields;
                                                                    $selected = (isset($_REQUEST['cliente']) && $_REQUEST['cliente'] == $row['idcliente']) ? 'selected' : '';
                                                                    echo "<option value=\"{$row['idcliente']}\" $selected>{$row['nom']}</option>";
                                                                    $rs->MoveNext();
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-12 col-sm-6 form-group">
                                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Beneficiario</label>
                                                        <div class="col-md-2 col-sm-5 col-xs-9">
                                                            <input type="text" name="idbeneficiario" id="idbeneficiario" class="form-control"
                                                                value="<?php echo $_REQUEST['idbeneficiario']; ?>" />
                                                        </div>
                                                        <div class="col-md-6 col-sm-5 col-xs-9">
                                                            <select name="beneficiario" id="beneficiario" class="form-control">
                                                                <option value="">Seleccione un Beneficiario</option>
                                                                <?php
                                                                $consulta = "SELECT idcliente, CONCAT(nombre, ' ', COALESCE(apellido, '')) as nom FROM cliente;";
                                                                $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                                                                while (!$rs->EOF) {
                                                                    $row = $rs->fields;
                                                                    $selected = (isset($_REQUEST['cliente']) && $_REQUEST['cliente'] == $row['idcliente']) ? 'selected' : '';
                                                                    echo "<option value=\"{$row['idcliente']}\" $selected>{$row['nom']}</option>";
                                                                    $rs->MoveNext();
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-12 col-sm-6 form-group">
                                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Direccion</label>
                                                        <div class="col-md-8 col-sm-5 col-xs-9">
                                                            <input type="text" name="direccion" class="form-control"
                                                                value="<?php echo isset($_POST['direccion']) ? htmlentities($_POST['direccion']) : ''; ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-12 col-sm-6 form-group">
                                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Zona</label>
                                                        <div class="col-md-8 col-sm-5 col-xs-9">
                                                            <input type="text" name="zona" class="form-control"
                                                                value="<?php echo isset($_POST['zona']) ? htmlentities($_POST['zona']) : ''; ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-12 col-sm-6 form-group">
                                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Vendedor</label>
                                                        <div class="col-md-8 col-sm-5 col-xs-9">
                                                            <input type="text" name="vendedor" class="form-control"
                                                                value="<?php echo isset($_POST['vendedor']) ? htmlentities($_POST['vendedor']) : ''; ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-12 col-sm-6 form-group">
                                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Plazo Ped</label>
                                                        <div class="col-md-8 col-sm-5 col-xs-9">
                                                            <select name="plazo" id="plazo" class="form-control">
                                                                <option value="">Seleccione un Plazo</option>
                                                                <!-- Populate options as needed -->
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12 col-sm-6 form-group">
                                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Lista Precio</label>
                                                        <div class="col-md-2 col-sm-5 col-xs-9">
                                                            <input type="text" name="idsector" id="idsector" class="form-control" value="1" >
                                                        </div>
                                                        <div class="col-md-6 col-sm-5 col-xs-9">
                                                            <select name="sector" id="sector" class="form-control">
                                                                <option value="">Seleccione una Lista de Precio</option>
                                                                <!-- Populate options as needed -->
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12 col-sm-6 form-group">
                                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Moneda</label>
                                                        <div class="col-md-8 col-sm-5 col-xs-9">
                                                            <select name="moneda" id="moneda" class="form-control">
                                                                <option value="">Seleccione una Moneda</option>
                                                                <!-- Populate options as needed -->
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12 col-sm-6 form-group">
                                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Cotización</label>
                                                        <div class="col-md-8 col-sm-5 col-xs-9">
                                                            <select name="cotizacion" id="cotizacion" class="form-control">
                                                                <option value="">Seleccione una Cotización</option>
                                                                <!-- Populate options as needed -->
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12 col-sm-6 form-group">
                                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Número Orden</label>
                                                        <div class="col-md-8 col-sm-5 col-xs-9">
                                                        <input type="number" name="nroorden" class="form-control"
                                                        value="<?php echo isset($_POST['nroorden']) ? htmlentities($_POST['nroorden']) : ''; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12 col-sm-6 form-group">
                                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Radio</label>
                                                        <div class="col-md-8 col-sm-5 col-xs-9">
                                                        <select name="radio" id="radio" class="form-control">
                                                                <!-- Populate options as needed -->
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <input type="hidden" name="MM_insert" value="form1" />
                                        <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
                                    </form>
                                </div>

                                <hr /><br />

                                <!-- Articulos / Productos-->
                                   
                                <form id="formProductos" name="formProductos" method="POST" action="">
    <div class="col-md-12 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Producto</label>
        <div class="col-md-3 col-sm-5 col-xs-9">
            <input type="text" name="barcode" id="barcode" class="form-control"
                value="<?php echo $_REQUEST['barcode'] ? antixss($_REQUEST['barcode']) : ''; ?>"
                placeholder="Ingrese el código de barras" />
        </div>
        <div class="col-md-5 col-sm-5 col-xs-9">
            <select name="producto" id="producto" class="form-control">
                <option value="">Seleccione un Producto</option>
                <?php
                $consulta = "SELECT idprod, barcode, descripcion FROM productos;";
                $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                while (!$rs->EOF) {
                    $row = $rs->fields;
                    $selected = (isset($_REQUEST['producto']) && $_REQUEST['producto'] == $row['idprod']) ? 'selected' : '';
                    echo "<option value=\"{$row['idprod']}\" data-barcode=\"{$row['barcode']}\" $selected>{$row['descripcion']}</option>";
                    $rs->MoveNext();
                }
                ?>
            </select>
        </div>
        <div class="col-md-1 col-sm-2">
            <button id="agregarProductoBtn" type="button" class="btn btn-primary">Agregar</button>
        </div>
    </div>
</form>

<!-- JavaScript -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const barcodeInput = document.getElementById("barcode");
        const productoSelect = document.getElementById("producto");
        const agregarProductoBtn = document.getElementById("agregarProductoBtn");

        // Capturar el evento "Enter" en el campo de código de barras
        barcodeInput.addEventListener("keypress", function (event) {
            if (event.key === "Enter") {
                event.preventDefault(); // Evita que el formulario se envíe
                const barcode = barcodeInput.value.trim();

                if (barcode) {
                    // Busca el producto con el código de barras ingresado
                    const options = productoSelect.options;
                    let found = false;
                    for (let i = 0; i < options.length; i++) {
                        if (options[i].dataset.barcode === barcode) {
                            options[i].selected = true; // Selecciona el producto
                            found = true;
                            break;
                        }
                    }

                    if (!found) {
                        alert("Producto no encontrado para el código de barras: " + barcode);
                    } else {
                        // Llama al botón "Agregar" o realiza cualquier acción adicional
                        agregarProductoBtn.click();
                    }
                } else {
                    alert("Por favor, ingrese un código de barras.");
                }
            }
        });

        // Acción del botón "Agregar"
        agregarProductoBtn.addEventListener("click", function () {
            const selectedProducto = productoSelect.value;
            if (selectedProducto) {
                alert("Producto agregado: " + selectedProducto);
                // Aquí puedes cargar la grilla o realizar cualquier acción adicional
            } else {
                alert("Seleccione un producto.");
            }
        });
    });
</script>

                                    <div class="table-responsive">
                                        <table class="table table-striped jambo_table bulk_action">
                                            <thead>
                                                <tr>
                                                    <th>IdProducto</th>
                                                    <th>Descripcion</th>
                                                    <th>Caja</th>
                                                    <th>Unidad</th>
                                                    <th>UxC</th>
                                                    <th>Cant. Unidad</th>
                                                    <th>Precio Lista</th>
                                                    <th>Precio Unitario</th>
                                                    <th>Desc%</th>
                                                    <th>Iva%</th>
                                                    <th>I.V.A</th>
                                                    <th>Total c/IVA</th>
                                                    <th>Cant. Max Pedido</th>
                                                </tr>
                                            </thead>
                                            <tbody id="productos">
                                                
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 col-sm-12 col-xs-12" style="display:flex;justify-content:center;">
                                <button id="finalizar" type="button" class="btn btn-success"><span class="fa fa-check-square-o"></span> Finalizar Pedido</button>
                                <button id="cancelar" type="button" class="btn btn-primary" onclick="window.location='pedidos.php'"><span class="fa fa-ban"></span> Cancelar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> 
        <?php require_once("../includes/pie_gen.php"); ?>
    </div>
    <?php require_once("../includes/footer_gen.php"); ?>
    <!-- Scripts -->
    <script src="../js/ventas_pedidos/pedidos_add_test.js"></script>
</body>

</html>