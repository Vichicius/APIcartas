<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsuariosController extends Controller
{

    public function register(Request $req){ //Pide: nickname, email, password y rol
        $jdata = $req->getContent();
        $data = json_decode($jdata);
        $response["status"]=1;

        if(isset($data->nickname) && isset($data->email) && isset($data->password) && isset($data->rol)){
            try{
                $user = new Usuario;
                //que sea string, max 40 caracteres y que sea unico
                $nombre = $data->nickname;
                if(!is_string($nombre) || strlen($nombre) > 40){
                    throw new Exception("Error: Usuario no válido");
                }
                $nombres = Usuario::pluck('nickname')->toArray();
                if(in_array($nombre, $nombres)){
                    throw new Exception("Error: Nombre de usuario en uso");
                }
                $user->nickname = $data->nickname;
                //check email
                if(!preg_match("^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$^", $data->email)) {
                    throw new Exception("Error: Email no válido");
                }
                $user->email = $data->email;
                if(preg_match("/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{6,}/", $data->password)){
                    $user->password = Hash::make($data->password);
                }else{
                    throw new Exception("Contraseña insegura. Mínimo: 1 Mayúscula, 1 minúscula, 1 caracter especial y 1 número");
                }
                $roles = ['particular', 'profesional', 'administrador'];

                if(in_array($data->rol,$roles)){
                    $user->rol = $data->rol;
                }else{
                    throw new Exception("Error: Rol introducido incorrecto");
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
            if(isset($data->nickname) && isset($data->password)){
                $user = Usuario::where('nickname', $data->nickname)->first();
                if(!isset($user)){
                    throw new Exception("Error: Nickname no existe");
                }
                if(Hash::check($data->password, $user->password)){

                    $allTokens = Usuario::pluck('api_token')->toArray();
                    do {
                        $user->api_token = Hash::make(now().$user->email);
                    } while (in_array($user->api_token, $allTokens)); //En bucle mientras que el apitoken esté duplicado
                    $user->save();
                    $response["msg"] = "sesion iniciada correctamente";
                    $response["api_token"] = $user->api_token;

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

    public function passRecovery(Request $req){ //Pide: email
        $jdata = $req->getContent();
        $data = json_decode($jdata);

        $response["status"]= 1;
        try{
            if(isset($data->email)){
                $user = Usuario::where('email', $data->email)->first();
                if(!isset($user)){
                    throw new Exception("email no existe");
                }
                $newPass = Str::random(16);
                $user->password = Hash::make($newPass);
                $user->api_token = NULL;
                $user->save();
                $response["msg"]="Contraseña cambiada. Vuelve a iniciar sesión.";
                $response["Password"]=$newPass;
            }else{
                throw new Exception("No has introducido nickname");
            }
        }catch(\Exception $e){
            $response["status"]=0;
            $response["msg"]=$e->getMessage();
        }
        return response()->json($response);
    }

}
