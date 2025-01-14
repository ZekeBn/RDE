 <div class="btn-group">

<button class="btn btn-sm btn-<?php if ($refemenu == 'mix') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='informe_venta_graf.php?desde=<?php echo $desde; ?>&hasta=<?php echo $hasta; ?>'"><span class="fa fa-search"></span>&nbsp;Mix de Ventas</button>
<button class="btn btn-sm btn-<?php if ($refemenu == 'mixnoven') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='informe_venta_noventa.php?desde=<?php echo $desde; ?>&hasta=<?php echo $hasta; ?>'"><span class="fa fa-search"></span>&nbsp;Mix Sin Mov</button>



<button class="btn btn-sm btn-<?php if ($refemenu == 'evo') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='informe_venta_graf_evo.php?desde=<?php echo $desde; ?>&hasta=<?php echo $hasta; ?>'"><span class="fa fa-search"></span>&nbsp;Evoluci&oacute;n</button>
<button class="btn btn-sm btn-<?php if ($refemenu == 'hora') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='informe_venta_graf_hora.php?desde=<?php echo $desde; ?>&hasta=<?php echo $hasta; ?>'"><span class="fa fa-search"></span>&nbsp;Hora/Dia/Mes</button>

<button class="btn btn-sm btn-<?php if ($refemenu == 'canal') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='informe_venta_graf_canal.php?desde=<?php echo $desde; ?>&hasta=<?php echo $hasta; ?>'"><span class="fa fa-search"></span>&nbsp;Canal</button>





<button class="btn btn-sm btn-<?php if ($refemenu == 'cond') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='informe_venta_graf_cond.php?desde=<?php echo $desde; ?>&hasta=<?php echo $hasta; ?>'"><span class="fa fa-search"></span>&nbsp;Condici&oacute;n</button>
<button class="btn btn-sm btn-<?php if ($refemenu == 'suc') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='informe_venta_graf_margen_sucursal.php?desde=<?php echo $desde; ?>&hasta=<?php echo $hasta; ?>'"><span class="fa fa-search"></span>&nbsp;Sucursal - Margen</button>





<button class="btn btn-sm btn-<?php if ($refemenu == 'prod') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='informe_venta_graf_margen.php?desde=<?php echo $desde; ?>&hasta=<?php echo $hasta; ?>'"><span class="fa fa-search"></span>&nbsp;Productos - Margen</button>
<button class="btn btn-sm btn-<?php if ($refemenu == 'prodvende') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='informe_venta_graf_prodvende.php?desde=<?php echo $desde; ?>&hasta=<?php echo $hasta; ?>'"><span class="fa fa-search"></span>&nbsp;Productos X Vendedor</button>
<button class="btn btn-sm btn-<?php if ($refemenu == 'prodhora') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='inf_productos_hora.php?desde=<?php echo $desde; ?>&hasta=<?php echo $hasta; ?>'"><span class="fa fa-search"></span>&nbsp;Productos X Hora</button>
<button class="btn btn-sm btn-<?php if ($refemenu == 'proddia') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='inf_productos_dia.php?desde=<?php echo $desde; ?>&hasta=<?php echo $hasta; ?>'"><span class="fa fa-search"></span>&nbsp;Productos X Dia</button>
    
    
<button class="btn btn-sm btn-<?php if ($refemenu == 'clihis') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='informe_venta_graf_cliente_his.php?desde=<?php echo $desde; ?>&hasta=<?php echo $hasta; ?>'"><span class="fa fa-search"></span>&nbsp;Venta x Cliente</button>

<button class="btn btn-sm btn-<?php if ($refemenu == 'vend') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='informe_venta_graf_vend.php?desde=<?php echo $desde; ?>&hasta=<?php echo $hasta; ?>'"><span class="fa fa-search"></span>&nbsp;Venta x Vendedor</button>
<button class="btn btn-sm btn-<?php if ($refemenu == 'prodop') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='informe_venta_graf_prod_oper.php?desde=<?php echo $desde; ?>&hasta=<?php echo $hasta; ?>'"><span class="fa fa-search"></span>&nbsp;Venta x Operador</button>

<button class="btn btn-sm btn-<?php if ($refemenu == 'comp') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='informe_venta_graf_comp.php?desde=<?php echo $desde; ?>&hasta=<?php echo $hasta; ?>'"><span class="fa fa-search"></span>&nbsp;Comprobantes</button>





<button class="btn btn-sm btn-<?php if ($refemenu == 'bus') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='informe_venta_graf_busq.php'"><span class="fa fa-search"></span>&nbsp;B&uacute;squeda </button>
<button class="btn btn-sm btn-<?php if ($refemenu == 'desc') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='informe_venta_graf_desc.php'"><span class="fa fa-download"></span>&nbsp;Descargas </button>


<br /> 
</div>
