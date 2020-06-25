<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Category;

class categoryController extends Controller
{
    public function __construct(){
        $this->middleware('api.auth', ['except' => ['index', 'show']]);
    }

    public function index(){
        //Sacamos de la base de datos todas las categorias que esten disponibles.
        $categories = Category::all();//Utilizando la función "all()"
        return response()->json([
            'categories'=>$categories,
            'code'      =>200,
            'status'    =>'success'
        ]);
    }   

    public function show($id){
        $category = Category::find($id);
        if (is_object($category)) {
            $data = array(
                'category'=>$category,
                'code'      =>200,
                'status'    =>'success'
            );
        }else {
            $data = array(
                'message'=>'No se encontro ninguna busqueda relacionada.',
                'code'      =>404,
                'status'    =>'error'
            );
        }

        return response()->json($data,$data['code']);
    }

    public function store(Request $request){
         //Recoger los datos por post
         $json = $request->input('json', null);
         //Paso de una cadena texto a convertir a un objeto de PHP.
         $parametros_array = \json_decode($json, true);//Asi que paso como segundo parametro TRUE.
 
        if (!empty($parametros_array)) {
            //validar los datos
            $validate = \Validator::make($parametros_array, [
                //Aqui pongo lo que me intersa validar
                'name' => 'required'
            ]);
            //Guardar la categoria
            //Pero.....
            //Si falla al momento de validar los datos que nos muestre un arreglo con esos datos
            if ($validate->fails()) { 
                $data =[
                    'message'   =>'No se ha guardado la categoria',
                    'code'      =>400,
                    'status'    =>'error'
                ];
            }else {
                $category = new Category();
                $category->name = $parametros_array['name'];
                $category->save();
                $data =[
                    'category'   =>$category,
                    'code'      =>200,
                    'status'    =>'success'
                ];
            }
        }else{
            $data =[
                'message'   =>'No se ha enviado la categoria',
                'code'      =>404,
                'status'    =>'error'
            ];
        }
        //Devolver el resultado
        return response()->json($data, $data['code']);
    }

    public function update($id,Request $request){
        //Recoger los datos por post
        $json = $request->input('json', null);
        //Validar los datos
        $parametros_array = \json_decode($json, true);//Asi que paso como segundo parametro TRUE.

        if (!empty($parametros_array)) {
            //validar los datos
            $validate = \Validator::make($parametros_array, [
                //Aqui pongo lo que me intersa validar
                'name' => 'required'
            ]);

            //Quitar lo que no quiere que se actualice
            unset($parametros_array['id']);
            unset($parametros_array['create_at']);

            //Actualizar el registro Categoria
            $category = Category::where('id',$id)->update($parametros_array);
            $data = array(
                'category'  => $parametros_array,
                'code'      => 200,
                'status'    => 'success'
            );
        }else{
            $data = array(
                'message'   =>'No se ha actualizado',
                'code'      =>400,
                'status'    =>'error'
            );
        }
        //Devolver los datos.
        return response()->json($data, $data['code']);
    }
}   