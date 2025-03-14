<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Caja</title>
</head>
<body style="font-family: Arial, sans-serif;">

<!-- Encabezado -->
<div style="margin-bottom: 20px;">
    <div style="text-align: left;">
        <strong>{{ $branch->business['name'] }}</strong><br>
        Sucursal: {{ $branch['name'] }}<br>
        Fecha: {{ $box['data'] }}<br>
    </div>
</div>

<!-- Estado de la caja -->
<div style="text-align: left; margin-bottom: 10px;">
    <strong>Estado de la Caja</strong>
</div>

<!-- Tabla de Estado de la Caja -->
<table width="100%" style="border-collapse: collapse; border: 1.5px solid black;">
    <tr style="background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;"><strong>Nombre</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;"><strong>Datos del Sistema</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;"><strong>Datos de la Cajera</strong></td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Fondo inicio del día</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($box['cashFound'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">-</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Efectivo en caja:</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($box['existence'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($cashierData['existence'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Extracción</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($box['extraction'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($cashierData['extraction'], 2), 2) }}</td>
    </tr>
</table>

<br>

<!-- Cierre de Cuentas y Formas de Pago -->
<div style="text-align: left; margin-bottom: 10px;">
    <strong>Cierre de las Cuentas y Métodos de Pago</strong>
</div>

<!-- Primera Tabla: Tipos de ingreso -->
<table width="100%" style="border-collapse: collapse; border: 1.5px solid black;">
    <tr style="background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;"><strong>Tipos de ingreso</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;"><strong>Datos del Sistema</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;"><strong>Datos de la Cajera</strong></td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Prestación de Servicios</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($data['totalService'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($cashierData['totalService'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Venta de Productos</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($data['totalProduct'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($cashierData['totalProduct'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Propinas</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($data['totalTip'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($cashierData['totalTip'], 2), 2) }}</td>
    </tr>
</table>

<br>

<!-- Segunda Tabla: Formas de pago -->
<table width="100%" style="border-collapse: collapse; border: 1.5px solid black;">
    <tr style="background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;"><strong>Métodos de pago</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;"><strong>Datos del Sistema</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;"><strong>Datos de la Cajera</strong></td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Efectivo</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($data['totalCash'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($cashierData['totalCash'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Tarjeta de Crédito</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($data['totalCreditCard'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($cashierData['totalCreditCard'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Débito</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($data['totalDebit'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($cashierData['totalDebit'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Transferencia</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($data['totalTransfer'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($cashierData['totalTransfer'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Total Giftcard</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($data['totalCardGif'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($cashierData['totalCardGif'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Otros Métodos</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($data['totalOther'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($cashierData['totalOther'], 2), 2) }}</td>
    </tr>
</table>

<br>

<!-- Tercera Tabla: Bonos -->
<table width="100%" style="border-collapse: collapse; border: 1.5px solid black;">
    <tr style="background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;"><strong>Bonos</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;"><strong>Datos del Sistema</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;"><strong>Datos de la Cajera</strong></td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Total bonos</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($totalBonus, 2), 2) }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($cashierData['totalBonus'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Total Ingresado</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($data['totalMount'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($cashierData['totalMount'], 2), 2) }}</td>
    </tr>
</table>

<!-- Diferencia, Descripción y Realizado por -->
@if(isset($cashierData['diferencia']) && $cashierData['diferencia'] !== null)
    <table width="100%" style="border-collapse: collapse; border: 1.5px solid black; margin-top: 20px;">
        <tr style="border: 1.5px solid black;">
            <td style="padding: 5px; text-align: left; line-height: 1;"><strong>Diferencia:</strong></td>
            <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($cashierData['diferencia'], 2), 2) }}</td>
        </tr>
        <tr style="border: 1.5px solid black;">
            <td style="padding: 5px; text-align: left; line-height: 1;"><strong>Descripción:</strong></td>
            <td style="padding: 5px; text-align: right; line-height: 1;">{{ $cashierData['description'] }}</td>
        </tr>
    </table>
@endif

<table width="100%" style="border-collapse: collapse; border: 1.5px solid black; margin-top: 20px;">
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;"><strong>Realizado por:</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ $nameProfessional }}</td>
    </tr>
</table>

</body>
</html>