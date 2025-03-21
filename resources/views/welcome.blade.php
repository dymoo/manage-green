<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>manage.green</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite('resources/css/app.css')
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6 not-has-[nav]:hidden">
            @if (Route::has('login'))
                <nav class="flex items-center justify-end gap-4">
                    @auth
                        <a
                            href="{{ url('/dashboard') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal"
                        >
                            Dashboard
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] text-[#1b1b18] border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
                        >
                            {{__("Log in")}}
                        </a>
{{-- 
                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                                Register
                            </a>
                        @endif --}}
                    @endauth
                </nav>
            @endif
        </header>
       
      <div class="w-full lg:max-w-4xl flex flex-col items-center justify-center text-center">
        <!-- Hero Section -->
        <div class="mb-12 w-full">
          <h1 class="text-4xl font-medium mb-6 text-gray-800 dark:text-gray-100">manage<span class="text-emerald-600 dark:text-emerald-500">.green</span></h1>
          <p class="text-lg mb-6 text-gray-700 dark:text-gray-300">{{__("The all-in-one solution for managing your club's membership, events, and communications.")}}</p>
        </div>

        <!-- Features Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 w-full">
          <!-- Feature 1 -->
          <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
        <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4 mx-auto">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-600 dark:text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </div>
        <h3 class="text-xl font-medium mb-2 text-gray-800 dark:text-gray-200">{{__("Member Management")}}</h3>
        <p class="text-gray-600 dark:text-gray-400">{{__("Easily track memberships, payments, and member details in one place.")}}</p>
          </div>

          <!-- Feature 2 -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
          <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4 mx-auto">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-600 dark:text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
          <h3 class="text-xl font-medium mb-2 text-gray-800 dark:text-gray-200">{{__("Event Planning")}}</h3>
          <p class="text-gray-600 dark:text-gray-400">{{__("Schedule and manage club events, track attendance, and send reminders.")}}</p>
            </div>

            <!-- Feature 3 -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
          <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4 mx-auto">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-600 dark:text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
          </div>
          <h3 class="text-xl font-medium mb-2 text-gray-800 dark:text-gray-200">{{__("Communication")}}</h3>
          <p class="text-gray-600 dark:text-gray-400">{{__("Send announcements, newsletters, and important updates to members with ease.")}}</p>
            </div>
          </div>

          <!-- Call to Action -->
          <div class="mt-12 p-8 bg-gray-50 dark:bg-gray-800/50 rounded-lg w-full">
            <h2 class="text-2xl font-medium mb-4 text-gray-800 dark:text-gray-200">{{__("Ready to streamline your club management?")}}</h2>
            <p class="mb-6 text-gray-600 dark:text-gray-400">{{__("Join thousands of clubs that trust our platform for their day-to-day operations.")}}</p>
            <div class="flex justify-center">
          <a href="https://cal.com/dylan-moore/30min" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-all shadow-md">{{__("Book a call")}}</a>
            </div>
          </div>
          </div>


        </div>

        {{-- @if (Route::has('login'))
        <div class="h-14.5 hidden lg:block"></div>
        @endif --}}
        </body>
</html>