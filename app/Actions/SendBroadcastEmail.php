<?php

namespace App\Actions;

use Illuminate\Support\Facades\Mail;

class SendBroadcastEmail
{
  public function execute($broadcast, $pdfPath, $target)
  {
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
    } catch (\Throwable $th) {
      throw $th;
    }
  }
}
