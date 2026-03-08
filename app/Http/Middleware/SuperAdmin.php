<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuperAdmin
{
    /**
     * Allow only authenticated users with the super_admin role.
     * Redirects unauthenticated visitors to the admin login page.
     * Returns 403 for authenticated non-admins.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('admin.login');
        }

        if (Auth::user()->role !== 'super_admin') {
            abort(403);
        }

        return $next($request);
    }
}
