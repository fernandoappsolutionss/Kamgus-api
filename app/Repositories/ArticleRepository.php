<?php
namespace App\Repositories;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class ArticleRepository implements ArticleRepositoryInterface
{
    protected $model;

    /**
     * PostRepository constructor.
     *
     * @param Post $post
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(array $data, $id)
    {
        return $this->model->where('id', $id)
            ->update($data);
    }

    public function delete($id)
    {
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
