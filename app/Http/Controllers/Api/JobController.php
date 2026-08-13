<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\Job;
use Illuminate\Http\Request;

class JobController extends Controller
{
    // GET /api/jobs?search=&location=&type=&work_mode=&page=1
    public function index(Request $request)
    {
        $query = Job::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }

        if ($location = $request->query('location')) {
            $query->where('location', 'like', "%{$location}%");
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($workMode = $request->query('work_mode')) {
            $query->where('work_mode', $workMode);
        }

        $perPage = 10;
        $page = max((int) $request->query('page', 1), 1);

        $paginated = $query->orderByDesc('posted_at')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $paginated->items(),
            'meta' => [
                'page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    // GET /api/jobs/{id}
    public function show($id)
    {
        $job = Job::findOrFail($id);
        return response()->json($job);
    }

    // GET /api/jobs/{id}/match?cv_id=5
    public function match(Request $request, $id)
    {
        $job = Job::findOrFail($id);

        $cvId = $request->query('cv_id');
        if (! $cvId) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['cv_id' => ['cv_id wajib diisi']],
            ], 422);
        }

        $cv = Cv::where('user_id', $request->user()->id)->findOrFail($cvId);

        $cvSkills = collect($cv->skills ?? [])
            ->map(fn ($s) => strtolower(trim($s)))
            ->filter()
            ->unique();

        $jobSkills = collect($job->required_skills ?? [])
            ->map(fn ($s) => strtolower(trim($s)))
            ->filter()
            ->unique();

        if ($jobSkills->isEmpty()) {
            return response()->json([
                'match_score' => 0,
                'matched_keywords' => [],
                'missing_keywords' => [],
            ]);
        }

        $matched = $cvSkills->intersect($jobSkills);
        $missing = $jobSkills->diff($cvSkills);

        $score = (int) round(($matched->count() / $jobSkills->count()) * 100);

        return response()->json([
            'match_score' => $score,
            'matched_keywords' => $matched->values(),
            'missing_keywords' => $missing->values(),
        ]);
    }
}