<?php

/**
 * Generator: td_05_implementation_report.md → .docx (Office Open XML).
 * Uses PHP ZipArchive only — no Composer dependencies.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$mdPath = $root . '/docs/reports/td_05_implementation_report.md';
$outPath = $root . '/docs/reports/td_05_implementation_report.docx';

if (! is_readable($mdPath)) {
    fwrite(STDERR, "Markdown source not found: {$mdPath}\n");
    exit(1);
}

$md = file_get_contents($mdPath);
$lines = preg_split('/\r\n|\n|\r/', $md);

$body = '';
foreach ($lines as $line) {
    $line = rtrim($line);

    if ($line === '---') {
        $body .= paragraph('', false, true);
        continue;
    }

    if (preg_match('/^# (.+)$/', $line, $m)) {
        $body .= paragraph($m[1], true, false, 32);
        continue;
    }

    if (preg_match('/^## (.+)$/', $line, $m)) {
        $body .= paragraph($m[1], true, false, 28);
        continue;
    }

    if (preg_match('/^### (.+)$/', $line, $m)) {
        $body .= paragraph($m[1], true, false, 24);
        continue;
    }

    if (preg_match('/^\|(.+)\|$/', $line) && ! str_contains($line, '---|')) {
        $cells = array_map('trim', explode('|', trim($line, '|')));
        $body .= paragraph(implode(' | ', $cells), false, false, 20);
        continue;
    }

    if (str_starts_with($line, '```')) {
        continue;
    }

    if ($line === '') {
        continue;
    }

    $body .= paragraph($line, false, false, 22);
}

$documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
    . '<w:body>'
    . $body
    . '<w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440"/></w:sectPr>'
    . '</w:body></w:document>';

$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
    . '</Types>';

$rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
    . '</Relationships>';

$docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>';

$zip = new ZipArchive();
if ($zip->open($outPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Cannot create DOCX: {$outPath}\n");
    exit(1);
}

$zip->addFromString('[Content_Types].xml', $contentTypes);
$zip->addFromString('_rels/.rels', $rels);
$zip->addFromString('word/document.xml', $documentXml);
$zip->addFromString('word/_rels/document.xml.rels', $docRels);
$zip->close();

echo "Wrote {$outPath}\n";

function paragraph(string $text, bool $bold = false, bool $rule = false, int $sizeHalfPoints = 22): string
{
    if ($rule) {
        return '<w:p><w:pPr><w:pBdr><w:bottom w:val="single" w:sz="6" w:space="1" w:color="auto"/></w:pBdr></w:pPr></w:p>';
    }

    $escaped = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $rPr = '';
    if ($bold) {
        $rPr .= '<w:b/>';
    }
    $rPr .= '<w:sz w:val="' . $sizeHalfPoints . '"/>';

    return '<w:p><w:r><w:rPr>' . $rPr . '</w:rPr><w:t xml:space="preserve">' . $escaped . '</w:t></w:r></w:p>';
}
