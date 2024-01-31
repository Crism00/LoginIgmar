<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleValidation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    /**
     * Middleware para validar el rol del usuario.
     *
     * @param Request $request La solicitud HTTP entrante.
     * @param Closure $next El siguiente middleware en la cadena.
     * @param mixed ...$roles Los roles permitidos para acceder a la página.
     * @return Response La respuesta HTTP redirigida en caso de no tener permiso, de lo contrario, pasa al siguiente middleware.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        try{
            if (!in_array($request->user()->role_id, $roles)){
                return redirect()->route('dashboard'. $request->user()->role_id)->with('error', 'No tienes permiso para acceder a esta página.');
            }
            return $next($request);
        }
        catch(\Exception $e){
            return redirect()->route('/');
        }
    }
}
