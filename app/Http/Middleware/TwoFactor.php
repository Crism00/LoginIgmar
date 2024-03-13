<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            if($user->two_factor == false && $user->role_id == 1){
                return redirect()->route('codeVerification')->withErrors('Please complete Two Factor Authentication');
            }
            return $next($request);
        }
        if($request->user()->two_factor == false && $request->user()->role_id == 1){
            return redirect()->route('codeVerification')->withErrors('Please complete Two Factor Authentication');
        };
        return $next($request);
    }
}
