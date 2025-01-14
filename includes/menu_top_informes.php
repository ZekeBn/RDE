 <?php
if (isset($_GET)) {
    $g_add = "?f=s";
}
if (isset($_GET['desde']) && isset($_GET['hasta'])) {
    $g_add .= "&desde=".date("Y-m-d", strtotime($_GET['desde']));
    $g_add .= "&hasta=".date("Y-m-d", strtotime($_GET['hasta']));
}
if (isset($_GET['suc'])) {
    $g_add .= "&suc=".htmlentities($_GET['suc']);
}


?><table width="980" border="1" class="tablaconborde">
          <tbody>
            <tr height="30">
              <td align="center"><a href="informe_venta.php<?php echo $g_add; ?>">Mix de Venta</a></td>
              <td align="center"><a href="informe_venta_canal.php<?php echo $g_add; ?>"> Canales</a></td>
              <td align="center"><a href="informe_venta_categoria.php<?php echo $g_add; ?>">Categorias</a></td>
              <td align="center"><a href="informe_venta_suc.php<?php echo $g_add; ?>">Sucursal</a></td>
              <td align="center"><a href="informe_venta_hora.php<?php echo $g_add; ?>">Hora, Dia, Mes</a></td>
              <!--<td align="center">Ventas por Zonas Delivery</td>-->
              <td align="center"><a href="informe_venta_evol.php<?php echo $g_add; ?>">Evolucion</a></td>
              <td align="center"><a href="informe_venta_ticket.php<?php echo $g_add; ?>">Ticket Promedio</a></td>
              <td align="center"><a href="informe_venta_fp.php<?php echo $g_add; ?>">Forma Pago</a></td>
              <td align="center"><a href="informe_ventas_proveedor.php">Proveedor</a></td>
              <td align="center"><a href="informe_venta_comp.php<?php echo $g_add; ?>">Comprobante</a></td>
              <td align="center"><a href="informe_venta_margen.php<?php echo $g_add; ?>">Margen</a></td>


            </tr>
          </tbody>
        </table>
