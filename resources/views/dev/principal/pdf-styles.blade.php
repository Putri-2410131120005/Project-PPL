{{-- Gaya khusus hasil PDF Kepala Sekolah. --}}
<style>
    @page { margin: 25mm 25mm 25mm 30mm; size: A4 portrait; }
    .ks-pdf-page-sheet { background: #fff; box-sizing: border-box; color: #111; font-family: "DejaVu Serif", serif; font-size: 11pt; line-height: 1.45; margin: 0 auto 24px; min-height: 900px; padding: 28px 34px; width: 650px; }
    .ks-pdf-document { margin: 0; }
    .ks-pdf-document .ks-pdf-page-sheet { margin: 0; min-height: 0; page-break-after: always; padding: 0; width: auto; }
    .ks-pdf-document .ks-pdf-page-sheet:last-child { page-break-after: auto; }
    .ks-pdf-letterhead { border-bottom: 1.2px solid #111; margin-bottom: 16px; padding-bottom: 10px; text-align: center; }
    .ks-pdf-school { font-size: 13pt; font-weight: 700; margin: 0 0 5px; text-transform: uppercase; }
    .ks-pdf-title { font-size: 14pt; font-weight: 700; line-height: 1.3; margin: 0 0 5px; text-transform: uppercase; }
    .ks-pdf-period { font-size: 11pt; font-weight: 600; margin: 0; text-transform: uppercase; }
    .ks-pdf-identity { border-collapse: collapse; margin: 0 0 16px; table-layout: auto; width: 100%; }
    .ks-pdf-identity td { border: 0; padding: 2px 0; vertical-align: top; }
    .ks-pdf-identity .ks-pdf-label { width: 118px; }
    .ks-pdf-identity .ks-pdf-separator { text-align: center; width: 16px; }
    .ks-pdf-page-sheet h2 { font-size: 11pt; margin: 15px 0 6px; }
    .ks-pdf-narrative { margin: 0 0 10px; overflow-wrap: break-word; text-align: justify; white-space: pre-wrap; }
    .ks-pdf-report-table { border-collapse: collapse; table-layout: fixed; width: 100%; }
    .ks-pdf-report-table thead { display: table-header-group; }
    .ks-pdf-report-table tr { page-break-inside: avoid; }
    .ks-pdf-report-table th, .ks-pdf-report-table td { border: .6px solid #555; font-size: 9.5pt; line-height: 1.35; overflow-wrap: break-word; padding: 5px 6px; vertical-align: top; }
    .ks-pdf-report-table th { background: #f0f0f0; font-weight: 700; text-align: center; vertical-align: middle; }
    .ks-pdf-center { text-align: center; }
    .ks-pdf-empty { padding: 14px 6px !important; text-align: center; }
    .ks-pdf-note { border-bottom: .6px solid #aaa; page-break-inside: avoid; padding: 0 0 10px; }
    .ks-pdf-note:last-of-type { border-bottom: 0; }
    .ks-pdf-note h3 { font-size: 10.5pt; margin: 13px 0 4px; }
    .ks-pdf-note p { margin: 3px 0; overflow-wrap: break-word; white-space: pre-wrap; }
    .ks-pdf-approval { page-break-inside: avoid; }
    .ks-pdf-place-date { margin: 18px 0 5px; text-align: right; }
    .ks-pdf-signatures { border-collapse: collapse; table-layout: fixed; width: 100%; }
    .ks-pdf-signatures td { border: 0; font-size: 10.5pt; padding: 0; text-align: center; vertical-align: top; width: 50%; }
    .ks-pdf-signature-space { height: 72px; }
    .ks-pdf-signatures strong { display: block; text-decoration: underline; }
    .ks-pdf-signatures small { display: block; font-size: 9.5pt; margin-top: 2px; }
    .ks-pdf-footer { border-top: .5px solid #777; bottom: -15mm; color: #444; font-size: 9pt; left: 0; padding-top: 5px; position: fixed; right: 0; }
    .ks-pdf-page-number { float: right; }
    .ks-pdf-page-number::after { content: counter(page); }
    .ks-pdf-preview-stage { background: #f4f6fa; height: max(520px, calc(100vh - 232px)); overflow: auto; padding: 0 28px 28px; }
    .ks-pdf-preview-pages { margin: 0 auto; min-width: 650px; width: 650px; }
    .ks-pdf-preview-stage .ks-pdf-page-sheet { box-shadow: 0 2px 8px rgb(0 0 0 / 8%); }
    .ks-pdf-preview-stage .ks-pdf-footer { display: none; }
    @media (max-width: 767.98px) {
        .ks-pdf-preview-stage { height: min(68vh, 720px); padding: 0 16px 16px; }
    }
</style>
