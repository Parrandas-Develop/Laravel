<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;

class userController extends Controller
{
    public function register(Request $request){
        //Recoger los datos del usuario
        $json = $request -> input('json', null);
        $parametros = \json_decode($json);
        $parametros_array = \json_decode($json,true);
        if(!empty($parametros) && !empty($parametros_array)){
            $parametros_array = \array_map('trim',$parametros_array);
            //Validar los datos del usuario por POST
            $validar_datos = \Validator::make($parametros_array, [
                'name'      => 'required | alpha',
                'surname'   => 'required | alpha',
                'email'     => 'required | email | unique:users',
                'password'  => 'required'
            ]);

            if($validar_datos->fails()){
                //La validación para la creación del usuario ha fallado
                $data = array(
                    'status' => 'error',
                    'code'   => 404,
                    'message'=> 'El usuario no se ha podido crear.',
                    'errores'=> $validar_datos->errors()
                );
            }else{
                //Validación de la creación del usuario correcto
                //Cifrar la contraseña
                $pwd = hash('sha256', $parametros->password);
                //Comprobar si el usuario ya existe para evitar duplicado de USUARIOS
                //Crear al usuario
                $user=new User();
                $user->name = $parametros_array['name'];
                $user->surname = $parametros_array['surname'];
                $user->email = $parametros_array['email'];
                $user->password = $pwd;
                $user->role='User_Role';
                //Guardar el usuario
                $user->save();
                //Devuelve un JSON
                $data = array(
                    'status' => 'succes',
                    'code'   => 200,
                    'message'=> 'El usuario SI se ha creado.'
                );
            }
            }else{
                $data = array(
                    'status' => 'error',
                    'code'   => 404,
                    'message'=> 'Los datos no son correctos.'
            );
        }
            return response()->json($data, $data['code']);
    }

    public function login(Request $request){
        $jwtAuth = new \JwtAuth();
        //Recibir datos por POST.
        $json=$request->input('json', null);
        $parametros = \json_decode($json);
        $parametros_array = \json_decode($json,true);
        //Validar los datos.
        $validar_datos = \Validator::make($parametros_array, [
            'email'     => 'required | email',
            'password'  => 'required'
            ]);
        if($validar_datos->fails()){
            //La validación para la creación del usuario ha fallado
            $signup = array(
                'status' => 'error',
                'code'   => 404,
                'message'=> 'El usuario NO se ha podido identificar.',
                'errores'=> $validar_datos->errors()
            );
        }else{
            //Cifrar la contraseña.
            $pwd = hash('sha256', $parametros->password);
            //Devolver el Token o Datos.
            $signup=$jwtAuth->signup($parametros->email, $pwd);
            if(!empty($parametros->gettoken)){
                $signup=$jwtAuth->signup($parametros->email, $pwd, true);
            }
        }
        //Mando a llamar el metodo de SINGUP de HELPERS/JWTAUTH
        return response()->json($signup, 200);
    }

    public function update(Request $request){
        //Comprobar si el usuario está identificado
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        //Recoger los datos por post.
        $json = $request -> input('json',null);
        $parametros_array = \json_decode($json, true);
        if ($checkToken && !empty($parametros_array)) {
            //Identificar al usuario de la base de datos y sacar su identificador.
            $user = $jwtAuth->checkToken($token, true);
            //Validar los datos.
            $validate = \Validator::make($parametros_array, [
                'name'      => 'required | alpha',
                'surname'   => 'required | alpha',
                'email'     => 'required | email | unique:users'.$user->sub
            ]);
            //Quitar los campos que no quiero actualizar.
            unset($parametros_array['id']);
            unset($parametros_array['role']);
            unset($parametros_array['remember_token']);
            unset($parametros_array['create_at']);

            //Actualizar el usuario en la base de datos.
            $user_update = User::where('id', $user->sub)->update($parametros_array);
            //Devolver un array con los resultados.
            $data = array(
                'status' => 'succes',
                'code'   => 200,
                'user'=> $user,
                'changes'=>$parametros_array
            );
        }else{
            $data = array(
                'status' => 'error',
                'code'   => 400,
                'message'=> 'El usuario NO se ha podido identificar.'
            );
        }
        return response()->json($data, $data['code']);
    }

    public function upload(Request $request){
        //Recoger los datos (IMAGEN) de la peticion.
        $image = $request->file('file0');
        //Validacion de la imagen
        $validate = \Validator::make($request->all(),[
            //Que tipos de archivos se permitira subir al servidor.
            'file0'=>'required | image | mimes:jpg,jpeg,png,gif'
        ]);
        //Guardar imagen
        if (!$image || $validate->fails()) {
            //Devolver el resultado, cuando es incorrrecta la información.
            $data = array(
                'status' => 'error',
                'code'   => 400,
                'message'=> 'El usuario no subio el formato de archivo correcto.'
            );
        }else{
            $image_name = time().$image->getClientOriginalname();
            //Guardamos la imagen en un disco virtual que
            //es una carpeta localizada en STORAGE, la carpeta se llama
            // 'users'
            \Storage::disk('users')->put($image_name, \File::get($image));
            $data = array(
                'image' => $image_name,
                'code'  => 200,
                'status'=> 'success'
            );
        }
        return response()->json($data,$data['code']);
    }

    public function getImage($filename){
        //Comprobar que existe el elemento que queremos extraer.
        $isset=\Storage::disk('users')->exists($filename);
        if ($isset) {
            //Sacar de mi disco donde se almacena las imagenes del usuario.
            $file = \Storage::disk('users')->get($filename);
            return new Response ($file, 200);
        }else{
            $data = array(
                'massage'   => 'Error no existe ningun archivo con ese nombre',
                'code'      => 404,
                'status'    => 'error'
            );
            return response()->json($data,$data['code']);
        }
    }

    public function detail($id){
        $user = User::find($id);
        if(is_object($user)){
            $data = array(
                'code'  => 200,
                'status'=> 'success',
                'user'  => $user
            );
        }else{
            $data = array(
                'code'  => 404,
                'status'=> 'error',
                'user'  => 'El usuario no exite.'
            );
        }
        return response()->json($data, $data['code']);
    }
}
