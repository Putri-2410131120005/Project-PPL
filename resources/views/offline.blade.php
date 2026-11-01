<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2563eb">
    <title>Anda sedang offline — SMART-ABK</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; box-sizing: border-box; background: #f5f8ff; color: #172554; }
        main { width: min(100%, 420px); padding: 32px; text-align: center; background: #fff; border-radius: 24px; box-shadow: 0 16px 48px rgba(30, 64, 175, .12); }
        .mark { width: 60px; height: 60px; display: grid; place-items: center; margin: 0 auto 20px; border-radius: 18px; background: #2563eb; color: #fff; font-weight: 800; font-size: 22px; }
        h1 { margin: 0; font-size: 24px; } p { margin: 12px 0 24px; color: #475569; line-height: 1.6; }
        button { border: 0; border-radius: 10px; padding: 12px 18px; background: #2563eb; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>
    <main>
        <div class="mark" aria-hidden="true">SA</div>
        <h1>Anda sedang offline</h1>
        <p>Periksa koneksi internet Anda, lalu coba buka SMART-ABK kembali. Data siswa dan catatan tidak disimpan di perangkat pada tahap ini.</p>
        <button type="button" onclick="window.location.reload()">Coba lagi</button>
    </main>
</body>
</html>
