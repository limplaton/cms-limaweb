<x-layouts.installer>
    <form action="{{ url('install/setup') }}" id="setup-form" method="POST">
        @csrf
        <div class="p-3">
            <h5 class="my-5 text-lg font-semibold text-neutral-800">General Config</h5>

            <div class="space-y-6 sm:space-y-5">
                {{-- <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:border-t sm:border-neutral-200 sm:pt-5">
                <label for="inputPurchaseKey" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                    <span class="text-danger-600 text-sm mr-1">*</span>Purchase Key
                </label>
                <div class="mt-1 sm:mt-0 sm:col-span-2">
                    <input
                    type="text"
                    value="{{ old('purchase_key') }}"
                    name="purchase_key"
                    class="block w-full shadow-sm focus:ring-primary-500 border focus:border-primary-500 border-neutral-300 sm:text-sm rounded-md"
                    id="inputPurchaseKey">
                    <p class="mt-2 text-sm text-neutral-500">* Enter your license purchase key, <a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-" class="link" target="_blank" rel="noopener noreferrer">find</a> your purchase key in your Envato account downloads page.</p>
                    @error('purchase_key')
                    <p class="mt-2 text-sm text-danger-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>
            </div> --}}

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:border-t sm:border-neutral-200 sm:pt-5">
                    <label for="appUrlName" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                        <span class="mr-1 text-sm text-danger-600">*</span>App URL
                    </label>
                    <div class="mt-1 sm:col-span-2 sm:mt-0">
                        <input type="text" value="{{ old('app_url', $guessedUrl) }}" name="app_url"
                            class="block w-full rounded-md border border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            id="appUrlName">
                        <p class="mt-2 text-sm text-neutral-500">* This is the URL where you are installing the
                            application,
                            for example, for subdomain in this field you need to enter "https://subdomain.example.com/",
                            make sure to check the documentation on how to create your subdomain.</p>
                        @error('app_url')
                            <p class="mt-2 text-sm text-danger-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:border-t sm:border-neutral-200 sm:pt-5">
                    <label for="inputAppName" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                        <span class="mr-1 text-sm text-danger-600">*</span>Application Name
                    </label>
                    <div class="mt-1 sm:col-span-2 sm:mt-0">
                        <input type="text" value="{{ old('app_name', config('app.name')) }}" name="app_name"
                            class="block w-full rounded-md border border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            id="inputAppName">
                        @error('app_name')
                            <p class="mt-2 text-sm text-danger-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:border-t sm:border-neutral-200 sm:pt-5">
                    <label for="inputCompanyCountry"
                        class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                        <span class="mr-1 text-sm text-danger-600">*</span>Country
                    </label>
                    <div class="mt-1 sm:col-span-2 sm:mt-0">
                        <select name="country" id="inputCompanyCountry"
                            class="block w-full rounded-md border border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value=""></option>
                            @foreach ($countries as $countryId => $country)
                                <option
                                    data-currency="{{ isset($country['currency_code']) ? $country['currency_code'] : null }}"
                                    value="{{ $countryId }}" {{ old('country') == $countryId ? 'selected' : '' }}>
                                    {{ $country['name'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('country')
                            <p class="mt-2 text-sm text-danger-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>


                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:border-t sm:border-neutral-200 sm:pt-5">
                    <label for="inputCurrency" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                        <span class="mr-1 text-sm text-danger-600">*</span>Currency
                    </label>
                    <div class="mt-1 sm:col-span-2 sm:mt-0">
                        <select name="currency" id="inputCurrency"
                            class="block w-full rounded-md border border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value=""></option>
                            @foreach ($currencies as $code => $currency)
                                <option value="{{ $code }}" {{ old('currency') === $code ? 'selected' : '' }}>
                                    {{ $code }} - {{ $currency['name'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('currency')
                            <p class="mt-2 text-sm text-danger-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            <h5 class="mb-5 mt-10 text-lg font-semibold text-neutral-800">Database Configuration</h5>

            @error('privilege')
                <div class="mb-5 rounded-md border border-danger-200 bg-danger-50 p-4 text-sm text-danger-500">
                    <p class="mb-2">
                        {{ $message }}
                    </p>
                    <p class="font-bold">Make sure to give <span class="font-bold">all privileges to the database
                            user</span>, check the installation video in the documentation.</p>
                </div>
            @enderror

            <div class="space-y-6 sm:space-y-5">
                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:border-t sm:border-neutral-200 sm:pt-5">
                    <label for="inputDatabaseHostname"
                        class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                        <span class="mr-1 text-sm text-danger-600">*</span>Hostname
                    </label>
                    <div class="mt-1 sm:col-span-2 sm:mt-0">
                        <input type="text" value="{{ old('database_hostname', 'localhost') }}"
                            name="database_hostname"
                            class="block w-full rounded-md border border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            id="inputDatabaseHostname">
                        @error('database_hostname')
                            <p class="mt-2 text-sm text-danger-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:border-t sm:border-neutral-200 sm:pt-5">
                    <label for="inputDatabasePort" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                        <span class="mr-1 text-sm text-danger-600">*</span>Port
                    </label>
                    <div class="mt-1 sm:col-span-2 sm:mt-0">
                        <input type="text" value="{{ old('database_port', '3306') }}" name="database_port"
                            class="block w-full rounded-md border border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            id="inputDatabasePort">
                        <p class="mt-2 text-sm text-neutral-500">* The default MySQL port is 3306, change the value
                            only if
                            you are certain that you are using different port.</p>
                        @error('database_port')
                            <p class="mt-2 text-sm text-danger-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:border-t sm:border-neutral-200 sm:pt-5">
                    <label for="inputDatabaseName" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                        <span class="mr-1 text-sm text-danger-600">*</span>Database Name
                    </label>
                    <div class="mt-1 sm:col-span-2 sm:mt-0">
                        <input type="text" value="{{ old('database_name') }}" name="database_name"
                            class="block w-full rounded-md border border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            id="inputDatabaseName">
                        <p class="mt-2 text-sm text-neutral-500">* Make sure that you have created the database before
                            configuring.</p>
                        @error('database_name')
                            <p class="mt-2 text-sm text-danger-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:border-t sm:border-neutral-200 sm:pt-5">
                    <label for="inputDatabaseUsername"
                        class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                        <span class="mr-1 text-sm text-danger-600">*</span>Database Username
                    </label>
                    <div class="mt-1 sm:col-span-2 sm:mt-0">
                        <input type="text" value="{{ old('database_username') }}" name="database_username"
                            class="block w-full rounded-md border border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            id="inputDatabaseUsername">
                        <p class="mt-2 text-sm text-neutral-500">* Make sure you have set ALL privileges for the user.
                        </p>
                        @error('database_username')
                            <p class="mt-2 text-sm text-danger-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:border-t sm:border-neutral-200 sm:pt-5">
                    <label for="inputDatabasePassword"
                        class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                        Database Password
                    </label>
                    <div class="mt-1 sm:col-span-2 sm:mt-0">
                        <input type="password" name="database_password"
                            class="block w-full rounded-md border border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            id="inputDatabasePassword">
                        <p class="mt-2 text-sm text-neutral-500">* Enter the database user password.</p>
                        @error('database_password')
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
                class="inline-flex items-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:pointer-events-none disabled:opacity-60"
                id="btn-setup">Test Connection &amp; Configure</button>
        </div>
    </form>
    <script>
        document.addEventListener("DOMContentLoaded", function(event) {
            document.getElementById('setup-form').onsubmit = function(e) {
                document.getElementById('btn-setup').disabled = true;
                document.getElementById('btn-setup').innerText = 'Please wait...';
            }

            document.getElementById('inputCompanyCountry').addEventListener('change', function(e) {
                if (e.target.value) {
                    var inputCurrency = document.getElementById('inputCurrency');
                    var currencyFromCountry = document.querySelector('[value="' + e.target.value + '"]')
                        .dataset.currency;
                    inputCurrency.value = currencyFromCountry ? currencyFromCountry : '';
                }
            })
        });
    </script>
</x-layouts.installer>
