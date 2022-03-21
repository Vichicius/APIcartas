<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Carta;
use App\Models\Venta;
use Exception;
use Illuminate\Support\Facades\DB;

class VentasController extends Controller
{

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

                $cartas = Carta::where('name','like','%'.$data->name.'%')->get();
                if(count($cartas) == 0){
                    throw new Exception("No hay ninguna coincidencia");
                }
                $listaRespuesta = [];
                $listaRespuesta2 = [];
                foreach ($cartas as $key => $carta) {
                    $response["msg"]="Cartas encontradas";
                    $listaRespuesta["id"] = $carta->id;
                    $listaRespuesta["nombre"] = $carta->name;
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

    public function buscarAnuncio(Request $req){ //Pide: name
        $jdata = $req->getContent();
        $data = json_decode($jdata);

        $response["status"]=1;
        try{
            if(isset($data->name)){


                $coincidenciasColeccion = DB::table('ventas')
                                        ->leftjoin('cartas', 'ventas.carta_id', '=', 'cartas.id')
                                        ->where('cartas.name', 'like','%'.$data->name.'%')
                                        ->get()->toArray();

                if(count($coincidenciasColeccion) == 0){
                    throw new Exception("No hay ninguna coincidencia");
                }
                $coincidencias = [];
                foreach ($coincidenciasColeccion as $key => $coincidencia) {
                    array_push($coincidencias, $coincidencia);
                }

                $response["msg"]="Articulos encontrados";

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

}
