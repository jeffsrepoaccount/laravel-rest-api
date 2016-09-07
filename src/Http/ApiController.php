<?php namespace Jnet\Api\Http;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Input;
use Jnet\Api\Filters\FilterInterface;
use Jnet\Api\Repository\RepositoryAbstract as Repository;
use Jnet\Api\Transformers\ErrorTransformer;
use League\Fractal\Pagination\Cursor;
use League\Fractal\TransformerAbstract as Transformer;
use League\Fractal\Resource\Collection as ResourceCollection;
use League\Fractal\Resource\Item as ResourceItem;
use League\Fractal\Manager as FractalManager;
use Log;

abstract class ApiController extends Controller
{
    protected $entity;
    protected $transformer;
    protected $errors;
    protected $startTime;
    protected $filters;

    public function __construct(Repository $entity, Transformer $transformer, ErrorTransformer $errors, FilterInterface $sieve)
    {
        $this->entity = $entity;
        $this->transformer = $transformer;
        $this->errors = $errors;
        $this->filters = $sieve;

        $this->startTime = microtime(true);
    }

    public function index()
    {
        $perPage = Input::get('number', config('api.per_page'));
        $cursor = base64_decode(Input::get('cursor', null));

        $query = $this->filters->filter( 
            $this->entity->all()
        );
        
        $entities = $this->entity->paginate($query, $perPage, $cursor)
            ->get()
        ;

        if($entities) {
            return $this->respondWithCollection(
                $entities,
                $perPage,
                $cursor
            );
        }

        return $this->emptyResponse();
    }

    public function show($id)
    {
        $query = $this->entity->byId($id);

        $entity = $this->filters->filter($query)->first();

        if($entity) {
            return $this->respondWithItem(
                $this->filters->filter($query)->first()
            );
        }

        return $this->errors->respondWithError(404);
    }

    public function create()
    {
        return $this->errors->respondWithError(501);
    }

    public function update($id)
    {
        return $this->errors->respondWithError(501);
    }

    public function delete($id)
    {
        return $this->errors->respondWithError(501);
    }

    protected function respondWithItem(Model $item)
    {
        $resource = new ResourceItem($item, $this->transformer);
        return $this->response($resource);
    }

    protected function respondWithCollection(Collection $collection, $perPage, $curCursor = null)
    {
        $resource = new ResourceCollection($collection, $this->transformer);
        $resource->setCursor($this->cursor($collection, $perPage, $curCursor));
        return $this->response($resource);
    }

    protected function emptyResponse()
    {
        return $this->response(['data' => []]);
    }

    protected function response($data, $status = 200)
    {
        if(!is_array($data)) {
            $data = $this->fractalizeData($data);
        }
        
        Log::info('API Response', [
//            'user_id' => $this->user->id,
//            'customer_id' => $this->user->customer_id,
            'time_ms' => round(microtime(true) - $this->startTime, 5) * 1000,
        ]);
        return response()->json($data, $status);
    }

    protected function fractalizeData($data)
    {
        $fractal = new FractalManager;

        if($includes = Input::get('include', null)) {
            $fractal->parseIncludes($includes);
        }

        return $fractal->createData($data)->toArray();
    }

    protected function cursor($collection, $perPage, $curCursor)
    {
        $curCursor = ($curCursor && count($collection)) ? $collection->first()->cursor : null; 
        $prevCursor = null;
        $nextCursor = $this->entity->nextPage($collection, $perPage, (string)$curCursor);

        if($curCursor) {
            $prevCursor = $this->entity->previousPage($collection, $perPage, (string)$curCursor);    
        }

        return new Cursor(
            base64_encode($curCursor), 
            $prevCursor ? base64_encode($prevCursor) : null,
            $nextCursor ? base64_encode($nextCursor) : null,
            $collection->count()
        );
    }

    protected function setTransformer(Transformer $transformer)
    {
        $this->transformer = $transformer;
    }
}
