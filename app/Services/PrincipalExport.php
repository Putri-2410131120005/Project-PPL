<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class PrincipalExport
{
    public function pdf(array $documents): string
    {
        $options = new Options(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false,
            'defaultFont' => 'DejaVu Sans', 'chroot' => resource_path('views/dev/principal')]);
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('dev.principal.pdf', compact('documents'))->render(), 'UTF-8');
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        return $pdf->output();
    }

    private function archive(array $entries): string
    {
        $file = tempnam(sys_get_temp_dir(), 'ks-export-');
        try {
            $zip = new ZipArchive;
            if ($zip->open($file, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Ekspor arsip gagal dibuat.');
            }
            foreach ($entries as $name => $body) {
                if (! $zip->addFromString($name, $body)) {
                    throw new RuntimeException('Isi arsip gagal ditambahkan.');
                }
            }
            if (! $zip->close()) {
                throw new RuntimeException('Arsip gagal diselesaikan.');
            }

            return file_get_contents($file);
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function xlsx(array $documents): string
    {
        $rows = [['Nama Siswa', 'NIS', 'Kelas Saat Ini', 'Periode', 'Ringkasan Resmi', 'Isi Laporan', 'Tanggal Catatan', 'Sumber', 'Mata Pelajaran', 'Nama Pencatat', 'Peristiwa', 'Tindakan / Tindak Lanjut', 'Hasil / Kendala', 'Materi', 'Catatan Tambahan']];
        foreach ($documents as $d) {
            foreach ($d['sources'] ?: [[]] as $s) {
                $rows[] = [$d['student'], $d['nis'], $d['class'], $d['period'], $d['summary'], $d['report'] ?? '', $s['date'] ?? '', $s['source'] ?? '', $s['subject'] ?? '', $s['teacher'] ?? '', $s['event'] ?? '', $s['action'] ?? '', $s['result'] ?? '', $s['material'] ?? '', $s['extra'] ?? ''];
            }
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach ($rows as $i => $row) {
            $xml .= '<row r="'.($i + 1).'">';
            foreach ($row as $j => $value) {
                // Inline strings prevent Excel interpreting untrusted report text as formulas.
                $value = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', (string) $value);
                $xml .= '<c r="'.chr(65 + $j).($i + 1).'" t="inlineStr"><is><t xml:space="preserve">'.htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</t></is></c>';
            }
            $xml .= '</row>';
        }

        return $this->archive([
            '[Content_Types].xml' => '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Laporan Siswa" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>',
            'xl/worksheets/sheet1.xml' => $xml.'</sheetData></worksheet>',
        ]);
    }

    public function zip(array $documents): string
    {
        $entries = [];
        foreach ($documents as $i => $document) {
            $entries[($i + 1).'-'.Str::slug($document['nis'].'-'.$document['period']).'.pdf'] = $this->pdf([$document]);
        }

        return $this->archive($entries);
    }
}
