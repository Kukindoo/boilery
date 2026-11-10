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
            grid-template-rows: 40mm 26mm 20mm 32mm 44mm 34mm 20mm 20mm 26mm;
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

        .protocol-header {
            grid-template-columns: 35% 30% 35%;
            grid-template-rows: 20mm repeat(3, minmax(0, 1fr));
            grid-template-areas:
                "order brand company"
                "type claim company"
                "number purchase warranty"
                "serial purchase building";
        }

        .protocol-header .order-cell { grid-area: order; }
        .protocol-header .brand-cell { grid-area: brand; }
        .protocol-header .company-cell { grid-area: company; }
        .product-type-cell { grid-area: type; }
        .product-number-cell { grid-area: number; }
        .product-serial-cell { grid-area: serial; }
        .product-claim-cell { grid-area: claim; }
        .product-purchase-cell { grid-area: purchase; }
        .product-warranty-cell { grid-area: warranty; }
        .product-building-cell { grid-area: building; }

        .protocol-header .order-cell,
        .protocol-header .brand-cell,
        .protocol-header .company-cell {
            border-bottom: .4mm solid #000;
        }

        .order-cell {
            grid-template-rows: auto 1fr;
        }

        .order-cell strong {
            font-size: 12pt;
        }

        .order-number {
            align-self: center;
            font-size: 15pt;
        }

        .brand-cell {
            display: grid;
            place-items: center;
            padding: 3mm;
        }

        .brand-cell img {
            width: 100%;
            max-height: 13mm;
            object-fit: contain;
            filter: grayscale(1);
        }

        .company-cell {
            text-align: center;
            font-size: 8pt;
        }

        .company-cell h2 {
            margin: 0 0 2mm;
            font-size: 12pt;
        }

        .company-cell strong {
            display: block;
            font-size: 10pt;
        }

        .field {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            align-items: start;
            gap: 1mm;
        }

        .customer-section {
            grid-template-columns: 65% 35%;
        }

        .customer-details-column {
            grid-template-rows: 1fr 2fr 1fr;
        }

        .work-dates-column {
            grid-template-rows: 1fr 1fr;
        }

        .text-section {
            grid-template-rows: auto minmax(0, 1fr);
        }

        .text-section .value {
            padding: 0 1mm 1mm;
        }

        .parts-section {
            grid-template-rows: 6mm repeat(5, minmax(0, 1fr)) 6mm;
        }

        .parts-row {
            grid-template-columns: 16% 58% 10% 16%;
        }

        .labour-section {
            grid-template-rows: 6mm repeat(4, minmax(0, 1fr)) 6mm;
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

        :is(.parts-total-row, .labour-total-row) {
            grid-template-columns: 84% 16%;
        }

        /* Draw shared dividers on the same side of each column boundary. */
        :is(.parts-total-row, .labour-total-row) .amount,
        .transport-section > :nth-child(n + 2),
        .notes-section > :nth-child(n + 2),
        .signatures-section > :nth-child(n + 2) {
            border-left: .4mm solid #000;
        }

        :is(.parts-total-row, .labour-total-row) .item-total-label {
            text-align: right;
            padding-right: 4mm;
        }

        .amount {
            text-align: right;
        }

        .transport-section {
            grid-template-columns: 64% 20% 16%;
        }

        .transport-route-column {
            grid-template-rows: repeat(3, 1fr);
        }

        .transport-cost-column {
            grid-template-rows: 1fr 1fr 1fr;
        }

        .transport-total-cell {
            border-top: .4mm solid #000;
            text-align: center;
        }

        .notes-section {
            grid-template-columns: 64% 36%;
        }

        .price-totals-column {
            /* Continue the 84% price divider through the nested 36% column.
               Account for this grid's left border before splitting its content. */
            grid-template-columns: calc((100% + .4mm) * 5 / 9 - .4mm) minmax(0, 1fr);
            grid-template-rows: repeat(3, 1fr);
        }

        .price-totals-column .cell {
            border-bottom: .4mm solid #000;
        }

        .price-totals-column .cell:nth-last-child(-n + 2) {
            border-bottom: 0;
        }

        .price-totals-column .amount {
            border-left: .4mm solid #000;
        }

        .signatures-section {
            grid-template-columns: 28% 36% 36%;
        }

        .signature-column {
            grid-template-rows: minmax(0, 1fr) auto;
        }

        .mechanic-name-cell {
            align-self: center;
            text-align: center;
            font-size: 13pt;
        }

        .company-stamp-cell {
            align-self: start;
            text-align: center;
            font-size: 8pt;
        }

        .company-stamp-cell strong {
            display: block;
            font-size: 10pt;
        }

        .customer-consent-cell {
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
        <header class="grid protocol-header section">
            <div class="grid order-cell cell divide">
                <strong>MONTÁŽNÍ LIST Č.</strong>
                <div class="order-number center">{{ $work->order_id ?? '' }}</div>
            </div>
            <div class="brand-cell divide">
                <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('images/drazice-logo.jpg'))) }}"
                     alt="DRAŽICE">
            </div>
            <div class="company-cell cell">
                <h2>SERVISNÍ FIRMA</h2>
                <strong>SERVIS – ZÁVACKÝ</strong>
                <div>IČ: 15499685</div>
                <div>TEL: 604897774</div>
                <div>bojleryzavacky@seznam.cz</div>
            </div>

            <div class="cell field product-type-cell divide">
                <span>Typ výrobku:</span><span>{{ $work->type_of_product ?? '' }}</span>
            </div>
            <div class="cell field product-number-cell divide">
                <span>Typové číslo:</span><span>{{ $work->type_number ?? '' }}</span>
            </div>
            <div class="cell field product-serial-cell divide">
                <span>Výr. číslo:</span><span>{{ $work->serial_number ?? '' }}</span>
            </div>
            <div class="cell product-claim-cell divide">
                Reklamace: {{ isset($work) ? ($work->warranty_accepted ? 'uznaná' : 'neuznaná') : 'uznaná / neuznaná' }}
            </div>
            <div class="cell product-purchase-cell divide">
                Datum zakoupení ohřívače:<br>{{ isset($work) ? $work->day_of_purchase?->format('d.m.Y') : '' }}
            </div>
            <div class="cell product-warranty-cell">
                Záruční oprava: {{ $work->repair_in_warranty ? 'ano': 'ne' }}
            </div>
            <div class="cell product-building-cell">
                Výstavba: {{ $work->building_type ?? 'neznámá' }}
            </div>
        </header>

        <section class="grid customer-section section" aria-label="Údaje o zákazníkovi">
            <div class="grid customer-details-column divide">
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
            <div class="grid work-dates-column">
                <div class="cell">Zakázka přijata:<br>
                    {{ isset($work) ? $work->work_accepted_at?->format('d.m.Y') : '' }}</div>
                <div class="cell">Zakázka vyřízena:<br>
                    {{ isset($work) ? $work->work_completed_at?->format('d.m.Y') : '' }}</div>
            </div>
        </section>

        <section class="grid reported-problem-section text-section section" aria-label="Nahlášená závada">
            <div class="cell">Nahlášená závada:</div>
            <div class="value">{{ $work->reported_problem ?? '' }}</div>
        </section>

        <section class="grid performed-work-section text-section section" aria-label="Provedená práce">
            <div class="cell">Popis provedené práce, odstranění závady</div>
            <div class="value">{{ $work->work_performed_description ?? '' }}</div>
        </section>

        <section class="grid parts-section section" aria-label="Náhradní díly">
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
            @for ($row = 0; $row < 5; $row++)
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
            <div class="grid parts-total-row">
                <div class="cell item-total-label">
                    ND celkem
                </div>
                <div class="cell amount">
                    {{ $partsTotal ?? '' }}
                </div>
            </div>
        </section>

        <section class="grid labour-section section" aria-label="Práce">
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
            @for ($row = 0; $row < 4; $row++)
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
            <div class="grid labour-total-row">
                <div class="cell item-total-label">
                    Práce celkem
                </div>
                <div class="cell amount">
                    {{ $assemblyActionsTotal ?? '' }}
                </div>
            </div>
        </section>

        <section class="grid transport-section section" aria-label="Jízdné">
            <div class="grid transport-route-column">
                <strong class="cell">Jízdné:</strong>
                <div class="cell field"><span>odkud:</span><span>{{ $work->transport_from ?? '' }}</span></div>
                <div class="cell field"><span>kam:</span><span>{{ $work->transport_to ?? '' }}</span></div>
            </div>
            <div class="grid transport-cost-column center">
                <div class="cell">sazba Kč/km</div>
                <div class="cell">{{ $work->transport_rate_in_czk ?? '' }}</div>
                <div class="cell transport-total-cell">Jízdné celkem</div>
            </div>
            <div class="grid transport-cost-column">
                <div class="cell center">ujeté km</div>
                <div class="cell center">{{ $work->transport_distance_in_km ?? '' }}</div>
                <div class="cell transport-total-cell amount">{{ $totals['transport'] ?? '' }}</div>
            </div>
        </section>

        <section class="grid notes-section section" aria-label="Poznámka a celková cena">
            <div class="grid text-section">
                <div class="cell">Poznámka (zákazníka nebo mechanika)</div>
                <div class="value">{{ $work->notes ?? '' }}</div>
            </div>
            <div class="grid price-totals-column">
                <div class="cell center">Součet</div>
                <div class="cell amount">{{ $totals['subtotal'] ?? '' }}</div>
                <div class="cell center">DPH</div>
                <div class="cell amount">{{ $totals['vat'] ?? '' }}</div>
                <strong class="cell center">Celkem</strong><strong
                        class="cell amount">{{ $totals['total'] ?? '' }}</strong>
            </div>
        </section>

        <footer class="grid signatures-section">
            <div class="grid signature-column">
                <div class="cell mechanic-name-cell">{{ $work->mechanic_name ?? '' }}</div>
                <div class="cell center">Jméno mechanika:</div>
            </div>
            <div class="grid signature-column">
                <div class="cell company-stamp-cell">
                    <strong>SERVIS – ZÁVACKÝ</strong>
                    <div>IČ: 15499685</div>
                    <div>TEL: 604897774</div>
                    <div>bojleryzavacky@seznam.cz</div>
                </div>
                <div class="cell center">datum, razítko, podpis</div>
            </div>
            <div class="grid signature-column">
                <div class="cell customer-consent-cell">Souhlasím s vyplněním zápisu a vykonanou prací.</div>
                <div class="cell">podpis zákazníka:</div>
            </div>
        </footer>
    </div>
</main>
</body>
</html>
