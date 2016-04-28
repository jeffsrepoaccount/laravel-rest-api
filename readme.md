# API

This is a general purpose REST API package.  Use it to get a jumpstart on creating HATEOAS compliant REST services.

### Features

__Filters__ - Define custom filters that can be reused across all endpoints
__Pagination__ - Don't overload your database.  Pagination is accomplished via cursors and is appropriate for handling real-time, fluid data.
_TODO_ __Extensibility__ - By default, only JSON data is supported.  However, should you need to be able to return data back in other formats, you can easily override how the data is presented back in each request.

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