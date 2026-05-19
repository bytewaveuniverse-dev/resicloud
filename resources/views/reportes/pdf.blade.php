<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Cuentas Resicloud</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        
        /* Contenedor Principal del Header */
        .header-container {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #512DA8; /* Única línea divisoria */
            padding-bottom: 10px;
        }

        .header-table {
            width: 100%;
            border: none;
            border-collapse: collapse;
        }

        .header-table td {
            border: none;
            vertical-align: middle; /* Centra el logo con el texto verticalmente */
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
            max-width: 130px; /* Tamaño ajustado para que destaque */
            height: auto;
        }

        /* Filtros */
        .filtros-banner {
            text-align: center; 
            font-size: 10px; 
            color: #555; 
            margin-top: 8px;
            font-style: italic;
        }
        
        /* Cuerpo del Reporte */
        .seccion-vecino { margin-bottom: 30px; page-break-inside: avoid; }
        .nombre-vecino { background-color: #512DA8; color: white; padding: 6px 12px; font-size: 12px; font-weight: bold; border-radius: 3px; }
        
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th { background-color: #f2f2f2; color: #512DA8; border: 1px solid #ddd; padding: 8px; text-align: left; text-transform: uppercase; font-size: 10px; }
        .table td { border: 1px solid #ddd; padding: 7px; text-align: left; }
        
        /* Totales */
        .subtotal-fila { background-color: #f9f9f9; font-weight: bold; font-size: 11px; }
        .monto-total-vecino { color: #d32f2f; text-align: right; border-top: 1px solid #512DA8 !important; }
        
        .total-general-caja { 
            margin-top: 30px; 
            padding: 15px; 
            border: 2px dashed #512DA8; 
            text-align: right; 
            font-size: 15px; 
            font-weight: bold; 
            background-color: #f3f0ff; 
        }
        
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
                    <p>REPORTE ADMINISTRATIVO DE CUENTAS</p>
                    <span style="font-size: 10px; font-weight: normal;">
                        Generado el: {{ now()->format('d/m/Y h:i A') }}
                    </span>
                </td>
            </tr>
        </table>
        
        @if($f_inicio || $nombre || $estado || $tipo) {{-- Añade $tipo --}}
            <div class="filtros-banner">
                <strong>Filtros aplicados:</strong> 
                @if($nombre) [Propietario: {{ $nombre }}] @endif
                @if($tipo) [Tipo: {{ strtoupper($tipo) }}] @endif {{-- Nuevo --}}
                @if($estado) [Estado: {{ strtoupper($estado) }}] @endif
                @if($f_inicio) [Periodo: {{ \Carbon\Carbon::parse($f_inicio)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($f_fin)->format('d/m/Y') }}] @endif
            </div>
        @endif

    </div>

    @foreach($asientosAgrupados as $nombreVecino => $registros)
        <div class="seccion-vecino">
            <div class="nombre-vecino">Usuario: {{ strtoupper($nombreVecino) }}</div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Fecha</th>
                        <th style="width: 45%;">Concepto / Descripción</th>
                        <th style="width: 20%;">Estado</th>
                        <th style="width: 20%; text-align: right;">Monto ($)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($registros as $asiento)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($asiento->fecha)->format('d/m/Y') }}</td>
                        <td>{{ $asiento->descripcion }}</td>
                        <td style="font-size: 9px;">{{ strtoupper($asiento->estado) }}</td>
                        <td style="text-align: right;">${{ number_format($asiento->monto_dolares, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="subtotal-fila">
                        <td colspan="3" style="text-align: right; padding-right: 15px;">SUB-TOTAL PROPIETARIO:</td>
                        <td class="monto-total-vecino">
                            ${{ number_format($registros->sum('monto_dolares'), 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endforeach

    <div class="total-general-caja">
        MONTO TOTAL DEL REPORTE: ${{ number_format($totalUsd, 2) }}
    </div>

    <div class="footer">
        Resicloud - Gestión Inteligente de Condominios | Reporte de Auditoría Interna<br>
        Emitido por: {{ auth()->user()->name }} (Ingeniero en Computación)
    </div>
</body>
</html>