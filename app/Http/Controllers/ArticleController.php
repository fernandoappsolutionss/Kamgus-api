<?php

namespace App\Http\Controllers;

use App\Constants\Constant;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Resources\Customers\ArticleCollection;
use Illuminate\Http\Request;
use App\Models\Article;
use App\Models\SubCategory;

class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
       return new ArticleCollection(Article::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreArticleRequest $request)
    {
        $request->validated();

        if ($request->file('url_imagen')) {
            
            $path = $request->file('url_imagen')->store('images');

        }

        $data = new Article();
        $data->name = $request->name;
        $data->url_imagen = $path;
        $data->m3 = $request->m3;
        $data->altura = $request->altura;
        $data->ancho = $request->ancho;
        $data->largo = $request->largo;
        $data->price = $request->price;
        $data->sub_category_id = $request->sub_category_id;

        if($data->save()){

            return response()->json([
                'mensaje' => Constant::CREATE_ARTICLE,
                'codigo' => 200,
                'data' => $data
            ]);

        }

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $data = [];
        $subca = [];
        $i = 0;
        $flag = 0;
        $resultados = Article::search($request->article);

        foreach($resultados as $resultado){

            if($flag == 0){
                $subca[$i] = $resultado->sub_category_id;
                $data[$i] = SubCategory::where('id', $resultado->sub_category_id)->first();
                $i++;
                $flag = 1;   
            }else{
                $indice = array_search($resultado->sub_category_id,$subca);
                if($subca[$indice] !== $resultado->sub_category_id){
                    $subca[$i] = $resultado->sub_category_id;
                    $data[$i] = SubCategory::where('id', $resultado->sub_category_id)->first();
                    $i++;
                }
            } 
        }
      
        return response()->json([
            'mensaje' => 'Se realizo la consulta',
            'codigo' => 200,
            'data' => $data
        ]);
    }

   
}
