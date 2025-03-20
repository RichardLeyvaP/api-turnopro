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
            border: 1px solid #616060;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header,
        .section-header {
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

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
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

        .difference-row {
            border: 2px solid #D32F2F;
            background-color: #FFEBEE;
        }

        .difference-label {
            padding: 5px;
            text-align: left;
            line-height: 1;
            color: #D32F2F;
            font-size: 16px;
            font-weight: bold;
        }

        .difference-value {
            padding: 5px;
            text-align: right;
            line-height: 1;
            color: #D32F2F;
            font-size: 16px;
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
                    <div style="display: flex; align-items: right;">
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
                <td colspan="2"><strong>Fondo Inicio del día:</strong>
                    {{ number_format(round($boxData['cashFound'], 2), 2) }}</td>
            </tr>
            <tr>
                <td><strong>Efectivo en caja:</strong> {{ number_format(round($boxData['existence'], 2), 2) }}</td>
                <td><strong>Efectivo en caja:</strong> {{ number_format(round($cashierData['existence'] ?? 0, 2), 2) }}
                </td>
            </tr>
            <tr>
                <td><strong>Extracción:</strong> {{ number_format(round($boxData['extraction'], 2), 2) }}</td>
                <td><strong>Extracción:</strong> {{ number_format(round($cashierData['extraction'] ?? 0, 2), 2) }}</td>
            </tr>
            <!-- Fila adicional para mostrar la diferencia en la caja si existe -->
            @if (isset($cashierData['differenceBox']) && $cashierData['differenceBox'] != 0)
                <tr class="difference-row">
                    <td class="difference-label">
                        <strong>Diferencia en Caja:</strong>
                    </td>
                    <td class="difference-value">
                        {{ number_format(round($cashierData['differenceBox'], 2), 2) }}
                    </td>
                </tr>
            @endif
            <tr class="section-header">
                <td colspan="2"><strong>Cierre de las Cuentas y Formas de Pago:</strong></td>
            </tr>
            <tr>
                <td><strong>Propinas:</strong> {{ number_format(round($boxcloseData['totalTip'], 2), 2) }}</td>
                <td><strong>Propinas:</strong> {{ number_format(round($cashierData['totalTip'] ?? 0, 2), 2) }}</td>
            </tr>
            <tr>
                <td><strong>Venta de Productos:</strong>
                    {{ number_format(round($boxcloseData['totalProduct'], 2), 2) }}</td>
                <td><strong>Venta de Productos:</strong>
                    {{ number_format(round($cashierData['totalProduct'] ?? 0, 2), 2) }}
                </td>
            </tr>
            <tr>
                <td><strong>Prestación de Servicios:</strong>
                    {{ number_format(round($boxcloseData['totalService'], 2), 2) }}</td>
                <td><strong>Prestación de Servicios:</strong>
                    {{ number_format(round($cashierData['totalService'] ?? 0, 2), 2) }}</td>
            </tr>
            <!-- Fila adicional para mostrar la diferencia en las Cuentas -->
            @if (isset($cashierData['differenceAccounts']) && $cashierData['differenceAccounts'] != 0)
                <tr class="difference-row">
                    <td class="difference-label">
                        <strong>Diferencia en Cuentas:</strong>
                    </td>
                    <td class="difference-value">
                        {{ number_format(round($cashierData['differenceAccounts'], 2), 2) }}
                    </td>
                </tr>
            @endif
            <tr class="section-header">
                <td colspan="2"><strong>Formas de pago</strong></td>
            </tr>
            <tr>
                <td><strong>Efectivo:</strong> {{ number_format(round($boxcloseData['totalCash'], 2), 2) }}</td>
                <td><strong>Efectivo:</strong> {{ number_format(round($cashierData['totalCash'] ?? 0, 2), 2) }}</td>
            </tr>
            <tr>
                <td><strong>Tarjeta de Créditos:</strong>
                    {{ number_format(round($boxcloseData['totalCreditCard'], 2), 2) }}</td>
                <td><strong>Tarjeta de Créditos:</strong>
                    {{ number_format(round($cashierData['totalCreditCard'] ?? 0, 2), 2) }}</td>
            </tr>
            <tr>
                <td><strong>Débito:</strong> {{ number_format(round($totalDebit, 2), 2) }}</td>
                <td><strong>Débito:</strong> {{ number_format(round($cashierData['totalDebit'] ?? 0, 2), 2) }}</td>
            </tr>
            <tr>
                <td><strong>Transferencia:</strong>
                    {{ number_format(round($boxcloseData['totalTransfer'] ?? 0, 2), 2) }}
                </td>
                <td><strong>Transferencia:</strong>
                    {{ number_format(round($cashierData['totalTransfer'] ?? 0, 2), 2) }}
                </td>
            </tr>
            <tr>
                <td><strong>Total Giftcard:</strong> {{ number_format(round($boxcloseData['totalCardGif'], 2), 2) }}
                </td>
                <td><strong>Total Giftcard:</strong>
                    {{ number_format(round($cashierData['totalCardGif'] ?? 0, 2), 2) }}
                </td>
            </tr>
            <tr>
                <td><strong>Otros Métodos:</strong> {{ number_format(round($boxcloseData['totalOther'], 2), 2) }}</td>
                <td><strong>Otros Métodos:</strong> {{ number_format(round($cashierData['totalOther'] ?? 0, 2), 2) }}
                </td>
            </tr>
            <!-- Fila adicional para mostrar la diferencia en la caja si existe -->
            @if (isset($cashierData['differencePay']) && $cashierData['differencePay'] != 0)
                <tr class="difference-row">
                    <td class="difference-label">
                        <strong>Diferencia en Formas de Pago:</strong>
                    </td>
                    <td class="difference-value">
                        {{ number_format(round($cashierData['differencePay'], 2), 2) }}
                    </td>
                </tr>
            @endif
            <tr class="total-row">
                <td><strong>Total de Bonos:</strong> {{ number_format(round($totalBonus, 2), 2) }}</td>
                <td><strong>Total de Bonos:</strong> {{ number_format(round($cashierData['totalBonus'] ?? 0, 2), 2) }}
                </td>
            </tr>
            <tr class="total-row">
                <td colspan="2"><strong>Total Ingresado:</strong>
                    {{ number_format(round($boxcloseData['totalMount'], 2), 2) }}</td>
            </tr>

            <!-- Fila adicional para mostrar la diferencia si existe -->
            @if (isset($cashierData['difference']) && $cashierData['difference'] != 0)
                <tr class="difference-row">
                    <td class="difference-label">
                        <strong>Total de Diferencias:</strong>
                    </td>
                    <td class="difference-value">
                        {{ number_format(round($cashierData['difference'], 2), 2) }}
                    </td>
                </tr>
                @if (isset($cashierData['description']) && $cashierData['description'])
                    <tr>
                        <td colspan="2"><strong>Descripción:</strong> {{ $cashierData['description'] ?? 0 }}</td>
                    </tr>
                @endif
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
