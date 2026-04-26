<?php

namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = Category::all();

        return response()->json([
            'mensaje' => 'Consulta realizada',
            'codigo' => 200,
            'data' => $data
        ]);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function subcategoriesinonecategory($id)
    {
        // $data = DB::table('sub_categories')->where('category_id', $id)->get();
        $data = SubCategory::where('category_id', $id)->get();
        $complete = $data->load('articles');

        return response()->json([
            'mensaje' => 'Consulta realizada',
            'codigo' => 200,
            'data' => $complete
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function articlesinonesubcategory($category, $subcategory)
    {

        $category = Category::where('categories.id', '=', $category)->get();

        foreach($category as $d){
            $id = $d->id;
        }

        $subcategory = SubCategory::where('sub_categories.category_id', '=', $id)->where('sub_categories.id', '=', $subcategory)->get();

        foreach($subcategory as $d){
            $id = $d->id;
        }

        $data = DB::table('articles')->where('articles.sub_category_id', '=', $id)
                                     ->select( 'articles.id','articles.name')
                                     ->get();
        
        return response()->json([
            'mensaje' => 'Consulta realizada',
            'codigo' => 200,
            'data' => $data
        ]);
    }

   
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
