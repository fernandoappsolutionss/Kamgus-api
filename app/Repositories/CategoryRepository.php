<?php
namespace App\Repositories;

use DB;
use App\Models\Article;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;

class CategoryRepository implements ArticleRepositoryInterface
{
    protected $model;

    /**
     * PostRepository constructor.
     *
     * @param Article $article
     */
    public function __construct(Category $model)
    {
        $this->model = DB::table('categories');
        //$this->model = $model;
    }

    public function all()
    {
        return Cache::remember("categories", Carbon::now()->addMinutes(15), function(){
            return $this->model->get(["id", "name as nombre", "url_imagen as imagen"]);
        });
    }

    public function create(array $data)
    {
        Cache::forget("categories");
        return $this->model->create($data);
    }
    
    public function update(array $data, $id)
    {
        Cache::forget("categories");
        return $this->model->where('id', $id)
        ->update($data);
    }
    
    public function delete($id)
    {
        Cache::forget("categories");        
        return $this->model->destroy($id);
    }

    public function find($id)
    {
        if (null == $article = $this->model->find($id)) {
            throw new ModelNotFoundException("Article not found");
        }

        return $article;
    }
}
