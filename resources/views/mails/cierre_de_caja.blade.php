<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Caja Sistema</title>
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

        .footer {
            text-align: center;
            padding: 20px;
            background-color: rgba(68, 112, 243, 0.85);
            color: #fff;
            border-radius: 0 0 10px 10px;
        }

        /* Estilos para las diferencias */
        /* Estilo para diferencias POSITIVAS (todo verde) */
        .difference-positive {
            background-color: #e8f5e9;
            /* Fondo verde claro */
            border: 1px solid #a5d6a7;
            /* Borde verde */
            color: #2e7d32;
            /* Texto verde oscuro */
        }

        .difference-positive .difference-value {
            font-weight: bold;
            color: #1b5e20;
            /* Valor en verde más oscuro */
        }

        /* Estilo para diferencias NEGATIVAS (todo rojo) */
        .difference-negative {
            background-color: #ffebee;
            /* Fondo rojo claro */
            border: 1px solid #ef9a9a;
            /* Borde rojo */
            color: #c62828;
            /* Texto rojo oscuro */
        }

        .difference-negative .difference-value {
            font-weight: bold;
            color: #b71c1c;
            /* Valor en rojo más oscuro */
        }

        .difference-zero {
            color: #6c757d;
            /* Gris */
        }

        .difference-description {
            padding: 5px;
            text-align: left;
            line-height: 1.5;
            word-wrap: break-word;
            white-space: normal;
        }

        .difference-row td {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <h2>Cierre de Caja del Sistema</h2>
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

        </table>
        <table>
            <tr class="section-header">
                <td colspan="3"><strong>Tipos de Ingresos:</strong></td>
            </tr>
            <tr>
                <td><strong>Propinas:</strong> {{ number_format(round($boxcloseData['totalTip'], 2), 2) }}</td>
                <td><strong>Venta de Productos:</strong>
                    {{ number_format(round($boxcloseData['totalProduct'], 2), 2) }}</td>
                <td><strong>Prestación de Servicios:</strong>
                    {{ number_format(round($boxcloseData['totalService'], 2), 2) }}</td>
             </tr>
            </table>
            <table>

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
            <!-- Fila adicional para mostrar la diferencia en la caja si existe
            @if (isset($cashierData['differenceBox']) && $cashierData['differenceBox'] != 0)
<tr class="difference-row">
                    <td class="difference-label">
                        <strong>Diferencia en Caja:</strong>
                    </td>
                    <td class="difference-value">
                        {{ number_format(round($cashierData['differenceBox'], 2), 2) }}
                    </td>
                </tr>
@endif-->
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
                <td><strong>Tarjeta Servicio:</strong>
                    {{ number_format(round($cashierData['totalService'] ?? 0, 2), 2) }}</td>
            </tr>
            <tr>
                <td><strong>Débito:</strong> {{ number_format(round($boxcloseData['totalDebit'], 2), 2) }}</td>
                <td><strong>Trajeta producto:</strong> {{ number_format(round($cashierData['totalProduct'] ?? 0, 2), 2) }}</td>
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
            <!-- Fila adicional para mostrar la diferencia en la caja si existe
            @if (isset($cashierData['differencePay']) && $cashierData['differencePay'] != 0)
<tr class="difference-row">
                    <td class="difference-label">
                        <strong>Diferencia en Formas de Pago:</strong>
                    </td>
                    <td class="difference-value">
                        {{ number_format(round($cashierData['differencePay'], 2), 2) }}
                    </td>
                </tr>
@endif-->
            <tr class="total-row">
                <td><strong>Total de Bonos:</strong> {{ number_format(round($totalBonus, 2), 2) }}</td>
                <td><strong>Total de Bonos:</strong> {{ number_format(round($cashierData['totalBonus'] ?? 0, 2), 2) }}
                </td>
            </tr>
            <tr class="total-row">
                <td colspan="2"><strong>Total Ingresado:</strong>
                    {{ number_format(round($boxcloseData['totalMount'], 2), 2) }}</td>
            </tr>

            @if (isset($cashierData['difference']))
                @php
                    $diffClass = '';
                    if ($cashierData['difference'] < 0) {
                        $diffClass = 'difference-negative';
                    } elseif ($cashierData['difference'] > 0) {
                        $diffClass = 'difference-positive';
                    } else {
                        $diffClass = 'difference-zero';
                    }
                @endphp

                <tr class="difference-row {{ $diffClass }}">
                    <td class="difference-label">
                        <strong>Total de Diferencias:</strong>
                    </td>
                    <td class="difference-value">
                        {{ number_format(round($cashierData['difference'], 2), 2) }}
                    </td>
                </tr>

                @if (!empty($cashierData['description']))
                    <tr class="{{ $diffClass }}">
                        <td colspan="2"><strong>Descripción:</strong></td>
                    </tr>
                    <tr class="{{ $diffClass }}">
                        <td colspan="2" class="difference-description">
                            {{ $cashierData['description'] }}
                        </td>
                    </tr>
                @endif
            @endif
            <!-- Fila adicional para mostrar la diferencia si existe
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
                    <td colspan="2"><strong>Descripción:</strong></td>
                </tr>
                <tr>
                    <td colspan="2" style="padding: 5px; text-align: left; line-height: 1.5; word-wrap: break-word; white-space: normal;">
                        <strong>Descripción:</strong> {{ $cashierData['description'] ?? 'Sin descripción' }}
                    </td>
                </tr>
@endif
@endif
          -->
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

<!--<!DOCTYPE html>
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
                <td><strong>Efectivo en caja:</strong> {{ number_format(round($boxExistence, 2), 2) }}</td>
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
                <td><strong>Propinas:</strong> {{ number_format(round($totalTip, 2), 2) }}</td>
                <td><strong>Venta de Productos:</strong> {{ number_format(round($totalProduct, 2), 2) }}</td>
                <td><strong>Prestación de Servicios:</strong> {{ number_format(round($totalService, 2), 2) }}</td>
            </tr>
            <tr class="section-header">
                <td colspan="3"><strong>Formas de pago</strong></td>
            </tr>
            <tr>
                <td><strong>Efectivo:</strong> {{ number_format(round($totalCash, 2), 2) }}</td>
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
    </html>-->
