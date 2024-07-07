<div class="p-3">

    <h4 class="my-5 text-lg font-semibold text-neutral-800">PHP Version</h4>

    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border border-neutral-200 shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">
                                Required PHP Version
                            </th>
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">
                                Current
                            </th>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 bg-white">
                            <td class="whitespace-nowrap px-4 py-2 text-sm font-medium text-neutral-900">
                                >= {{ $php['minimum'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-2 text-sm text-neutral-900">
                                <span
                                    class="{{ $php['supported'] ? 'text-success-500' : 'text-danger-500' }} inline-flex">
                                    @if ($php['supported'])
                                        @include('installer.passes-icon')
                                    @endif
                                    {{ $php['current'] }}
                                </span>
                            </td>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <h4 class="mb-5 mt-10 text-lg font-semibold text-neutral-800">Required PHP Extensions</h4>

    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border border-neutral-200 shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">
                                Extension
                            </th>
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">
                                Enabled
                            </th>

                        </thead>
                        <tbody>
                            @foreach ($requirements['results']['php'] as $requirement => $enabled)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm font-medium text-neutral-900">
                                        {{ $requirement }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm text-neutral-900">
                                        <span
                                            class="{{ $enabled ? 'text-success-500' : 'text-danger-500' }} inline-flex">
                                            @if ($enabled)
                                                @include('installer.passes-icon')
                                            @endif
                                            {{ $enabled ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <h4 class="mb-5 mt-10 text-lg font-semibold text-neutral-800">Required PHP Functions</h4>

    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border border-neutral-200 shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">
                                Function
                            </th>
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">
                                Enabled
                            </th>

                        </thead>
                        <tbody>
                            @foreach ($requirements['results']['functions'] as $function => $enabled)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm font-medium text-neutral-900">
                                        {{ $function }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm text-neutral-900">
                                        <span
                                            class="{{ $enabled ? 'text-success-500' : 'text-danger-500' }} inline-flex">
                                            @if ($enabled)
                                                @include('installer.passes-icon')
                                            @endif
                                            {{ $enabled ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <h4 class="mb-5 mt-10 text-lg font-semibold text-neutral-800">Recommended PHP Extensions/Functions</h4>

    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border border-neutral-200 shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">
                                Requirement
                            </th>
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">
                                Enabled
                            </th>

                        </thead>
                        <tbody>
                            @foreach ($requirements['recommended']['php'] as $requirement => $enabled)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm font-medium text-neutral-900">
                                        {{ $requirement }} <span class="text-xs text-neutral-400">(ext)</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm text-neutral-900">
                                        <span
                                            class="{{ $enabled ? 'text-success-500' : 'text-warning-500' }} inline-flex">
                                            @if ($enabled)
                                                @include('installer.passes-icon')
                                            @endif
                                            {{ $enabled ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                            @foreach ($requirements['recommended']['functions'] as $function => $enabled)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm font-medium text-neutral-900">
                                        {{ $function }} <span class="text-xs text-neutral-400">(func)</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm text-neutral-900">
                                        <span
                                            class="{{ $enabled ? 'text-success-500' : 'text-warning-500' }} inline-flex">
                                            @if ($enabled)
                                                @include('installer.passes-icon')
                                            @endif
                                            {{ $enabled ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
