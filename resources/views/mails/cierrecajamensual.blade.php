<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago de Bono de Productos a Profesionales</title>
</head>

<body style="font-family: Arial, sans-serif;">

    <!-- Encabezado -->
    <div style="margin-bottom: 20px;">
        <div style="text-align: left;">
            <strong>{{ $branchBusinessName }}</strong><br>
            Sucursal: {{ $branchName }}<br>
            Fecha: {{ $boxData }}<br>
        </div>
    </div>

    <!-- Cuarta Tabla: Bonos de Ventas -->
    <div style="text-align: left; margin-bottom: 10px;">
        <strong>Pago a profesionales por bono de venta de productos</strong>
    </div>
    <table width="100%" style="border-collapse: collapse; border: 1.5px solid black;">

        <!-- Encabezado -->
        <tr style="background-color: rgba(0, 0, 0, 0.1); border: 1.5px solid black;">
            <td style="padding: 5px; line-height: 1;"><strong>Nombre</strong></td>
            <td style="padding: 5px; text-align: right; line-height: 1;"><strong>Valor</strong></td>
        </tr>
        @foreach ($professionalBonus as $bonus)
            <tr style="border: 1.5px solid black;">
                <td style="padding: 5px; line-height: 1;">{{ $bonus['name'] }}</td>
                <td style="padding: 5px; text-align: right; line-height: 1;">
                    {{ number_format(round($bonus['winProduct'], 2), 2) }}</td>
            </tr>
        @endforeach
    </table>

</body>

</html>
