<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Shrnutí poptávky {{ $quote->id }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #e5e5e5;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11pt;
            color: #18181b;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 8mm auto;
            padding: 10mm;
            background: #fff;
        }

        h1 {
            margin-top: 0;
            font-size: 20pt;
        }

        .generated-at {
            font-size: 9pt;
            color: #71717a;
        }

        h2 {
            margin-top: 24pt;
            font-size: 14pt;
        }

        th, td {
            padding: 6pt;
            text-align: left;
            vertical-align: top;
        }

        th {
            width: 35%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        tr {
            border-bottom: 1px solid #e4e4e7;
        }

        .message {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .attachment {
            page-break-before: always;
            break-before: page;
            overflow-wrap: anywhere;
        }

        .attachment h2, .attachment h3 {
            margin-top: 0;
        }

        .attachment img {
            display: block;
            max-width: 100%;
            max-height: 220mm;
            margin-top: 8pt;
            object-fit: contain;
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                width: auto;
                min-height: 0;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
<main class="page">
    <h1>Shrnutí poptávky {{ $quote->id }}</h1>
    <p class="generated-at">Vygenerováno: {{ $generatedAt }}</p>
    <h2>Kontaktní údaje</h2>
    <table>
        <tr>
            <th>Jméno</th>
            <td>{{ $quote->first_name }} {{ $quote->last_name }}</td>
        </tr>
        <tr>
            <th>Email</th>
            <td>{{ $quote->email }}</td>
        </tr>
        <tr>
            <th>Telefon</th>
            <td>{{ $quote->phone }}</td>
        </tr>
        <tr>
            <th>Adresa</th>
            <td>{{ $quote->address ?? 'Neuvedeno' }}</td>
        </tr>
    </table>
    <h2>Informace o kotli</h2>
    <table>
        <tr>
            <th>Výrobce</th>
            <td>{{ $quote->boiler_manufacturer?->label() ?? 'Neuvedeno' }}</td>
        </tr>
        <tr>
            <th>Výrobní číslo</th>
            <td>{{ $quote->boiler_serial_number ?? 'Neuvedeno' }}</td>
        </tr>
        <tr>
            <th>Typ kotle</th>
            <td>{{ $quote->boiler_type ?? 'Neuvedeno' }}</td>
        </tr>
        <tr>
            <th>Záruka</th>
            <td>{{ $quote->under_warranty ? 'Ano' : 'Ne' }}</td>
        </tr>
    </table>
    <h2>Zpráva</h2>
    <div class="message">{{ $quote->message }}</div>
    @if ($attachments->isEmpty())
        <h2>Přílohy</h2>
        <p>Bez příloh.</p>
    @endif
</main>
@if ($attachments->isNotEmpty())
    @foreach ($attachments as $attachment)
        <div class="page attachment">
            <h3>Příloha: {{ $attachment['type'] }}</h3>
            <p>{{ $attachment['label'] }} ({{ $attachment['mime_type'] }})</p>
            @if ($attachment['image'] !== null)
                <img src="{{ $attachment['image'] }}" alt="{{ $attachment['label'] }}">
            @endif
        </div>
    @endforeach
@endif
</body>
</html>
