<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Carta;
use App\Models\Coleccion;
use App\Models\Cartacoleccion;
use App\Models\Venta;
use Exception;
use DateTime;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
                $user->email = $data->email; //validar email
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
            if($data->nickname && $data->password){
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

    public function plantilla(Request $req){ //Pide: nickname y password
        $jdata = $req->getContent();
        $data = json_decode($jdata);

        $response["status"]=1;
        try{
            if($data->nickname && $data->password){
                //throw new Exception("Error: Contraseña incorrecta");
            }
        }catch(\Exception $e){
            $response["status"]=0;
            $response["msg"]=$e->getMessage();
        }
        return response()->json($response);
    }

    public function passRecovery(Request $req){ //Pide: nickname
        $jdata = $req->getContent();
        $data = json_decode($jdata);

        $response["status"]= 1;
        try{
            if($data->nickname){
                $user = Usuario::where('nickname', $data->nickname)->first();
                if(!isset($user)){
                    throw new Exception("Nickname no existe");
                }
                $newPass = Str::random(16);
                $user->password = Hash::make($newPass);
                $user->save();
                $response["msg"]=$newPass;
            }else{
                throw new Exception("No has introducido nickname");
            }
        }catch(\Exception $e){
            $response["status"]=0;
            $response["msg"]=$e->getMessage();
        }
        return response()->json($response);
    }

    public function crearCarta(Request $req){ //Pide: name, description y collection (opcional image)
        $jdata = $req->getContent();
        $data = json_decode($jdata);

        $response["status"]=1;

        $carta = new Carta;
        $cartacoleccion = new Cartacoleccion;

        try{
            if($data->name && $data->description && $data->collection){

                $collection = Coleccion::find($data->collection);
                if(!isset($collection)){
                    throw new Exception("Error: La coleccion introducida no existe");
                }
                //crear carta
                $carta->name = $data->name;
                $carta->description = $data->description;
                if(isset($data->image)){
                    $carta->image = $data->image;
                }
                $carta->save();
                //vincularla a la coleccion
                $cartacoleccion->carta_id = $carta->id;
                $cartacoleccion->coleccion_id = $data->collection;
                $cartacoleccion->save();
                $response["msg"] = "Carta creada con éxito. ID: $carta->id";
            }else{
                throw new Exception("Error: Introduce name, description y collection");
            }
        }catch(\Exception $e){
            $response["status"]=0;
            $response["msg"]=$e->getMessage();
        }
        return response()->json($response);
    }

    public function crearColecion(Request $req){ //Pide: name, description y collection (opcional imagen)
        $jdata = $req->getContent();
        $data = json_decode($jdata);

        $response["status"]=1;
        $carta = new Carta;
        $coleccion = new Coleccion;
        $cartacoleccion = new Cartacoleccion;
        try{
            if($data->name_coleccion && $data->symbol_coleccion && $data->release_date_coleccion && $data->name_card && $data->description_card){
                //crear colección vacía
                $coleccion->name = $data->name_coleccion;
                $coleccion->symbol = $data->symbol_coleccion;
                $coleccion->release_date = $data->release_date_coleccion; //validar fecha
                $coleccion->save();
                //crear carta
                $carta->name = $data->name;
                $carta->description = $data->description;
                if(isset($data->image)){
                    $carta->image = $data->image;
                }
                $carta->save();
                //vincular carta a la colección
                $cartacoleccion->carta_id = $carta->id;
                $cartacoleccion->coleccion_id = $coleccion->id;
                $cartacoleccion->save();
                //responder
                $response["msg"] = "Colección y carta creada con éxito";
                $response["id"]["Coleccion"] = $coleccion->id;
                $response["id"]["Carta"] = $carta->id;
            }else{
                throw new Exception("Error: Introduce name_coleccion, symbol_coleccion, release_date_coleccion, name_card y description_card");
            }
        }catch(\Exception $e){
            $response["status"]=0;
            $response["msg"]=$e->getMessage();
        }
        return response()->json($response);
    }

    public function asociarCartaColeccion(Request $req){ //Pide: api_token, carta_id y coleccion_id
        $jdata = $req->getContent();
        $data = json_decode($jdata);

        $response["status"]=1;
        try{
            if($data->carta_id && $data->coleccion_id){
                //checkear si existen
                $carta = Carta::find($data->carta_id);
                $coleccion = Coleccion::find($data->coleccion_id);
                if($coleccion == null){
                    throw new Exception("Error: Colección no encontrado");
                }
                if($carta == null){
                    throw new Exception("Error: Carta no encontrada");
                }
                $cartacoleccionRepetida = Cartacoleccion::where('carta_id', $carta->id)->where('coleccion_id', $data->collection)->first();
                if(isset($cartacoleccionRepetida)){
                    throw new Exception("Error: Esta carta ya está en esta colección");
                }//Probar si funciona esto
                $cartacoleccion = new Cartacoleccion;
                $cartacoleccion->carta_id = $data->carta_id;
                $cartacoleccion->coleccion_id = $data->coleccion_id;
                $cartacoleccion->save();
            }else{
                throw new Exception("Error: Introduce carta_id y coleccion_id");
            }
        }catch(\Exception $e){
            $response["status"]=0;
            $response["msg"]=$e->getMessage();
        }
        return response()->json($response);
    }

    public function crearVenta(Request $req){ //Pide: api_token, usuario_id, carta_id, quantity, price
        $jdata = $req->getContent();
        $data = json_decode($jdata);

        $response["status"]=1;
        try{
            if($data->usuario_id && $data->carta_id && $data->quantity && $data->price){
                $user = Usuario::find($data->usuario_id);
                $carta = Carta::find($data->carta_id);
                if($user == null){
                    throw new Exception("Error: Usuario no encontrado");
                }
                if($carta == null){
                    throw new Exception("Error: Carta no encontrada");
                }
                if($data->quantity < 1){
                    throw new Exception("Error: Vende al menos una carta");
                }
                if($data->price < 0.01){
                    throw new Exception("Error: Precio mínimo es 0.01€");
                }
                $articulo = new Venta;
                $articulo->usuario_id = $data->usuario_id;
                $articulo->carta_id = $data->carta_id;
                $articulo->quantity = $data->quantity;
                $articulo->price = $data->price;
                $articulo->save();
            }else{
                throw new Exception("Error: introduce api_token, usuario_id, carta_id, quantity, price");
            }
        }catch(\Exception $e){
            $response["status"]=0;
            $response["msg"]=$e->getMessage();
        }
        return response()->json($response);
    }

    //VENTA CARTAS
    //poner articulo
    //buscar cartas por nombre (devolver id)
    //buscar anuncios por nombre || ordenar de mayor a menor || mostrar: nombre cantidad precio vendedor

    //FUNCIONES ADMIN:
    //añadir carta a coleccion     X
    //quitar carta de coleccion    -
    //editar carta                 -
    //editar coleccion             -
}
