{{-- Gaya cetak yang menjaga ukuran, tabel, dan tanda tangan PDF tetap konsisten. --}}
<style>
    @page { margin: 25mm 25mm 25mm 30mm; size: A4 portrait; }
    .abk-pdf-sheet { background: #fff; box-sizing: border-box; color: #111; font-family: "DejaVu Serif", serif; font-size: 11pt; line-height: 1.45; margin: 0 auto; min-height: 900px; padding: 28px 34px; width: 650px; }
    .abk-pdf-document { margin: 0; }
    .abk-pdf-document .abk-pdf-sheet { min-height: 0; padding: 0; width: auto; }
    .abk-pdf-letterhead { border-bottom: 1.2px solid #111; margin-bottom: 16px; padding-bottom: 10px; text-align: center; }
    .abk-pdf-school { font-size: 13pt; font-weight: 700; margin: 0 0 5px; text-transform: uppercase; }
    .abk-pdf-title { font-size: 14pt; font-weight: 700; line-height: 1.3; margin: 0 0 5px; text-align: center; text-transform: uppercase; }
    .abk-pdf-period { font-size: 11pt; font-weight: 600; margin: 0; text-align: center; text-transform: uppercase; }
    .abk-pdf-info { border-collapse: collapse; margin: 0 0 16px; table-layout: auto; width: 100%; }
    .abk-pdf-info td { border: 0; padding: 2px 0; vertical-align: top; }
    .abk-pdf-info .abk-pdf-label { width: 116px; }
    .abk-pdf-info .abk-pdf-separator { text-align: center; width: 16px; }
    .abk-pdf-table { border-collapse: collapse; table-layout: fixed; width: 100%; }
    .abk-pdf-table thead { display: table-header-group; }
    .abk-pdf-table tr { page-break-inside: avoid; }
    .abk-pdf-table th, .abk-pdf-table td { border: .6px solid #555; font-size: 9.5pt; line-height: 1.35; overflow-wrap: break-word; padding: 5px 6px; vertical-align: top; }
    .abk-pdf-table th { background: #f0f0f0; font-weight: 700; text-align: center; vertical-align: middle; }
    .abk-pdf-center { text-align: center; }
    .abk-pdf-empty { padding: 14px 6px !important; text-align: center; }
    .abk-pdf-approval { page-break-inside: avoid; }
    .abk-pdf-date { margin: 18px 0 5px; text-align: right; }
    .abk-pdf-signatures { border-collapse: collapse; table-layout: fixed; width: 100%; }
    .abk-pdf-signature { border: 0; font-size: 10.5pt; padding: 0; text-align: center; vertical-align: top; width: 50%; }
    .abk-pdf-signature-space { height: 72px; }
    .abk-pdf-signature strong { display: block; text-decoration: underline; }
    .abk-pdf-signature span { display: block; font-size: 9.5pt; margin-top: 2px; }
    .abk-pdf-footer { border-top: .5px solid #777; bottom: -15mm; color: #444; font-size: 9pt; left: 0; padding-top: 5px; position: fixed; right: 0; }
    .abk-pdf-page { float: right; }
    .abk-pdf-page::after { content: counter(page); }
    .abk-pdf-preview-toolbar { align-items: center; display: flex; gap: 12px; justify-content: space-between; margin: 28px 20px 30px; }
    .abk-pdf-preview-back { align-items: center; color: #414fdb; display: inline-flex; font-family: Inter, ui-sans-serif, system-ui, sans-serif; font-size: 12px; font-weight: 600; gap: 8px; min-height: 40px; text-decoration: none; }
    .abk-pdf-preview-back:hover { color: #303cb0; text-decoration: underline; }
    .abk-pdf-download-button { border-radius: 12px; font-size: 13px; min-height: 40px; min-width: 132px; padding: 10px 16px; }
    .abk-pdf-preview-stage { background: #f4f6fa; height: max(520px, calc(100vh - 232px)); margin: 0 20px; overflow: auto; padding: 0 28px 28px; }
    .abk-pdf-preview-pages { margin: 0 auto; min-width: 650px; width: 650px; }
    .abk-pdf-preview-stage .abk-pdf-sheet { box-shadow: 0 2px 8px rgb(0 0 0 / 8%); }
    .abk-pdf-preview-stage .abk-pdf-footer { display: none; }
    @media (max-width: 767.98px) {
        .abk-pdf-preview-toolbar { align-items: stretch; flex-direction: column; margin: 8px 0 16px; }
        .abk-pdf-preview-back, .abk-pdf-download-button { justify-content: center; width: 100%; }
        .abk-pdf-preview-stage { height: min(68vh, 720px); margin-left: 0; margin-right: 0; padding: 0 16px 16px; }
    }
</style>
