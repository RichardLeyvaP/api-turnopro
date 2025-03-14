<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Caja Parcial</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            width: 80%;
            margin: auto;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header, .section-header {
            background-color: rgba(68, 112, 243, 0.85);
            color: #fff;
            padding: 10px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .header {
            border-radius: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: rgba(68, 112, 243, 0.85);
            color: #fff;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .total-row {
            background-color: #e9e9e9;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            padding: 20px;
            background-color: rgba(68, 112, 243, 0.85);
            color: #fff;
            border-radius: 0 0 10px 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>Cierre de Caja Parcial</h2>
    </div>
    <table>
        <tr>
            <td><strong>Empresa:</strong> {{ $branchBusinessName }}</td>
            <td>
                <div style="display: flex; align-items: left;">
                    <span><strong>Sucursal:</strong> {{ $branchName }}</span>
                </div>
                <div style="display: flex; jalign-items: right;">
                    <span><strong>Fecha:</strong> {{ $boxData['data'] }}</span>
                </div>
            </td>
        </tr>
        
        <tr>
            <th>Datos del Sistema</th>
            <th>Datos de la Cajera</th>
        </tr>
        <tr class="section-header">
            <td colspan="2"><strong>Estado de la caja:</strong></td>
        </tr>
        <tr>
            <td colspan="2"><strong>Fondo Inicio del día:</strong> {{ number_format(round($boxData['cashFound'], 2), 2) }}</td>
        </tr>
        <tr>
            <td><strong>Efectivo en caja:</strong> {{ number_format(round($boxData['existence'], 2), 2) }}</td>
            <td><strong>Efectivo en caja:</strong> {{ number_format(round($cashierData['existence'], 2), 2) }}</td>
        </tr>
        <tr>
            <td><strong>Extracción:</strong> {{ number_format(round($boxData['extraction'], 2), 2) }}</td>
            <td><strong>Extracción:</strong> {{ number_format(round($cashierData['extraction'], 2), 2) }}</td>
        </tr>
        <tr class="section-header">
            <td colspan="2"><strong>Cierre de las Cuentas y Formas de Pago:</strong></td>
        </tr>
        <tr>
            <td><strong>Propinas:</strong> {{ number_format(round($boxcloseData['totalTip'], 2), 2) }}</td>
            <td><strong>Propinas:</strong> {{ number_format(round($cashierData['totalTip'], 2), 2) }}</td>
        </tr>
        <tr>
            <td><strong>Venta de Productos:</strong> {{ number_format(round($boxcloseData['totalProduct'], 2), 2) }}</td>
            <td><strong>Venta de Productos:</strong> {{ number_format(round($cashierData['totalProduct'], 2), 2) }}</td>
        </tr>
        <tr>
            <td><strong>Prestación de Servicios:</strong> {{ number_format(round($boxcloseData['totalService'], 2), 2) }}</td>
            <td><strong>Prestación de Servicios:</strong> {{ number_format(round($cashierData['totalService'], 2), 2) }}</td>
        </tr>
        <tr class="section-header">
            <td colspan="2"><strong>Formas de pago</strong></td>
        </tr>
        <tr>
            <td><strong>Efectivo:</strong> {{ number_format(round($boxcloseData['totalCash'], 2), 2) }}</td>
            <td><strong>Efectivo:</strong> {{ number_format(round($cashierData['totalCash'], 2), 2) }}</td>
        </tr>
        <tr>
            <td><strong>Tarjeta de Créditos:</strong> {{ number_format(round($boxcloseData['totalCreditCard'], 2), 2) }}</td>
            <td><strong>Tarjeta de Créditos:</strong> {{ number_format(round($cashierData['totalCreditCard'], 2), 2) }}</td>
        </tr>
        <tr>
            <td><strong>Débito:</strong> {{ number_format(round($totalDebit, 2), 2) }}</td>
            <td><strong>Débito:</strong> {{ number_format(round($cashierData['totalDebit'], 2), 2) }}</td>
        </tr>
        <tr>
            <td><strong>Transferencia:</strong> {{ number_format(round($boxcloseData['totalTransfer'], 2), 2) }}</td>
            <td><strong>Transferencia:</strong> {{ number_format(round($cashierData['totalTransfer'], 2), 2) }}</td>
        </tr>
        <tr>
            <td><strong>Total Giftcard:</strong> {{ number_format(round($boxcloseData['totalCardGif'], 2), 2) }}</td>
            <td><strong>Total Giftcard:</strong> {{ number_format(round($cashierData['totalCardGif'], 2), 2) }}</td>
        </tr>
        <tr>
            <td><strong>Otros Métodos:</strong> {{ number_format(round($boxcloseData['totalOther'], 2), 2) }}</td>
            <td><strong>Otros Métodos:</strong> {{ number_format(round($cashierData['totalOther'], 2), 2) }}</td>
        </tr>
        <tr class="total-row">
            <td><strong>Total de Bonos:</strong> {{ number_format(round($totalBonus, 2), 2) }}</td>
            <td><strong>Total de Bonos:</strong> {{ number_format(round($cashierData['totalBonus'], 2), 2) }}</td>
        </tr>
        <tr class="total-row">
            <td colspan="2"><strong>Total Ingresado:</strong> {{ number_format(round($boxcloseData['totalMount'], 2), 2) }}</td>
        </tr>
        @if(isset($cashierData['diferencia']) && $cashierData['diferencia'] !== null)
            <tr>
                <td colspan="2"><strong>Diferencia:</strong> {{ number_format(round($cashierData['diferencia'], 2), 2) }}</td>
            </tr>
            <tr>
                <td colspan="2"><strong>Descripción:</strong> {{ $cashierData['description'] }}</td>
            </tr>
        @endif
        <tr>
            <td colspan="2"><strong>Realizado por:</strong> {{ $nameProfessional }}</td>
        </tr>
    </table>
    <div class="footer">
        <p>Gracias por su colaboración.</p>
    </div>
</div>

</body>
</html>