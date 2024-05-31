<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Caja Mensual</title>
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
            <h2>Cierre de Caja Mensual</h2>
        </div>
        <table>
            <tr>
                <th>Empresa</th>
                <th>Sucursal</th>
                <th>Fecha</th>
            </tr>
            <tr>
                <td>{{ $branchBusinessName }}</td>
                <td>{{ $branchName }}</td>
                <td>{{ $boxData }}</td>
            </tr>
        </table>
        <div class="section-header">
            <h3>Cierre de las Cuentas y Formas de Pago</h3>
        </div>
        <table>
            <tr>
                <th colspan="3">Tipos de ingreso</th>
            </tr>
            <tr>
                <td>Propinas</td>
                <td>Venta de Productos</td>
                <td>Prestación de Servicios</td>
            </tr>
            <tr>
                <td>{{ $totalTip }}</td>
                <td>{{ $totalProduct }}</td>
                <td>{{ $totalService }}</td>
            </tr>
        </table>
        <table>
            <tr>
                <th colspan="3">Formas de pago</th>
            </tr>
            <tr>
                <td>Efectivo</td>
                <td>Tarjeta de Créditos</td>
                <td>Débito</td>
            </tr>
            <tr>
                <td>{{ $totalCash }}</td>
                <td>{{ $totalCreditCard }}</td>
                <td>{{ $totalDebit }}</td>
            </tr>
            <tr>
                <td>Transferencia</td>
                <td>Total Giftcard</td>
                <td>Otros Métodos</td>
            </tr>
            <tr>
                <td>{{ $totalTransfer }}</td>
                <td>{{ $totalGiftcard }}</td>
                <td>{{ $totalOther }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="2">Total Ingresado</td>
                <td>{{ $totalMount }}</td>
            </tr>
        </table>
        <table>
            <tr class="total-row">
                <td>Ingreso</td>
                <td>Gasto</td>
                <td>Utilidad</td>
            </tr>
            <tr>
                <td>{{ $ingreso }}</td>
                <td>{{ $gasto }}</td>
                <td>{{ $utilidad }}</td>
            </tr>
        </table>
        <div class="section-header">
            <h3>Pago a profesionales por bonos de ventas de producto</h3>
        </div>
        <table>
            <tr>
                <th>Nombre</th>
                <th>Bonos de Venta de Producto</th>
            </tr>
            @foreach($professionalBonus as $bonus)
            <tr>
                <td>{{ $bonus['name'] }}</td>
                <td>{{ $bonus['winProduct'] }}</td>
            </tr>
            @endforeach
        </table>
        <div class="footer">
            <p>Gracias por su colaboración.</p>
        </div>
    </div>
</body>
</html>
<!--<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Caja Mensual</title>
</head>
<body style="font-family: Arial, sans-serif;">

<table width="100%" height="100%" style="border-collapse: collapse; border: 1.5px solid black">
<tr height="10%" style="border-collapse: collapse; background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
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
</tr>-->
<!--<tr height="10%" style="border-collapse: collapse; background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Cierre de las Cuentas y Formas de Pago:</strong></td>
</tr>
<tr height="10%" style="border-collapse: collapse; background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Tipos de ingreso</strong></td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td><strong>Propinas:</strong> {{ $totalTip }}</td>
    <td><strong>Venta de Productos:</strong> {{ $totalProduct }}</td>
    <td><strong>Prestación de Servicios:</strong> {{ $totalService }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse; background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Formas de pago</strong></td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td><strong>Efectivo:</strong> {{ $totalCash }}</td>
    <td><strong>Tarjeta de Créditos:</strong> {{ $totalCreditCard }}</td>
    <td><strong>Débito:</strong> {{ $totalDebit }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td><strong>Transferencia:</strong> {{ $totalTransfer }}</td>
    <td><strong>Total Giftcard:</strong> {{ $totalGiftcard }}</td>
    <td><strong>Otros Méthodos:</strong> {{ $totalOther }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse;border: 1.5px solid black">
    <td colspan="3" align="center"><strong>Total Ingresado:</strong> {{ $totalMount }}</td>
</tr>
<tr height="10%" style="border-collapse: collapse; background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td><strong>Ingreso:</strong> {{ $ingreso }}</td>
    <td><strong>Gasto:</strong> {{ $gasto }}</td>
    <td><strong>Utilidad:</strong> {{ $utilidad }}</td>
</tr>
</table>

<br><br>

<table width="100%" height="100%" style="border-collapse: collapse; border: 1.5px solid black">
<tr height="10%" style="border-collapse: collapse; background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black">
    <td colspan="2" align="center"><strong>Pago a profesionales por bonos de ventas de producto</strong></td>
</tr>
<tr height="10%" style="border-collapse: collapse; border: 1.5px solid black">
    <td><strong>Nombre</strong></td>
    <td><strong>Bonos de Venta de Producto</strong></td>
</tr>
@foreach($professionalBonus as $bonus)
<tr height="10%" style="border-collapse: collapse; border: 1.5px solid black">
    <td>{{ $bonus['name'] }}</td>
    <td>{{ $bonus['winProduct'] }}</td>
</tr>
@endforeach
</table>

</body>
</html>-->
