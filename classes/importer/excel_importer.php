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
 * Excel question importer.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizwizard\importer;

defined('MOODLE_INTERNAL') || die();

/**
 * Imports questions from Excel files.
 *
 * Requires PhpSpreadsheet. If unavailable, falls back to CSV parsing for .csv files.
 */
class excel_importer {

    /**
     * Parse an uploaded file and return DTOs.
     *
     * @param string $filepath Full server path.
     * @param string $filename Original file name.
     * @return array
     */
    public static function parse(string $filepath, string $filename): array {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            return csv_importer::parse(file_get_contents($filepath));
        }

        if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            throw new \moodle_exception('error/phpspreadsheetnotavailable', 'local_quizwizard');
        }

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filepath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filepath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        if (empty($rows)) {
            return [];
        }

        $header = array_map('strtolower', array_shift($rows));
        $map = array_flip($header);
        $questions = [];
        $row = 1;
        foreach ($rows as $fields) {
            $row++;
            $dto = self::build_dto($fields, $map, $row);
            if ($dto) {
                $questions[] = $dto;
            }
        }
        return $questions;
    }

    /**
     * Build a DTO from Excel row.
     *
     * @param array $fields
     * @param array $map
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
        return trim((string) $value);
    }
}
