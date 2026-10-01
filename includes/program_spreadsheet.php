<?php
require_once __DIR__ . '/../vendor/autoload.php';

function programHeader(string $value): ?string
{
    $value = mb_strtolower(trim($value), 'UTF-8');
    $value = preg_replace('/\([^)]*\)/u', '', $value);
    $value = strtr($value, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
    $value = preg_replace('/[^a-z0-9]/', '', $value);
    $map = [
        'nombre' => 'nombre', 'nombres' => 'nombre', 'name' => 'nombre', 'apellidos' => 'apellidos', 'apellido' => 'apellidos',
        'centro' => 'centro', 'centrodeportivo' => 'centro', 'instalacion' => 'instalacion', 'instalaciones' => 'instalacion',
        'actividad' => 'actividad', 'actividades' => 'actividad', 'grupo' => 'grupo',
        'inicio' => 'fecha_inicio', 'fechainicio' => 'fecha_inicio', 'finicio' => 'fecha_inicio', 'fechadeinicio' => 'fecha_inicio',
        'fechadeinicioactividad' => 'fecha_inicio', 'fechainicioactividad' => 'fecha_inicio',
        'fin' => 'fecha_fin', 'fechafin' => 'fecha_fin', 'ffin' => 'fecha_fin', 'fechadefin' => 'fecha_fin', 'fechafinal' => 'fecha_fin',
        'fechadefinactividad' => 'fecha_fin', 'fechafinactividad' => 'fecha_fin',
        'horainicio' => 'hora_inicio', 'hinicio' => 'hora_inicio', 'horadeinicio' => 'hora_inicio',
        'horainicioactividad' => 'hora_inicio', 'horadeinicioactividad' => 'hora_inicio',
        'horafin' => 'hora_fin', 'hfin' => 'hora_fin', 'horadefin' => 'hora_fin',
        'horafinactividad' => 'hora_fin', 'horadefinactividad' => 'hora_fin',
        'dias' => 'dias_semana', 'diassemana' => 'dias_semana', 'diasdelasemana' => 'dias_semana',
        'tipo' => 'tipo_control', 'tipocontrol' => 'tipo_control', 'tipodecontrol' => 'tipo_control',
    ];
    return $map[$value] ?? null;
}

/** Some supplied templates repeat "Hora fin" over the calendar end-date column. */
function programCalendarDateColumn(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $column, int $headerRow, int $lastRow): bool
{
    for ($r = $headerRow + 1; $r <= min($lastRow, $headerRow + 10); $r++) {
        $cell = $sheet->getCell([$column, $r]);
        $value = $cell->getValue();
        if ($value === null || $value === '') continue;
        if ($cell->getDataType() === 'f') return false;
        if (is_numeric($value)) {
            $format = preg_replace('/"[^"]*"|\\\\./', '', $cell->getStyle()->getNumberFormat()->getFormatCode());
            return \PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell) && preg_match('/[dy]/i', $format) === 1;
        }
        return preg_match('/^(?:\d{4}-\d{2}-\d{2}|\d{1,2}[\/.]\d{1,2}[\/.]\d{2,4})$/', trim((string) $value)) === 1;
    }
    return false;
}

/** Read values, never execute spreadsheet formulas or treat workbook text as instructions. */
function programSpreadsheet(string $path, string $extension, ?string $selectedSheet = null): array
{
    if (!in_array($extension, ['xlsx', 'csv'], true)) throw new DomainException('Selecciona un archivo Excel (.xlsx) o CSV.');
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($extension === 'xlsx' ? 'Xlsx' : 'Csv');
    if ($extension === 'xlsx') {
        $info = $reader->listWorksheetInfo($path);
        foreach ($info as $sheet) {
            if ($sheet['totalRows'] > 20010 || $sheet['totalColumns'] > 50) throw new DomainException('La hoja supera 20.000 filas o 50 columnas.');
        }
    } else {
        $handle = fopen($path, 'r');
        if (!$handle) throw new DomainException('No se pudo abrir el CSV.');
        $firstLine = fgets($handle) ?: '';
        fclose($handle);
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        if (substr_count($firstLine, "\t") > max(substr_count($firstLine, ';'), substr_count($firstLine, ','))) $delimiter = "\t";
        $reader->setDelimiter($delimiter);
        $reader->setInputEncoding('UTF-8');
    }
    $workbook = $reader->load($path);
    try {
        $lists = [];
        foreach ($workbook->getWorksheetIterator() as $sheet) {
            if ($selectedSheet !== null && $sheet->getTitle() !== $selectedSheet) continue;
            $lastRow = $sheet->getHighestDataRow();
            $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
            if ($lastRow > 20010 || $lastColumn > 50) throw new DomainException('La hoja supera 20.000 filas o 50 columnas.');
            $headers = null;
            $headerRow = null;
            for ($r = 1; $r <= min(10, $lastRow); $r++) {
                $mapped = [];
                for ($c = 1; $c <= $lastColumn; $c++) {
                    $cell = $sheet->getCell([$c, $r]);
                    if ($cell->getDataType() === 'f') continue;
                    $field = programHeader((string) $cell->getValue());
                    if ($field) {
                        $mapped[$c] = $field;
                    }
                }
                if (count(array_keys($mapped, 'hora_fin', true)) > 1 && !in_array('fecha_fin', $mapped, true)) {
                    $dateColumns = array_values(array_filter(array_keys($mapped, 'hora_fin', true), static fn($c) => programCalendarDateColumn($sheet, $c, $r, $lastRow)));
                    if (count($dateColumns) === 1) $mapped[$dateColumns[0]] = 'fecha_fin';
                }
                if (count(array_unique($mapped)) !== count($mapped)) throw new DomainException('Hay encabezados repetidos en la hoja ' . $sheet->getTitle() . '.');
                if (count(array_intersect(['instalacion', 'actividad', 'fecha_inicio', 'dias_semana'], $mapped)) === 4) {
                    $headers = $mapped; $headerRow = $r; break;
                }
            }
            if (!$headers) continue;
            $rows = [];
            for ($r = $headerRow + 1; $r <= $lastRow; $r++) {
                $row = [];
                foreach ($headers as $column => $field) {
                    if ($field === 'centro') continue;
                    $cell = $sheet->getCell([$column, $r]);
                    $value = $cell->getValue();
                    if ($cell->getDataType() === 'f') throw new DomainException('Fila ' . $r . ': sustituye las fórmulas por sus valores antes de cargar el listado.');
                    if (is_numeric($value) && in_array($field, ['fecha_inicio', 'fecha_fin', 'hora_inicio', 'hora_fin'], true)) {
                        $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);
                        $value = $date->format(str_starts_with($field, 'fecha_') ? 'Y-m-d' : 'H:i:s');
                    }
                    $row[$field] = trim((string) $value);
                }
                if (count(array_filter($row, static fn($v) => $v !== '')) > 0) $rows[] = $row;
            }
            if ($rows) $lists[$sheet->getTitle()] = $rows;
        }
        if (!$lists) throw new DomainException('No se encontró un listado con encabezados Instalación, Actividad, Fecha inicio y Días.');
        if (count($lists) > 1) return ['necesita_hoja' => true, 'hojas' => array_keys($lists)];
        return ['necesita_hoja' => false, 'hoja' => array_key_first($lists), 'rows' => reset($lists)];
    } finally { $workbook->disconnectWorksheets(); }
}
