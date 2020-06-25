<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Post;
use App\Helpers\JwtAuth;

class postController extends Controller
{
    public function __construct(){
        $this->middleware('api.auth', ['except' => [
            'index',
            'show',
            'getImage',
            'getPostsByCategory',
            'getPostsByUser'
            ]]);
    }

    
    public function upload(Request $request){ //Request para poder recibir datos
    $image = $request->file('file0'); //Recoger los datos de la peticion.
    //Validacion de la imagen
    $validate = \Validator::make($request->all(),[  //<- todos los datos que recibimos por REQUEST
        'file0'=>'required | image | mimes:jpg,jpeg,png,gif'//Tipos de archivos se permitira subir al servidor.
    ]);
    //Guardar imagen
    if (!$image || $validate->fails()){
        //Devolver el resultado, cuando es incorrrecta la información. 
        $data = array(
        'status' => 'error',
        'code'   => 400,
        'message'=> 'Error al subir imagen.'
        );
    }else{
        $image_name = time().$image->getClientOriginalName();
        //Guardamos la imagen en un disco virtual que
        //es una carpeta localizada en STORGAE, la carpeta se llama
        // 'images'
        \Storage::disk('images')->put($image_name, \File::get($image));
        $data = array(
            'image' => $image_name,
            'code'  => 200,
            'status'=> 'success'
        );
    }
    return response()->json($data,$data['code']);
    }

    public function index(){
        //Sacamos de la base de datos todas las categorias que esten disponibles.
        $posts = Post::all()->load('category');//Utilizando la función "all()"
        return response()->json([
            'posts'     =>$posts,
            'code'      =>200,
            'status'    =>'success'
        ],200);
    }

    public function show($id){
        $post = Post::find($id)->load('category');
        if (is_object($post)) {
            $data = array(
                'posts'     =>$post,
                'code'      =>200,
                'status'    =>'success'
            );
        }else {
            $data = array(
                'message'   => 'No se encontro ninguna busqueda relacionada.',
                'code'      => 404,
                'status'    => 'error'
            );
        }
        return response()->json($data,$data['code']);
    }

    public function store(Request $request){
        //Recoger datos por post
        $json = $request->input('json', null);
        $parametros = json_decode($json);
        $parametros_array = json_decode($json, true);

        if (!empty($parametros_array)) {
            //Conseguir el usuario identificado
            $user = $this->getIdentity($request);

            //validar Datos
            $validate = \Validator::make($parametros_array , [
                'title'         =>'required',
                'content'       =>'required',
                'category_id'   =>'required',
                'image'         =>'required'
            ]);

            if ($validate->fails()) {
                $data = array(
                    'code' => 400,
                    'status'=>'error',
                    'message'=>'No se puede guardar, Faltan datos......'
                );
            } else {
                //Guardar el post
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $parametros->category_id;
                $post->title = $parametros->title;
                $post->content = $parametros->content;
                $post->image = $parametros->image;
                $post->save();

                $data = [
                    'code' => 200,
                    'status'=>'success',
                    'post'=>$post
                ];
            }       
        }else{
            $data = [
                'code' => 400,
                'status'=>'error',
                'message'=>'Envia los datos correctamente.'
            ];
        }
        //Devolver la respuesta
        return response()->json($data, $data['code']);
    }

    public function update($id, Request $request){
        //recoger los datos por post
        $json = $request->input('json', null);
        $parametros_array = json_decode($json, true);
        
        $data = array(
            'code'      => 400,
            'status'    => 'error',
            'masage'    => 'Datos enviados incorrectos'
        );
        
        if (!empty($parametros_array)) {
            //Validar los datos
            $validate = \Validator::make($parametros_array, [
                'title' => 'required',
                'content'=>'required',
                'category_id'=> 'required'
            ]);
            if ($validate->fails()) {
                $data['errors'] = $validate->errors();
                return response()->json($data, $data['code']);
            }
            //Eliminar lo que no queremos actualizar
            unset($parametros_array['id']);
            unset($parametros_array['user_id']);
            unset($parametros_array['create_at']);
            unset($parametros_array['user']);

            //Conseguir el usuario identificado
            $user = $this->getIdentity($request);

            //Buscar el registro a actualizar
            $post = Post::where('id', $id)
                        ->where('user_id', $user->sub)
                        ->first();
            if (!empty($post) && is_object($post)) {
                //Actualizar el contenido del POST
                $post->update($parametros_array);
                $data = array(
                    'code'      => 200,
                    'status'    => 'success',
                    'post'      => $post,
                    'changes'   => $parametros_array
                );    
            }

        }
        return response()->json($data, $data['code']);
    }

    public function destroy($id, request $request){
        //Conseguir el usuario identificado
        $user = $this->getIdentity($request);

        //Conseguir el registro.
        $post = Post::where('id',$id)
                    ->where('user_id', $user->sub)
                    ->first();
        if (!empty($post)) {
            //Borrar el registro.
            $post->delete();
            //Devolver información.
            $data = array(
                'code' => 200,
                'status' => 'succes',
                'post'=> $post
            );
        }else{
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message'=> 'Error, no es posbile borrar.'
            );
        }
        return response()->json($data, $data['code']);
    }

    private function getIdentity($request){
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);
        return $user;
    }

    public function getImage($filename){
        //Comprobar si existe el archi
        $isset = \Storage::disk('images')->exists($filename);
        if ($isset) {
            //Conseguir la image
            $file = \Storage::disk('images')->get($filename);
            //Devolver la imagen
            return new Response($file, 200);
        }else{
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message'=> 'Error, no existe la imagen.'
            );        
        }
        return response()->json($data, $data['code']);
    }

    public function getPostsByCategory($id){
        //Resultado de todos los post por categoria que coincidan con el id que haga la consulta
        $posts = Post::where('category_id', $id)->get();
        return \response()->json([
                'posts'      => $posts,
                'status'    => 'success'
        ], 200);
    }

    public function getPostsByUser($id){
        //Resultado de todos los post por usuario que coincidan con el id que haga la consulta
        $posts = Post::where('user_id', $id)->get();
        return \response()->json([
                'posts'      => $posts,
                'status'    => 'success'
        ], 200);
    }
} 