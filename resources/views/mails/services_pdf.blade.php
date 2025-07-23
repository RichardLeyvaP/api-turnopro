<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sucursales y Servicios</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }
        h1, h2, h3 {
            margin: 10px 0 5px;
        }
        .branch {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        th, td {
            border: 1px solid #999;
            padding: 6px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background-color: #f0f0f0;
        }
        .section-title {
            background-color: #eaeaea;
            padding: 5px;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <h1>Sucursales, Servicios y Profesionales</h1>

    @foreach($branches as $branch)
        <div class="branch">
            <h2>Sucursal: {{ $branch['sucursal']['nombre'] }} (ID: {{ $branch['sucursal']['id'] }})</h2>
            <p><strong>Teléfono:</strong> {{ $branch['sucursal']['telefono'] }}</p>
            <p><strong>Dirección:</strong> {{ $branch['sucursal']['direccion'] }}</p>
            <p><strong>Imagen:</strong> {{ $branch['sucursal']['imagen'] ?? 'N/A' }}</p>

            {{-- Tabla de servicios de la sucursal --}}
            <div class="section-title"><strong>Servicios ofrecidos en la sucursal</strong></div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Comentario</th>
                        <th>Imagen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($branch['servicios'] as $servicio)
                        <tr>
                            <td>{{ $servicio['id'] }}</td>
                            <td>{{ $servicio['nombre'] }}</td>
                            <td>${{ number_format($servicio['precio'], 0, ',', '.') }}</td>
                            <td>{{ $servicio['comentario'] }}</td>
                            <td>{{ $servicio['imagen'] ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Profesionales --}}
            @if(count($branch['profesionales']) > 0)
                <div class="section-title"><strong>Profesionales en esta sucursal</strong></div>
                @foreach($branch['profesionales'] as $pro)
                    <h3>Profesional: {{ $pro['nombre'] }} (ID: {{ $pro['id'] }})</h3>
                    <p><strong>Email:</strong> {{ $pro['email'] }} | <strong>Teléfono:</strong> {{ $pro['telefono'] }}</p>
                    <p><strong>Imagen:</strong> {{ $pro['imagen'] ?? 'N/A' }}</p>

                    {{-- Tabla de servicios que realiza el profesional --}}
                    <table>
                        <thead>
                            <tr>
                                <th>ID del Servicio</th>
                                <th>Nombre del Servicio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pro['servicios_que_realiza'] as $servicio)
                                <tr>
                                    <td>{{ $servicio['id'] }}</td>
                                    <td>{{ $servicio['nombre'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            @else
                <p>No hay profesionales registrados en esta sucursal.</p>
            @endif

        </div>
    @endforeach

</body>
</html>
