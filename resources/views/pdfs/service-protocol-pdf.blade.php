{{-- Standalone A4 view. All data is optional so the form can also be printed blank.
     CSS Grid requires a browser-based PDF renderer (such as Browsershot), not Dompdf.
     Optional presentation data: $customer (DTO), $parts (DTO list), $partsTotal,
     $assemblyActions (DTO list), $assemblyActionsTotal and $totals (array).
     Monetary values are supplied already formatted; this view performs no calculations. --}}
        <!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Servisní protokol{{ isset($work) ? ' – '.$work->order_id : '' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
        }

        body {
            color: #000;
            background: #e5e5e5;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            line-height: 1.2;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 8mm auto;
            padding: 10mm;
            background: #fff;
        }

        h1 {
            height: 10mm;
            margin: 0 0 5mm;
            text-align: center;
            font-size: 17pt;
            font-weight: 500;
        }

        .protocol {
            display: grid;
            grid-template-rows: 40mm 26mm 20mm 32mm minmax(44mm, auto) minmax(34mm, auto) 20mm 20mm 26mm;
            border: .5mm solid #000;
        }

        .grid {
            display: grid;
            min-width: 0;
            min-height: 0;
        }

        .section {
            border-bottom: .4mm solid #000;
        }

        .cell {
            min-width: 0;
            min-height: 0;
            padding: 1mm;
            overflow-wrap: anywhere;
        }

        .divide {
            border-right: .4mm solid #000;
        }

        .center {
            text-align: center;
        }

        .value {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .header {
            grid-template-columns: 35% 30% 35%;
            grid-template-rows: 20mm repeat(3, minmax(0, 1fr));
            grid-template-areas:
                "order brand company"
                "type claim company"
                "number purchase warranty"
                "serial purchase building";
        }

        .header .order { grid-area: order; }
        .header .brand { grid-area: brand; }
        .header .company { grid-area: company; }
        .product-type { grid-area: type; }
        .product-number { grid-area: number; }
        .product-serial { grid-area: serial; }
        .product-claim { grid-area: claim; }
        .product-purchase { grid-area: purchase; }
        .product-warranty { grid-area: warranty; }
        .product-building { grid-area: building; }

        .header .order,
        .header .brand,
        .header .company {
            border-bottom: .4mm solid #000;
        }

        .order {
            grid-template-rows: auto 1fr;
        }

        .order strong {
            font-size: 12pt;
        }

        .order-number {
            align-self: center;
            font-size: 15pt;
        }

        .brand {
            display: grid;
            place-items: center;
            padding: 3mm;
        }

        .brand img {
            width: 100%;
            max-height: 13mm;
            object-fit: contain;
            filter: grayscale(1);
        }

        .company {
            text-align: center;
            font-size: 8pt;
        }

        .company h2 {
            margin: 0 0 2mm;
            font-size: 12pt;
        }

        .company strong {
            display: block;
            font-size: 10pt;
        }

        .field {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            align-items: start;
            gap: 1mm;
        }

        .customer {
            grid-template-columns: 65% 35%;
        }

        .customer-details {
            grid-template-rows: 1fr 2fr 1fr;
        }

        .dates {
            grid-template-rows: 1fr 1fr;
        }

        .text-section {
            grid-template-rows: auto minmax(0, 1fr);
        }

        .text-section .value {
            padding: 0 1mm 1mm;
        }

        .items {
            grid-template-rows: 6mm repeat(var(--parts-row-count, 5), minmax(6mm, 1fr)) 6mm;
        }

        .parts-row {
            grid-template-columns: 16% 58% 10% 16%;
        }

        .labour {
            grid-template-rows: 6mm repeat(var(--labour-row-count, 4), minmax(6mm, 1fr)) 6mm;
        }

        .labour-row {
            grid-template-columns: 16% 68% 16%;
        }

        .item-row {
            border-bottom: .2mm solid #000;
            align-items: stretch;
        }

        .item-row .cell + .cell {
            border-left: .4mm solid #000;
        }

        .item-heading {
            text-align: center;
        }

        .item-total {
            grid-template-columns: 84% 16%;
        }

        /* Draw shared dividers on the same side of each column boundary. */
        .item-total .amount,
        .transport > :nth-child(n + 2),
        .notes > :nth-child(n + 2),
        .signatures > :nth-child(n + 2) {
            border-left: .4mm solid #000;
        }

        .item-total .label {
            text-align: right;
            padding-right: 4mm;
        }

        .amount {
            text-align: right;
        }

        .transport {
            grid-template-columns: 64% 20% 16%;
        }

        .transport-route {
            grid-template-rows: repeat(3, 1fr);
        }

        .transport-cost {
            grid-template-rows: 1fr 1fr 1fr;
        }

        .total-label {
            border-top: .4mm solid #000;
            text-align: center;
        }

        .notes {
            grid-template-columns: 64% 36%;
        }

        .totals {
            /* Continue the 84% price divider through the nested 36% column.
               Account for this grid's left border before splitting its content. */
            grid-template-columns: calc((100% + .4mm) * 5 / 9 - .4mm) minmax(0, 1fr);
            grid-template-rows: repeat(3, 1fr);
        }

        .totals .cell {
            border-bottom: .4mm solid #000;
        }

        .totals .cell:nth-last-child(-n + 2) {
            border-bottom: 0;
        }

        .totals .amount {
            border-left: .4mm solid #000;
        }

        .signatures {
            grid-template-columns: 28% 36% 36%;
        }

        .signature {
            grid-template-rows: minmax(0, 1fr) auto;
        }

        .mechanic {
            align-self: center;
            text-align: center;
            font-size: 13pt;
        }

        .stamp {
            align-self: start;
            text-align: center;
            font-size: 8pt;
        }

        .stamp strong {
            display: block;
            font-size: 10pt;
        }

        .consent {
            font-size: 9pt;
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                margin: 0;
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
<main class="page">
    <h1>SERVISNÍ PROTOKOL</h1>
    <div class="protocol">
        <header class="grid header section">
            <div class="grid order cell divide">
                <strong>MONTÁŽNÍ LIST Č.</strong>
                <div class="order-number center">{{ $work->order_id ?? '' }}</div>
            </div>
            <div class="brand divide">
                <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('images/drazice-logo.jpg'))) }}"
                     alt="DRAŽICE">
            </div>
            <div class="company cell">
                <h2>SERVISNÍ FIRMA</h2>
                <strong>SERVIS – ZÁVACKÝ</strong>
                <div>IČ: 15499685</div>
                <div>TEL: 604897774</div>
                <div>bojleryzavacky@seznam.cz</div>
            </div>

            <div class="cell field product-type divide">
                <span>Typ výrobku:</span><span>{{ $work->type_of_product ?? '' }}</span>
            </div>
            <div class="cell field product-number divide">
                <span>Typové číslo:</span><span>{{ $work->type_number ?? '' }}</span>
            </div>
            <div class="cell field product-serial divide">
                <span>Výr. číslo:</span><span>{{ $work->serial_number ?? '' }}</span>
            </div>
            <div class="cell product-claim divide">
                Reklamace: {{ isset($work) ? ($work->warranty_accepted ? 'uznaná' : 'neuznaná') : 'uznaná / neuznaná' }}
            </div>
            <div class="cell product-purchase divide">
                Datum zakoupení ohřívače:<br>{{ isset($work) ? $work->day_of_purchase?->format('d.m.Y') : '' }}
            </div>
            <div class="cell product-warranty">
                Záruční oprava: {{ $work->repair_in_warranty ? 'ano': 'ne' }}
            </div>
            <div class="cell product-building">
                Výstavba: {{ $work->building_type ?? 'neznámá' }}
            </div>
        </header>

        <section class="grid customer section" aria-label="Údaje o zákazníkovi">
            <div class="grid customer-details divide">
                <div class="cell field">
                    <span>Zákazník:</span><span>{{ $customer->name ?? '' }}</span>
                </div>
                <div class="cell field">
                    <span>Adresa:</span><span class="value">{{ $customer->address ?? '' }}</span>
                </div>
                <div class="cell field">
                    <span>Tel:</span><span>{{ $customer->phone ?? '' }}</span>
                </div>
            </div>
            <div class="grid dates">
                <div class="cell">Zakázka přijata:<br>
                    {{ isset($work) ? $work->work_accepted_at?->format('d.m.Y') : '' }}</div>
                <div class="cell">Zakázka vyřízena:<br>
                    {{ isset($work) ? $work->work_completed_at?->format('d.m.Y') : '' }}</div>
            </div>
        </section>

        <section class="grid text-section section" aria-label="Nahlášená závada">
            <div class="cell">Nahlášená závada:</div>
            <div class="value">{{ $work->reported_problem ?? '' }}</div>
        </section>

        <section class="grid text-section section" aria-label="Provedená práce">
            <div class="cell">Popis provedené práce, odstranění závady</div>
            <div class="value">{{ $work->work_performed_description ?? '' }}</div>
        </section>

        <section class="grid items section" aria-label="Náhradní díly"
                 style="--parts-row-count: {{ max(5, count($parts ?? [])) }}">
            <div class="grid parts-row item-row item-heading">
                <div class="cell">
                    Číslo ND
                </div>
                <div class="cell">
                    Název ND
                </div>
                <div class="cell">
                    ks
                </div>
                <div class="cell">
                    Cena celkem
                </div>
            </div>
            @for ($row = 0; $row < max(5, count($parts ?? [])); $row++)
                <div class="grid parts-row item-row">
                    <div class="cell">
                        {{ $parts[$row]->number ?? '' }}
                    </div>
                    <div class="cell">
                        {{ $parts[$row]->name ?? '' }}
                    </div>
                    <div class="cell center">
                        {{ $parts[$row]->quantity ?? '' }}
                    </div>
                    <div class="cell amount">
                        {{ $parts[$row]->price ?? '' }}
                    </div>
                </div>
            @endfor
            <div class="grid item-total">
                <div class="cell label">
                    ND celkem
                </div>
                <div class="cell amount">
                    {{ $partsTotal ?? '' }}
                </div>
            </div>
        </section>

        <section class="grid labour section" aria-label="Práce"
                 style="--labour-row-count: {{ max(4, count($assemblyActions ?? [])) }}">
            <div class="grid labour-row item-row item-heading">
                <div class="cell">
                    Kód opravy
                </div>
                <div class="cell">
                    Popis práce
                </div>
                <div class="cell">
                    Cena
                </div>
            </div>
            @for ($row = 0; $row < max(4, count($assemblyActions ?? [])); $row++)
                <div class="grid labour-row item-row">
                    <div class="cell">
                        {{ $assemblyActions[$row]->code ?? '' }}
                    </div>
                    <div class="cell">
                        {{ $assemblyActions[$row]->name ?? '' }}
                    </div>
                    <div class="cell amount">
                        {{ $assemblyActions[$row]->price ?? '' }}
                    </div>
                </div>
            @endfor
            <div class="grid item-total">
                <div class="cell label">
                    Práce celkem
                </div>
                <div class="cell amount">
                    {{ $assemblyActionsTotal ?? '' }}
                </div>
            </div>
        </section>

        <section class="grid transport section" aria-label="Jízdné">
            <div class="grid transport-route">
                <strong class="cell">Jízdné:</strong>
                <div class="cell field"><span>odkud:</span><span>{{ $work->transport_from ?? '' }}</span></div>
                <div class="cell field"><span>kam:</span><span>{{ $work->transport_to ?? '' }}</span></div>
            </div>
            <div class="grid transport-cost center">
                <div class="cell">sazba Kč/km</div>
                <div class="cell">{{ $work->transport_rate_in_czk ?? '' }}</div>
                <div class="cell total-label">Jízdné celkem</div>
            </div>
            <div class="grid transport-cost">
                <div class="cell center">ujeté km</div>
                <div class="cell center">{{ $work->transport_distance_in_km ?? '' }}</div>
                <div class="cell total-label amount">{{ $totals['transport'] ?? '' }}</div>
            </div>
        </section>

        <section class="grid notes section" aria-label="Poznámka a celková cena">
            <div class="grid text-section">
                <div class="cell">Poznámka (zákazníka nebo mechanika)</div>
                <div class="value">{{ $work->notes ?? '' }}</div>
            </div>
            <div class="grid totals">
                <div class="cell center">Součet</div>
                <div class="cell amount">{{ $totals['subtotal'] ?? '' }}</div>
                <div class="cell center">DPH</div>
                <div class="cell amount">{{ $totals['vat'] ?? '' }}</div>
                <strong class="cell center">Celkem</strong><strong
                        class="cell amount">{{ $totals['total'] ?? '' }}</strong>
            </div>
        </section>

        <footer class="grid signatures">
            <div class="grid signature">
                <div class="cell mechanic">{{ $work->mechanic_name ?? '' }}</div>
                <div class="cell center">Jméno mechanika:</div>
            </div>
            <div class="grid signature">
                <div class="cell stamp">
                    <strong>SERVIS – ZÁVACKÝ</strong>
                    <div>IČ: 15499685</div>
                    <div>TEL: 604897774</div>
                    <div>bojleryzavacky@seznam.cz</div>
                </div>
                <div class="cell center">datum, razítko, podpis</div>
            </div>
            <div class="grid signature">
                <div class="cell consent">Souhlasím s vyplněním zápisu a vykonanou prací.</div>
                <div class="cell">podpis zákazníka:</div>
            </div>
        </footer>
    </div>
</main>
</body>
</html>
