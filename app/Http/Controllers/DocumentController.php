<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Models\ApplicationDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    use ResolvesModuleContext;

    public function show(Request $request, ApplicationDocument $document): BinaryFileResponse
    {
        $document->load('application.sponsorshipProgram');
        $user = $this->actor($request);
        $application = $document->application;

        $isStudentOwner = $user->isStudent()
            && $application->student_profile_id === $user->studentProfile?->id;
        $isFassg = $user->isFassg();
        $isSponsorOwner = $user->isSponsor()
            && $user->sponsor?->ownsProgram($application->sponsorshipProgram);

        abort_unless($isStudentOwner || $isFassg || $isSponsorOwner, 403);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404, 'Document file not found.');

        $path = Storage::disk('local')->path($document->file_path);
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';
        $fileName = addcslashes(basename($document->file_name), "\\\"");

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }
}
