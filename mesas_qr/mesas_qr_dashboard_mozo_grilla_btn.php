<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("./mesas_qr_funciones.php");
require_once("./mesas_preferencias.php");

// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";





// Ejemplo de uso
$id_mesa = intval($_GET['id_mesa']);
if ($id_mesa == 0) {
    $id_mesa = intval($_POST['id_mesa']);
}
if ($id_mesa == 0) {
    header("Location: ./mesas_qr2.php");
}

$parametros_array = [
  "id_mesa" => $id_mesa
];
$rs_mesas_atc = buscar_mesa_atc($parametros_array);
$idatc = intval($rs_mesas_atc['idatc']);
if ($idatc == 0) {
    header("Location: ./mesas_qr2.php");
}
$parametros_array = [
  "id_mesa" => $id_mesa
];

$rs_mesas_respuesta = verificar_pedidos_pendientes($parametros_array);
$idpedido_mesero = $rs_mesas_respuesta['idpedido_mesero'];
$idpedido_cuenta = $rs_mesas_respuesta['idpedido_cuenta'];
$idpedido_ver = 0;
$logo = "./logo.png";

$logo = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAOEAAADhCAMAAAAJbSJIAAAAkFBMVEUdNzHBhRYaNjEVNTEAMTIJMjIPMzLEhxayfR2KaCY9QzJYTyuOaiipeCEtPTKRaybIiRPOjBG9gxkjOTK5gRtJSC+VbSGjdSJFRjA2QDCGZievex8mOjFPSy9gUy0vPTFvWisALjOeciF1XSp8YSloVy1sWS1kVS2BZCpbUCxUTS97YCtBRTE7QjOYbyAALDN2VOCZAAAN+ElEQVR4nO2cCXeivtfHIStagRj2TRAXEBme9//unptYrV2mdeZnp+3/5HPmjIgI+XqTu4RQyzIYDAaDwWAwGAwGg8FgMBgMBoPBYDAYDAaDwWAwGAwGg8FgMBgMBoPBYDAYDAaDwWAwGAwGg8FgMPwtCGHMKMUKeGUYI/TVbboXCDPGaDk+NHkct5p4OjbVLwv2M/zDdSJMqdf1sbtUSKdINI5jq/d2suirgPxclYjhcbcIbS4LN99VY1aWAdKUZZ1FQ+O7js3toj9k9AeKRAxlR1dyuz0+jIEagFrb44dIDUtMmZd2PagMk1WN8dc2+A/BBG0SsSzyylKdEFmg57mVlEL9PyMeGHppTytCf4whMav8pXC3Hj0PMVbtd+O1kXC22w3s9CG4IpIunKXs5+RHGBLjwbWlHwXsYhLkL6J5vntqPj7MxrRvg8sRmHlDYtt+yr69HcF+rZDbjFz1SpQ60EmZ/3QUnSyMaLK6MhlYsloI28++t0ZEx2Qpj+g8ok7uA3c5VXYLQIY6CFu4gQ2865l1ev94NKl9IfqAfUXTbwOXvZDHc/cEB7MblClR6qpGlxYeD9hCUYVhWxnyoJ1N2gRnkYhlPg/339aMrHL4ND83D411JWTqqTztsT+yYsYsvA/1+MORo1xsOXdEh6LzORAbChHX39LjINYI2ZHzz488XiQcov2hqqqNHKGfsu0SuiveLxcggNVhHlXV4IacF+1yfHI6wVaEA/mXLb8NXLdLH10NIZxzbts2n/ZNs1vwDWa4FT212E4UAaM7GXdN0yzUQVxM1xekoyNm5Mbr3kFh6d1EGtqbZ+MHTGorhf0akzS3RdiU++VDZs1TkQddIYRfUUY6Wx80e+ZdcDmJqb7tuv9dIts6N2HLA332RTSG2oahxTa2nPpJOke7XZC+lcdCJr0f8hliLtcSh5cZD/TUmy7rP7/o30AXMp/dwGL+0snTRnBgeYiKHSOMoEYuI2LRzLa3JbynB/fwa6kOEduXww7R7paL5o773wcsXThrdguvugsepliBHyMe1BorFSWC1WM8wSygM3XEtHrtO/Et11wv7qPwL06iEmtVHCqum336/6rEIITAIX8bHKj/JQoRY9avYRhS672Gg2sNMkBt/LW3+BKFiAZN6075fp/7u99KxKSaFUuhcPx9QP5S4xcohFi2kG0VMAaVn7WPftNylibCLhI1Z5MUXNiLTLkphE/cLvffK2R1LsPVudshnX6+dVgnwyYqsRqEqIz2LpcNoladHvZdtzuM73bvZ437VwqRmiCklKz3oXDrD0sDPIj8afSpL0etaPtELt1td+jy0O1uLPf/iULVQO+wXYDTTxzBC/rhmEKWHJ4dpIYuZKe2D1FST99MS/e2XOUfKIREdNhOSb+r0nJwOHdkmGcf9DGW5y9ytA1kP1zEl6oE2by9KVf5dIWYZb3bNiN0OUwOYIYESljupB9IdINn+WtagP244/NLfUGgKMluMeInK8QkasO8Ps1X0AGyr7DEDDJR593GoSy5OiHUXLaqLsJsXRzPplUKh1u8zacqxHRInA167FnsoJoZYYvE3BaH91qHsvbphIjNVAXC5agmsB4VYg9s+uU2ZOMk8/o8clilRlJO1DVB4fE9d4pK96mqozNdW2iLYT84HYAm6O9fPA4R2cgwurh0nCqBjhpea9gSzbs9jPmbc/PZ9lRDqt/GQpFWiPFCKJve1LjPUsgyVyzYpQ3Y05WgtsMATRbz9wciCquTRLo/CZxOb9W3EEldyORuE/hZChHbhfyqnodupwahD10TBbDJ/Q+ahx+KlbI/ruSpRs6egn/WcyHz4Mak5nMUItZz3l2NNOQL5SsgRiO1yT+O1qxy/QwizKnAF48ng3Ix9UPQF908m/gpChEGFburXeSoDbGnUA9CNBR++RsDIErO9yUw2hRttz15mUlV2JSwwS+WPN5bf1AtfoZCFLSCb6726Dhh82SelQcHSqHsdyklnudu8nCZ+qVjL06zUNvNcTubQttttyOjfzQb/AkKEWo5pM1XOzypA7Yfugkvmmx9/dn1ZB+aQ87jpE872OJkwkWz2TT7Q5QF+I/nuj9BIV1wXlw3BMWqneJYRp7XSV5cPBBiuJyPnr5zoXapwCDm5OKBcXSyoFOyx6rwfXlvzo3eX6GeA51fmYk2uo+6MDwRoqma29VJJ5QLx1aIJKm3ReGoiSbWgZPsF/k5G4PM7BLr3xL0rBLGlNZjOmcvj727QhxJmx+vsg2s50S5rE+N0SmbmtzF6Ci5mpt/cFW6CvER120WoEwKsTndj6qk9qP9W+kPRA0vqqLHmAE/3kMOF3ZbZ/8i07m7QgQZsVNfdRbSakPsHpvJwK2q6IbnYCDISxhT3oSvmIWzCTLPMgFNK20bMp2M/8bQQyzbu07rSidSErG1KmzO5dZaF/zhuRXvrRB3L3JO1il3KPKnJEw1+4FEjhKYYhRIFQ0wWHCqMK7VbhezWjkonXCHv173UYbyUPDtei94AvLpmAj4mn2giBVQQ36qQlJwW16Z8ORHxeLiPrRJZZXZqkkVQ3hSSuE1TeZQi0j1ziO9QyE31b07eiUQkcYWnDcEzsUTeNvrWzdwNovsJY+xvi/5SQrRyLVBLo1BqqfxON30D+i0I9Q5mE7i9syim9MGnodQJK+0wGG9d0aEamVc+7WXwV4MikTOcKU6Bwu0q7blATr6Dvoq2BCVTznTnRVCtmZDPvr0ifKjvO1CIfhpxKxsvUONPchN2UHJSBjOkghDcFBHN+uVhGFJYXxy+/V0Kp676ssF+NFChaVgOqXmKsXAra5aEEv8SxvurRB6Fr+6k5mqMRirLmkL3ViijCcGHQagJVqTjKiXqHUl2rDx+iDALenoz/evGqerMCBlkBqKpKTxKWaq0YewA+4GI7qQ2WfZUP2I/DLFghBcUfQqQIASVS/pYCnyTNWKG0K7BD7iyXosIuhilTKGtHJ7BW6J+NDY6FWcwNnpftx27bXLsGFrH3I51e9LdfYNnGFHsS+v8t5PsKE4K0QBFOKyobpckp0K6cPJcepm7rJ8CpQxw6RQtR6OVNvD0FGVIR6g1a8rQFwnhfq5iqwLw76GUJPr+pE38FvQTuU/Qe0XzxKOOytU0c47r7OAbuSqKh9CZJtCsymECM4XCKkSXUh3g0gOTvGy6qBVNxNjj0AZwRyRvF6LwLJkK7XJkmnnUcxm0/qoc14QyCKpfKobPv/ivX1pAM6sUbEPsSBZykYFCdwImRHMwO3AyKlUYcGGPF9hldnsZtv5Y6mhbnnmq6jp+37bCn/9cs0lJoPdj9pkFSMY8pjEJ7pnii1FaiZPzEi6S59XLveOh2xnizCijHp76Wy90zhifRg/PGwd6c7Sx/WFmLHLxiUPr3d+4YRS3W2CqCrjpirJ44JFtca2msSRqCkQlePCF4diAx+mKuA+EGsruPsAcfXlTZu7Z22siqUzOy7ctrtMs1ls3q1WzWEMXuXFT7Asd6bDCAx7DimmmuKWRbuv5l7peVm1cSXvKZ7rUBEQNMbFoH5AvAq5WHSFdFdvTmzcv7bAxPo1jnN0vWrt8abYe8UPREaIDXAIJix2omEmhRIJ/Zo7UHrAq7D3OuSpkZvErn2eqcHevs/z3e+W2H7OLMafr2GBUMGL/9OdcSMXFGNKqjgUp+U2XBvUzfT4pps2SZJ4Ry49BL27GPyL7nK/gh0FD6Oyjo5OUp1nnbIuAePplRi8aM53DLWyd7r7y8Z9E4VoXkAsdF03r57WOquZw9Vx1razzUPwt0v1votCqBC8+TgPyAvjqFuPhNCftlLhbe61BO0F30jhJ3Evheu/XiT4fC96w4X81rI3XZPcSWE9v4W3RlMQBE/L79Gv/nF1F4adOvHBpEoxWb9xWVzecs06votC7dI/Jnl1/57ltusWqUoyla2wF26Yttmm2JHDIqxIlMRZVPDj6zS8Cm+6qLiDQpQ+3MSGFy8rPjzybeC4pIwylGYsexCZlUIlybYyDexqaqswZqhtupe3chBkwOHqpstWd1gvfV6p9AEEMrPu+UoTvBLz9bS0ChFvkkUXtpK03FE3fguSFyiJHSf0s9DpXv4y1BdFzW677n8XeDOsbpdTdj1lS2Kb4MKd2VPkO/VyaGcNT3bYovaWjAUUEzzahmXtimczUohWjsi/5XJ9hLZ22KAnzxKEMc3tofCjwy6M5cb2JyeNMB6WK0wrmQ8ycqeMeeK6q7F6xsPhmz7LBoVqIZL0EhMWieP7A+lDf1f0g7Nzk062c5y2SY+HooEyuojLWTJtnk6BoV76rg8jaDBrpJjOD2gxtToWwwuFqgkiBIF/UKMjCqVDZDFI5uAtnl8W+ENxtXKXxfC3azH/DazMpYirD3NN9GoLhK4K4TS3VxZfBGLzXNrJUP6Zr8Cs3jg83Hvf+KGnC4jMe7ks8l/0RnPoeZoYvtJY39KFvgGMtK7lSycfMPlgya96vNJbLeyljEfyU/RpMMp26jFgvxk8dnoK+PkBeoqN4l+HjWvzcBrqbz/+XgECxr0vOXdcf19lXqA64+n5BDXzUtbzoWndUNhOv6p/4sPcCjW3NjaLhKuH8Ytkiv3Toy5x3CaO2hm2+SojP1XeI3qyrKz2s6mFQiPUuG7STv1qDOjP/6MKjyC1ehsHQameIcnqAHosZf9Dfxnjwl2fGjQYDAaDwWAwGAwGg8FgMBgMBoPBYDAYDAaDwWAwGAwGg8FgMBgMBoPBYDAYDAaDwWAwGAwGg8Fg+D3/DxRqCLXYR7tRAAAAAElFTkSuQmCC";

