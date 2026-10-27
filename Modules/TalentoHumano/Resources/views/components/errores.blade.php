{{-- Lista de errores de validación del formulario (RNF-016: indicar qué falta y cómo resolverlo). --}}
@if ($errors->any())
    <div class="mb-6 rounded-2xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200" role="alert">
        <p class="font-semibold"><i class="fas fa-circle-exclamation mr-1" aria-hidden="true"></i> Revise los siguientes puntos:</p>
        <ul class="mt-2 list-disc space-y-0.5 pl-5">
            @foreach (array_unique($errors->all()) as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
