@if (file_exists(public_path('index.html')) && Auth::user()->isSuperAdmin())
    <i-alert variant="danger" class="rounded-none">
        <i-alert-body>
            <h3 class="text-sm font-semibold text-danger-700">
                Static <b>index.html</b> file detected in the installation public directory!
            </h3>

            <div class="mt-2">
                The system has detected static <b>index.html</b> file in the public root directory
                <b>{{ public_path() }}</b>
                <br />
                To prevent any unwanted results, you should delete the file <b>index.html</b> and leave only the core
                <b>index.php</b> file.
            </div>
        </i-alert-body>
    </i-alert>
@endif
