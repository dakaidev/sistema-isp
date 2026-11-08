<?php

namespace App\Http\Middleware;

use App\Support\IspAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIspPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        IspAccess::enforce($permission);

        return $next($request);
    }
}
