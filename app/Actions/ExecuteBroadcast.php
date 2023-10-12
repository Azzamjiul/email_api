<?php

namespace App\Actions;

use Exception;
use App\Models\Broadcast;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ExecuteBroadcast
{
  public function __construct(
    private readonly CheckPdfExist $checkPdfExistAction,
    private readonly SendBroadcastEmail $sendBroadcastEmail
  ) {
  }

  public function execute($document_id)
  {
    try {
      $broadcast = Broadcast::findOrFail($document_id);
      $pdfPath = storage_path('app/public/') . $broadcast->uuid . '.pdf';
      $this->checkPdfExistAction->execute($pdfPath, $broadcast->attachment_content);

      $targets = $broadcast->targets()->where('status', '!=', 'SENT')->get();
      if ($targets->isEmpty()) {
        throw new ModelNotFoundException('No targets available.');
      }

      foreach ($targets as $target) {
        try {
          $this->sendBroadcastEmail->execute($broadcast, $pdfPath, $target);
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
    } catch (\Throwable $th) {
      throw $th;
    }
  }
}
