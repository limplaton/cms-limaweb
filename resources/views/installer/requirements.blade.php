<x-layouts.installer>
    @include('installer/includes/requirements')

    @if ((isset($requirements['errors']) && $requirements['errors'] === true) || $php['supported'] === false)
        <div class="-m-7 mt-6 rounded-b border-t border-warning-100 bg-warning-50 px-10 py-7 text-right">
            <div class="flex">
                <div class="shrink-0">
                    <!-- Heroicon name: solid/exclamation -->
                    <svg class="size-5 text-warning-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-warning-800">
                        Please fix the requirements to proceed further with the installation process.
                    </h3>
                </div>
            </div>
        </div>
    @else
        <div class="-m-7 mt-6 rounded-b border-t border-neutral-200 bg-neutral-50 p-4 text-right">
            <a href="{{ url('install/permissions') }}"
                class="inline-flex items-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">Next</a>
        </div>
    @endif

</x-layouts.installer>
