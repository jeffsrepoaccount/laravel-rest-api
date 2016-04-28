<?php namespace Jnet\Api\Repository;

use Illuminate\Database\Eloquent\Model;
use App\Jnet\Api\Validators\ValidatorAbstract;

abstract class RepositoryAbstract
{
    protected $entity;
    protected $validator;

    public function __construct(Model $entity, ValidatorAbstract $validator)
    {
        $this->entity = $entity;
        $this->validator = $validator;
    }

    public function all($direction = 'DESC')
    {
        // Order by both cursor and created_at. If auto_increment 
        // ever gets reset, this will guarantee that results will
        // still be returned in the correct order
        return $this->entity
            ->orderBy('created_at', $direction)
            ->orderBy('cursor', $direction)
        ;
    }

    public function byId($id)
    {
        return $this->entity->where('id', $id);
    }

    public function create(array $data)
    {
        if(!$this->validator->fails($data)) {
            throw new InvalidArgumentException('Invalid data in input');
        }

        return $this->entity->create($data);
    }

    public function update($id, $data)
    {

    }

    public function delete($id)
    {

    }

    public function paginate($query, $perPage, $curCursor = null)
    {
        if($curCursor) {
            $query = $query->where('cursor', '<=', $curCursor);
        }

        return $query
            ->take($perPage)
        ;
    }

    public function previousPage($collection, $perPage, $curCursor)
    {

        $prev = $this->entity->where('cursor', '>=', $curCursor)
            ->orderBy('created_at', 'asc')
            ->orderBy('cursor', 'asc')
            ->take($perPage + 1)
            ->get()
            ->reverse()
            ->first()
        ;

        if($prev && ((string)$prev->cursor) !== $curCursor) {
            return $prev->cursor;
        }

        return null;
    }

    public function nextPage($collection, $perPage, $curCursor)
    {
        $next = $this->entity->where('cursor', '<=', $curCursor)
            ->orderBy('created_at', 'desc')
            ->orderBy('cursor', 'desc')
            ->take(1)
            ->skip($perPage)
            ->get()
            ->first()
        ;

        if($next && ((string)$next->cursor) !== $curCursor) {
            return $next->cursor;
        }

        return null;
    }
}
