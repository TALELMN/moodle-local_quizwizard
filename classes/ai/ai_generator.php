<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Optional AI question generation.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizwizard\ai;

defined('MOODLE_INTERNAL') || die();

/**
 * Generates question drafts via an external API.
 */
class ai_generator {

    /**
     * Check whether AI is enabled and configured.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        return (bool) get_config('local_quizwizard', 'aienabled');
    }

    /**
     * Generate question DTOs from a topic.
     *
     * @param array $params
     * @return array
     */
    public static function generate(array $params): array {
        $endpoint = get_config('local_quizwizard', 'aiendpoint');
        $apikey = get_config('local_quizwizard', 'aiapikey');
        $model = get_config('local_quizwizard', 'aimodel') ?: 'gpt-4o-mini';

        if (empty($endpoint) || empty($apikey)) {
            throw new \moodle_exception('err_aiconfig', 'local_quizwizard');
        }

        $topic = $params['topic'] ?? '';
        $count = (int) ($params['count'] ?? 5);
        $difficulty = $params['difficulty'] ?? 'medium';
        $language = $params['language'] ?? 'English';

        $prompt = self::build_prompt($topic, $count, $difficulty, $language);

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You generate multiple-choice quiz questions as JSON.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
        ];

        $curl = new \curl();
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apikey,
        ];
        $response = $curl->post($endpoint, json_encode($payload), ['CURLOPT_HTTPHEADER' => $headers]);
        $httpcode = $curl->get_info()['http_code'] ?? 0;

        if ($httpcode < 200 || $httpcode >= 300) {
            throw new \moodle_exception('err_aiinvalid', 'local_quizwizard');
        }

        $data = json_decode($response, true);
        if (empty($data['choices'][0]['message']['content'])) {
            throw new \moodle_exception('err_aiinvalid', 'local_quizwizard');
        }

        $content = $data['choices'][0]['message']['content'];
        // Strip markdown fences if present.
        $content = preg_replace('/^```json\s*|\s*```$/m', '', trim($content));
        $questions = json_decode($content, true);
        if (!is_array($questions)) {
            throw new \moodle_exception('err_aiinvalid', 'local_quizwizard');
        }

        return self::normalize_questions($questions);
    }

    /**
     * Build prompt text.
     *
     * @param string $topic
     * @param int $count
     * @param string $difficulty
     * @param string $language
     * @return string
     */
    protected static function build_prompt(string $topic, int $count, string $difficulty, string $language): string {
        return <<<PROMPT
Generate {$count} multiple-choice quiz questions about: {$topic}.
Difficulty: {$difficulty}.
Language: {$language}.

Return ONLY a JSON array. Each object must have:
- "questiontext": the question text (can include basic HTML)
- "answers": array of answer strings
- "correct": array of one or more 1-based indices of correct answers
- "points": number (default 1)
- "explanation": optional explanation string
- "difficulty": optional "easy", "medium", or "hard"

Example:
[
  {
    "questiontext": "What is the capital of France?",
    "answers": ["London", "Paris", "Madrid", "Rome"],
    "correct": [2],
    "points": 1,
    "explanation": "Paris is the capital."
  }
]
PROMPT;
    }

    /**
     * Normalize AI questions into wizard DTOs.
     *
     * @param array $questions
     * @return array
     */
    protected static function normalize_questions(array $questions): array {
        $dtos = [];
        foreach ($questions as $q) {
            $dto = new \stdClass();
            $dto->questiontext = $q['questiontext'] ?? '';
            $dto->single = count($q['correct'] ?? []) <= 1 ? 1 : 0;
            $dto->points = (float) ($q['points'] ?? 1);
            $dto->explanation = $q['explanation'] ?? '';
            $dto->difficulty = $q['difficulty'] ?? '';
            $dto->answers = $q['answers'] ?? [];
            $dto->correct = array_map('intval', $q['correct'] ?? []);
            $dtos[] = $dto;
        }
        return $dtos;
    }
}
