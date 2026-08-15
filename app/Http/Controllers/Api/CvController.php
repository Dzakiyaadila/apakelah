<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\CvAnalysis;
use App\Models\Job;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;

class CvController extends Controller
{
    public function __construct(private AiService $ai)
    {
    }

    // GET /api/cv
    public function index(Request $request)
    {
        $cvs = Cv::where('user_id', $request->user()->id)
            ->select('id', 'title', 'updated_at')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json($cvs);
    }

    // POST /api/cv  (create/update builder CV)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'personal_info' => 'nullable|array',
            'summary' => 'nullable|string',
            'skills' => 'nullable|array',
            'experience' => 'nullable|array',
            'education' => 'nullable|array',
            'projects' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cv = Cv::create([
            'user_id' => $request->user()->id,
            'title' => $request->title ?? 'CV Utama',
            'personal_info' => $request->personal_info,
            'summary' => $request->summary,
            'skills' => $request->skills,
            'experience' => $request->experience,
            'education' => $request->education,
            'projects' => $request->projects,
            'source' => 'builder',
        ]);

        return response()->json($cv, 201);
    }

    // GET /api/cv/{id}
    public function show(Request $request, $id)
    {
        $cv = Cv::where('user_id', $request->user()->id)->findOrFail($id);
        return response()->json($cv);
    }

    // POST /api/cv/upload
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,docx|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');
        $path = $file->store('cv_uploads');
        $fullPath = Storage::path($path);

        $extension = strtolower($file->getClientOriginalExtension());
        $text = $extension === 'pdf'
            ? $this->extractPdfText($fullPath)
            : $this->extractDocxText($fullPath);

        $cv = Cv::create([
            'user_id' => $request->user()->id,
            'title' => $file->getClientOriginalName(),
            'source' => 'upload',
            'original_file_path' => $path,
            'parsed_text' => $text,
        ]);

        return response()->json([
            'cv_id' => $cv->id,
            'parsed_preview' => mb_substr($text, 0, 500),
            'status' => 'parsed',
        ], 201);
    }


    // PATCH /api/cv/{id}
    public function update(Request $request, $id)
    {
        $cv = Cv::where('user_id', $request->user()->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'personal_info' => 'nullable|array',
            'summary' => 'nullable|string',
            'skills' => 'nullable|array',
            'experience' => 'nullable|array',
            'education' => 'nullable|array',
            'projects' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cv->update($request->only([
            'title', 'personal_info', 'summary', 'skills', 'experience', 'education', 'projects',
        ]));

        return response()->json($cv);
    }

    // POST /api/cv/{id}/analyze
    public function analyze(Request $request, $id)
    {
        $cv = Cv::where('user_id', $request->user()->id)->findOrFail($id);

        $cvText = $cv->parsed_text ?: $this->buildTextFromBuilder($cv);

        $job = null;
        if ($request->job_id) {
            $job = Job::find($request->job_id);
        }

        try {
            $result = $this->ai->analyzeCv($cvText, $job?->description);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Analisis gagal, coba lagi',
            ], 500);
        }

        $analysis = CvAnalysis::create([
            'cv_id' => $cv->id,
            'job_id' => $job?->id,
            'ats_score' => $result['ats_score'] ?? 0,
            'headline' => $result['headline'] ?? null,
            'strengths' => $result['strengths'] ?? [],
            'improvements' => $result['improvements'] ?? [],
            'suggestions' => $result['suggestions'] ?? [],
            'keyword_gaps' => $result['keyword_gaps'] ?? [],
        ]);

        return response()->json([
            'ats_score' => $analysis->ats_score,
            'headline' => $analysis->headline,
            'strengths' => $analysis->strengths,
            'improvements' => $analysis->improvements,
            'suggestions' => $analysis->suggestions,
            'keyword_gaps' => $analysis->keyword_gaps,
        ]);
    }

    // POST /api/cv/{id}/generate
    public function generate(Request $request, $id)
    {
        $cv = Cv::where('user_id', $request->user()->id)->findOrFail($id);
        $job = Job::findOrFail($request->job_id);

        $cvText = $cv->parsed_text ?: $this->buildTextFromBuilder($cv);

        try {
            $optimizedText = $this->ai->generateOptimizedCv($cvText, $job->description ?? '');
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Generate gagal, coba lagi'], 500);
        }

        $newCv = Cv::create([
            'user_id' => $request->user()->id,
            'title' => $cv->title . ' (Optimized - ' . $job->title . ')',
            'source' => 'upload',
            'parsed_text' => $optimizedText,
        ]);

        return response()->json([
            'generated_cv_id' => $newCv->id,
            'download_url' => "/api/cv/{$newCv->id}/download",
            'highlighted_keywords' => $job->required_skills ?? [],
        ]);
    }
    // GET /api/cv/{id}/download
    public function download(Request $request, $id)
    {
        $cv = Cv::where('user_id', $request->user()->id)->findOrFail($id);

        $content = $cv->parsed_text ?: $this->buildTextFromBuilder($cv);

        return response($content, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="cv_' . $cv->id . '.txt"',
        ]);
    }

    private function extractPdfText(string $fullPath): string
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($fullPath);
        return $pdf->getText();
    }

    private function extractDocxText(string $fullPath): string
    {
        $phpWord = IOFactory::load($fullPath);
        $text = '';
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                }
            }
        }
        return $text;
    }

    private function buildTextFromBuilder(Cv $cv): string
    {
        $parts = [
            $cv->summary,
            'Skills: ' . implode(', ', $cv->skills ?? []),
        ];

        foreach ($cv->experience ?? [] as $exp) {
            $parts[] = ($exp['role'] ?? '') . ' at ' . ($exp['company'] ?? '') . ': ' . ($exp['description'] ?? '');
        }

        foreach ($cv->education ?? [] as $edu) {
            $parts[] = ($edu['degree'] ?? '') . ' - ' . ($edu['school'] ?? '');
        }

        return implode("\n", array_filter($parts));
    }
}