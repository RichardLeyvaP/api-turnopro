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
        @if($entityType === 'Sucursal')
        Sucursal: {{ $branchName }}<br>
        @endif
        Mes: {{$monthName}}<br>
        Realizado: {{ $boxData }}<br>
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
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($boxcloseData['totalService'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Venta de Productos</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($boxcloseData['totalProduct'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Propinas</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($boxcloseData['totalTip'], 2), 2) }}</td>
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
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($boxcloseData['totalCash'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Tarjeta de Crédito</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($boxcloseData['totalCreditCard'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Débito</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($boxcloseData['totalDebit'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Transferencia</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($boxcloseData['totalTransfer'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Total Giftcard</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($boxcloseData['totalCardGif'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Otros Métodos</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($boxcloseData['totalOther'], 2), 2) }}</td>
    </tr>
    
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Total Métodos de Pago</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($boxcloseData['totalMount'], 2), 2) }}</td>
    </tr>
</table>

<br>

<!-- Tercera Tabla: Ingreso, Gasto y Utilidad -->
<table width="100%" style="border-collapse: collapse; border: 1.5px solid black;">
    <tr style="background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black;">
        <th style="padding: 8px; text-align: center; border: 1.5px solid black;" colspan="4">Resumen del cierre de mes</th>
    </tr>
    <tr style="background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black;">
        <th style="padding: 8px; text-align: center; border: 1.5px solid black;">Operación</th>
        <th style="padding: 8px; text-align: center; border: 1.5px solid black;">Datos del Administrador</th>
        <th style="padding: 8px; text-align: center; border: 1.5px solid black;">Datos del Sistema</th>
        <th style="padding: 8px; text-align: center; border: 1.5px solid black;">Diferencia</th>
    </tr>
    
    <tr>
        <td style="padding: 8px; border: 1.5px solid black;">Ingresos</td>
        <td style="padding: 8px; text-align: right; border: 1.5px solid black;">
            {{ number_format(round($editedItem['available_money'], 2), 2) }}
        </td>
        <td style="padding: 8px; text-align: right; border: 1.5px solid black;">
            {{ number_format(round($editedItem['system_incomes'], 2), 2) }}
        </td>
        <td style="padding: 8px; text-align: right; border: 1.5px solid black; color: {{ $editedItem['difference_incomes'] >= 0 ? 'black' : 'red' }};">
            <!--{{ number_format(round($editedItem['difference_incomes'], 2), 2) }}-->
        </td>
    </tr>
    
    
    <tr>
        <td style="padding: 8px; border: 1.5px solid black;">Utilidad</td>
        <td style="padding: 8px; text-align: right; border: 1.5px solid black;">
            {{ number_format(round($editedItem['client_utility'], 2), 2) }}
        </td>
        <td style="padding: 8px; text-align: right; border: 1.5px solid black;">
            {{ number_format(round($editedItem['utility'], 2), 2) }}
        </td>
        <td style="padding: 8px; text-align: right; border: 1.5px solid black; color: {{ $editedItem['difference_utility'] >= 0 ? 'black' : 'red' }};">
            <!--{{ number_format(round($editedItem['difference_utility'], 2), 2) }}-->
        </td>
    </tr>
    
    <tr>
        <td style="padding: 8px; border: 1.5px solid black;">Gastos</td>
        <td style="padding: 8px; text-align: right; border: 1.5px solid black;">
            <!--{{ number_format(round($editedItem['discounts'], 2), 2) }}-->
        </td>
        <td style="padding: 8px; text-align: right; border: 1.5px solid black;">
            {{ number_format(round($editedItem['spent'], 2), 2) }}
        </td>
        <td style="padding: 8px; text-align: right; border: 1.5px solid black; color: {{ $editedItem['difference_spent'] >= 0 ? 'black' : 'red' }};">
            <!--{{ number_format(round($editedItem['difference_spent'], 2), 2) }}-->
        </td>
    </tr>
    <tr>
        <td style="padding: 8px; border: 1.5px solid black;">Retención</td>
        <td style="padding: 8px; text-align: right; border: 1.5px solid black;">
            <!--{{ number_format(round($editedItem['client_retention'], 2), 2) }}-->
        </td>
        <td style="padding: 8px; text-align: right; border: 1.5px solid black;">
            {{ number_format(round($editedItem['retention'], 2), 2) }}
        </td>
        <td style="padding: 8px; text-align: right; border: 1.5px solid black; color: {{ $editedItem['difference_retention'] >= 0 ? 'black' : 'red' }};">
            <!--{{ number_format(round($editedItem['difference_retention'], 2), 2) }}-->
        </td>
    </tr>
    
    <!-- Diferencia Total -->
    @if($editedItem['differences'] != 0)
    <tr>
        <td colspan="4" style="padding: 8px; border: 1.5px solid black; background-color: rgba(0, 0, 0, 0.05); text-align: left; font-weight: bold; color: {{ $editedItem['differences'] >= 0 ? 'black' : 'red' }};">
            Diferencia Total de : {{ number_format(round($editedItem['differences'], 2), 2) }}
        </td>
    </tr>
    <tr>
        <td colspan="4" style="padding: 8px; border: 1.5px solid black;">
            <strong>¿Por qué?</strong><br>
            {{ $editedItem['description'] ?? 'Sin descripción' }}
        </td>
    </tr>
    @endif
</table>

<br><br>

<table width="100%" style="border-collapse: collapse; border: 1.5px solid black; margin-top: 20px;">
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;"><strong>Realizado por:</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ $nameProfessional }}</td>
    </tr>
</table>

</body>
</html>