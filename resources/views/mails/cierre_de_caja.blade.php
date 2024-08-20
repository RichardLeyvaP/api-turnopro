<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Caja</title>
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
        <h2>Cierre de Caja</h2>
    </div>
    <table>
        <tr>
            <td><strong>Empresa:</strong> {{ $branchBusinessName }}</td>
            <td><strong>Sucursal:</strong> {{ $branchName }}</td>
            <td><strong>Fecha:</strong> {{ $boxData }}</td>
        </tr>
        <tr class="section-header">
            <td colspan="3"><strong>Estado de la caja:</strong></td>
        </tr>
        <tr>
            <td><strong>Fondo Inicio del día:</strong> {{ number_format(round($boxCashFound, 2), 2) }}</td>
            <td><strong>Existencia:</strong> {{ number_format(round($boxExistence, 2), 2) }}</td>
            <td><strong>Extracción:</strong> {{ number_format(round($boxExtraction, 2), 2) }}</td>
        </tr>
        <tr class="section-header">
            <td colspan="3"><strong>Cierre de las Cuentas y Formas de Pago:</strong></td>
        </tr>
        <tr>
           
        </tr>

        <tr class="section-header">
            <td colspan="3"><strong>Tipos de ingreso</strong></td>
        </tr>
        <tr>
            <td><strong>Propinas:</strong> {{ $totalTip }}</td>
            <td><strong>Venta de Productos:</strong> {{ number_format(round($totalProduct, 2), 2) }}</td>
            <td><strong>Prestación de Servicios:</strong> {{ number_format(round($totalService, 2), 2) }}</td>
        </tr>
        <tr class="section-header">
            <td colspan="3"><strong>Formas de pago</strong></td>
        </tr>
        <tr>
            <td><strong>Efectivo en caja:</strong> {{ number_format(round($totalCash, 2), 2) }}</td>
            <td><strong>Tarjeta de Créditos:</strong> {{ number_format(round($totalCreditCard, 2), 2) }}</td>
            <td><strong>Débito:</strong> {{ number_format(round($totalDebit, 2), 2) }}</td>
        </tr>
        <tr>
            <td><strong>Transferencia:</strong> {{ number_format(round($totalTransfer, 2), 2) }}</td>
            <td><strong>Total Giftcard:</strong> {{ number_format(round($totalGiftcard, 2), 2) }}</td>
            <td><strong>Otros Métodos:</strong> {{ number_format(round($totalOther, 2), 2) }}</td>
        </tr>
        <tr class="total-row">
            <td><strong>Total de Bonos:</strong> {{ number_format(round($totalBonus, 2), 2) }}</td>
            <td colspan="2"><strong>Total Ingresado:</strong> {{ number_format(round($totalMount, 2), 2) }}</td>
        </tr>
    </table>
    <div class="footer">
        <p>Gracias por su colaboración.</p>
    </div>
</div>

</body>
</html>
