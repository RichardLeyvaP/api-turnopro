<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Caja</title>
</head>
<body style="font-family: Arial, sans-serif;">

<table width="100%" height="100%" style="border-collapse: collapse; border: 1.5px solid black">
<tr height="10%" style="border-collapse: collapse;  background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td><strong>Empresa:</strong> {{ $branchBusinessName }}</td>
    <td><strong>Sucursal:</strong> {{ $branchName }}</td>
    <td><strong>Fecha:</strong> {{ $boxData }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse; background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Estado de la caja:</strong></td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td><strong>Fondo Inicio del día:</strong> {{ $boxCashFound }}</td>
    <td><strong>Existencia:</strong> {{ $boxExistence }}</td>
    <td><strong>Extracción:</strong> {{ $boxExtraction }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse; background-color: rgba(0, 0, 0, 0.1);  border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Cierre de las Cuentas y Formas de Pago:</strong></td>
</tr>
<tr height="10%" style="border-collapse: collapse; background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Tipos de ingreso</strong></td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td><strong>Propinas:</strong> {{ $totalTip }}</td>
    <td><strong>Venta de Productos:</strong> {{ $totalProduct }}</td>
    <td><strong>Prestacion de Servicios:</strong> {{ $totalService }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse;  background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Formas de pago</strong></td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td><strong>Efectivo en caja:</strong> {{ $totalCash }}</td>
    <td><strong>Tarjeta de Créditos:</strong> {{ $totalCreditCard }}</td>
    <td><strong>Débito:</strong> {{ $totalDebit }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td><strong>Transferencia:</strong> {{ $totalTransfer }}</td>
    <td colspan="2"><strong>Otros Méthodos:</strong> {{ $totalOther }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Total Ingresado:</strong> {{ $totalMount }}</td>
</tr>
</table>

</body>
</html>
