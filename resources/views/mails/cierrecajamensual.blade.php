<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Caja Mensual</title>
</head>
<body style="font-family: Arial, sans-serif;">

<table width="100%" height="100%" style="border-collapse: collapse; border: 1.5px solid black">
<tr height="10%" style="border-collapse: collapse;  background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td><strong>Empresa:</strong> {{ $branchBusinessName }}</td>
    <td><strong>Sucursal:</strong> {{ $branchName }}</td>
    <td><strong>Fecha:</strong> {{ $boxData }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse;  background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Cierre de las Cuentas y Formas de Pago:</strong></td>
</tr>
<tr height="10%" style="border-collapse: collapse;  background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Tipos de ingreso</strong></td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td><strong>Propinas:</strong> {{ number_format(round($totalTip, 2), 2) }}</td>
    <td><strong>Venta de Productos:</strong> {{ number_format(round($totalProduct, 2), 2) }}</td>
    <td><strong>Prestación de Servicios:</strong> {{ number_format(round($totalService, 2), 2) }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse;  background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Formas de pago</strong></td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td><strong>Efectivo:</strong> {{ number_format(round($totalCash, 2), 2) }}</td>
    <td><strong>Tarjeta de Créditos:</strong> {{ number_format(round($totalCreditCard, 2), 2) }}</td>
    <td><strong>Débito:</strong> {{ number_format(round($totalDebit, 2), 2) }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td><strong>Transferencia:</strong> {{ number_format(round($totalTransfer, 2), 2) }}</td>
    <td><strong>Total Giftcard:</strong> {{ number_format(round($totalCardGif, 2), 2) }}</td>
    <td><strong>Otros Méthodos:</strong> {{ number_format(round($totalOther, 2), 2) }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Total Ingresado:</strong> {{ $totalMount }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse;  background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td><strong>Ingreso:</strong> {{ number_format(round($ingreso, 2), 2) }}</td>
    <td><strong>Gasto:</strong> {{ number_format(round($gasto, 2), 2) }}</td>
    <td><strong>Utilidad:</strong> {{ number_format(round($utilidad, 2), 2) }}</td>
</tr>
</table>

<br><br>

<table width="100%" height="100%" style="border-collapse: collapse; border: 1.5px solid black">
<tr height="10%" style="border-collapse: collapse;  background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td colspan="2" align="center"><strong>Pago a profesionales por bonos de ventas de producto</strong></td>
</tr>
<tr height="10%" style="border-collapse: collapse; border: 1.5px solid black">
    <td><strong>Nombre</strong></td>
    <td><strong>Bonos de Venta de Producto</strong></td>
</tr>
@foreach($professionalBonus as $bonus)
<tr height="10%" style="border-collapse: collapse; border: 1.5px solid black">
    <td>{{ $bonus['name'] }}</td>
    <td>{{ number_format(round($bonus['winProduct'], 2), 2) }}</td>
</tr>
@endforeach
</table>

</body>
</html>