?>
<section class=" py-8">
    <div class="container mx-auto text-center px-4">
        <div class="flex flex-wrap -mx-4">
            <div  class="w-full md:w-1/3 px-4 mb-8 <?php if ($idpedido_mesero > 0) {
                echo "white_text";
            } else {
                echo "hidden black_text";
            } ?>">
                <div id="mesero" onclick="<?php if ($idpedido_mesero > 0) {
                    echo "agregarSombra(4)";
                } else {
                    echo "agregarSombra(1)";
                } ?>" class="<?php if ($idpedido_mesero > 0) {
                    echo "activo";
                }?> bg-white p-8 shadow-md rounded-md">
                    <i class="fas fa-users text-4xl text-blue-500 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Confirmar atenci&oacute;n </h3>
                    <p class="text-gray-600">Al presionar el bot&oacute;n, la solicitud de la mesa se marcará como atendida y se guardar&aacute; el registro de la atenci&oacute;n.</p>
                </div>
            </div>
            
            <div class="w-full md:w-1/3 px-4 mb-8 <?php if ($idpedido_cuenta > 0) {
                echo "white_text";
            } else {
                echo "hidden black_text";
            }?>">
                <div id="pedir_cuenta" onclick="<?php if ($idpedido_cuenta > 0) {
                    echo "agregarSombra(6)";
                } else {
                    echo "agregarSombra(3)";
                }?>" class="<?php if ($idpedido_cuenta > 0) {
                    echo "activo";
                }?> bg-white p-8 shadow-md rounded-md">
                    <i class="fas fa-list-check text-4xl text-blue-500 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Confirmar Cuenta Entregada</h3>
                    <p class="text-gray-600">
                    Al presionar el bot&oacute;n, confirmar&aacute;s que la solicitud de la entrega de la cuenta ha sido atendida y se registrar&aacute; esta acci&oacute;n.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>