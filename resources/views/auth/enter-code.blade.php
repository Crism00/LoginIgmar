@include('components.notificacion')
<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('codeVerification') }}">
        @csrf

        <!-- Email Address -->
        <div class="flex flex-col items-center justify-center">
            <div class="mb-4">
                <x-input-label for="code" :value="__('code')" />
                <x-text-input id="code" class="block mt-1" type="int" name="code" :value="old('code')" required autofocus autocomplete="" maxlength="4" style="width: 40px; display: flex; text-align: center;" />
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>

            <div class="flex items-center justify-center mt-4">
                <x-primary-button>
                    {{ __('Veirificar Codigo') }}
                </x-primary-button>
            </div>
        </div>
    </form>
    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <x-dropdown-link :href="route('logout')"
                onclick="event.preventDefault();
                            this.closest('form').submit();">
            {{ __('Log Out') }}
        </x-dropdown-link>
    </form>
    <form method="POST" action="{{ route('resendCode') }}">
        @csrf

        <x-dropdown-link :href="route('resendCode')"
                onclick="event.preventDefault();
                            this.closest('form').submit();">
            {{ __('Reenviar Codigo') }}
        </x-dropdown-link>
    </form>
</x-guest-layout>

