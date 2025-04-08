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
        <td style="padding: 5px; text-align: left; line-height: 1;">Total Métodos de Pago</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ number_format(round($boxcloseData['totalMount'], 2), 2) }}</td>
    </tr>
    
           
           
            
        </table>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="background-color: rgba(68, 112, 243, 0.85); border: 1.5px solid black;">
                <td style="padding: 5px; text-align: left; line-height: 1;color: white;" colspan="3"><strong>Resumen del cierre de mes</strong></td>
            </tr>
            <!-- Encabezado -->
            <tr style="background-color: rgba(68, 112, 243, 0.85); border: 1.5px solid black;">
                <th style="padding: 8px; text-align: center; line-height: 1; color: white;">Operación</th>
                <th style="padding: 8px; text-align: center; line-height: 1; color: white;">Datos del Administrador</th>
                <th style="padding: 8px; text-align: center; line-height: 1; color: white;">Datos del Sistema</th>
                <th style="padding: 8px; text-align: center; line-height: 1; color: white;">Diferencias</th>
            </tr>
            
            <!-- Ingresos -->
            <tr style="border: 1.5px solid black;">
                <td style="padding: 8px; text-align: center; line-height: 1;">Ingresos</td>
                <td style="padding: 8px; text-align: center; line-height: 1;">
                    {{ number_format(round($editedItem['available_money'], 2), 2) }}
                </td>
                <td style="padding: 8px; text-align: center; line-height: 1;">
                    {{ number_format(round($editedItem['system_incomes'], 2), 2) }}
                </td>
                <td style="padding: 8px; text-align: center; line-height: 1; color: {{ $editedItem['difference_incomes'] >= 0 ? 'black' : 'red' }};">
                    {{ number_format(round($editedItem['difference_incomes'], 2), 2) }}
                </td>
            </tr>
            
            <!-- Gastos -->
            <tr style="border: 1.5px solid black;">
                <td style="padding: 8px; text-align: center; line-height: 1;">Gastos</td>
                <td style="padding: 8px; text-align: center; line-height: 1;">
                    {{ number_format(round($editedItem['discounts'], 2), 2) }}
                </td>
                <td style="padding: 8px; text-align: center; line-height: 1;">
                    {{ number_format(round($editedItem['spent'], 2), 2) }}
                </td>
                <td style="padding: 8px; text-align: center; line-height: 1; color: {{ $editedItem['difference_spent'] >= 0 ? 'black' : 'red' }};">
                    {{ number_format(round($editedItem['difference_spent'], 2), 2) }}
                </td>
            </tr>
            
            <!-- Utilidad -->
            <tr style="border: 1.5px solid black;">
                <td style="padding: 8px; text-align: center; line-height: 1;">Utilidad</td>
                <td style="padding: 8px; text-align: center; line-height: 1;">
                    {{ number_format(round($editedItem['client_utility'], 2), 2) }}
                </td>
                <td style="padding: 8px; text-align: center; line-height: 1;">
                    {{ number_format(round($editedItem['utility'], 2), 2) }}
                </td>
                <td style="padding: 8px; text-align: center; line-height: 1; color: {{ $editedItem['difference_utility'] >= 0 ? 'black' : 'red' }};">
                    {{ number_format(round($editedItem['difference_utility'], 2), 2) }}
                </td>
            </tr>
            
            <!-- Retención -->
            <tr style="border: 1.5px solid black;">
                <td style="padding: 8px; text-align: center; line-height: 1;">Retención</td>
                <td style="padding: 8px; text-align: center; line-height: 1;">
                    {{ number_format(round($editedItem['client_retention'], 2), 2) }}
                </td>
                <td style="padding: 8px; text-align: center; line-height: 1;">
                    {{ number_format(round($editedItem['retention'], 2), 2) }}
                </td>
                <td style="padding: 8px; text-align: center; line-height: 1; color: {{ $editedItem['difference_retention'] >= 0 ? 'black' : 'red' }};">
                    {{ number_format(round($editedItem['difference_retention'], 2), 2) }}
                </td>
            </tr>
            
            <!-- Diferencia Total -->
            <tr>
                <td colspan="4" style="padding: 8px; text-align: left; line-height: 1; background-color: #f0f0f0; font-weight: bold; color: {{ $editedItem['differences'] >= 0 ? 'black' : 'red' }};">
                    Diferencia Total: {{ number_format(round($editedItem['differences'], 2), 2) }}
                </td>
            </tr>
            
            <!-- Descripción -->
            <tr>
                <td colspan="4" style="padding: 8px; text-align: left; line-height: 1;">
                    <strong>¿Por qué?</strong><br>
                    {{ $editedItem['description'] ?? 'Sin descripción' }}
                </td>
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
