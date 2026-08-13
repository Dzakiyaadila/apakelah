<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Cv;
use App\Models\Job;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // GET /api/dashboard
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $latestCv = Cv::where('user_id', $userId)->orderByDesc('updated_at')->first();

        $profileCompleteness = $this->calculateCompleteness($latestCv);
        $recommendedJobs = $this->getRecommendedJobs($latestCv);
        $jobMatchesCount = $recommendedJobs->count();

        $activeApplicationsCount = Application::where('user_id', $userId)
            ->whereIn('status', ['applied', 'interview'])
            ->count();

        return response()->json([
            'profile_completeness' => $profileCompleteness,
            'job_matches_count' => $jobMatchesCount,
            'active_applications_count' => $activeApplicationsCount,
            'recommended_jobs' => $recommendedJobs->values(),
            'tips' => [
                'Selesaikan CV Builder untuk meningkatkan skor akurasi lowongan',
                'Analisis CV terhadap 3 lowongan pertama minggu ini',
                'Update status lamaran di Tracker setelah kamu melamar',
            ],
        ]);
    }

    private function calculateCompleteness(?Cv $cv): int
    {
        if (! $cv) {
            return 0;
        }

        $fields = [
            $cv->personal_info,
            $cv->summary,
            $cv->skills,
            $cv->experience,
            $cv->education,
            $cv->projects,
        ];

        $filled = collect($fields)->filter(fn ($f) => ! empty($f))->count();

        return (int) round(($filled / count($fields)) * 100);
    }

    private function getRecommendedJobs(?Cv $cv)
    {
        $jobs = Job::orderByDesc('posted_at')->get();

        if (! $cv || empty($cv->skills)) {
            return $jobs->take(3)->map(fn ($job) => [
                'id' => $job->id,
                'title' => $job->title,
                'company' => $job->company,
                'location' => $job->location,
                'match_score' => 0,
            ]);
        }

        $cvSkills = collect($cv->skills)->map(fn ($s) => strtolower(trim($s)))->filter()->unique();

        return $jobs->map(function ($job) use ($cvSkills) {
            $jobSkills = collect($job->required_skills ?? [])
                ->map(fn ($s) => strtolower(trim($s)))
                ->filter()
                ->unique();

            $score = $jobSkills->isEmpty()
                ? 0
                : (int) round(($cvSkills->intersect($jobSkills)->count() / $jobSkills->count()) * 100);

            return [
                'id' => $job->id,
                'title' => $job->title,
                'company' => $job->company,
                'location' => $job->location,
                'match_score' => $score,
            ];
        })
        ->filter(fn ($j) => $j['match_score'] > 0)
        ->sortByDesc('match_score')
        ->take(5);
    }
}