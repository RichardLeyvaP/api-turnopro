<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta de Stock Bajo</title>
</head>
<body style="font-family: Arial, sans-serif;">

<!-- Encabezado -->
<div style="margin-bottom: 20px;">
    <div style="text-align: left;">
        
    <strong>Sucursal:</strong> {{ $branch['name'] }}<br>
        <strong>Almacén:</strong> {{ $store['address'] }}<br>
        <strong>Fecha:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y') }}<br>
    </div>
</div>

<!-- Título de la Alerta -->
<div style="text-align: left; margin-bottom: 10px;">
    <strong>Alerta de Stock Bajo</strong>
</div>

<!-- Información del Producto -->
<div style="margin-bottom: 10px;">
    <p>El siguiente producto está cerca de agotarse en el inventario:</p>
</div>

<!-- Detalles del Producto -->
<table width="60%" style="border-collapse: collapse; border: 1.5px solid black;">
<tr style="background-color: rgba(68, 112, 243, 0.85); border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;"><strong>Producto</strong></td>
        <td style="padding: 5px; text-align: right; line-height: 1;"><strong>Existencia</strong></td>
    </tr>
    <tr style="border: 1.5px solid black;">
        <td style="padding: 5px; text-align: left; line-height: 1;">{{ $product->name }}</td>
        <td style="padding: 5px; text-align: right; line-height: 1;">{{ $productstoreexist }} unidades</td>
    </tr>
</table>

<!-- Mensaje Final -->
<div style="margin-top: 20px;">
    <p>Le recomendamos realizar las acciones necesarias para reabastecer este producto lo antes posible.</p>
</div>

<!-- Firma -->
<div style="margin-top: 20px;">
    <p>Gracias</p>
</div>

</body>
</html>
