<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddTargetsRequest;
use App\Http\Requests\StoreBroadcastRequest;
use App\Http\Requests\UpdateBroadcastRequest;
use App\Models\Broadcast;
use Barryvdh\DomPDF\PDF;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
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

    public function store(StoreBroadcastRequest $request, PDF $pdf)
    {
        try {
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

    public function update(UpdateBroadcastRequest $request, $id)
    {
        try {
            $broadcast = Broadcast::findOrFail($id);
            $broadcast->update($request->validated());

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
            $broadcast = Broadcast::findOrFail($document_id);

            $pdfPath = storage_path('app/public/') . $broadcast->uuid . '.pdf';

            if (!File::exists($pdfPath)) {
                $pdf = $pdf->loadView('pdf_view', ['content' => $broadcast->attachment_content]);
                $pdf->save($pdfPath);
            }

            $targets = $broadcast->targets()->where('status', '!=', 'SENT')->get();

            if ($targets->isEmpty()) {
                return response()->json(['message' => 'No targets available.'], 400);
            }

            foreach ($targets as $target) {
                try {
                    Mail::send(
                        'mail',
                        [
                            'broadcastMessage' => $broadcast->message,
                            'target' => $target
                        ],
                        function ($m) use ($target, $pdfPath) {
                            $m->from('your_email@example.com', 'Your Name');
                            $m->to($target->email, $target->name)->subject('Your subject here');
                            $m->attach($pdfPath);
                        }
                    );

                    $target->status = 'SENT';
                    $target->sent_at = now();
                    $target->save();
                } catch (Exception $mailException) {
                    $target->status = 'FAILED';
                    $target->sent_at = now();
                    $target->save();
                    throw $mailException;
                }
            }

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
