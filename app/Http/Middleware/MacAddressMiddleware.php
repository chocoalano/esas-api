<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MacAddressMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $ipAddress = $request->ip();
        $allowedIp = explode(',', "125.165.73.72,103.136.6.65,127.0.0.1,103.136.6.65,125.165.74.151");
        // dd($ipAddress, $allowedIp);
        if (!in_array($ipAddress, $allowedIp)) {
            return response()->view('errors.errors', [
                'msg' => 'Unauthorized: Your IP Address is not allowed.',
                'status' => 404
            ], 404);
        }

        return $next($request);
    }
}
