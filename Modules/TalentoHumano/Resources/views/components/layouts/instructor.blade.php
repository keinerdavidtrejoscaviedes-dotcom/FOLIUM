@props(['title' => null])

{{-- Dashboard del Instructor: el menú lateral es el mismo que se mantiene en las demás páginas del rol (ver layouts.master) --}}
<x-talentohumano::layouts.master :title="$title" :full-width="true">
    <x-talentohumano::instructor-menu>
        {{ $slot }}
    </x-talentohumano::instructor-menu>
</x-talentohumano::layouts.master>
