<?php

namespace App\Http\Controllers;

use App\Models\Broadcast;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BroadcastController extends Controller
{
    public function index()
    {
        $broadcasts = Broadcast::all();
        return response()->json([
            'message' => 'Success',
            'data' => $broadcasts
        ], 200);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'message' => 'required|string',
                'attachment_content' => 'required|string',
            ]);

            $broadcast = Broadcast::create($validatedData);

            return response()->json([
                'message' => 'Success',
                'data' => $broadcast
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Resource not found',
                'error' => $e->getMessage()
            ], 404);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Database Error',
                'error' => $e->getMessage()
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $broadcast = Broadcast::findOrFail($id);

            return response()->json([
                'message' => 'Success',
                'data' => $broadcast
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Broadcast not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:255|not_in:""',
                'message' => 'sometimes|required|string|not_in:""',
                'attachment_content' => 'sometimes|required|string|not_in:""',
            ]);    

            $broadcast = Broadcast::findOrFail($id);
            $broadcast->update($validatedData);

            return response()->json([
                'message' => 'Updated successfully',
                'data' => $broadcast
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Broadcast not found'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function destroy($id)
    {
        try {
            $broadcast = Broadcast::findOrFail($id);
            $broadcast->delete();

            return response()->json([
                'message' => 'Deleted successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Broadcast not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
