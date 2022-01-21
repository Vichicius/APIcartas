<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use App\Models\Usuario;
use Exception;

class isAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $jdata = $request->getContent();
        $data = json_decode($jdata);

        $response["status"] = 1;

        try{
            if(!isset($data->api_token)){
                throw new Exception("Error: No hay api_token");
            }
            $user = Usuario::where('api_token', $data->api_token)->first();
            if(!isset($user)){
                throw new Exception("Error: Ese token no existe");
            }
            if($user->rol != "administrador"){
                throw new Exception("Error: No tienes suficientes permisos");
            }
            
            $request->attributes->add(['userMiddleware' => $user]);

            return $next($request);

        }catch(\Exception $e){
            $response["status"] = 0;
            $response["msg"] = $e->getMessage();
        }

        return response()->json($response);
    }
}
