<?php

namespace App\Listeners;

use App\Events\BroadcastCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Facades\File;

class CreatePdfForBroadcast
{
    private $pdf;

    /**
     * Create the event listener.
     */
    public function __construct(PDF $pdf)
    {
        $this->pdf = $pdf;
    }

    /**
     * Handle the event.
     */
    public function handle(BroadcastCreated $event)
    {
        $broadcast = $event->broadcast;
        $pdfPath = storage_path('app/public/') . $broadcast->uuid . '.pdf';

        if (!File::exists($pdfPath)) {
            $pdf = $this->pdf->loadView('pdf_view', ['content' => $broadcast->attachment_content]);
            $pdf->save($pdfPath);
        }
    }
}
