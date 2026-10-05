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
 * CSV question importer.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizwizard\importer;

defined('MOODLE_INTERNAL') || die();

/**
 * Imports questions from a CSV file.
 *
 * Expected columns (case-insensitive):
 *   question, answer1, answer2, answer3, answer4, answer5, correct, explanation, points, difficulty
 *
 * The correct column contains the index/letter of the correct answer(s).
 * Single correct: 1, 2, 3... or A, B, C...
 * Multiple correct: comma-separated.
 */
class csv_importer {

    /**
     * Parse CSV content.
     *
     * @param string $content
     * @return array Array of DTOs.
     */
    public static function parse(string $content): array {
        $lines = preg_split('/\r\n|\r|\n/', $content);
        if (empty($lines)) {
            return [];
        }
        $header = self::parse_csv_line(array_shift($lines));
        $header = array_map('strtolower', $header);
        $map = array_flip($header);

        $questions = [];
        $row = 1;
        foreach ($lines as $line) {
            $row++;
            if (trim($line) === '') {
                continue;
            }
            $fields = self::parse_csv_line($line);
            $dto = self::build_dto($fields, $map, $row);
            if ($dto) {
                $questions[] = $dto;
            }
        }
        return $questions;
    }

    /**
     * Parse a single CSV line (simple parser, no quoted commas support for MVP).
     *
     * @param string $line
     * @return array
     */
    protected static function parse_csv_line(string $line): array {
        return str_getcsv($line, ',', '"', "\\");
    }

    /**
     * Build a DTO from CSV fields.
     *
     * @param array $fields
     * @param array $map Header index map.
     * @param int $row
     * @return \stdClass|null
     */
    protected static function build_dto(array $fields, array $map, int $row): ?\stdClass {
        $dto = new \stdClass();
        $dto->row = $row;
        $dto->questiontext = self::get_field($fields, $map, 'question');
        $dto->single = 1;
        $dto->points = (float) (self::get_field($fields, $map, 'points') ?: 1);
        $dto->explanation = self::get_field($fields, $map, 'explanation');
        $dto->difficulty = self::get_field($fields, $map, 'difficulty');
        $dto->answers = [];
        $dto->correct = [];

        // Collect answers.
        $answerindex = 1;
        while (isset($map["answer{$answerindex}"])) {
            $text = self::get_field($fields, $map, "answer{$answerindex}");
            if ($text !== '') {
                $dto->answers[] = $text;
            }
            $answerindex++;
        }

        $correctraw = self::get_field($fields, $map, 'correct');
        if ($correctraw !== '') {
            $parts = array_map('trim', explode(',', $correctraw));
            foreach ($parts as $part) {
                if (is_numeric($part)) {
                    $dto->correct[] = (int) $part;
                } else if (preg_match('/^[A-Za-z]$/', $part)) {
                    $dto->correct[] = ord(strtoupper($part)) - ord('A') + 1;
                }
            }
        }

        if (count($dto->correct) > 1) {
            $dto->single = 0;
        }

        return $dto;
    }

    /**
     * Get a field value by header name.
     *
     * @param array $fields
     * @param array $map
     * @param string $name
     * @return string
     */
    protected static function get_field(array $fields, array $map, string $name): string {
        if (!isset($map[$name])) {
            return '';
        }
        $value = $fields[$map[$name]] ?? '';
        return trim($value);
    }
}
