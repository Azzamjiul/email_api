<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddTargetsRequest;
use App\Http\Requests\StoreBroadcastRequest;
use App\Http\Requests\UpdateBroadcastRequest;
use App\Models\Broadcast;
use App\Services\BroadcastService;
use App\Traits\HandleResponseJson;
use Barryvdh\DomPDF\PDF;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class BroadcastController extends Controller
{
    use HandleResponseJson;

    public function index()
    {
        $broadcasts = Broadcast::all();
        return response()->json([
            'message' => 'Success',
            'data' => $broadcasts
        ], 200);
    }

    public function store(StoreBroadcastRequest $request, PDF $pdf)
    {
        return $this->handleResponseJson(function () use ($request, $pdf) {
            $broadcast = Broadcast::create($request->validated());

            // Cek apakah file PDF sudah ada
            $pdfPath = storage_path('app/public/') . $broadcast->uuid . '.pdf';

            if (!File::exists($pdfPath)) {
                // Jika belum, buat PDF dari attachment_content
                $pdf = $pdf->loadView('pdf_view', ['content' => $broadcast->attachment_content]);
                $pdf->save($pdfPath);
            }

            return response()->json([
                'message' => 'Success',
                'data' => $broadcast
            ], 201);
        });
    }

    public function show($id)
    {
        return $this->handleResponseJson(function () use ($id) {
            $broadcast = Broadcast::findOrFail($id);

            return response()->json([
                'message' => 'Success',
                'data' => $broadcast
            ], 200);
        });
    }

    public function update(UpdateBroadcastRequest $request, $id)
    {
        return $this->handleResponseJson(function () use ($request, $id) {
            $broadcast = Broadcast::findOrFail($id);
            $broadcast->update($request->validated());

            return response()->json([
                'message' => 'Updated successfully',
                'data' => $broadcast
            ], 200);
        });
    }


    public function destroy($id)
    {
        return $this->handleResponseJson(function () use ($id) {
            $broadcast = Broadcast::findOrFail($id);
            $broadcast->delete();

            return response()->json([
                'message' => 'Deleted successfully'
            ], 200);
        });
    }

    public function addTargetToBroadcast(AddTargetsRequest $request, $broadcastId)
    {
        try {
            $broadcast = Broadcast::findOrFail($broadcastId);
            $validatedData = $request->validated();
            $targets = $validatedData['targets'];

            foreach ($targets as $target) {
                $broadcast->targets()->create($target);
            }

            return response()->json([
                'message' => 'Targets added successfully'
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Broadcast not found'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function executeBroadcast($document_id, PDF $pdf)
    {
        try {
            $broadcastService = new BroadcastService();
            $broadcastService->executeBroadcast($document_id, $pdf);

            return response()->json(['message' => 'Broadcast executed successfully.']);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Broadcast not found'
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'An error occurred during broadcast execution.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
