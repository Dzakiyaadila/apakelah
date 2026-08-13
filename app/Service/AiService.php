<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    public function analyzeCv(string $cvText, ?string $jobDescription = null): array
    {
        $prompt = $this->buildAnalysisPrompt($cvText, $jobDescription);
        return $this->callWithFallback($prompt);
    }

    public function generateOptimizedCv(string $cvText, string $jobDescription): string
    {
        $prompt = <<<PROMPT
        Kamu adalah penulis CV profesional. Tulis ulang CV berikut agar lebih optimal
        untuk lowongan target, tanpa mengarang pengalaman baru. Sorot kata kunci relevan.
        Kembalikan HANYA teks CV yang sudah dioptimasi (bukan JSON, plain text).

        Lowongan target:
        {$jobDescription}

        CV asli:
        {$cvText}
        PROMPT;

        $result = $this->callWithFallback($prompt, expectJson: false);
        return $result['raw_text'] ?? '';
    }

    private function callWithFallback(string $prompt, bool $expectJson = true): array
    {
        try {
            return $this->callGemini($prompt, $expectJson);
        } catch (\Throwable $e) {
            Log::warning('Gemini failed, trying Groq', ['error' => $e->getMessage()]);
            try {
                return $this->callGroq($prompt, $expectJson);
            } catch (\Throwable $e2) {
                Log::warning('Groq failed, trying OpenRouter', ['error' => $e2->getMessage()]);
                return $this->callOpenRouter($prompt, $expectJson);
            }
        }
    }

    private function callGemini(string $prompt, bool $expectJson): array
    {
        $body = [
            'contents' => [['parts' => [['text' => $prompt]]]],
        ];
        if ($expectJson) {
            $body['generationConfig'] = ['response_mime_type' => 'application/json'];
        }

        $response = Http::timeout(30)->post(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . env('GEMINI_API_KEY'),
            $body
        )->throw()->json();

        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';

        return $expectJson ? $this->parseJsonSafely($text) : ['raw_text' => $text];
    }

    private function callGroq(string $prompt, bool $expectJson): array
    {
        $response = Http::timeout(30)->withToken(env('GROQ_API_KEY'))->post(
            'https://api.groq.com/openai/v1/chat/completions',
            [
                'model' => 'llama-3.3-70b-versatile',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'response_format' => $expectJson ? ['type' => 'json_object'] : null,
            ]
        )->throw()->json();

        $text = $response['choices'][0]['message']['content'] ?? '';

        return $expectJson ? $this->parseJsonSafely($text) : ['raw_text' => $text];
    }

    private function callOpenRouter(string $prompt, bool $expectJson): array
    {
        $response = Http::timeout(30)->withToken(env('OPENROUTER_API_KEY'))->post(
            'https://openrouter.ai/api/v1/chat/completions',
            [
                'model' => 'meta-llama/llama-3.3-70b-instruct:free',
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]
        )->throw()->json();

        $text = $response['choices'][0]['message']['content'] ?? '';

        return $expectJson ? $this->parseJsonSafely($text) : ['raw_text' => $text];
    }

    private function parseJsonSafely(string $text): array
    {
        // Kadang model bungkus JSON dengan ```json ... ``` — bersihkan dulu
        $clean = preg_replace('/^```json|```$/m', '', trim($text));
        $decoded = json_decode(trim($clean), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('AI response bukan JSON valid: ' . $text);
        }

        return $decoded;
    }

    private function buildAnalysisPrompt(string $cvText, ?string $jobDesc): string
    {
        $targetLine = $jobDesc ? " Bandingkan juga dengan lowongan berikut: {$jobDesc}" : '';

        return <<<PROMPT
        Kamu adalah sistem analisis ATS (Applicant Tracking System) untuk pasar kerja Indonesia.
        Analisis CV berikut secara menyeluruh.{$targetLine}

        Kembalikan HANYA JSON valid (tanpa markdown, tanpa penjelasan tambahan) dengan struktur PERSIS seperti ini:
        {
          "ats_score": <integer 0-100>,
          "headline": "<satu kalimat headline motivasional singkat>",
          "strengths": [{"title": "", "description": ""}],
          "improvements": [{"title": "", "description": ""}],
          "suggestions": [{"section": "", "tip": "", "example": ""}],
          "keyword_gaps": ["kata kunci yang hilang"]
        }

        CV:
        {$cvText}
        PROMPT;
    }
}