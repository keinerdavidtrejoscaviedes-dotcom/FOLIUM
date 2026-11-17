<x-talentohumano::layouts.master title="Actas por firmar">
    <x-talentohumano::encabezado titulo="Actas de recibimiento por firmar" :volver="route('talentohumano.panel')" hu="HU-057C"
        subtitulo="Actas de los empalmes en los que usted firma como instructor del área o jefe inmediato del cargo. Cada firmante firma desde su propia cuenta." />

    @include('talentohumano::actas-firma._lista')
</x-talentohumano::layouts.master>
