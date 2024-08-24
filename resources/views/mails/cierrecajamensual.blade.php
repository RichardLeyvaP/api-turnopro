<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Caja Mensual</title>
</head>
<body style="font-family: Arial, sans-serif;">

<!-- Encabezado -->
<div style="margin-bottom: 20px;">
    <div style="text-align: left;">
        <strong>{{ $branchBusinessName }}</strong><br>
        Sucursal: {{ $branchName }}<br>
        Fecha: {{ $boxData }}<br>
    </div>
</div>

<!-- Cierre de Cuentas y Formas de Pago -->
<div style="text-align: left; margin-bottom: 10px;">
    <strong>Cierre de las Cuentas y Métodos de Pago</strong>
</div>

<!-- Primera Tabla: Tipos de ingreso -->
<table width="100%" style="border-collapse: collapse; border: 1.5px solid black;">
    <tr style="background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;"><strong>Tipos de ingreso</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;"><strong>Valor</strong></td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Prestación de Servicios</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($totalService, 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Venta de Productos</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($totalProduct, 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Propinas</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($totalTip, 2), 2) }}</td>
    </tr>
</table>

<br>

<!-- Segunda Tabla: Formas de pago -->
<table width="100%" style="border-collapse: collapse; border: 1.5px solid black;">
    <tr style="background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;"><strong>Métodos de pago</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;"><strong>Valor</strong></td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Efectivo</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($totalCash, 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Tarjeta de Crédito</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($totalCreditCard, 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Débito</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($totalDebit, 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Transferencia</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($totalTransfer, 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Total Giftcard</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($totalCardGif, 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Otros Métodos</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($totalOther, 2), 2) }}</td>
    </tr>
    
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Total Ingresado</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($totalMount, 2), 2) }}</td>
    </tr>
</table>

<br>

<!-- Tercera Tabla: Ingreso, Gasto y Utilidad -->
<table width="100%" style="border-collapse: collapse; border: 1.5px solid black;">
    <!-- Encabezado -->
    <tr style="background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;"><strong>Ingreso, Gasto y Utilidad</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;"><strong>Valor</strong></td>
    </tr>
    
    <!-- Ingreso -->
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Ingreso</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($ingreso, 2), 2) }}</td>
    </tr>
    
    <!-- Gasto -->
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Gasto</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($gasto, 2), 2) }}</td>
    </tr>
    
    <!-- Utilidad -->
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Utilidad</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($utilidad, 2), 2) }}</td>
    </tr>
</table>

<br><br>

<!-- Cuarta Tabla: Bonos de Ventas -->
<div style="text-align: left; margin-bottom: 10px;">
    <strong>Pago a profesionales por bono de venta de productos</strong>
</div>
<table width="100%" style="border-collapse: collapse; border: 1.5px solid black;">
    
    <!-- Encabezado -->
    <tr style="background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black;">
        <td style="padding: 5px; line-height: 1;"><strong>Nombre</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;"><strong>Valor</strong></td>
    </tr>
    @foreach($professionalBonus as $bonus)
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; line-height: 1;">{{ $bonus['name'] }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($bonus['winProduct'], 2), 2) }}</td>
    </tr>
    @endforeach
</table>

</body>
</html>






<!--<html lang="es">-->
<!--<head>-->
<!--    <meta charset="UTF-8">-->
<!--    <meta name="viewport" content="width=device-width, initial-scale=1.0">-->
<!--    <title>Cierre de Caja Mensual</title>-->
<!--</head>-->
<!--<body style="font-family: Arial, sans-serif;">-->

<!--<table width="100%" height="100%" style="border-collapse: collapse; border: 1.5px solid black">-->
<!--<tr height="10%" style="border-collapse: collapse;  background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">-->
<!--    <td><strong>Empresa:</strong> {{ $branchBusinessName }}</td>-->
<!--    <td><strong>Sucursal:</strong> {{ $branchName }}</td>-->
<!--    <td><strong>Fecha:</strong> {{ $boxData }}</td>-->
<!--</tr>-->
<!--<tr height="10%" style="border-collapse: collapse;  background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">-->
<!--    <td colspan="3" align="center"><strong>Cierre de las Cuentas y Formas de Pago:</strong></td>-->
<!--</tr>-->
<!--<tr height="10%" style="border-collapse: collapse;  background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">-->
<!--    <td colspan="3" align="center"><strong>Tipos de ingreso</strong></td>-->
<!--</tr>-->
<!--<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">-->
<!--    <td><strong>Propinas:</strong> {{ number_format(round($totalTip, 2), 2) }}</td>-->
<!--    <td><strong>Venta de Productos:</strong> {{ number_format(round($totalProduct, 2), 2) }}</td>-->
<!--    <td><strong>Prestación de Servicios:</strong> {{ number_format(round($totalService, 2), 2) }}</td>-->
<!--</tr>-->
<!--<tr height="10%" style="border-collapse: collapse;  background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">-->
<!--    <td colspan="3" align="center"><strong>Formas de pago</strong></td>-->
<!--</tr>-->
<!--<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">-->
<!--    <td><strong>Efectivo:</strong> {{ number_format(round($totalCash, 2), 2) }}</td>-->
<!--    <td><strong>Tarjeta de Créditos:</strong> {{ number_format(round($totalCreditCard, 2), 2) }}</td>-->
<!--    <td><strong>Débito:</strong> {{ number_format(round($totalDebit, 2), 2) }}</td>-->
<!--</tr>-->
<!--<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">-->
<!--    <td><strong>Transferencia:</strong> {{ number_format(round($totalTransfer, 2), 2) }}</td>-->
<!--    <td><strong>Total Giftcard:</strong> {{ number_format(round($totalCardGif, 2), 2) }}</td>-->
<!--    <td><strong>Otros Méthodos:</strong> {{ number_format(round($totalOther, 2), 2) }}</td>-->
<!--</tr>-->
<!--<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">-->
<!--    <td colspan="3" align="center"><strong>Total Ingresado:</strong> {{ number_format(round($totalMount, 2), 2) }}</td>-->
<!--</tr>-->
<!--<tr height="10%" style="border-collapse: collapse;  background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">-->
<!--    <td><strong>Ingreso:</strong> {{ number_format(round($ingreso, 2), 2) }}</td>-->
<!--    <td><strong>Gasto:</strong> {{ number_format(round($gasto, 2), 2) }}</td>-->
<!--    <td><strong>Utilidad:</strong> {{ number_format(round($utilidad, 2), 2) }}</td>-->
<!--</tr>-->
<!--</table>-->

<!--<br><br>-->

<!--<table width="100%" height="100%" style="border-collapse: collapse; border: 1.5px solid black">-->
<!--<tr height="10%" style="border-collapse: collapse;  background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">-->
<!--    <td colspan="2" align="center"><strong>Pago a profesionales por bonos de ventas de producto</strong></td>-->
<!--</tr>-->
<!--<tr height="10%" style="border-collapse: collapse; border: 1.5px solid black">-->
<!--    <td><strong>Nombre</strong></td>-->
<!--    <td><strong>Bonos de Venta de Producto</strong></td>-->
<!--</tr>-->
<!--@foreach($professionalBonus as $bonus)-->
<!--<tr height="10%" style="border-collapse: collapse; border: 1.5px solid black">-->
<!--    <td>{{ $bonus['name'] }}</td>-->
<!--    <td>{{ number_format(round($bonus['winProduct'], 2), 2) }}</td>-->
<!--</tr>-->
<!--@endforeach-->
<!--</table>-->

<!--</body>-->
<!--</html>-->