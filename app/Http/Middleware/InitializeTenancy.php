<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancy
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && session()->has('tenant_id')) {
            $tenant = Tenant::find(session('tenant_id'));
            
            if ($tenant && Auth::user()->tenants->contains($tenant)) {
                // Set tenant globally available
                app()->instance('tenant', $tenant);
            }
        }

        return $next($request);
    }
} 