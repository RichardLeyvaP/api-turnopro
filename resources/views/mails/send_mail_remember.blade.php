<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holaaa.. estamos aqui para Recordar su Reserva</title>
</head>
<body style="font-family: Arial, sans-serif;">

<h2 style="color: #F18254;">Hola {{$client_name}} &#x1F44B;,</h2>

<p style="color: #333;">
    &#x1F64F; ¡Gracias por elegir Simplifies! Estamos encantados de confirmar tu reserva para el siguiente servicio:
</p>
<img src="{{$logoUrl}}" alt="Descripción de la imagen" style="height: auto; width: auto;">

<ul>
    <li><strong>Fecha de Reserva:</strong> {{$data}}</li>
    <li><strong>Hora de Reserva:</strong> {{$start_time}}</li>
    <li><strong>Barbero:</strong> {{$name_professional}}</li>
    <li><strong>Sucursal:</strong> {{$branch_name}}</li>
    <li><strong>Dirección:</strong> {{$branch_address}}</li>
</ul>

<p style="color: #555;">
    &#x1F603; Estamos ansiosos de brindarte una experiencia excepcional en nuestro salón.
</p>

<p style="color: #555;">
    <span style="font-size: 1em; font-weight: bold;">¡IMPORTANTE!</span><br>
Puede llegar 10 minutos antes o despues de la hora indicada y debe anunciarse en la caja para que se sitúe de primero en la lista de espera y así su barbero lo pueda atender después del servicio que este realizando.
</p>

<p style="color: #555;">
    Si tienes alguna pregunta o necesitas cambiar tu reserva, no dudes en ponerte en contacto con nosotros. ¡Estamos aquí para ayudarte!
</p>

<p style="color: #555;">
    &#x1F917; Por favor, has click en el botón de abajo para confirmar tu reserva con id={{$id_reservation}}.
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
    &#x1F917; Gracias de nuevo por confiar en nosotros. ¡Esperamos verte pronto!
</p>

<p style="color: #555;">
    Atentamente,<br>
    El equipo de <strong> Simplifies </strong><br>
    Teléfono: (+56) 920202023
    Correo: reservas@simplifies.cl
</p>

</body>
</html>
