 <!DOCTYPE html>
<html class="html" lang="es-ES">
 <head>
  <meta http-equiv="Content-type" content="text/html;charset=UTF-8"/>
  <title>Carrito de compras - Inicio</title>
    <!--custom head HTML-->
    <script type="text/javascript" src="js/jquery-1.9.1.js"></script>
    <link rel="stylesheet" type="text/css" href="css/bootstrap.css">
    <script type="text/javascript" src="js/bootstrap.js"></script>
    <script type="text/javascript" src="js/simpleCart.js"></script>
<style>    
.simpleCart_items table{
    border-collapse:collapse;
    border:1px solid #000000;
    width:98%;
}
.simpleCart_items th{
    border:1px solid #000000;
}
.simpleCart_items td{
    border:1px solid #000000;
}
 </style>


<script type="text/javascript">//<![CDATA[
$(window).load(function(){

simpleCart.currency({ 
    code: "GS" ,
    name: "Guaranies" ,
    symbol: "Gs. ",
    delimiter: "." , 
    decimal: "," , 
    after: false ,
    accuracy: 0
});
    simpleCart({
  checkout: {
        type: "SendForm" , 
        url: "enviar.php" ,
        method: "POST"   },
  cartStyle: 'table',
                        cartColumns: [
                            {
                                attr: "name",
                                label: "Producto"
                            }, 
                            {
                                attr: "price",
                                label: "Precio",
                                view: 'currency'
                            }, 
                            {     attr: "quantity" ,
                                label: "Cantidad" 
                            } ,
                            {     attr: "total" ,
                                label: "SubTotal",
                                view: 'currency'
                            } ,
                            {
                                view: "remove",
                                text: "Eliminar",
                                label: false
                            }
                        ]
  });

$(".btn").on('click', function(){
  
  checkCart()

});
// simpleCart.grandTotal()
//simpleCart.total();
Cart.currency( "PEN" );
function checkCart(){
  var sum = simpleCart.quantity();
  $("#dLabel").html(' Cart '+ sum +' Productos <span class="caret"></span>')

    if (simpleCart.items().length == 0) {
     $("#dLabel").html(' Cart Empty<span class="caret"></span>');

    }else{
        $("#dLabel").html(' Cart '+ sum +' Productos <span class="caret"></span>')
    }
    
  
}




});//]]> 

</script>



 </head>
 <body>
 


<br />
<!-- Contenido del Carrito -->
<div class="simpleCart_items">
<table>
    <tr>
        <th class="item-name" width="200px">Producto</th>
        <th class="item-quantity">Cant</th>
        <th class="item-price">Precio</th>
        <th class="item-total">Subtotal</th>
        <th class="item-remove">Eliminar</th>
    </tr>
</table>
</div>
<br />


 <!-- Vaciar Carrito -->         
<a href="#" class="simpleCart_empty btn btn-lg btn-warning">LIMPIAR</a>

<!-- Registrar Venta  -->
<a href="#" class="simpleCart_checkout btn btn-lg btn-warning">PROCESAR</a>


<!-- Totales del Carrito -->
<br /><hr /><br />
<span class="simpleCart_quantity">0</span> Productos - <span class="simpleCart_total">$0.00</span>
<br /><hr /><br />


<!-- Producto A -->
<div class="simpleCart_shelfItem">
   <h2 class="item_name">Producto A</h2>
   <img src="item-image.jpg" alt="Imagen del Producto">
   <label>Cantidad: <input class="item_Quantity form-control" type="text" value="1"></label>
   Gs. <span class="item_price">2000</span>
   <!--<a href="javascript:;" class="item_add">Agregar al Carrito</a>-->
   <span class='btn btn-danger btn-md item_add'>Agregar</span>
</div>       
    
    
 <!-- Producto B -->   
<div class="simpleCart_shelfItem">
   <h2 class="item_name">Producto B</h2>
   <img src="item-image.jpg" alt="Imagen del Producto">
   <label>Cantidad: <input class="item_Quantity form-control" type="text" value="1"></label>
   Gs. <span class="item_price">5000</span>
   <!--<a href="javascript:;" class="item_add">Agregar al Carrito</a>-->
   <span class='btn btn-danger btn-md item_add'>Agregar</span>
</div>   

<!-- Producto C -->
<div class="simpleCart_shelfItem">
   <h2 class="item_name">Producto C</h2>
   <img src="item-image.jpg" alt="Imagen del Producto">
   <label>Cantidad: <input class="item_Quantity form-control" type="text" value="1"></label>
   Gs. <span class="item_price">1500</span>
   <!--<a href="javascript:;" class="item_add">Agregar al Carrito</a>-->
   <span class='btn btn-danger btn-md item_add'>Agregar</span>
</div>  
 
   </body>
</html>
