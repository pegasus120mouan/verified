<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelTicketNumbersExtractor
{
    private const MAX_ROWS = 2000;

    /**
     * Extrait les numéros de ticket depuis .xlsx, .xls ou .csv.
     * Détecte une colonne d’en-tête contenant « ticket », « numero », « n° », « ref », etc. ;
     * sinon utilise la colonne A (lignes données).
     *
     * @return list<string>
     */
    public function extract(string $absolutePath): array
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = min((int) $sheet->getHighestDataRow(), self::MAX_ROWS);
        $highestColumn = $sheet->getHighestDataColumn();
        $maxColIndex = Coordinate::columnIndexFromString($highestColumn);

        $dataCol = $this->detectTicketColumn($sheet, $maxColIndex);
        $firstDataRow = $dataCol !== null ? 2 : 1;

        if ($dataCol === null) {
            $dataCol = 1;
            if ($this->rowLooksLikeHeader($sheet, 1, $maxColIndex)) {
                $firstDataRow = 2;
            }
        }

        $seen = [];
        $out = [];

        for ($row = $firstDataRow; $row <= $highestRow; $row++) {
            $cell = $sheet->getCell([$dataCol, $row]);
            $val = $cell->getCalculatedValue();
            if ($val === null || $val === '') {
                continue;
            }
            $s = trim((string) $val);
            if ($s === '' || mb_strlen($s) > 190) {
                continue;
            }
            if (! $this->looksLikeTicketToken($s)) {
                continue;
            }
            $key = mb_strtolower(str_replace(' ', '', $s));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $s;
        }

        return $out;
    }

    private function rowLooksLikeHeader(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row, int $maxColIndex): bool
    {
        $hits = 0;
        for ($c = 1; $c <= $maxColIndex; $c++) {
            $v = $sheet->getCell([$c, $row])->getCalculatedValue();
            if ($v === null || $v === '') {
                continue;
            }
            $t = mb_strtolower(trim((string) $v));
            if (preg_match('/ticket|numero|numéro|n°|nº|ref|bordereau|code/i', $t)) {
                $hits++;
            }
        }

        return $hits >= 1;
    }

    private function detectTicketColumn(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $maxColIndex): ?int
    {
        for ($c = 1; $c <= $maxColIndex; $c++) {
            $v = $sheet->getCell([$c, 1])->getCalculatedValue();
            if ($v === null || $v === '') {
                continue;
            }
            $t = mb_strtolower(trim((string) $v));
            if (preg_match('/ticket|numero|numéro|n°|nº|n´|ref\s*ticket|no\.?\s*ticket/i', $t)) {
                return $c;
            }
        }

        return null;
    }

    private function looksLikeTicketToken(string $s): bool
    {
        if (mb_strlen($s) < 3) {
            return false;
        }
        if (preg_match('/^total|^somme|^date|^usine|^montant/i', $s)) {
            return false;
        }

        return (bool) preg_match('/^[0-9A-Za-zÀ-ÿ.\/_\-]+$/u', $s);
    }
}
