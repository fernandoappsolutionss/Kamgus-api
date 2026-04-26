<?php

namespace App\Http\Controllers\V2\Inviteds;

use App\Classes\K_HelpersV1;
use App\Classes\StripeCustomClass;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleService;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\DriverService;
use App\Models\DriverVehicle;
use App\Models\FcmToken;
use App\Models\Image;
use App\Models\Route;
use App\Models\Service;
use App\Models\SubCategory;
use App\Models\Transaction;
use App\Models\TypeTransport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Notifications\SendExpoPushNotification;
use App\Repositories\CategoryRepository;
use DateTime;
use Illuminate\Support\Facades\Storage;
use DB;
class ArticlesController extends Controller
{
    protected $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository) {
        $this->categoryRepository = $categoryRepository;
    }
 
    private function getArticlesFields(){
        return [
            'articles.id as id', 
            'articles.sub_category_id as id_articulo_acarreo_2', 
            'sub_categories.category_id as id_articulo_acarreo_1', 
            "articles.name as nombre", 
            "articles.url_imagen as img",
            "articles.url_imagen as imagen",
            "articles.m3",
            "articles.altura",
            "articles.ancho",
            "articles.largo",
            "articles.price",
            "articles.tc",
        ];
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        //$articles = DB::table('categories')->get(["id", "name as nombre", "url_imagen as imagen"]);
        $articles = $this->categoryRepository->all();
        $response = array('error' => false, 'msg' => 'Cargando articulos', 'articulos'=> $articles);
        return response()->json( $response , self::HTTP_OK );
    }

    /**
     * Display all articles by a category id specified.
     *
     * @param  int  $id. category identifier
     * @return \Illuminate\Http\Response
     */
    public function show($id) //getArticulosNivel2, getArticulosNivel2All
    {
        //
        if($id === "all"){
            $subCategories = DB::table("sub_categories")->get(['id', 'category_id as id_articulo_acarreo', "name as nombre", "url_imagen as imagen"]);
            $response = array('error' => false, 'msg' => 'Cargando articulos', 'articulos'=> $subCategories);
            return response()->json($response);
        }

        $subCategories = DB::table("sub_categories")->where("category_id", $id)->get(['id', 'category_id as id_articulo_acarreo', "name as nombre", "url_imagen as imagen"]);
        $response = array('error' => false, 'msg' => 'Cargando articulos', 'articulos'=> $subCategories);

       return response()->json($response);
    }
     /**
     * Display all articles by a sub category id specified.
     *
     * @param  int  $id. Sub category identifier
     * @return \Illuminate\Http\Response
     */
    public function show2($id) //getArticulosNivel2, getArticulosNivel2All
    {
        //
        if($id === "all"){
            $articles = DB::table('articles')->join("sub_categories", "sub_categories.id", "=", "sub_category_id")->get($this->getArticlesFields());
            $response = array('error' => false, 'msg' => 'Cargando articulos', 'articulos'=> $articles);
            return response()->json($response);
        }

        $articles = DB::table('articles')->join("sub_categories", "sub_categories.id", "=", "sub_category_id")->where("sub_category_id", $id)->get($this->getArticlesFields());
        $response = array('error' => false, 'msg' => 'Cargando articulos', 'articulos'=> $articles);

       return response()->json($response);
    }
    /**
     * Display all articles that containt the expecified text.
     *
     * @param  int  $id. Reference text
     * @return \Illuminate\Http\Response
     */
    public function search($id) //getArticulosNivel2, getArticulosNivel2All
    {
        //
        $name = $id;
        $articles = DB::table('articles')
            ->join("sub_categories", "sub_categories.id", "=", "sub_category_id")
            ->where("name", "like", '%'.$name.'%')->get($this->getArticlesFields());
        $response = array('error' => false, 'msg' => 'Cargando articulos', 'articulos'=> $articles);

       return response()->json($response);
    }

}