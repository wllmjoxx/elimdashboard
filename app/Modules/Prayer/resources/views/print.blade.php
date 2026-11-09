<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Daftar Doa {{ $week['label'] }} - GGP Elim</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: "Source Sans 3", "Segoe UI", Arial, sans-serif; color: #111; margin: 0; padding: 32px; font-size: 13px; line-height: 1.5; }
        header { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #f26b1d; padding-bottom: 12px; margin-bottom: 20px; }
        h1 { font-family: "Playfair Display", Georgia, serif; font-size: 22px; margin: 0; }
        .muted { color: #555; }
        .confidential { font-weight: 600; color: #b42318; text-transform: uppercase; letter-spacing: .08em; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; vertical-align: top; padding: 8px; border-bottom: 1px solid #ddd; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #555; }
        td.body { white-space: pre-line; width: 45%; }
        tr { break-inside: avoid; }
        .actions { margin-bottom: 16px; }
        .actions button { font: inherit; padding: 6px 14px; border-radius: 999px; border: 1px solid #ccc; background: #fff; cursor: pointer; }
        @media print { body { padding: 0; } .actions { display: none; } }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">Cetak / Simpan sebagai PDF</button>
    </div>
    <header>
        <div>
            <h1>Daftar Pokok Doa</h1>
            <div class="muted">Minggu {{ $week['label'] }} · {{ count($requests) }} pokok doa</div>
        </div>
        <div style="text-align: right">
            <div class="confidential">Rahasia · hanya untuk Tim Doa</div>
            <div class="muted">Dicetak {{ $printedAt }}</div>
        </div>
    </header>

    @if (count($requests) === 0)
        <p class="muted">Tidak ada pokok doa untuk minggu ini.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>Kontak</th>
                    <th>Kategori</th>
                    <th>Pokok doa</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($requests as $index => $prayer)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $prayer['fullName'] }}</strong>
                            @if ($prayer['age'])<div class="muted">{{ $prayer['age'] }} tahun</div>@endif
                        </td>
                        <td>
                            {{ $prayer['phone'] ?? '-' }}
                            @if ($prayer['address'])<div class="muted">{{ $prayer['address'] }}</div>@endif
                        </td>
                        <td>{{ $prayer['category'] }}</td>
                        <td class="body">{{ $prayer['body'] }}</td>
                        <td>{{ $prayer['statusLabel'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    <script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
