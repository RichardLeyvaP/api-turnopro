<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asunto: Recordatorio de tu cita en {{$branch_name}}
    </title>
</head>
<body style="font-family: Arial, sans-serif;">

<h2 style="color: #F18254;">Asunto: Recordatorio de tu cita en {{$branch_name}} &#x1F44B;,</h2><br>
<h2 style="color: #F18254;">Estimado/a {{$client_name}} &#x1F44B;,</h2>

<p style="color: #333;">
    &#x1F64F; ¡Esperamos que estés teniendo un excelente día!
</p>
<img src="{{$logoUrl}}" alt="Descripción de la imagen" style="height: 300px; width: 300px;">

<p style="color: #333;">
    &#x1F64F; Queremos recordarte que tienes una cita programada en {{$branch_name}} mañana. Aquí están los detalles de tu reserva:
</p>

<ul>
    <li><strong>Fecha de Reserva:</strong> {{$data}}</li>
    <li><strong>Hora de Reserva:</strong> {{$start_time}}</li>
    <li><strong>Barbero:</strong> {{$name_professional}}</li>
    <li><strong>Sucursal:</strong> {{$branch_name}}</li>
    <li><strong>Dirección:</strong> {{$branch_address}}</li>
    <li><strong>Código de Reserva:</strong> {{$code_reserva}}</li>
</ul>

<p style="color: #555;">
    &#x1F603; En {{$branch_name}}, nos esforzamos por ofrecerte una experiencia de primera clase y estamos ansiosos por atenderte.
</p>

<p style="color: #555;">
    <span style="font-size: 1em; font-weight: bold;">¡IMPORTANTE!</span><br>
Puede llegar 10 minutos antes o despues de la hora indicada y debe anunciarse en la caja para que se sitúe de primero en la lista de espera y así su barbero lo pueda atender después del servicio que este realizando.
</p>

<p style="color: #555;">
    Recuerda anunciar tu llegada en nuestra sucursal.
</p>

<p style="color: #555;">
    &#x1F917; Por favor, has click en los botones de abajo para confirmar o cancelar tu reserva.
</p>

<!-- Botón de Confirmación -->
<a href="https://api2.simplifies.cl/api/update-confirmation?id={{$id_reservation}}&confirmation=1" style="text-decoration: none;">
    <button style="background-color: #4470F3; color: #FFFFFF; border: none; border-radius: 6px; padding: 12px 24px; font-size: 16px;">
        CONFIRMACIÓN
    </button>
</a>
<a href="https://api2.simplifies.cl/api/update-confirmation?id={{$id_reservation}}&confirmation=3" style="text-decoration: none;">
    <button style="background-color: #F34444; color: #FFFFFF; border: none; border-radius: 6px; padding: 12px 24px; font-size: 16px;">
        CANCELAR
    </button>
</a>

<p style="color: #555;">
    Atentamente,<br>
    El equipo de <strong> Simplifies </strong><br>
    Teléfono: (+56) 920202023
    Correo: reservas@simplifies.cl
</p>

</body>
</html>
