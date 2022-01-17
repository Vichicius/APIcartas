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

        if($data->nickname && $data->email && $data->password && $data->rol){
            try{
                $user = new Usuario;
                $user->name = $data->name;
                $user->email = $data->email;
                if(preg_match("/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{6,}/", $data->password)){
                    $user->password = Hash::make($data->password);
                }else{
                    $response["status"]=0;
                    $response["msg"]="Contraseña insegura. Mínimo: 1 Mayúscula, 1 minúscula, 1 caracter especial y 1 número";
                    return response()->json($response);
                }
                $roles = ['particular', 'profesional', 'administrador'];

                if(in_array($data->rol,$roles)){
                    $user->rol = $data->rol;
                }else{
                    throw new Exception("Error al asignar el rol", 1); //comprobar q esto funciona
                }

                $user->save();
                $response["status"]=1;
                $response["msg"]="Guardado con éxito";

            }catch(\Exception $e){
                $response["status"]=0;
                $response["msg"]="Error al intentar guardar el usuario: ".$e;
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
        $user = Usuario::
        try{
            if($data->nickname && $data->password){
                if(Hash::check($data->password, $user->password)){

                }
            }
        }catch(\Exception $e){

        }
        return response()->json($response);
    }
}
