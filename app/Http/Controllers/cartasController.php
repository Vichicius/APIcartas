<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Carta;
use App\Models\Coleccion;
use App\Models\Cartacoleccion;
use Exception;

class cartasController extends Controller
{

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
                $nombre = $data->name;
                if(!is_string($nombre) || strlen($nombre) > 60){
                    throw new Exception("Error: Nombre no válido");
                }
                $descripcion = $data->description;
                if(!is_string($descripcion) || strlen($descripcion) > 150){
                    throw new Exception("Error: Descripción no válida");
                }
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
            if(!( isset($data->carta_id) || (isset($data->name_card) && isset($data->description_card))) ){
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
            if(!is_string($data->name_coleccion) || strlen($data->name_coleccion) > 50){
                throw new Exception("Error: Nombre de colección no válido");
            }
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
                    if(!is_string($nombre) || strlen($nombre) > 60){
                        throw new Exception("Error: Nombre no válido");
                    }
                    if(!is_string($data->description_card[$posicion]) || strlen($data->description_card[$posicion]) > 150){
                        throw new Exception("Error: Descripción no válida");
                    }
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

}
