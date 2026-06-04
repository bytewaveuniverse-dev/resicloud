<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Accesos Resicloud</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #333; line-height: 1.4; }

        /* Contenedor Principal del Header */
        .header-container {
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 2px solid #512DA8;
            padding-bottom: 10px;
        }

        .header-table {
            width: 100%;
            border: none;
            border-collapse: collapse;
        }

        .header-table td {
            border: none;
            vertical-align: middle;
        }

        /* Estilos del Texto del Header */
        .header-info {
            text-align: right;
        }

        .header-info h1 {
            color: #512DA8;
            margin: 0;
            font-size: 22px;
            text-transform: uppercase;
        }

        .header-info p {
            margin: 2px 0;
            font-weight: bold;
            font-size: 12px;
        }

        .logo-img {
            max-width: 130px;
            height: auto;
        }

        /* Cuerpo del Reporte - Tabla */
        .table-container { margin-bottom: 30px; }

        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th {
            background-color: #f2f2f2;
            color: #512DA8;
            border: 1px solid #ddd;
            padding: 10px 8px;
            text-align: left;
            text-transform: uppercase;
            font-size: 10px;
        }
        .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        /* Resaltar la contraseña por defecto */
        .badge-acceso {
            font-weight: bold;
            color: #d32f2f;
            letter-spacing: 1px;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: -10px;
            width: 100%;
            text-align: center;
            font-size: 9px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <table class="header-table">
            <tr>
                <td style="width: 30%;">
                    <img src="{{ public_path('images/logo.png') }}" class="logo-img">
                </td>
                <td style="width: 70%;" class="header-info">
                    <h1>RESICLOUD</h1>
                    <p>REPORTE DE CREDENCIALES DE ACCESO</p>
                    <span style="font-size: 10px; font-weight: normal;">
                        Generado el: {{ now()->format('d/m/Y h:i A') }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 40%;">Nombre del Usuario</th>
                    <th style="width: 40%;">Correo Electrónico</th>
                    <th style="width: 20%; text-align: center;">Clave de Acceso</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $usuario)
                    <tr>
                        <td>{{ strtoupper($usuario->name) }}</td>
                        <td>{{ strtolower($usuario->email) }}</td>
                        <td style="text-align: center;" class="badge-acceso">admin123</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 20px;">No hay usuarios registrados en el sistema.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        Resicloud - Gestión Inteligente de Condominios | Reporte Confidencial<br>
        Emitido por: {{ auth()->user()->name ?? 'Administrador' }}
    </div>
</body>
</html>
