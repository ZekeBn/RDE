 <table width="98%" border="1" class="categoria" bgcolor="#FFFFFF">
  <tbody>
    <tr>
      <td width="33%" align="center" <?php if ($totalex > 1) { ?>onMouseUp="document.location.href='editareceta.php?id=<?php echo $idprod; ?>'"<?php } else { ?>onMouseUp="document.location.href='gest_ventas_resto_caja.php'"<?php } ?>><strong><img src="tablet/gfx/iconos/atras.png" width="50"  alt="Favorito"/><br />
        Volver</strong></td>
      <td width="33%" align="center" onClick="document.location.href='editareceta.php?idvt=<?php echo intval($idvt); ?>&ac=a'" <?php if ($ac != 'e') { ?>bgcolor="#F8FFCC"<?php } ?>><strong><img src="tablet/gfx/iconos/agregar_ing.fw.png" width="70"  alt="Favorito"/></strong></td>
      <td width="33%" align="center"  onClick="document.location.href='editareceta_sacados.php?idvt=<?php echo intval($idvt); ?>&ac=e'" <?php if ($ac == 'e') { ?>bgcolor="#F8FFCC"<?php } ?>><strong><img src="tablet/gfx/iconos/eliminar_ing.fw.png" width="70"  alt="Favorito"/></strong></td>
    </tr>
  </tbody>
</table>
