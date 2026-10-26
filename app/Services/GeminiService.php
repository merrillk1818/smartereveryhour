<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model  = config('services.gemini.model', 'gemini-2.5-flash');
    }

    /**
     * Two-step 200-word index card study note for a topic.
     * Step 1: pick the best angle for this learner.
     * Step 2: write the structured study card on that angle.
     */
    public function generateNotificationMessage(string $topicTitle, string $keywords = '', string $skillLevel = 'intermediate'): string
    {
        $levelLabel = ucfirst($skillLevel);
        $keywordHint = $keywords ? "Focus keywords: {$keywords}." : '';

        // Step 1 — pick a specific angle
        $anglePrompt = <<<PROMPT
Topic: {$topicTitle}
Skill level: {$levelLabel}
{$keywordHint}

In ONE sentence, name the single most useful concept or subtopic a {$levelLabel} learner should study right now. Output only that sentence, nothing else.
PROMPT;

        $angle = mb_substr(trim($this->generate($anglePrompt, maxTokens: 600, temperature: 0.9)), 0, 200);

        // Step 2 — write the index card (short hook for notifications)
        $cardPrompt = <<<PROMPT
Write a micro study note for a {$levelLabel} learner about: {$angle}
(Part of the broader topic: {$topicTitle})

Use exactly this format:

[Concept title — 4 to 8 words]

[2 sentence explanation in plain, conversational language. Include one concrete analogy or real-world example.]

Key points:
• [point 1 — one short, specific line]
• [point 2 — one short, specific line]
• [point 3 — one short, specific line]

Remember: [one punchy takeaway sentence]

Rules: 80–100 words total, no code blocks, no jargon beyond {$levelLabel} level. Every word earns its place.
PROMPT;

        $card = trim($this->generate($cardPrompt, maxTokens: 400, temperature: 0.7));

        return $card;
    }

    /**
     * Suggest topics for a curriculum using AI.
     * Returns an array of topic title strings.
     */
    public function suggestTopics(string $curriculumName, string $description = '', string $skillLevel = 'intermediate'): array
    {
        $levelLabel = ucfirst($skillLevel);
        $descHint = $description ? "Description: {$description}." : '';

        $prompt = <<<PROMPT
I'm building a learning curriculum called "{$curriculumName}". {$descHint}
Target skill level: {$levelLabel}.

Suggest exactly 8 specific, distinct topics a {$levelLabel} student should study. Each topic should be a clear, focused subject (3–8 words), not a chapter title.

Return ONLY a valid JSON array of 8 strings. No explanation, no markdown, no code block — just the raw JSON array.
Example: ["Topic one", "Topic two", ...]
PROMPT;

        $raw = trim($this->generate($prompt, maxTokens: 2000, temperature: 0.8));

        // Extract JSON array directly — handles markdown-fenced responses
        if (preg_match('/\[.*\]/s', $raw, $matches)) {
            $topics = json_decode($matches[0], true);
        } else {
            $topics = json_decode(trim($raw), true);
        }

        return is_array($topics) ? array_values(array_filter($topics, 'is_string')) : [];
    }

    /**
     * Suggest a new topic to save based on what came up in a chat session.
     * Returns ['title' => '...', 'description' => '...'] or null.
     */
    public function suggestTopicFromChat(string $sessionTopic, array $messages): ?array
    {
        if (empty($messages)) {
            return null;
        }

        $recent = array_slice($messages, -6);
        $transcript = collect($recent)
            ->map(fn ($m) => ucfirst($m['role']) . ': ' . $m['content'])
            ->implode("\n");

        $prompt = <<<PROMPT
A student was learning about "{$sessionTopic}" and had this conversation:

{$transcript}

Did a specific new topic or concept come up that the student might want to study separately?
If yes, return ONLY valid JSON: {"title": "short topic name (3–8 words)", "description": "one sentence description"}
If no clear new topic emerged, return the word: null
PROMPT;

        $raw = trim($this->generate($prompt, maxTokens: 800, temperature: 0.3));

        if (strtolower($raw) === 'null' || empty($raw)) {
            return null;
        }

        // Extract JSON object directly — handles markdown-fenced responses
        if (preg_match('/\{.*\}/s', $raw, $matches)) {
            $result = json_decode($matches[0], true);
        } else {
            $result = json_decode(trim($raw), true);
        }

        return (is_array($result) && isset($result['title'])) ? $result : null;
    }

    /**
     * Continue a chat conversation about a topic.
     */
    public function chat(string $topicTitle, string $initialMessage, array $history, string $userMessage): string
    {
        $contents = [];

        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => "I am learning about: {$topicTitle}. The lesson that brought me here was: \"{$initialMessage}\". Please help me understand this topic better with clear, simple explanations."]],
        ];
        $contents[] = [
            'role'  => 'model',
            'parts' => [['text' => "Great! I'd be happy to help you learn more about {$topicTitle}. What would you like to know?"]],
        ];

        foreach ($history as $msg) {
            $contents[] = [
                'role'  => $msg['role'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $msg['content']]],
            ];
        }

        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $userMessage]],
        ];

        return $this->generateFromContents($contents, maxTokens: 1500, temperature: 0.7);
    }

    private function generate(string $prompt, int $maxTokens = 200, float $temperature = 0.7): string
    {
        return $this->generateFromContents([
            ['role' => 'user', 'parts' => [['text' => $prompt]]],
        ], $maxTokens, $temperature);
    }

    private function generateFromContents(array $contents, int $maxTokens = 200, float $temperature = 0.7): string
    {
        $response = Http::timeout(60)->post(
            "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}",
            [
                'contents'         => $contents,
                'generationConfig' => [
                    'maxOutputTokens' => $maxTokens,
                    'temperature'     => $temperature,
                    'thinkingConfig'  => ['thinkingBudget' => 0],
                ],
            ]
        );

        if ($response->failed()) {
            Log::error('Gemini API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('Gemini API request failed: ' . $response->status());
        }

        return $response->json('candidates.0.content.parts.0.text', '');
    }
}
