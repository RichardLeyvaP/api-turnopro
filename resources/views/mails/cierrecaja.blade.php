<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1252" />
<title>Cierre de Caja</title>
</head>
<body>
<table width="100%" height="100%" style="border-collapse: collapse; border: 1.5px solid black">
<tr height="10%" style="border-collapse: collapse;  background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td><strong>Empresa:</strong> {{ $branch->business['name'] }}</td>
    <td><strong>Sucursal:</strong> {{ $branch['name'] }}</td>
    <td><strong>Fecha:</strong> {{ $box['data'] }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse; background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Estado de la caja:</strong></td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td><strong>Fondo Inicio del día:</strong> {{ number_format(round($box['cashFound'], 2), 2) }}</td>
    <td><strong>Existencia:</strong> {{ number_format(round($box['existence'], 2), 2) }}</td>
    <td><strong>Extracción:</strong> {{ number_format(round($box['extraction'], 2), 2) }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse; background-color: rgba(0, 0, 0, 0.1);  border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Cierre de las Cuentas y Formas de Pago:</strong></td>
</tr>
<tr height="10%" style="border-collapse: collapse; background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Tipos de ingreso</strong></td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td><strong>Propinas:</strong> {{ number_format(round($data['totalTip'], 2), 2) }}</td>
    <td><strong>Venta de Productos:</strong> {{ number_format(round($data['totalProduct'], 2), 2) }}</td>
    <td><strong>Prestacion de Servicios:</strong> {{ number_format(round($data['totalService'], 2), 2) }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse;  background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Formas de pago</strong></td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td><strong>Efectivo en caja:</strong> {{ number_format(round($data['totalCash'], 2), 2) }}</td>
    <td><strong>Tarjeta de Créditos:</strong> {{ number_format(round($data['totalCreditCard'], 2), 2) }}</td>
    <td><strong>Débito:</strong> {{ number_format(round($data['totalDebit'], 2), 2) }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td><strong>Transferencia:</strong> {{ number_format(round($data['totalTransfer'], 2), 2) }}</td>
    <td><strong>Total Giftcard:</strong> {{ number_format(round($data['totalCardGif'], 2), 2) }}</td>
    <td><strong>Otros Méthodos:</strong> {{ number_format(round($data['totalOther'], 2), 2) }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td align="center"><strong>Total Bonos:</strong> {{ number_format(round($totalBonus, 2), 2) }}</td>
    <td colspan="2" align="center"><strong>Total Ingresado:</strong> {{ number_format(round($data['totalMount'], 2), 2) }}</td>
</tr>
</table>
</body>