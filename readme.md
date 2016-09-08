# API

This is a general purpose REST API package.  Use it to get a jumpstart on creating HATEOAS compliant REST services.

### Features

__Filters__ - Define custom filters that can be reused across all endpoints

__Pagination__ - Don't overload your database.  Pagination is accomplished via cursors and is appropriate for handling real-time, fluid data.

__Extensibility__ - By default, only JSON data is supported.  However, should you need to be able to return data back in other formats, you can easily override how the data is presented back in each request.

## Usage

To be able to return resources, you need some resources to return.  In a migration create your table structure.  An additional auto-increment field is necessary for use when constructing cursors.

```php
Schema::create('resource', function($tale) {
    $table->string('id', 36)->primary(); // GUIDs prevent maths
    $table->string('name', '255');
    // ... other fields ...
});

// Add cursor column. Laravel schema builder automatically makes auto increment
// fields primary keys, which we don't really want to do.
DB::statement('ALTER TABLE messages ADD cursor BIGINT NOT NULL UNIQUE AUTO_INCREMENT');
```
With a resource, now you need a model:

```php
<?php namespace My\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Jnet\Api\Models\UuidForKey;

class Amodel extends Model
{
    use UuidForKey, SoftDeletes;

    protected $table = 'resource';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        // your fillable fields
        'name',
    ];

    // any relationships
}
```

Create a corresponding validator for the resource:

```php
<?php namespace My\Validators;

class ResourceValidator extends ValidatorAbstract
{
    protected $rules = [
        'name' => 'required',
    ];

    protected $messages = [
        'name.required' => 'A name is required to create this resource',
    ];
}
```

Register the validator inside of a service provider:

```php
$app->bind('My\Validators\ResourceValidator', function() {
    return new ResourceValidator(Input::all());
});
```

Now that you have a model and a validator properly registered, create a repository:

```php
<?php namespace My\Repository;

use Jnet\Api\Repository\RepositoryAbstract;
use My\Models\Amodel;
use My\Validators\ResourceValidator;

class ResourceRepository extends RepositoryAbstract
{
    public function __construct(Amodel $entity, ResourceValidator $validator)
    {
        parent::__construct($entity, $validator);
    }
}

```

With these pieces in place, create the resource's transformer:

```php
<?php namespace My\Transformers;

use League\Fractal\TransformerAbstract;
use My\Models\Amodel;

class RoomTransformer extends TransformerAbstract
{
    protected $availableIncludes = [

    ];

    public function transform(Amodel $resource)
    {
        return [
            'id'            => $resource->id,
            'name'          => $resource->name,
            'created_at'    => (string) $resource->created_at,
            'links'         => [
                [ 'rel' => 'self', 'uri' => '/resource/' . $resource->id ],
            ],
        ];
    }
}
```

Lastly, create a controller and add route endpoints to your resource:

```php
<?php namespace My\Controllers;

use Jnet\Api\Controllers\ApiController;
use Jnet\Api\Filters\FilterInterface;
use Jnet\Api\Transformers\ErrorTransformer;
use My\Repository\ResourceRepository;
use My\Transformers\ResourceTransformer;

class ResourceController extends ApiController
{
    public function __construct(
        ResourceRepository $resources, 
        ResourceTransformer $transformer, 
        ErrorTransformer $errors, 
        FilterInterface $sieve
    ) {
        parent::__construct($resources, $transformer, $errors, $sieve);
    }
}
```

```php
Route::group(['prefix' => '/api/v1',], function() {
    Route::get('/resources',        'My\Controllers\ResourceController@index');
    Route::get('/resources/{id}',   'My\Controllers\ResourceController@show');
    Route::post('/resources',       'MyControllers\ResourceController@create')
    Route::post('/resources/{id}',   'My\Controllers\ResourceController@update');
});
```

### Filters

Filters can be useful for allowing resource listings to be constrained to arbitrary conditions.  Setting them up is a pretty straightforward process.  The API provides a filter container which I refer to as a _sieve_ that is used to parse incoming request parameters and determine whcih filters need to be applied.  Inside of a service provider, register your application-specific filters with the sieve:

```php
use Illuminate\Http\Request;
use Jnet\Api\Filters\FilterInterface;
use My\Filters\TitleFilter;
//...

$request = $app->make(Request::class);
// Retrieve instance of API sieve
$sieve = $app->make(FilterInterface::class);
// Each filter needs an input key
$myFilterKey = 'title';
// If the request has an input with the specific key,
// create a new TitleFilter instance and attach to 
// the sieve.
if($request->has($myFilterKey)) {
    $sieve->addFilter(new TitleFilter($myFilterKey, $request->input($myFilterKey)));
}
```

The filter class itself is dead simple.  The API provides a number of convenient classes that implement `Jnet\Api\Filters\FilterInterface` that can simply be extended.  For example, the title filter above could be implemented like this:

```php
<?php namespace My\Filters;

use Jnet\Api\Filters\Equals;

class TitleFilter extends Equals {}
```

What this is saying is that, when a request comes in specifying a title, we want only those records to be returned back whose title matches exactly (_equals_) the request input value.  For instance, a request to this endpoint:

```
GET /resources?title=foo
```

Will return all `resources` whose title is equal to `foo`.  Similar convenience classes are available, such as `GreaterThan`, `GreaterThanOrEqualTo`, `LessThan`, and `LessThanOrEqualTo`.  If you need a more complicated approach for your filter, you can extend `Jnet\Api\Filters\FilterAbstract` directly.  Below is the implementation of the `Equals` filter:

```php
<?php namespace Jnet\Api\Filters;

use Illuminate\Database\Eloquent\Builder;

class Equals extends FilterAbstract implements FilterInterface
{
    public function filter(Builder $query)
    {
        return $query->where($this->key, $this->value);
    }
}
```

Each filter needs to implement a `filter` method that will be handed a `Builder` object that you have a chance to modify as you see fit. Once the query is modified, return it back out and you're done.