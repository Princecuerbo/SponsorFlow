<?php

namespace App\Http\Controllers\Fassg;

use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Models\GeneratedBatch;
use App\Models\SponsorshipProgram;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BatchController extends Controller
{
    use ResolvesModuleContext;

    public function index(Request $request): View
    {
        $programId = $request->integer('sponsorship_program_id', 0);

        $lists = GeneratedBatch::query()
            ->with('sponsorshipProgram')
            ->withCount('items')
            ->when($programId > 0, fn ($query) => $query->where('sponsorship_program_id', $programId))
            ->latest()
            ->get();

        $viewName = view()->exists('fassg.batches.index')
            ? 'fassg.batches.index'
            : 'fassg.generated_batches.index';

        return view($viewName, [
            'user' => $this->actor($request),
            'lists' => $lists,
            'fixedLists' => $lists,
            'batches' => $lists,
            'programs' => SponsorshipProgram::query()->orderBy('program_name')->get(),
            'selectedProgramId' => $programId,
        ]);
    }
}
