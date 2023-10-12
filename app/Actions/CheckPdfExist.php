<?php

namespace App\Actions;

use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Facades\File;

class CheckPdfExist
{
  public function __construct(
    private readonly PDF $pdf
  ) {}

  public function execute($pdfPath, $attachment_content)
  {
    if (!File::exists($pdfPath)) {
      $pdf = $this->pdf->loadView('pdf_view', ['content' => $attachment_content]);
      $pdf->save($pdfPath);
    }
  }
}
