<?php
 

namespace Modules\Documents\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Documents\App\Enums\DocumentStatus;
use Modules\Documents\App\Models\Document;
use Modules\Documents\App\Notifications\DocumentViewed;

class DocumentController extends Controller
{
    /**
     * Display the document.
     */
    public function show(string $uuid, Request $request): View
    {
        $document = Document::with('brand')->where('uuid', $uuid)->firstOrFail();

        abort_if($document->status === DocumentStatus::LOST && ! Auth::check(), 404);

        app()->setLocale($document->locale);

        $title = $document->type->name;

        if (! Auth::check()) {
            if (views($document)
                ->cooldown(now()->addHour())
                ->record()) {
                $document->addActivity([
                    'lang' => [
                        'key' => 'documents::document.activity.viewed',
                    ],
                ]);

                $document->user->notify(new DocumentViewed($document));
            }
        }

        return view('documents::view', compact('document', 'title'));
    }

    /**
     * Display the document PDF.
     */
    public function pdf(string $uuid, Request $request)
    {
        $document = Document::with('brand')->where('uuid', $uuid)->firstOrFail();

        abort_if($document->status === DocumentStatus::LOST && ! Auth::check(), 404);

        app()->setLocale($document->locale);

        $pdf = $document->pdf();

        if (! Auth::check()) {
            $document->addActivity([
                'lang' => [
                    'key' => 'documents::document.activity.downloaded',
                ],
            ]);
        }

        if ($request->get('output') === 'download') {
            return $pdf->download($document->pdfFilename());
        }

        return $pdf->stream($document->pdfFilename());
    }
}
