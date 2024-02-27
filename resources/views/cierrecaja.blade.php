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
    <td><strong>Fondo Inicio del día:</strong> {{ $box['cashFound'] }}</td>
    <td><strong>Existencia:</strong> {{ $box['existence'] }}</td>
    <td><strong>Extracción:</strong> {{ $box['extraction'] }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse; background-color: rgba(0, 0, 0, 0.1);  border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Cierre de las Cuentas y Formas de Pago:</strong></td>
</tr>
<tr height="10%" style="border-collapse: collapse; background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Tipos de ingreso</strong></td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td><strong>Propinas:</strong> {{ $data['totalTip'] }}</td>
    <td><strong>Venta de Productos:</strong> {{ $data['totalProduct'] }}</td>
    <td><strong>Prestacion de Servicios:</strong> {{ $data['totalService'] }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse;  background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Formas de pago</strong></td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td><strong>Efectivo en caja:</strong> {{ $data['totalCash'] }}</td>
    <td><strong>Tarjeta de Créditos:</strong> {{ $data['totalCreditCard'] }}</td>
    <td><strong>Débito:</strong> {{ $data['totalDebit'] }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td><strong>Transferencia:</strong> {{ $data['totalTransfer'] }}</td>
    <td colspan="2"><strong>Otros Méthodos:</strong> {{ $data['totalOther'] }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Total Ingresado:</strong> {{ $data['totalMount'] }}</td>
</tr>
</table>
</body>