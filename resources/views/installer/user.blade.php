<x-layouts.installer>
    <form action="{{ url('install/user') }}" id="user-form" method="POST">
        @csrf
        <div class="p-3">
            <h5 class="my-5 text-lg font-semibold text-neutral-800">Configure Admin User</h5>
            <div class="space-y-6 sm:space-y-5">
                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:border-t sm:border-neutral-200 sm:pt-5">
                    <label for="inputUserName" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                        <span class="mr-1 text-sm text-danger-600">*</span>Name (Full Name)
                    </label>
                    <div class="mt-1 sm:col-span-2 sm:mt-0">
                        <input type="text" name="name" placeholder="Enter your full name"
                            value="{{ old('name') }}"
                            class="block w-full rounded-md border border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            id="inputUserName">
                        @error('name')
                            <p class="mt-2 text-sm text-danger-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:border-t sm:border-neutral-200 sm:pt-5">
                    <label for="inputEmail" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                        <span class="mr-1 text-sm text-danger-600">*</span>E-Mail Address
                    </label>
                    <div class="mt-1 sm:col-span-2 sm:mt-0">
                        <input type="email" value="{{ old('email') }}" name="email"
                            placeholder="Enter your email address that will be used for login"
                            class="block w-full rounded-md border border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            id="inputEmail">
                        @error('email')
                            <p class="mt-2 text-sm text-danger-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:border-t sm:border-neutral-200 sm:pt-5">
                    <label for="inputTimezone" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                        <span class="mr-1 text-sm text-danger-600">*</span>Timezone
                    </label>
                    <div class="mt-1 sm:col-span-2 sm:mt-0">
                        <select name="timezone" id="inputTimezone"
                            class="block w-full rounded-md border border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value=""></option>
                            @foreach (tz()->all() as $timezone)
                                <option value="{{ $timezone }}"
                                    {{ old('timezone') === $timezone ? 'selected' : '' }}>
                                    {{ $timezone }}</option>
                            @endforeach
                        </select>
                        @error('timezone')
                            <p class="mt-2 text-sm text-danger-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:border-t sm:border-neutral-200 sm:pt-5">
                    <label for="inputPassword" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                        <span class="mr-1 text-sm text-danger-600">*</span>Password
                    </label>
                    <div class="mt-1 sm:col-span-2 sm:mt-0">
                        <input type="password" name="password" placeholder="Login password" autocomplete="new-password"
                            class="block w-full rounded-md border border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            id="inputPassword">
                        @error('password')
                            <p class="mt-2 text-sm text-danger-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:border-t sm:border-neutral-200 sm:pt-5">
                    <label for="inputPasswordConfirm"
                        class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                        <span class="mr-1 text-sm text-danger-600">*</span>Confirm Password
                    </label>
                    <div class="mt-1 sm:col-span-2 sm:mt-0">
                        <input type="password" name="password_confirmation" autocomplete="new-password"
                            placeholder="Confirm login password"
                            class="block w-full rounded-md border border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            id="inputPasswordConfirm">
                        @error('password_confirmation')
                            <p class="mt-2 text-sm text-danger-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="-m-7 -mb-11 mt-6 rounded-b border-t border-neutral-200 bg-neutral-50 p-4 text-right">
            <button type="submit"
                class="inline-flex items-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:pointer-events-none disabled:opacity-60 hover:bg-primary-700"
                id="btn-install">Install</button>
        </div>
    </form>

    <script>
        document.addEventListener("DOMContentLoaded", function(event) {
            if (typeof Intl == 'object' && typeof Intl.DateTimeFormat == 'function') {
                var userDetectedTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                var timezoneFromInput = document.querySelector('#inputTimezone [value="' + userDetectedTimezone +
                    '"]');
                if (timezoneFromInput) {
                    document.getElementById('inputTimezone').value = userDetectedTimezone
                }
            }

            document.getElementById('user-form').onsubmit = function(e) {
                document.getElementById('btn-install').disabled = true;
                document.getElementById('btn-install').innerText = 'Please wait...';
            }
        });
    </script>
</x-layouts.installer>
