<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Shrnutí poptávky {{ $quote->id }}</title>
    <style>
        @page { margin: 20mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 11pt; color: #18181b; }
        h1 { font-size: 20pt; }
        h2 { margin-top: 24pt; font-size: 14pt; }
        th, td { padding: 6pt; text-align: left; vertical-align: top; }
        th { width: 35%; }
        table { width: 100%; border-collapse: collapse; }
        tr { border-bottom: 1px solid #e4e4e7; }
        .message { white-space: pre-wrap; overflow-wrap: anywhere; }
        .attachment { margin-bottom: 18pt; overflow-wrap: anywhere; }
        .attachment img { display: block; max-width: 100%; max-height: 220mm; margin-top: 8pt; object-fit: contain; }
    </style>
</head>
<body>
    <h1>Shrnutí poptávky {{ $quote->id }}</h1>
    <h2>Kontaktní údaje</h2>
    <table>
        <tr><th>Jméno</th><td>{{ $quote->first_name }} {{ $quote->last_name }}</td></tr>
        <tr><th>Email</th><td>{{ $quote->email }}</td></tr>
        <tr><th>Telefon</th><td>{{ $quote->phone }}</td></tr>
        <tr><th>Adresa</th><td>{{ $quote->address ?? 'Neuvedeno' }}</td></tr>
    </table>
    <h2>Informace o kotli</h2>
    <table>
        <tr><th>Výrobce</th><td>{{ $quote->boiler_manufacturer?->label() ?? 'Neuvedeno' }}</td></tr>
        <tr><th>Výrobní číslo</th><td>{{ $quote->boiler_serial_number ?? 'Neuvedeno' }}</td></tr>
        <tr><th>Typ kotle</th><td>{{ $quote->boiler_type ?? 'Neuvedeno' }}</td></tr>
        <tr><th>Záruka</th><td>{{ $quote->under_warranty ? 'Ano' : 'Ne' }}</td></tr>
    </table>
    <h2>Zpráva</h2>
    <div class="message">{{ $quote->message }}</div>
    @if ($attachments->isNotEmpty())
        <h2>Přílohy</h2>
        @foreach ($attachments as $attachment)
            <div class="attachment">
                <h3>{{ $attachment['type'] }}</h3>
                <p>{{ $attachment['label'] }} ({{ $attachment['mime_type'] }})</p>
                @if ($attachment['image'] !== null)
                    <img src="{{ $attachment['image'] }}" alt="{{ $attachment['label'] }}">
                @endif
            </div>
        @endforeach
    @endif
</body>
</html>
