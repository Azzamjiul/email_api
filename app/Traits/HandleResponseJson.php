<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;

trait HandleResponseJson
{
  protected function handleResponseJson($callback)
  {
    try {
      return $callback();
    } catch (ValidationException $e) {
      return response()->json([
        'message' => 'Validation Error',
        'errors' => $e->errors()
      ], 422);
    } catch (ModelNotFoundException $e) {
      return response()->json([
        'message' => 'Not found',
        'error' => $e->getMessage()
      ], 404);
    } catch (Exception $e) {
      return response()->json([
        'message' => 'Something went wrong',
        'error' => $e->getMessage()
      ], 500);
    }
  }
}
