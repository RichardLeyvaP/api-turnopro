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
<h2 style="color: white;">Cierre de Caja Mensual</h2>
            <strong>{{ $branchBusinessName }}</strong><br>
            @if($typeClose === 'Sucursal')
            Sucursal: {{ $branchName }}<br>
            @endif
        Mes: {{ $monthName }}<br>
        Realizado: {{ $boxData }}<br>
        </div>
        <!-- Encabezado -->

       <br>
        <div class="section-header">
            <h3>Cierre de las Cuentas y Formas de Pago</h3>
        </div>
        <table>
          
            <tr style="background-color: rgba(68, 112, 243, 0.85); border: 1.5px solid black;">
        
         <td style="padding: 5px; text-align: left; line-height: 1; color: white;"><strong>Tipos de ingreso</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1; color: white;"><strong>Valor</strong></td>
    </tr>
     <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Propinas</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($boxcloseData['totalTip'], 2), 2) }}</td>
    </tr>
        </tr>
     <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Venta de Productos</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($boxcloseData['totalProduct'], 2), 2) }}</td>
    </tr>
     <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Prestación de Servicios</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($boxcloseData['totalService'], 2), 2) }}</td>
    </tr>

        </table>
        <table>
            <tr style="background-color: rgba(68, 112, 243, 0.85); border: 1.5px solid black;">
                  <td style="padding: 5px; text-align: left; line-height: 1;color: white;"><strong>Métodos de pago</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;color: white;"><strong>Valor</strong></td>
            </tr>
         
            
              <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Efectivo</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($boxcloseData['totalCash'], 2), 2) }}</td>
    </tr>
                
              <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Tarjeta de Créditos</td>
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
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($boxcloseData['totalGiftcard'], 2), 2) }}</td>
    </tr>
              <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Otros Métodos</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($boxcloseData['totalOther'], 2), 2) }}</td>
    </tr>
    
              <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Total Ingresado</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($boxcloseData['totalMount'], 2), 2) }}</td>
    </tr>
    
           
           
            
        </table>
        <table>
               <tr style="background-color: rgba(68, 112, 243, 0.85); border: 1.5px solid black;">
        
         <td style="padding: 5px; text-align: left; line-height: 1;color: white;"><strong>Resumen del cierre de mes</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;color: white;"><strong>Valor</strong></td>
    </tr>
    
            <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Dinero Disponible</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($editedItem['available_money'], 2), 2) }}</td>
    </tr>
            <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Utilidad</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($editedItem['utility'], 2), 2) }}</td>
    </tr>
    
        <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Retención</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($editedItem['retention'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">-Descuentos</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($editedItem['discounts'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Diferencias</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($editedItem['differences'], 2), 2) }}</td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">Utilidad Final</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($editedItem['net_utility'], 2), 2) }}</td>
    </tr>
        </table>
        <table>
            <tr style="background-color: rgba(68, 112, 243, 0.85); border: 1.5px solid black;">
                  <td style="padding: 5px; text-align: left; line-height: 1;color: white;"><strong>Realizado por: </strong></td>
                <td style="padding: 5px; text-align: right; line-height: 1;color: white;"><strong> {{ $nameProfessional }} </strong></td>
            </tr>
        </table>
       
        <div class="footer">
            <p>Gracias por su colaboración.</p>
        </div>
    </div>
</body>
</html>
