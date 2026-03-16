<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Student;
use DateTimeInterface;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\Common\Creator\ReaderFactory;
use RuntimeException;

class StudentXlsxImporter
{
    /**
     * @return array{total_data_rows:int,created:int,skipped:int,failed:int,errors:array<int,string>}
     */
    public function import(string $filePath): array
    {
        $reader = ReaderFactory::createFromFile($filePath);
        $reader->open($filePath);

        $headerMap = null;
        $result = [
            'total_data_rows' => 0,
            'created' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $coursesByName = $this->buildCourseLookup();

        try {
            $sheet = null;

            foreach ($reader->getSheetIterator() as $firstSheet) {
                $sheet = $firstSheet;
                break;
            }

            if (! $sheet) {
                throw new RuntimeException('El archivo Excel no contiene hojas para importar.');
            }

            foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                $values = $this->normalizeRowValues($row);

                if ($this->rowIsEmpty($values)) {
                    continue;
                }

                if ($headerMap === null) {
                    $headerMap = $this->resolveHeaderMap($values);
                    continue;
                }

                $courseName = trim((string) ($values[$headerMap['course']] ?? ''));
                $studentName = trim((string) ($values[$headerMap['name']] ?? ''));

                if ($courseName === '' && $studentName === '') {
                    continue;
                }

                $result['total_data_rows']++;

                if ($courseName === '' || $studentName === '') {
                    $result['failed']++;
                    $result['errors'][] = "Fila {$rowNumber}: las columnas curso y nombre son obligatorias.";

                    continue;
                }

                $courseId = $coursesByName[$this->normalizeText($courseName)] ?? null;

                if (! $courseId) {
                    $result['failed']++;
                    $result['errors'][] = "Fila {$rowNumber}: el curso '{$courseName}' no existe.";

                    continue;
                }

                $alreadyExists = Student::query()
                    ->where('course_id', $courseId)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($studentName)])
                    ->exists();

                if ($alreadyExists) {
                    $result['skipped']++;

                    continue;
                }

                Student::query()->create([
                    'name' => $studentName,
                    'course_id' => $courseId,
                ]);

                $result['created']++;
            }

            if ($headerMap === null) {
                throw new RuntimeException('El archivo Excel está vacío o no contiene encabezados.');
            }
        } finally {
            $reader->close();
        }

        return $result;
    }

    /**
     * @return array<string, int>
     */
    private function buildCourseLookup(): array
    {
        return Course::query()
            ->select(['id', 'name'])
            ->get()
            ->reduce(function (array $carry, Course $course): array {
                $carry[$this->normalizeText($course->name)] = (int) $course->id;

                return $carry;
            }, []);
    }

    /**
     * @param  array<int, string>  $headerValues
     * @return array{course:int,name:int}
     */
    private function resolveHeaderMap(array $headerValues): array
    {
        $courseIndex = null;
        $nameIndex = null;

        foreach ($headerValues as $index => $headerValue) {
            $normalized = $this->normalizeText($headerValue);

            if ($courseIndex === null && in_array($normalized, ['curso', 'course', 'grupo', 'grado'], true)) {
                $courseIndex = $index;
            }

            if ($nameIndex === null && in_array($normalized, ['nombre', 'estudiante', 'nombre completo', 'alumno'], true)) {
                $nameIndex = $index;
            }
        }

        if ($courseIndex === null || $nameIndex === null) {
            throw new RuntimeException("Encabezados inválidos. El archivo debe incluir las columnas 'curso' y 'nombre'.");
        }

        return [
            'course' => $courseIndex,
            'name' => $nameIndex,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function normalizeRowValues(Row $row): array
    {
        return array_map(function (mixed $value): string {
            if ($value instanceof DateTimeInterface) {
                return $value->format('Y-m-d H:i:s');
            }

            return trim((string) ($value ?? ''));
        }, $row->toArray());
    }

    /**
     * @param  array<int, string>  $values
     */
    private function rowIsEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if (trim($value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeText(string $value): string
    {
        $normalized = mb_strtolower(trim($value));

        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
        if ($transliterated !== false) {
            $normalized = $transliterated;
        }

        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }
}
