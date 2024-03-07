<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Reserva</title>
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
    </ul>

    <p style="color: #555;">
    &#x1F603; Estamos ansiosos de brindarte una experiencia excepcional en nuestro salón. Por favor,&#x231A; llega unos minutos antes de tu cita para que podamos asegurarnos de ofrecerte el mejor servicio posible.
    </p>

    <p style="color: #555;">
        Si tienes alguna pregunta o necesitas cambiar tu reserva, no dudes en ponerte en contacto con nosotros. ¡Estamos aquí para ayudarte!
    </p>

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