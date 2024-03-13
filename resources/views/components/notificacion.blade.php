<x-modal name="error-modal" :show="$errors->any()">
    <div class="p-6">
        <!-- Contenido del modal -->
        <h2 class="text-lg font-semibold mb-2">Alerts (Click anywere to quit)</h2>
        @if ($errors->any())
            <ul class="text-red-500">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
        @if (session('info') && session('info') != "The code is incorrect")
            <p class="text-green-500">{{ session('info') }}</p>
        @endif
    </div>
</x-modal>


