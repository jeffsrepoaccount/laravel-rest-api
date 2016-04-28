<?php namespace Jnet\Api\Transformers;

class ErrorTransformer
{
    protected static $errors = [
        400 => [ 'code' => 'FUBARGS',       'message' => 'Bad Request' ],
        404 => [ 'code' => 'GONLIKWIND',    'message' => 'Not Found' ],
        500 => [ 'code' => 'OOPS',          'message' => 'Internal Server Error' ],
        501 => [ 'code' => 'IGIT2IT',       'message' => 'Not Implemented' ],

    ];

    public function respondWithError($status)
    {
        if(!isset(self::$errors[$status])) {
            $status = 500;
        }

        return response()->json(self::$errors[$status], $status);
    }
}
