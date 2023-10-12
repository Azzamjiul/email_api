<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use App\Models\Broadcast;
use Exception;

class BroadcastService
{
  public function executeBroadcast($document_id, $pdf)
  {
    $broadcast = Broadcast::findOrFail($document_id);

    $pdfPath = storage_path('app/public/') . $broadcast->uuid . '.pdf';

    // Check PDF
    if (!File::exists($pdfPath)) {
      $pdf = $pdf->loadView('pdf_view', ['content' => $broadcast->attachment_content]);
      $pdf->save($pdfPath);
    }

    // Get Targets
    $targets = $broadcast->targets()->where('status', '!=', 'SENT')->get();

    if ($targets->isEmpty()) {
      return response()->json(['message' => 'No targets available.'], 400);
    }

    foreach ($targets as $target) {
      try {
        // Send Email
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
  }
}
