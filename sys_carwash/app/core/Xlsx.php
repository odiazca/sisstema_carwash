<?php
/** Generador mínimo de .xlsx (una hoja) sin dependencias; usa ZipArchive y cae a CSV si no está disponible. */
class Xlsx {
    /** $cols: [['Encabezado','s|n|m'], ...]  s=texto, n=número, m=dinero (2 decimales). $rows: arrays en el mismo orden. */
    public static function download(string $filename, string $sheet, array $cols, array $rows): void {
        if (!class_exists('ZipArchive')) { self::csv($filename, $cols, $rows); return; }
        $x = fn($s) => htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $col = function (int $i): string { $s = ''; $i++; while ($i > 0) { $m = ($i - 1) % 26; $s = chr(65 + $m) . $s; $i = intdiv($i - 1, 26); } return $s; };
        $w = array_map(fn($c) => max(10, mb_strlen($c[0]) + 3), $cols);
        $data = '<row r="1">';
        foreach ($cols as $i => $c) $data .= '<c r="' . $col($i) . '1" s="1" t="inlineStr"><is><t>' . $x($c[0]) . '</t></is></c>';
        $data .= '</row>';
        foreach ($rows as $ri => $r) {
            $rn = $ri + 2; $data .= '<row r="' . $rn . '">'; $r = array_values($r);
            foreach ($cols as $i => $c) {
                $v = $r[$i] ?? null; $ref = $col($i) . $rn;
                if ($v === null || $v === '') { continue; }
                if ($c[1] !== 's' && is_numeric($v)) $data .= '<c r="' . $ref . '"' . ($c[1] === 'm' ? ' s="2"' : '') . '><v>' . (0 + $v) . '</v></c>';
                else { $data .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . $x($v) . '</t></is></c>'; $w[$i] = min(60, max($w[$i], mb_strlen((string)$v) + 2)); }
            }
            $data .= '</row>';
        }
        $cw = '<cols>'; foreach ($w as $i => $wd) $cw .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $wd . '" customWidth="1"/>'; $cw .= '</cols>';
        $last = $col(count($cols) - 1) . (count($rows) + 1);
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>' . $cw . '<sheetData>' . $data . '</sheetData><autoFilter ref="A1:' . $last . '"/></worksheet>';
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0EA5E9"/></patternFill></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/><xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
        $tmp = tempnam(sys_get_temp_dir(), 'xl'); $z = new ZipArchive();
        $z->open($tmp, ZipArchive::OVERWRITE);
        $z->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $z->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $z->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="' . $x(mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', ' ', $sheet), 0, 31)) . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $z->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $z->addFromString('xl/styles.xml', $styles);
        $z->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $z->close();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp); @unlink($tmp); exit;
    }

    private static function csv(string $filename, array $cols, array $rows): void {
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        echo "\xEF\xBB\xBF"; $o = fopen('php://output', 'w');
        fputcsv($o, array_column($cols, 0), ';'); foreach ($rows as $r) fputcsv($o, array_values($r), ';'); fclose($o); exit;
    }
}
