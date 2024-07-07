 @if (Auth::user()->isSuperAdmin() && \Modules\Core\App\Environment::hasChanged() && !config('app.ngrok'))
     <i-alert variant="danger" class="rounded-none">
         <i-alert-body>
             <p>
                 A change in your environment has been detected, it's possible that you moved the installation to a new
                 server or the application URL configured in your <b>.env</b> file does not match with the URL that was
                 used
                 during installation.
             </p>
             <p class="mt-0.5">
                 Double check and confirm the requirements below.
             </p>

             <div class="mt-4">
                 <div class="-mx-2 -my-1.5 flex space-x-2">
                     <a href="/requirements" target="_blank"
                         class="rounded-md bg-danger-50 px-2 py-1.5 text-sm font-medium text-danger-800 hover:bg-danger-100 focus:outline-none focus:ring-2 focus:ring-danger-600 focus:ring-offset-2 focus:ring-offset-danger-50">
                         Check Requirements
                     </a>
                     <form method="POST" action="/requirements">
                         @csrf
                         <button type="submit"
                             class="rounded-md bg-danger-50 px-2 py-1.5 text-sm font-medium text-danger-800 hover:bg-danger-100 focus:outline-none focus:ring-2 focus:ring-danger-600 focus:ring-offset-2 focus:ring-offset-danger-50">
                             Confirm
                         </button>
                     </form>

                 </div>
             </div>
         </i-alert-body>
     </i-alert>
 @endif
