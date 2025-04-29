<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago de Bono de Productos a Profesionales</title>
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
            padding: 5px;
            text-align: left;
            border-radius: 10px 10px 0 0;
        }
        .header {
            border-radius: 10px;
        }
        .header strong {
            display: block;
            color: white;
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
<h2 style="color: white;">Pago de Bono de Productos a Profesionales</h2>
            <strong>{{ $branchBusinessName }}</strong><br>
        Sucursal: {{ $branchName }}<br>
        Fecha: {{ $boxData }}<br>
        </div>
        <!-- Encabezado -->

       <br>
        
        <div class="section-header">
            <h3>Pago a profesionales por bonos de ventas de producto</h3>
        </div>
        <table>
                 <tr style="background-color: rgba(68, 112, 243, 0.85); border: 1.5px solid black;">
        
         <td style="padding: 5px; text-align: left; line-height: 1;color: white;"><strong>Nombre</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;color: white;"><strong>Valor</strong></td>
    </tr>
    
    @foreach($professionalBonus as $bonus)
            <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">{{ $bonus['name'] }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($bonus['winProduct'], 2), 2) }}</td>
    </tr>
     @endforeach
       
        </table>
        <!--<div class="footer">
            <p>Gracias por su colaboración.</p>
        </div>-->
    </div>
</body>
</html>
