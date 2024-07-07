<nav>
    <ol class="divide-y divide-neutral-300 rounded-md border border-neutral-300 md:flex md:divide-y-0">

        <li class="relative md:flex md:flex-1">
            <a href="#" class="pointer-events-none flex items-center px-6 py-4 text-sm font-medium">
                <span
                    class="{{ $step == 1 ? 'border-2 border-primary-600' : 'bg-primary-600' }} size-10 flex shrink-0 items-center justify-center rounded-full">
                    @if ($step > 1)
                        <svg class="size-6 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                    @else
                        <span class="text-primary-600">01</span>
                    @endif
                </span>
                <span class="ml-4 text-sm font-medium text-primary-600">Requirements</span>
            </a>

            <!-- Arrow separator for lg screens and up -->
            <div class="absolute right-0 top-0 hidden h-full w-5 md:block" aria-hidden="true">
                <svg class="h-full w-full text-neutral-300" viewBox="0 0 22 80" fill="none"
                    preserveAspectRatio="none">
                    <path d="M0 -2L20 40L0 82" vector-effect="non-scaling-stroke" stroke="currentcolor"
                        stroke-linejoin="round" />
                </svg>
            </div>
        </li>

        <li class="relative md:flex md:flex-1">
            <a href="#" class="group pointer-events-none flex items-center">
                <span class="flex items-center px-6 py-4 text-sm font-medium">
                    <span
                        class="{{ $step == 2 ? 'border-2 border-primary-600' : ($step > 2 ? 'bg-primary-600' : 'border-2 border-neutral-300') }} size-10 flex shrink-0 items-center justify-center rounded-full">
                        @if ($step > 2)
                            <svg class="size-6 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        @else
                            <span class="{{ $step < 2 ? 'text-neutral-500' : 'text-primary-600' }}">02</span>
                        @endif
                    </span>
                    <span class="ml-4 text-sm font-medium text-neutral-500">Permissions</span>
                </span>
            </a>

            <!-- Arrow separator for lg screens and up -->
            <div class="absolute right-0 top-0 hidden h-full w-5 md:block" aria-hidden="true">
                <svg class="h-full w-full text-neutral-300" viewBox="0 0 22 80" fill="none"
                    preserveAspectRatio="none">
                    <path d="M0 -2L20 40L0 82" vector-effect="non-scaling-stroke" stroke="currentcolor"
                        stroke-linejoin="round" />
                </svg>
            </div>
        </li>


        <li class="relative md:flex md:flex-1">
            <a href="#" class="group pointer-events-none flex items-center">
                <span class="flex items-center px-6 py-4 text-sm font-medium">
                    <span
                        class="{{ $step == 3 ? 'border-2 border-primary-600' : ($step > 3 ? 'bg-primary-600' : 'border-2 border-neutral-300') }} size-10 flex shrink-0 items-center justify-center rounded-full">
                        @if ($step > 3)
                            <svg class="size-6 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        @else
                            <span class="{{ $step < 3 ? 'text-neutral-500' : 'text-primary-600' }}">03</span>
                        @endif
                    </span>
                    <span class="ml-4 text-sm font-medium text-neutral-500">Setup</span>
                </span>
            </a>

            <!-- Arrow separator for lg screens and up -->
            <div class="absolute right-0 top-0 hidden h-full w-5 md:block" aria-hidden="true">
                <svg class="h-full w-full text-neutral-300" viewBox="0 0 22 80" fill="none"
                    preserveAspectRatio="none">
                    <path d="M0 -2L20 40L0 82" vector-effect="non-scaling-stroke" stroke="currentcolor"
                        stroke-linejoin="round" />
                </svg>
            </div>
        </li>


        <li class="relative md:flex md:flex-1">
            <a href="#" class="group pointer-events-none flex items-center">
                <span class="flex items-center px-6 py-4 text-sm font-medium">
                    <span
                        class="{{ $step == 4 ? 'border-2 border-primary-600' : ($step > 4 ? 'bg-primary-600' : 'border-2 border-neutral-300') }} size-10 flex shrink-0 items-center justify-center rounded-full">
                        @if ($step > 4)
                            <svg class="size-6 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        @else
                            <span class="{{ $step < 4 ? 'text-neutral-500' : 'text-primary-600' }}">04</span>
                        @endif
                    </span>
                    <span class="ml-4 text-sm font-medium text-neutral-500">User</span>
                </span>
            </a>

            <!-- Arrow separator for lg screens and up -->
            <div class="absolute right-0 top-0 hidden h-full w-5 md:block" aria-hidden="true">
                <svg class="h-full w-full text-neutral-300" viewBox="0 0 22 80" fill="none"
                    preserveAspectRatio="none">
                    <path d="M0 -2L20 40L0 82" vector-effect="non-scaling-stroke" stroke="currentcolor"
                        stroke-linejoin="round" />
                </svg>
            </div>
        </li>

        <li class="relative md:flex md:flex-1">
            <a href="#" class="group pointer-events-none flex items-center">
                <span class="flex items-center px-6 py-4 text-sm font-medium">
                    <span
                        class="{{ $step == 5 ? 'bg-primary-600' : 'border-2 border-neutral-300' }} size-10 flex shrink-0 items-center justify-center rounded-full">
                        @if ($step == 5)
                            <svg class="size-6 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        @else
                            <span class="text-neutral-500">05</span>
                        @endif
                    </span>
                    <span class="ml-4 text-sm font-medium text-neutral-500">Finish</span>
                </span>
            </a>
        </li>
    </ol>
</nav>
