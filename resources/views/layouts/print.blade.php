<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Print')</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #0f172a;
            background: #f8fafc;
        }
        .print-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 24px;
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
        }
        .print-toolbar-actions { display: flex; gap: 8px; }
        .print-btn, .print-btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .print-btn {
            border: none;
            background: #4f46e5;
            color: #fff;
        }
        .print-btn-secondary {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
        }
        .print-page {
            max-width: 800px;
            margin: 24px auto;
            padding: 40px;
            background: #fff;
            box-shadow: 0 1px 3px rgb(15 23 42 / 0.08);
        }
        .print-header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 2px solid #0f172a;
        }
        .print-org { font-size: 20px; font-weight: 700; margin: 0 0 4px; }
        .print-doc-title {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .print-meta { text-align: right; font-size: 13px; color: #475569; }
        .print-meta strong { display: block; color: #0f172a; font-size: 15px; }
        .print-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 28px;
        }
        .print-section-title {
            margin: 0 0 8px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
        }
        .print-section-body { margin: 0; font-size: 14px; color: #0f172a; }
        .print-section-body p { margin: 0 0 4px; }
        .print-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .print-table th {
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
        }
        .print-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }
        .print-table .text-right { text-align: right; }
        .print-total {
            margin-left: auto;
            width: 280px;
        }
        .print-total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }
        .print-total-row.grand {
            margin-top: 8px;
            padding-top: 12px;
            border-top: 2px solid #0f172a;
            font-size: 18px;
            font-weight: 700;
        }
        .print-footer {
            margin-top: 40px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #64748b;
            text-align: center;
        }
        @media print {
            body { background: #fff; }
            .print-toolbar { display: none !important; }
            .print-page {
                margin: 0;
                padding: 0;
                box-shadow: none;
                max-width: none;
            }
        }
    </style>
    @stack('head')
</head>
<body>
    <div class="print-toolbar">
        <span style="font-size: 14px; color: #64748b;">@yield('toolbar-label', 'Document preview')</span>
        <div class="print-toolbar-actions">
            <button type="button" class="print-btn" onclick="window.print()">Print</button>
            <button type="button" class="print-btn-secondary" onclick="window.close()">Close</button>
        </div>
    </div>

    <div class="print-page">
        @yield('content')
    </div>

    @if(!empty($autoPrint))
        <script>window.addEventListener('load', function () { window.print(); });</script>
    @endif
</body>
</html>
