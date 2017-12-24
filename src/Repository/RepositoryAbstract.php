<?php namespace Jnet\Api\Repository;

use Illuminate\Database\Eloquent\Model;
use Jnet\Api\Validators\ValidatorAbstract;

use Jnet\Api\Exceptions\ValidationFailed;
use Jnet\Api\Exceptions\NotFound;

abstract class RepositoryAbstract
{
    protected $entity;
    protected $validator;

    /* @var Illuminate\Support\MessageBag  */
    protected $errors = null;

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
            ->orderBy('cursor', $direction)
            ->orderBy('created_at', $direction)
        ;
    }

    public function byId($id)
    {
        return $this->entity->where('id', $id)->first();
    }

    public function create(array $data)
    {
        if($this->validator->setData($data)->fails()) {
            $this->errors = $this->validator->errors();
            throw new ValidationFailed('Invalid data in input');
        }

        return $this->entity->create($data);
    }

    public function update($id, array $data)
    {
        if($this->validator->setData($data)->fails()) {
            $this->errors = $this->validator->errors();
            throw new ValidationFailed('Invalid data in input');
        }

        if(!$entity = $this->byId($id)) {
            // TODO: Add error message in message bag
            throw new NotFound('Record not found');
        }

        // Prevent overwriting of id
        $data[$this->entity->getKeyName()] = $id;

        $entity->fill($data)->save();

        return $entity;
    }

    public function delete($id)
    {
        if(!$entity = $this->byId($id)) {
            // TODO: Add error message in message bag
            throw new NotFound('Record not found');
        }

        $entity->delete();
        return $entity;
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
        $next = $this->entity
            ->orderBy('created_at', 'desc')
            ->orderBy('cursor', 'desc')
            ->take(1)
            ->skip($perPage)
        ;

        if($curCursor) {
            $next = $next->where('cursor', '<=', $curCursor);
        }

        $next = $next->get()->first();

        if($next && ((string)$next->cursor) !== $curCursor) {
            return $next->cursor;
        }

        return null;
    }
}
