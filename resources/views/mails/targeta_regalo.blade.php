<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignación de Tarjeta de Regalo</title>
</head>
<body style="font-family: Arial, sans-serif;">

<h2 style="color: #F18254;">Hola {{$client_name}} &#x1F44B;,</h2>

<p style="color: #333;">
    Te informamos que se te ha asignado una tarjeta de regalo. A continuación, te proporcionamos los detalles:
</p>

    <img src="{{$image_cardgift}}" alt="Descripción de la imagen" style="height: 150px; width: 280px;">


<ul>
    <li><strong>Fecha de Expiración:</strong> {{$expiration_date}} </li>
    <li><strong>Código de la Tarjeta:</strong> {{$code}} </li>
    <li><strong>Valor:</strong>  {{$value_card}} </li>
</ul>

<p style="color: #555;">
    Utiliza el código proporcionado para canjear tu tarjeta de regalo en nuestra tienda en línea.
</p>

<p style="color: #555;">
    Si tienes alguna pregunta o necesitas asistencia adicional, no dudes en ponerte en contacto con nosotros.
</p>

<p style="color: #555;">
    Atentamente,<br>
    El equipo de <strong> Simplifies </strong><br>
    Teléfono: (+56) 920202023<br>
    Correo: reservas@simplifies.cl
</p>

</body>
</html>
