<?php

namespace Modules\TalentoHumano\Support;

use Carbon\Carbon;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Lectura y escritura mínima de archivos .xlsx con la extensión zip de PHP (sin dependencias externas).
 * Suficiente para la plantilla de carga inicial: texto, números y fechas en la primera hoja.
 */
class HojaExcel
{
    private const NS_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    /**
     * Filas de la primera hoja del libro, cada una como lista de textos (columna A en la posición 0).
     *
     * @throws RuntimeException si el archivo no es un .xlsx válido
     */
    public static function leer(string $ruta): array
    {
        $zip = new ZipArchive();
        if ($zip->open($ruta) !== true || $zip->locateName('xl/workbook.xml') === false) {
            throw new RuntimeException('No es un archivo .xlsx');
        }

        $compartidos = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            foreach (simplexml_load_string($xml)->si as $si) {
                $compartidos[] = self::texto($si);
            }
        }

        $libro = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
        $idHoja = (string) $libro->sheets->sheet[0]->attributes(self::NS_REL)['id'];
        $relaciones = simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));
        $destino = null;
        foreach ($relaciones->Relationship as $rel) {
            if ((string) $rel['Id'] === $idHoja) {
                $destino = ltrim((string) $rel['Target'], '/');
            }
        }
        $hoja = $zip->getFromName(str_starts_with($destino, 'xl/') ? $destino : 'xl/' . $destino);
        $zip->close();
        if ($hoja === false) {
            throw new RuntimeException('El libro no tiene hojas');
        }

        $filas = [];
        foreach (simplexml_load_string($hoja)->sheetData->row as $row) {
            $fila = [];
            foreach ($row->c as $c) {
                $columna = self::indiceColumna(preg_replace('/\d+/', '', (string) $c['r']));
                $fila[$columna] = match ((string) $c['t']) {
                    's' => $compartidos[(int) $c->v] ?? '',
                    'inlineStr' => self::texto($c->is),
                    default => (string) $c->v,
                };
            }
            if ($fila) {
                $filas[(int) $row['r'] ?: count($filas) + 1] = array_replace(array_fill(0, max(array_keys($fila)) + 1, ''), $fila);
            }
        }

        return $filas;
    }

    /**
     * Genera un .xlsx con varias hojas: [nombre => [[celda, celda, …], …]]. La primera fila de cada hoja va en negrita.
     */
    public static function escribir(array $hojas): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'folium');
        $zip = new ZipArchive();
        $zip->open($ruta, ZipArchive::OVERWRITE);

        $tipos = $libro = $rels = '';
        $n = 0;
        foreach ($hojas as $nombre => $filas) {
            $n++;
            $tipos .= "<Override PartName=\"/xl/worksheets/sheet{$n}.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml\"/>";
            $libro .= '<sheet name="' . self::xml(mb_substr($nombre, 0, 31)) . "\" sheetId=\"{$n}\" r:id=\"rId{$n}\"/>";
            $rels .= "<Relationship Id=\"rId{$n}\" Type=\"" . self::NS_REL . "/worksheet\" Target=\"worksheets/sheet{$n}.xml\"/>";
            $zip->addFromString("xl/worksheets/sheet{$n}.xml", self::hoja($filas));
        }
        $estilos = $n + 1;

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . $tipos . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="' . self::NS_REL . '/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="' . self::NS_REL . '"><sheets>' . $libro . '</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $rels . "<Relationship Id=\"rId{$estilos}\" Type=\"" . self::NS_REL . '/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF064C3D"/></patternFill></fill></fills>'
            . '<borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs>'
            . '<cellXfs count="2"><xf/><xf fontId="1" fillId="2" applyFont="1" applyFill="1"/></cellXfs></styleSheet>');
        $zip->close();

        $contenido = file_get_contents($ruta);
        unlink($ruta);

        return $contenido;
    }

    /**
     * Convierte el valor de una celda de fecha (número de serie de Excel o texto dd/mm/aaaa, aaaa-mm-dd) en fecha.
     */
    public static function fecha(string $valor): ?Carbon
    {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }
        if (is_numeric($valor)) {
            return Carbon::create(1899, 12, 30)->addDays((int) $valor);
        }
        foreach (['d/m/Y', 'j/n/Y', 'Y-m-d', 'd-m-Y'] as $formato) {
            try {
                $fecha = Carbon::createFromFormat('!' . $formato, $valor);
            } catch (\Throwable) {
                continue;
            }
            if ($fecha && $fecha->format($formato) === $valor) {
                return $fecha;
            }
        }

        return null;
    }

    private static function hoja(array $filas): string
    {
        $anchos = [];
        $xmlFilas = '';
        foreach (array_values($filas) as $i => $fila) {
            $celdas = '';
            foreach (array_values($fila) as $j => $valor) {
                $anchos[$j] = max($anchos[$j] ?? 10, min(60, mb_strlen((string) $valor) + 2));
                $ref = self::letraColumna($j) . ($i + 1);
                $estilo = $i === 0 ? ' s="1"' : '';
                $celdas .= "<c r=\"{$ref}\" t=\"inlineStr\"{$estilo}><is><t xml:space=\"preserve\">" . self::xml((string) $valor) . '</t></is></c>';
            }
            $xmlFilas .= '<row r="' . ($i + 1) . "\">{$celdas}</row>";
        }
        $columnas = collect($anchos)->map(fn ($ancho, $j) => '<col min="' . ($j + 1) . '" max="' . ($j + 1) . "\" width=\"{$ancho}\" customWidth=\"1\"/>")->implode('');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . ($columnas ? "<cols>{$columnas}</cols>" : '') . "<sheetData>{$xmlFilas}</sheetData></worksheet>";
    }

    private static function texto(SimpleXMLElement $nodo): string
    {
        if (isset($nodo->t)) {
            return (string) $nodo->t;
        }

        return collect($nodo->r)->map(fn ($r) => (string) $r->t)->implode('');
    }

    private static function indiceColumna(string $letras): int
    {
        return array_reduce(str_split(strtoupper($letras)), fn ($n, $l) => $n * 26 + ord($l) - 64, 0) - 1;
    }

    private static function letraColumna(int $indice): string
    {
        $letras = '';
        for ($n = $indice + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $letras = chr(65 + ($n - 1) % 26) . $letras;
        }

        return $letras;
    }

    private static function xml(string $texto): string
    {
        return htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
