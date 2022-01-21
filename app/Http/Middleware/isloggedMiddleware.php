<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use App\Models\Usuario;
use Exception;

class isloggedMiddleware
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
            
            $user = Usuario::where('api_token', $data->api_token)->first();
            if(!isset($user)){
                throw new Exception("Error: Ese token no existe");
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


//$user = $request->get('userMiddleware');
//$request->attributes->add(['permiso' => $permiso]);