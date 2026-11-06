@php
    $metodos = [
        'efectivo' => 'Efectivo',
        'transferencia' => 'Transferencia',
        'debito' => 'Tarjeta de débito',
        'credito' => 'Tarjeta de crédito',
        'mercado_pago' => 'Mercado Pago',
    ];
    $pesos = fn ($monto) => '$ '.number_format((float) $monto, 2, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo N.º {{ $numero }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #222; }
        table { width: 100%; border-collapse: collapse; }
        .encabezado td { vertical-align: top; }
        .logo { max-height: 60px; max-width: 180px; }
        .gimnasio { font-size: 14px; font-weight: bold; }
        .titulo { text-align: right; }
        .titulo h1 { font-size: 20px; margin: 0; letter-spacing: 1px; }
        .numero { font-size: 12px; color: #555; }
        .separador { border-top: 2px solid #1e40af; margin: 14px 0; }
        .seccion { font-size: 10px; text-transform: uppercase; color: #1e40af; font-weight: bold; margin: 0 0 6px; }
        .datos td { padding: 3px 0; }
        .datos .etiqueta { width: 35%; color: #555; }
        .importes { margin-top: 6px; }
        .importes td { padding: 5px 8px; border-bottom: 1px solid #ddd; }
        .importes .monto { text-align: right; width: 35%; }
        .importes .total td { border-bottom: none; border-top: 2px solid #222; font-size: 13px; font-weight: bold; }
        .leyenda { margin-top: 24px; padding: 8px; border: 1px solid #999; text-align: center; font-size: 10px; color: #555; }
    </style>
</head>
<body>
    <table class="encabezado">
        <tr>
            <td>
                @if ($logo)
                    <img src="{{ $logo }}" class="logo" alt=""><br>
                @endif
                <div class="gimnasio">{{ $gimnasio['nombre'] }}</div>
                @if ($gimnasio['direccion'])<div>{{ $gimnasio['direccion'] }}</div>@endif
                @if ($gimnasio['telefono'])<div>Tel.: {{ $gimnasio['telefono'] }}</div>@endif
            </td>
            <td class="titulo">
                <h1>RECIBO</h1>
                <div class="numero">N.º {{ $numero }}</div>
            </td>
        </tr>
    </table>

    <div class="separador"></div>

    <p class="seccion">Socio</p>
    <table class="datos">
        <tr><td class="etiqueta">Nombre</td><td>{{ $pago->socio->user->nombre }} {{ $pago->socio->user->apellido }}</td></tr>
        <tr><td class="etiqueta">DNI</td><td>{{ $pago->socio->user->dni }}</td></tr>
    </table>

    <div class="separador"></div>

    <p class="seccion">Pago</p>
    <table class="datos">
        <tr><td class="etiqueta">Plan</td><td>{{ $pago->plan->nombre }}</td></tr>
        <tr><td class="etiqueta">Mes abonado</td><td>{{ ucfirst($pago->mes_abonado->locale('es')->translatedFormat('F Y')) }}</td></tr>
        <tr><td class="etiqueta">Fecha de pago</td><td>{{ $pago->fecha_pago->format('d/m/Y') }}</td></tr>
        <tr><td class="etiqueta">Método de pago</td><td>{{ $metodos[$pago->metodo_pago] ?? $pago->metodo_pago }}</td></tr>
    </table>

    <table class="importes">
        <tr><td>Monto del plan</td><td class="monto">{{ $pesos($pago->monto_original) }}</td></tr>
        <tr><td>Mora</td><td class="monto">{{ $pago->monto_mora === null ? 'Sin mora' : $pesos($pago->monto_mora) }}</td></tr>
        <tr class="total"><td>Total abonado</td><td class="monto">{{ $pesos($pago->total) }}</td></tr>
    </table>

    <div class="leyenda">
        Comprobante interno de pago. No válido como factura.
    </div>
</body>
</html>
