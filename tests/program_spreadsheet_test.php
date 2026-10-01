<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/program_spreadsheet.php';
require_once __DIR__ . '/../includes/program_import.php';
function sheetCheck(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
$path = tempnam(sys_get_temp_dir(), 'puri-sheet-');
try {
    file_put_contents($path, "Actividad;Nombre;Apellidos;Instalación;F. Inicio;F. Fin;H. Inicio;H. Fin;Días;Tipo\nNatación;Alicia;Cuervo;Piscina;3/9/26;30/6/27;9:15;10:15;Lunes;\nRetos;;;Sala;3/9/26;30/6/27;11:00;12:00;Miércoles;A\n");
    $csv = programSpreadsheet($path, 'csv');
    sheetCheck(count($csv['rows']) === 2 && $csv['rows'][0]['actividad'] === 'Natación', 'CSV con encabezados en otro orden.');
    sheetCheck(count(programNormalize($csv['rows'], 'participantes')) === 2, 'Los datos cargados se pueden normalizar.');
    $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $book->getActiveSheet();
    $sheet->setTitle('Listado');
    $sheet->fromArray([['Nombre', 'Apellidos', 'Centro', 'Instalación', 'Actividad', 'Grupo', 'Fecha inicio', 'Fecha fin', 'Hora inicio', 'Hora fin', 'Días', 'Tipo'],
        ['Alicia', 'Cuervo', 'Se ignora', 'Piscina', 'Natación', '1', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTime('2026-09-03')), \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTime('2027-06-30')), 9.25 / 24, 10.25 / 24, 'Lunes,Miércoles', '']]);
    $sheet->getStyle('G2:H2')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
    $sheet->getStyle('I2:J2')->getNumberFormat()->setFormatCode('hh:mm');
    $instructions = $book->createSheet(); $instructions->setTitle('Instrucciones'); $instructions->setCellValue('A1', 'Texto que no es un listado');
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);
    $xlsx = programSpreadsheet($path, 'xlsx');
    sheetCheck($xlsx['hoja'] === 'Listado' && count($xlsx['rows']) === 1, 'Las hojas auxiliares se ignoran.');
    sheetCheck($xlsx['rows'][0]['fecha_inicio'] === '2026-09-03' && $xlsx['rows'][0]['hora_inicio'] === '09:15:00', 'Fechas y horas numéricas de Excel.');
    sheetCheck(!isset($xlsx['rows'][0]['centro']), 'Se usa el centro elegido y no el texto de la hoja.');
    $other = $book->createSheet(); $other->setTitle('Otro listado'); $other->fromArray($sheet->toArray(null, false, false));
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);
    $ambiguous = programSpreadsheet($path, 'xlsx');
    sheetCheck($ambiguous['necesita_hoja'] && count($ambiguous['hojas']) === 2, 'Dos listados requieren elegir una hoja.');
    sheetCheck(programSpreadsheet($path, 'xlsx', 'Listado')['hoja'] === 'Listado', 'La elección de hoja se respeta.');
    $sheet->setCellValue('A2', '=1+1');
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);
    try { programSpreadsheet($path, 'xlsx', 'Listado'); throw new RuntimeException('Se permitió una fórmula.'); }
    catch (DomainException $expected) { /* No se ejecutan fórmulas. */ }
    $book->disconnectWorksheets();
    $template = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $templateSheet = $template->getActiveSheet();
    $templateSheet->fromArray([
        ['Nombre', 'Apellidos', 'Centro (No tocar)', 'Instalación', 'Actividad', 'Grupo (Opcional)', 'Fecha de inicio actividad', 'Hora Fin actividad', 'Hora inicio actividad', 'Hora Fin actividad', 'Días de la semana (Marcar los que corresponda)'],
        ['Ana', 'Prueba', 'Centro', 'Piscina', 'Natación', '1', 46296, 46568, 0.875, 22 / 24, 'Lunes, Miércoles, Viernes'],
    ]);
    $templateSheet->getStyle('G2:H2')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
    $templateSheet->getStyle('I2:J2')->getNumberFormat()->setFormatCode('hh:mm');
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($template))->save($path);
    $providedLayout = programSpreadsheet($path, 'xlsx');
    sheetCheck($providedLayout['rows'][0]['fecha_fin'] === '2027-06-30' && $providedLayout['rows'][0]['hora_fin'] === '22:00:00', 'La plantilla distingue fecha y hora fin aunque tengan el mismo encabezado.');
    sheetCheck($providedLayout['rows'][0]['grupo'] === '1' && count(programNormalize($providedLayout['rows'], 'participantes')) === 1, 'Admite aclaraciones en los encabezados de la plantilla.');
    $templateSheet->getStyle('H2')->getNumberFormat()->setFormatCode('hh:mm');
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($template))->save($path);
    try { programSpreadsheet($path, 'xlsx'); throw new RuntimeException('Se admitieron dos horas fin ambiguas.'); }
    catch (DomainException $expected) { /* Inferir solo cuando una columna contiene fechas de calendario. */ }
    $template->disconnectWorksheets();
    fwrite(STDOUT, "[OK] Excel/CSV, columnas reordenadas, fechas, horas, hojas y fórmulas.\n");
} finally { unlink($path); }
