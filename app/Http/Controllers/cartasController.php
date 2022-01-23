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
    public function register(Request $req){ //Pide: nickname, email, password y rol
        $jdata = $req->getContent();
        $data = json_decode($jdata);
        $response["status"]=1;

        if(isset($data->nickname) && isset($data->email) && isset($data->password) && isset($data->rol)){
            try{
                $user = new Usuario;
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

    public function passRecovery(Request $req){ //Pide: nickname
        $jdata = $req->getContent();
        $data = json_decode($jdata);

        $response["status"]= 1;
        try{
            if(isset($data->nickname)){
                $user = Usuario::where('nickname', $data->nickname)->first();
                if(!isset($user)){
                    throw new Exception("Nickname no existe");
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

    public function crearCarta(Request $req){ //Pide: api_token, name, description y collection (opcional image)
        $jdata = $req->getContent();
        $data = json_decode($jdata);

        $response["status"]=1;

        $carta = new Carta;
        $cartacoleccion = new Cartacoleccion;

        try{
            if(isset($data->name) && isset($data->description) && isset($data->collection)){

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

    public function crearColecion(Request $req){ //Pide: api_token, name_coleccion, symbol_coleccion, release_date_coleccion, name_card[], description_card[] o carta_id[]
        $jdata = $req->getContent();
        $data = json_decode($jdata);

        $response["status"]=1;
        $carta = new Carta;
        $coleccion = new Coleccion;
        $cartacoleccion = new Cartacoleccion;
        try{

            //Primero que me haya puesto algo para crear la coleccion
            if(!(isset($data->name_coleccion) && isset($data->symbol_coleccion) && isset($data->release_date_coleccion)) ){
                throw new Exception("Error: Introduce datos para crear la coleccion");
            }

            //Comprobar que no me va a crear la coleccion vacia (que haya introducido carta_id o una carta nueva con nombre y descripcion)
            //o me pones carta id o me pones cartanueva
            if(!( isset($data->carta_id) || (isset($data->name_card) && isset($data->description_card)) )){
                throw new Exception("Error: No se puede crear una colección vacía. Introduce una carta_id o name_card y description_card para una carta nueva");
            }

            //comprobar que no existe ya la coleccion y que carta_id existe o los arrays de name_card y description_card son de la misma longitud
            $coleccionRepetida = Coleccion::where('name',$data->name_coleccion)->first();
            if(isset($coleccionRepetida)){
                throw new Exception("Error: Esa colección ya existe");
            }
            if(isset($data->carta_id)){
                foreach ($data->carta_id as $key => $id) {
                    $cartabuscada = Carta::find($id);
                    if(!isset($cartabuscada)){
                        throw new Exception("Error: La carta con id: $id no existe");
                    }
                }
            }
            if(isset($data->name_card) && isset($data->description_card)){
                $cantidadNombres = count($data->name_card);
                $cantidadDescripciones = count($data->description_card);
                if($cantidadNombres != $cantidadDescripciones){
                    throw new Exception("Error: Introduce tantos nombres como descripciones");
                }
            }

            //crear colección vacía
            $coleccion->name = $data->name_coleccion;
            $coleccion->symbol = $data->symbol_coleccion;
            //regex de release_date en forma YYYY-MM-DD
            if(!preg_match("^(19|20)\d\d[- /.](0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])$^", $data->release_date_coleccion)) { 
                throw new Exception("Error: fecha no válida. El formato correcto es YYYY-MM-DD");
            }
            $coleccion->release_date = $data->release_date_coleccion; //validar fecha
            $coleccion->save();

            //crear las cartas y vincularlas
            if(isset($data->name_card)){
                foreach ($data->name_card as $posicion => $nombre) {
                    $carta = new Carta;
                    $carta->name = $nombre;
                    $carta->description = $data->description_card[$posicion];
                    $carta->save();
    
                    $cartacoleccion = new Cartacoleccion;
                    $cartacoleccion->carta_id = $carta->id;
                    $cartacoleccion->coleccion_id = $coleccion->id;
                    $cartacoleccion->save();
                }
            }
            //o vincular las ya existentes
            if(isset($data->carta_id)){
                foreach ($data->carta_id as $key => $id) {
                    $cartacoleccion = new Cartacoleccion;
                    $cartacoleccion->carta_id = $id;
                    $cartacoleccion->coleccion_id = $coleccion->id;
                    $cartacoleccion->save();
                }
            }

            //responder
            $response["msg"] = "Colección creada con éxito";
            $response["id"] = $coleccion->id;
            
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
            if(isset($data->carta_id) && isset($data->coleccion_id)){
                //checkear si existen
                $carta = Carta::find($data->carta_id);
                $coleccion = Coleccion::find($data->coleccion_id);
                if($coleccion == null){
                    throw new Exception("Error: Colección no encontrado");
                }
                if($carta == null){
                    throw new Exception("Error: Carta no encontrada");
                }
                $cartacoleccionRepetida = Cartacoleccion::where('carta_id', $data->carta_id)->where('coleccion_id', $data->coleccion_id)->first();
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

    public function crearVenta(Request $req){ //Pide: api_token, carta_id, quantity, price
        $jdata = $req->getContent();
        $data = json_decode($jdata);

        $response["status"]=1;
        try{
            if(isset($data->carta_id) && isset($data->quantity) && isset($data->price)){
                $user = Usuario::where('api_token', $data->api_token)->first();
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
                $articulo->usuario_id = $user->id;
                $articulo->carta_id = $data->carta_id;
                $articulo->name = $carta->name;
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

    public function buscarCartas(Request $req){ //Pide: api_token y name
        $jdata = $req->getContent();
        $data = json_decode($jdata);

        $response["status"]=1;
        try{
            if(isset($data->name)){
                $listaNombres = Carta::pluck('name','id')->toArray();
                $listaRespuesta = [];
                $listaRespuesta2 = [];
                foreach ($listaNombres as $key => $nombreCompleto) {
                    if(str_contains($nombreCompleto, $data->name)){
                        $response["msg"]="Cartas encontradas";
                        $coincidencia = Carta::where('name',$nombreCompleto)->first();
                        $listaRespuesta["id"] = $coincidencia->id;
                        $listaRespuesta["nombre"] = $coincidencia->name;
                        array_push($listaRespuesta2, $listaRespuesta);
                    }
                }
                if(!isset($coincidencia)){
                    $response["msg"] = "No hay ninguna coincidencia";
                }else{
                    $response["coincidencias"] = $listaRespuesta2;
                }
            }else{
                throw new Exception("Error: Introduce un nombre de una carta (name)");
            }
        }catch(\Exception $e){
            $response["status"]=0;
            $response["msg"]=$e->getMessage();
        }
        return response()->json($response);
    }
    
    public function buscarAnuncio(Request $req){ //Pide: nombre
        $jdata = $req->getContent();
        $data = json_decode($jdata);

        $response["status"]=1;
        try{
            if(isset($data->name)){
                $listaNombres = Venta::pluck('name');
                $coincidencias = [];
                $response["msg"] = "No hay ninguna coincidencia";
                foreach ($listaNombres as $key => $nombreCompleto) {
                    if(str_contains($nombreCompleto, $data->name)){
                        $response["msg"]="Articulos encontrados";
                        array_push($coincidencias, Venta::where('name', $nombreCompleto)->first());
                        //probar si se bugea con cartas que comparten parte del nombre
                    }
                }
                
                usort($coincidencias, function($object1, $object2) {
                    return $object1->price > $object2->price;
                });

                $listaRespuesta = [];
                $listaRespuesta2 = [];
                foreach ($coincidencias as $key => $anuncio) {
                    $listaRespuesta["id_anuncio"] =  $anuncio->id;
                    $listaRespuesta["id_carta"] =  $anuncio->carta_id;
                    $listaRespuesta["Carta"] =  $anuncio->name;
                    $listaRespuesta["Cantidad"] = $anuncio->quantity;
                    $listaRespuesta["Precio"] = $anuncio->price;
                    $listaRespuesta["Vendedor"] = (Usuario::find($anuncio->usuario_id))->nickname;
                    $listaRespuesta["id_vendedor"] =  $anuncio->usuario_id;
                    array_push($listaRespuesta2, $listaRespuesta);
                }
                $response["coincidencias"] = $listaRespuesta2;
            }else{
                throw new Exception("Error: Introduce un nombre de una carta (name)");
            }
        }catch(\Exception $e){
            $response["status"]=0;
            $response["msg"]=$e->getMessage();
        }
        return response()->json($response);
    }

    //FUNCIONES ADMIN:
    //añadir carta a coleccion     X
    //quitar carta de coleccion    -
    //editar carta                 -
    //editar coleccion             -
}
