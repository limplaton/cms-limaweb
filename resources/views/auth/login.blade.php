<x-layouts.auth>
    @section('title', __('auth.login'))
    @section('subtitle', __('auth.login_subheading'))

    {{-- Login Form Start Flag --}}
    <auth-login></auth-login>
    {{-- Login Form End Flag --}}
</x-layouts.auth>
