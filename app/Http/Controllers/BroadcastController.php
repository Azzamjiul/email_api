<?php

namespace App\Http\Controllers;

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

    public function addTargetToBroadcast(Request $request, $broadcastId)
    {
        try {
            $broadcast = Broadcast::findOrFail($broadcastId);

            // Validasi data targets
            $validatedData = $request->validate([
                'targets.*.name' => 'required|string|max:255',
                'targets.*.email' => 'required|email|max:255',
            ]);

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
            // Mengambil broadcast berdasarkan ID yang diberikan
            $broadcast = Broadcast::findOrFail($document_id);

            // Cek apakah file PDF sudah ada
            $pdfPath = storage_path('app/public/') . $broadcast->uuid . '.pdf';

            if (!File::exists($pdfPath)) {
                // Jika belum, buat PDF dari attachment_content
                $pdf = $pdf->loadView('pdf_view', ['content' => $broadcast->attachment_content]);
                $pdf->save($pdfPath);
            }

            // Ambil semua target
            $targets = $broadcast->targets;

            // Jika tidak ada target, return info
            if ($targets->isEmpty()) {
                return response()->json(['message' => 'No targets available.'], 400);
            }

            // Loop melalui semua target dan kirim email
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

                    // Jika pengiriman berhasil, update status target
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
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'An error occurred during broadcast execution.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
