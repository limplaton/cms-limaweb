<x-core::layouts.skin>
    @section('title', 'Database Migration Required')

    <div class="h-screen min-h-screen bg-neutral-100 dark:bg-neutral-800">
        <action-panel title="Database migration required"
            description="The application detected that database migration is required, click the 'Migrate' button on the right side to run the migrations."
            redirect-to="{{ url('settings/update') }}" action="{{ URL::asAppUrl('migrate') }}" button-text="Migrate">
        </action-panel>
    </div>
</x-core::layouts.skin>
