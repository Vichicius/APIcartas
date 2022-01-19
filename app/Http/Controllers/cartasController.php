<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use Exception;
use DateTime;
use Illuminate\Support\Facades\Hash;

class cartasController extends Controller
{
    //
    public function register(Request $req){ //Pide: api_token, nickname, email, password y rol
        $jdata = $req->getContent();
        $data = json_decode($jdata);
        $response["status"]=1;

        if($data->nickname && $data->email && $data->password && $data->rol){
            try{
                $user = new Usuario;
                $user->name = $data->name;
                $user->email = $data->email;
                if(preg_match("/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{6,}/", $data->password)){
                    $user->password = Hash::make($data->password);
                }else{
                    $response["status"]=0;
                    throw new Exception("Contraseña insegura. Mínimo: 1 Mayúscula, 1 minúscula, 1 caracter especial y 1 número");
                }
                $roles = ['particular', 'profesional', 'administrador'];

                if(in_array($data->rol,$roles)){
                    $user->rol = $data->rol;
                }else{
                    $response["status"]=0;
                    throw new Exception("Error al asignar el rol"); //comprobar q esto funciona
                }

                $user->save();
                $response["msg"]="Guardado con éxito";

            }catch(\Exception $e){
                $response["status"]=0;
                $response["msg"]=$e->getMessage();
            }
            
        }else{
            $response["status"]=0;
            $response["msg"]="introduce name, email, password y rol";
        }

        
        return response()->json($response);
    }

    public function login(Request $req){ //Pide: nickname y password
        $jdata = $req->getContent();
        $data = json_decode($jdata);
        $response["status"]=1;
        try{
            if($data->nickname && $data->password){
                $user = Usuario::where('nickname', $data->nickname)->first();
                if(!isset($user)){
                    throw new Exception("Error: Nickname no existe");
                }
                if(Hash::check($data->password, $user->password)){
                    
                    $allTokens = User::pluck('api_token')->toArray();
                    do {
                        $user->api_token = Hash::make(now().$user->email);
                    } while (in_array($user->api_token, $allTokens)); //En bucle mientras que el apitoken esté duplicado
                    $user->save();

                }else{
                    //contraseña incorrecta
                    throw new Exception("Error: Contraseña incorrecta");
                }
            }
        }catch(\Exception $e){
            $response["status"]=0;
            $response["msg"]=$e->getMessage();
        }
        return response()->json($response);
    }

    
}
