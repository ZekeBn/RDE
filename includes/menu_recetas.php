 <div class="btn-group">

<button class="btn btn-sm btn-<?php if ($refemenu == 'vent') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='recetas.php?desde=<?php echo $desde; ?>&hasta=<?php echo $hasta; ?>'"><span class="fa fa-search"></span> Receta Ventas</button>


<button class="btn btn-sm btn-<?php if ($refemenu == 'prod') { ?>primary<?php } else { ?>default<?php } ?>" type="button" 
onmouseup="document.location.href='recetas_produccion.php?desde=<?php echo $desde; ?>&hasta=<?php echo $hasta; ?>'"><span class="fa fa-search"></span> Receta Produccion</button>

<br /> 
</div>
