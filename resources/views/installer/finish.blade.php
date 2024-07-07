<x-layouts.installer>
    <div class="p-3">
        <h4 class="mb-8 mt-5 text-center text-2xl font-semibold text-success-500">Installation Successfull</h4>

        <p class="text-neutral-700">
            <span class="font-semibold">{{ config('app.name') }} has been successfully installed</span>, as last
            requirement, you must configure a cron job:
        </p>

        <div
            class="mb-3 mt-4 block w-full rounded-md border border-neutral-300 bg-neutral-50 px-5 py-4 text-base shadow-sm">
            * * * * * <span class="select-all"> {{ $phpExecutable ?: 'php' }} {{ base_path() }}/artisan schedule:run
                >> /dev/null 2>&1</span>
        </div>

        <p class="mt-4 text-neutral-700">
            If you are not certain on how to configure the cron job with the minimum required PHP version
            ({{ $minPHPVersion }}), the best is to consult with your hosting provider.
        </p>

        <p class="mt-4 text-neutral-700">
            On some <span class="font-medium">shared hostings you may need to specify full path</span> to the PHP
            executable
            (for example, <code
                class="select-all bg-danger-100 px-2">/usr/local/bin/php{{ str_replace('.', '', $minPHPVersion) }}</code>
            or <code
                class="select-all bg-danger-100 px-2">/opt/alt/php{{ str_replace('.', '', $minPHPVersion) }}/usr/bin/php</code>instead
            of <code class="bg-danger-100 px-2">php</code>), additionally, you can refer to our docs by clicking <a
                href="https://www.crm.com/docs/cron" class="link" target="_blank" rel="noopener"
                rel="noopener">here</a> in order to read more about cron job configuration.
        </p>

        <h4 class="mb-2 mt-5 text-lg font-semibold text-neutral-800">Admin Credentials</h4>

        <p>
            <span class="font-semibold text-neutral-700">Email:</span> <span
                class="select-all">{{ $user->email }}</span><br />
            <span class="font-semibold text-neutral-700">Password:</span> <span>your chosen password</span>
        </p>

        @if (count($patches) > 0)
            <hr class="border-1 -mx-10 mt-8 border-neutral-200" />
            <h4 class="mt-8 text-lg font-semibold text-neutral-900">Patch your Installation</h4>
            <p class="mb-5 text-neutral-700">
                There are patches available for your installation, before starting using  CRM, would you want to
                apply the patches? You can always apply them later via Settings -> System -> Update.
            </p>

            <ul class="space-y-2">
                @foreach ($patches as $patch)
                    <li class="rounded-lg border border-neutral-200 px-4 py-3 shadow-sm">
                        <p class="mb-1 block text-sm font-medium text-neutral-800">
                            {{ $patch->description() }}
                        </p>
                        <span
                            class="inline-flex items-center rounded-full bg-neutral-100 px-3 py-0.5 text-xs font-medium text-neutral-800">
                            {{ $patch->token() }} </span>
                        <span class="text-xs text-neutral-500">
                            {{ $patch->date()->toFormattedDateString() }}
                        </span>
                    </li>
                @endforeach
            </ul>

            <form class="mt-5 flex items-center" method="post" action="{{ url()->full() }}" id="patchForm">
                @csrf
                <div class="w-full max-w-xs">
                    <label class="text-gray-600 mb-1 block text-sm font-medium" for="purchaseKeyInput">
                        Purchase key <small class="text-xs"><a rel="noopener noreferrer"
                                href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-"
                                class="link" target="_blank">Learn more</a></small>
                    </label>

                    <input type="text" value="{{ old('purchase_key') }}" name="purchase_key"
                        class="block w-full rounded-md border border-neutral-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        placeholder="Enter your purchase key" id="purchaseKeyInput">
                </div>

                <button type="submit" id="applyPatchesBtn"
                    class="ml-3 mt-6 inline-flex items-center rounded-md border border-transparent bg-success-500 px-4 py-2 text-sm text-white shadow-sm hover:bg-success-600 focus:outline-none focus:ring-2 focus:ring-success-400 focus:ring-offset-2 disabled:pointer-events-none disabled:opacity-70">
                    Apply Patches
                </button>

            </form>
            @error('purchase_key')
                <p class="mt-1 text-sm text-danger-600">
                    {{ $message }}
                </p>
            @enderror
        @endif
    </div>

    <div class="-m-7 mt-6 rounded-b border-t border-neutral-200 bg-neutral-50 p-4 text-right">
        <a href="{{ url('login') }}" target="_blank" rel="noopener noreferrer"
            class="inline-flex items-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
            Login
        </a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('patchForm').addEventListener('submit', function() {
                document.getElementById('applyPatchesBtn').disabled = true;
                document.getElementById('applyPatchesBtn').innerText = 'Applying...';
            })
        })
    </script>
</x-layouts.installer>
