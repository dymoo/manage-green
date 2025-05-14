<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <div class="text-center mb-6">
                <div class="mx-auto w-20 h-20 rounded-full flex items-center justify-center bg-emerald-100 mb-4">
                    <x-heroicon-o-rocket-launch class="w-12 h-12 text-emerald-600" />
                </div>
                <h2 class="text-2xl font-bold">
                    Your club is ready to go!
                </h2>
                <p class="text-gray-500 mt-2">
                    Follow these steps to get your cannabis club up and running quickly.
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <ol class="space-y-8">
                <li class="relative flex gap-x-4">
                    <div class="absolute left-0 top-0 flex w-6 h-6 items-center justify-center bg-emerald-600 rounded-full -ml-3">
                        <span class="text-white text-sm font-medium">1</span>
                    </div>
                    <div class="flex min-w-0 flex-1 flex-col pb-8 ml-4">
                        <div class="flex flex-wrap items-start gap-x-3">
                            <h3 class="text-lg font-semibold">Complete your club profile</h3>
                            <span class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                First step
                            </span>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-gray-500">
                            Add your club's logo, contact information, and configure your settings to personalize your experience.
                        </p>
                        <div class="mt-4">
                            <a href="{{ route('filament.admin.tenant.profile', ['tenant' => tenant()->slug]) }}" class="text-sm font-semibold text-emerald-600 hover:text-emerald-500">
                                Edit club settings &rarr;
                            </a>
                        </div>
                    </div>
                </li>

                <li class="relative flex gap-x-4">
                    <div class="absolute left-0 top-0 flex w-6 h-6 items-center justify-center bg-emerald-600 rounded-full -ml-3">
                        <span class="text-white text-sm font-medium">2</span>
                    </div>
                    <div class="flex min-w-0 flex-1 flex-col pb-8 ml-4">
                        <div class="flex flex-wrap items-start gap-x-3">
                            <h3 class="text-lg font-semibold">Invite your staff</h3>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-gray-500">
                            Add staff members to help manage your club. Assign them appropriate roles and permissions.
                        </p>
                        <div class="mt-4">
                            <a href="{{ route('filament.admin.resources.users.create', ['tenant' => tenant()->slug]) }}" class="text-sm font-semibold text-emerald-600 hover:text-emerald-500">
                                Invite staff &rarr;
                            </a>
                        </div>
                    </div>
                </li>

                <li class="relative flex gap-x-4">
                    <div class="absolute left-0 top-0 flex w-6 h-6 items-center justify-center bg-emerald-600 rounded-full -ml-3">
                        <span class="text-white text-sm font-medium">3</span>
                    </div>
                    <div class="flex min-w-0 flex-1 flex-col pb-8 ml-4">
                        <div class="flex flex-wrap items-start gap-x-3">
                            <h3 class="text-lg font-semibold">Set up your product categories</h3>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-gray-500">
                            Create categories for your cannabis products to organize your inventory effectively.
                        </p>
                        <div class="mt-4">
                            <a href="{{ route('filament.admin.resources.product-categories.index', ['tenant' => tenant()->slug]) }}" class="text-sm font-semibold text-emerald-600 hover:text-emerald-500">
                                Manage categories &rarr;
                            </a>
                        </div>
                    </div>
                </li>

                <li class="relative flex gap-x-4">
                    <div class="absolute left-0 top-0 flex w-6 h-6 items-center justify-center bg-emerald-600 rounded-full -ml-3">
                        <span class="text-white text-sm font-medium">4</span>
                    </div>
                    <div class="flex min-w-0 flex-1 flex-col ml-4">
                        <div class="flex flex-wrap items-start gap-x-3">
                            <h3 class="text-lg font-semibold">Add your inventory</h3>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-gray-500">
                            Add your products to the system, set prices, and track inventory levels.
                        </p>
                        <div class="mt-4">
                            <a href="{{ route('filament.admin.resources.products.index', ['tenant' => tenant()->slug]) }}" class="text-sm font-semibold text-emerald-600 hover:text-emerald-500">
                                Manage products &rarr;
                            </a>
                        </div>
                    </div>
                </li>
            </ol>
        </x-filament::section>

        <x-filament::section>
            <div class="rounded-lg bg-emerald-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <x-heroicon-s-information-circle class="h-5 w-5 text-emerald-600" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-emerald-800">Need help getting started?</h3>
                        <div class="mt-2 text-sm text-emerald-700">
                            <p>
                                Check out our <a href="#" class="font-medium text-emerald-600 underline hover:text-emerald-500">documentation</a> or <a href="#" class="font-medium text-emerald-600 underline hover:text-emerald-500">contact support</a> if you have any questions.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page> 