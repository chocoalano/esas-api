<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Dokumen PDF')</title>
    <style>
        * {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            box-sizing: border-box;
        }

        body {
            margin: 30px;
            color: #000;
            background: #fff;
        }

        .header {
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .kop-container {
            display: flex;
            align-items: center;
        }

        .logo {
            width: 70px;
            height: 70px;
        }

        .kop-text {
            flex: 1;
            text-align: center;
        }

        .kop-text h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .kop-text h2 {
            margin: 2px 0;
            font-size: 14px;
        }

        .kop-text p {
            font-size: 11px;
            margin: 0;
        }

        h2.title {
            text-align: center;
            margin-bottom: 20px;
            text-decoration: underline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table, th, td {
            border: 1px solid #333;
        }

        th, td {
            padding: 6px 10px;
            text-align: left;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }

        .footer {
            margin-top: 30px;
            text-align: right;
        }

        .signature {
            margin-top: 60px;
            text-align: right;
        }

    </style>
</head>
<body>
    {{-- Kop Surat --}}
    <div class="header">
        <div class="kop-container">
            <img src="{{ public_path('images/logo.png') }}" class="logo" alt="Logo">
            <div class="kop-text">
                <h1>PT. Sinergi Abadi Sentosa</h1>
                <h2>Jl. Contoh Alamat No.123, Jakarta</h2>
                <p>Telepon: (021) 12345678 | Email: info@namaperusahaan.com | Website: www.namaperusahaan.com</p>
            </div>
        </div>
    </div>

    {{-- Konten --}}
    @yield('content')

    {{-- Footer opsional --}}
    <div class="footer">
        <p>Dicetak pada: {{ \Carbon\Carbon::now()->format('d-m-Y') }}</p>
    </div>
</body>
</html>
