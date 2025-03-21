<div class="flex flex-col items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900 p-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-6">
            <h2 class="text-3xl font-bold text-emerald-600">
                {{ $tenant->name }}
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                Create your account
            </p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden p-6">
            <form wire:submit="register">
                {{ $this->form }}
                
                <x-filament::button
                    type="submit"
                    color="primary"
                    class="w-full mt-6"
                >
                    Register
                </x-filament::button>
            </form>
            
            <div class="mt-4 text-center text-sm text-gray-600 dark:text-gray-400">
                Already have an account?
                <a href="{{ route('filament.admin.auth.login') }}" class="text-emerald-600 hover:text-emerald-500">
                    Sign in
                </a>
            </div>
        </div>
    </div>
</div> 