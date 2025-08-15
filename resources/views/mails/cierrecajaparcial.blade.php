<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Caja Parcial</title>
</head>
<body style="font-family: Arial, sans-serif;">

<!-- Encabezado -->
<div style="margin-bottom: 20px;">
    <div style="text-align: left;">
        <strong>{{ $branch->business['name'] }}</strong><br>
        Sucursal: {{ $branch['name'] }}<br>
        Fecha: {{ \Carbon\Carbon::parse($box['data'])->format('Y-m-d H:i') }}<br>
    </div>
</div>

<!-- Primera Tabla: Tipos de ingreso -->
<table width="100%" style="border-collapse: collapse; border: 1.5px solid black;">
    <tr style="background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;" colspan="2"><strong>Tipos de ingreso</strong></td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Prestación de Servicios</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($data['totalService'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Venta de Productos a Clientes</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($data['totalProduct'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Venta de Productos a Profesionales</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($data['workerpurchase'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Propinas</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($data['totalTip'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;"><strong>Total</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;"><strong>{{ number_format(round($data['totalMount'], 2), 2) }}</strong></td>
    </tr>
</table>
<br>

<!-- Cierre de Cuentas y Formas de Pago -->
<div style="text-align: left; margin-bottom: 10px;">
    <strong>Métodos de Pago</strong><br>
    <strong>Excepto Venta de Productos a Profesionales</strong>
</div>
<br>

<!-- Segunda Tabla: Formas de pago -->
<table width="100%" style="border-collapse: collapse; border: 1.5px solid black;">
    <tr style="background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;"><strong>Datos del Sistema</strong></td>
        <td style="padding: 5px; text-align: left; line-height: 1;"><strong>Datos de la Cajera</strong></td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Efectivo: {{ number_format(round($data['totalCash'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: left; line-height: 1;">Efectivo: {{ number_format(round($cashierData['totalCash'] ?? 0, 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Tarjeta de Crédito: {{ number_format(round($data['totalCreditCard'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: left; line-height: 1;">Tarjeta Servicio: {{ number_format(round($cashierData['totalService'] ?? 0, 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Débito: {{ number_format(round($data['totalDebit'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: left; line-height: 1;">Tarjeta producto: {{ number_format(round($cashierData['totalProduct'] ?? 0, 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Transferencia: {{ number_format(round($data['totalTransfer'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: left; line-height: 1;">Transferencia: {{ number_format(round($cashierData['totalTransfer'] ?? 0, 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Total Giftcard: {{ number_format(round($data['totalCardGif'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: left; line-height: 1;">Total Giftcard: {{ number_format(round($cashierData['totalCardGif'] ?? 0, 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Otros Métodos: {{ number_format(round($data['totalOther'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: left; line-height: 1;">Otros Métodos: {{ number_format(round($cashierData['totalOther'] ?? 0, 2), 2) }}</td>
    </tr>
</table>
<br>

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
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($cashierData['cashFound'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Método de Pago Efectivo</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($data['totalCash'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($cashierData['totalCash'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Extracción</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($box['extraction'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($cashierData['extraction'] ?? 0, 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Efectivo en caja:</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($box['existence'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($cashierData['existence'] ?? 0, 2), 2) }}</td>
    </tr>
</table>



<br>

<!-- Tercera Tabla: Bonos 
<table width="100%" style="border-collapse: collapse; border: 1.5px solid black;">
    <tr style="background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;"><strong>Totales</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;"><strong>Datos del Sistema</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;"><strong>Datos de la Cajera</strong></td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Tipos de Ingresos</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($data['totalMount'], 2), 2) }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($cashierData['totalMount'] ?? 0, 2), 2) }}</td>
    </tr>
</table>-->
@if(isset($cashierData['difference']) && $cashierData['difference'] !== null)
    @php
        // Determinar color y estilo según el valor
        $color = '#D32F2F'; // Rojo por defecto (para valores negativos)
        $bgColor = '#FFEBEE'; // Fondo rojo claro
        $borderColor = '#D32F2F'; // Borde rojo
        
        if ($cashierData['difference'] > 0) {
            $color = '#388E3C'; // Verde para positivos
            $bgColor = '#E8F5E9'; // Fondo verde claro
            $borderColor = '#388E3C'; // Borde verde
        } elseif ($cashierData['difference'] == 0) {
            $color = '#616161'; // Gris para cero
            $bgColor = '#FAFAFA'; // Fondo gris muy claro
            $borderColor = '#616161'; // Borde gris
        }
    @endphp

    <table width="100%" style="border-collapse: collapse; border: 1.5px solid {{ $borderColor }}; margin-top: 20px;">
        <tr style="border: 2px solid {{ $borderColor }}; background-color: {{ $bgColor }};">
            <td style="padding: 5px; text-align: left; line-height: 1; color: {{ $color }}; font-size: 16px; font-weight: bold;">
                <strong>Total de Diferencias:</strong>
            </td>
            <td style="padding: 5px; text-align: right; line-height: 1; color: {{ $color }}; font-size: 16px; font-weight: bold;">
                {{ number_format(round($cashierData['difference'], 2), 2) }}
            </td>
        </tr>
        
        @if(isset($cashierData['description']))
        <tr style="border: 1.5px solid {{ $borderColor }};">
            <td style="padding: 5px; text-align: left; line-height: 1;" colspan="2">
                <strong>Descripción:</strong>
            </td>
        </tr>
        <tr style="border: 1.5px solid {{ $borderColor }};">
            <td style="padding: 5px; text-align: left; line-height: 1.5; word-wrap: break-word; white-space: normal;" colspan="2">
                {{ $cashierData['description'] ?? 'Sin descripción' }}
            </td>
        </tr>
        @endif
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