<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solare — Acceso</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen bg-white font-sans">

    <div class="solare-sidebar">
        <div class="absolute w-[360px] h-[360px] rounded-full border border-white/5 -top-[80px] -right-[80px]"></div>
        <div class="absolute w-[240px] h-[240px] rounded-full border border-white/5 bottom-[50px] -left-[40px]"></div>

        <div class="z-10">
            <div class="font-serif-solare text-[30px] font-light text-white tracking-[4px]">SOLARE</div>
            <div class="text-[8px] text-white/40 tracking-[3px] uppercase mt-[2px]">Muebles de Exterior</div>
        </div>

        <div class="z-10">
            <div class="font-serif-solare text-[38px] font-light text-white leading-[1.25] mb-4">
                Diseñado para<br/>vivir afuera.
            </div>
            <p class="text-[13px] text-white/55 leading-[1.75] max-w-[280px]">
                Gestiona tu inventario, catálogo y ventas desde un solo lugar.
            </p>
        </div>

        <div class="z-10 text-[10px] text-white/30">© 2026 Solare — Muebles de Exterior</div>
    </div>

    <div class="w-full lg:w-[440px] flex flex-col justify-center p-[48px]">
        <div class="mb-8">
            <div class="text-[9px] font-semibold tracking-[1.5px] uppercase text-[#757575] mb-1">Plataforma Interna</div>
            <h1 class="font-serif-solare text-[32px] font-normal mb-[5px]">Iniciar sesión</h1>
            <p class="text-[12px] text-[#757575]">Ingresa tus credenciales para continuar</p>
        </div>

        <form action="{{ route('loginScreen.login') }}" method="POST" class="flex flex-col gap-4">
            @csrf
            <div>
                <label class="text-[9px] font-semibold tracking-[1.5px] uppercase text-[#757575] block mb-[6px]">Correo electrónico</label>
                <input type="email" name="email" placeholder="admin@solare.mx" class="solare-input" required>
            </div>

            <div>
                <label class="text-[9px] font-semibold tracking-[1.5px] uppercase text-[#757575] block mb-[6px]">Contraseña</label>
                <div class="relative">
                    <input type="password" name="password" placeholder="••••••••" class="solare-input pr-10" required>
                    <button type="button" class="absolute right-[11px] top-1/2 -translate-y-1/2 text-[#757575] text-[12px]">○</button>
                </div>
            </div>

            <div class="text-right">
                <button type="button" class="text-[11px] text-solare-clay hover:underline bg-transparent border-none cursor-pointer italic">¿Olvidaste tu contraseña?</button>
            </div>

            <button type="submit" class="solare-btn-p">Ingresar al sistema</button>
        </form>
    </div>

</body>
</html>