<div class="p-3">
    <h4 class="my-5 text-lg font-semibold text-neutral-800">Files and folders permissions</h4>
    <p class="text-neutral-700">
        These folders must be writable by web server user: <strong
            class="select-all">{{ get_current_process_user() }}</strong>
        <br />Recommended permissions: <strong class="select-all">0775</strong><br /><br />
    </p>

    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border border-neutral-200 shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th scope="col"
                                    class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">
                                    Path
                                </th>
                                <th scope="col"
                                    class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">
                                    Permission
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 bg-white">
                            @foreach ($permissions['results'] as $permission)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm font-medium text-neutral-900">
                                        {{ rtrim($permission['folder'], '/') }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm font-medium text-neutral-900">
                                        <span
                                            class="{{ $permission['isSet'] ? 'text-success-500' : 'text-danger-500' }} inline-flex">
                                            @if ($permission['isSet'])
                                                @include('installer.passes-icon')
                                            @endif
                                            {{ $permission['permission'] }}
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
