<?php
/**
 * رادار Magnet PSAR - النسخة الاحترافية الكاملة
 * تنسيق متوافق مع جميع الأجهزة بدون تمرير
 */

error_reporting(0);

$intervals = [
    "15M" => "15m",
    "30M" => "30m",
    "1H"  => "1h",
    "4H"  => "4h"
];

// --- إعدادات الاستراتيجية ---
$MIN_PROFIT_PERCENT = 1.0; // الربح الأدنى (يمكنك رفعه لـ 5.0 كما طلبت)

function get_mexc_data($symbol, $interval) {
    $symbol = strtoupper(trim($symbol));
    $symbol = str_replace(['/', '-', ' '], '', $symbol);
    if (!str_ends_with($symbol, 'USDT')) $symbol .= 'USDT';

    $url = "https://api.mexc.com/api/v3/klines?symbol=$symbol&interval=$interval&limit=60";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $headers = ["User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)"];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) return json_decode($response, true);
    return null;
}

function calculate_psar($klines, $af_start = 0.02, $af_max = 0.20) {
    if (!$klines || count($klines) < 20) return null;
    $ohlc = [];
    foreach ($klines as $k) {
        $ohlc[] = ['h' => (float)$k[2], 'l' => (float)$k[3], 'c' => (float)$k[4]];
    }
    $is_long = $ohlc[1]['c'] > $ohlc[0]['c'];
    $sar = $is_long ? $ohlc[0]['l'] : $ohlc[0]['h'];
    $ep = $is_long ? $ohlc[1]['h'] : $ohlc[1]['l'];
    $af = $af_start;
    for ($i = 2; $i < count($ohlc); $i++) {
        $prev_sar = $sar;
        $sar = $prev_sar + $af * ($ep - $prev_sar);
        if ($is_long) {
            $sar = min($sar, $ohlc[$i-1]['l'], $ohlc[$i-2]['l']);
            if ($ohlc[$i]['l'] < $sar) {
                $is_long = false; $sar = $ep; $ep = $ohlc[$i]['l']; $af = $af_start;
            } else {
                if ($ohlc[$i]['h'] > $ep) { $ep = $ohlc[$i]['h']; $af = min($af + $af_start, $af_max); }
            }
        } else {
            $sar = max($sar, $ohlc[$i-1]['h'], $ohlc[$i-2]['h']);
            if ($ohlc[$i]['h'] > $sar) {
                $is_long = true; $sar = $ep; $ep = $ohlc[$i]['h']; $af = $af_start;
            } else {
                if ($ohlc[$i]['l'] < $ep) { $ep = $ohlc[$i]['l']; $af = min($af + $af_start, $af_max); }
            }
        }
    }
    return ['sar' => $sar, 'is_long' => $is_long];
}

$results = [];
$symbol = isset($_POST['symbol']) ? strtoupper(trim($_POST['symbol'])) : '';

if ($symbol) {
    foreach ($intervals as $label => $code) {
        $data = get_mexc_data($symbol, $code);
        if ($data) {
            $current_kline = end($data);
            $prev_kline = $data[count($data)-2];

            $entry_price = (float)$current_kline[4];
            $high_now = (float)$current_kline[2];
            $low_now = (float)$current_kline[3];

            $psar_res = calculate_psar(array_slice($data, 0, -1));

            if ($psar_res) {
                $target_tp = $psar_res['sar'];
                $is_long_trend = $psar_res['is_long'];
                $profit_pct = abs($target_tp - $entry_price) / $entry_price * 100;

                if ($profit_pct < $MIN_PROFIT_PERCENT) continue;

                if (!$is_long_trend && $entry_price < $target_tp && $high_now < $target_tp) {
                    $signal = "🟢 شراء (LONG)";
                    $sl_price = (float)$prev_kline[3];
                    $color = "text-success fw-bold";
                } elseif ($is_long_trend && $entry_price > $target_tp && $low_now > $target_tp) {
                    $signal = "🔴 بيع (SHORT)";
                    $sl_price = (float)$prev_kline[2];
                    $color = "text-danger fw-bold";
                } else { continue; }

                $results[] = [
                    'tf' => $label,
                    'entry' => $entry_price,
                    'tp' => round($target_tp, 6),
                    'sl' => round($sl_price, 6),
                    'profit' => number_format($profit_pct, 2) . "%",
                    'signal' => $signal,
                    'color' => $color
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>محلل التداول الاحترافي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #0b0e11; color: #ffffff; font-family: 'Cairo', sans-serif; }
        .card { background-color: #1e2329; border: none; border-radius: 12px; }
        .form-control { background: #2b3139; border: 1px solid #474d57; color: white; }
        .btn-warning { background-color: #f0b90b; font-weight: bold; border: none; }

        /* تنسيق الجدول بدون شريط تمرير */
        .table { color: #ffffff; border-color: #333; width: 100% !important; table-layout: fixed; }
        .table th, .table td { padding: 8px 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .tp-cell { color: #f0b90b; font-weight: bold; }
        .sl-cell { color: #ff6666; }
        .badge-tf { background: #474d57; padding: 2px 6px; border-radius: 4px; font-size: 0.85rem; }

        /* نصوص بيضاء صريحة */
        .text-white-fixed { color: #ffffff !important; opacity: 1; }

        @media (max-width: 600px) {
            .table { font-size: 0.7rem; } /* تصغير الخط للجوال ليناسب الشاشة */
            .badge-tf { font-size: 0.65rem; padding: 1px 4px; }
            .container { padding: 10px; }
        }
    </style>
</head>
<body class="p-3">

<div class="container" style="max-width: 950px;">
    <div class="text-center mb-4">
        <h2 style="color: #f0b90b;">رادار التداول الاحترافي 🎯</h2>
        <p class="text-white-fixed small">نظام الدخول الدقيق - أهداف PSAR المغناطيسية</p>
    </div>

    <div class="card p-4 mb-4 shadow">
        <form method="POST" class="row g-2">
            <div class="col-8 col-md-9">
                <input type="text" name="symbol" class="form-control form-control-lg text-uppercase" placeholder="أدخل العملة (مثلاً BTC)" value="<?= htmlspecialchars($symbol) ?>" required>
            </div>
            <div class="col-4 col-md-3">
                <button type="submit" class="btn btn-warning btn-lg w-100 h-100">تحليل</button>
            </div>
        </form>
    </div>

    <?php if ($symbol && !empty($results)): ?>
        <div class="card p-2 p-md-3 shadow">
            <h5 class="mb-3 px-2 text-white-fixed">الفرص المتاحة لعملة: <span class="text-warning"><?= $symbol ?></span></h5>
            <!-- أزلنا الفئة table-responsive لمنع التمرير -->
            <table class="table table-hover align-middle text-center">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 12%;">الفريم</th>
                        <th style="width: 18%;">دخول</th>
                        <th style="width: 18%;">هدف</th>
                        <th style="width: 18%;">وقف</th>
                        <th style="width: 14%;">ربح</th>
                        <th style="width: 20%;">إشارة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $res): ?>
                    <tr>
                        <td><span class="badge-tf"><?= $res['tf'] ?></span></td>
                        <td><?= $res['entry'] ?></td>
                        <td class="tp-cell"><?= $res['tp'] ?></td>
                        <td class="sl-cell"><?= $res['sl'] ?></td>
                        <td class="text-info fw-bold"><?= $res['profit'] ?></td>
                        <td class="<?= $res['color'] ?>"><?= $res['signal'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($symbol): ?>
        <div class="alert alert-dark text-center shadow text-white">
            لا توجد فرص حالية لعملة (<?= htmlspecialchars($symbol) ?>) تحقق شرط الربح الأدنى.
        </div>
    <?php endif; ?>

    <div class="mt-4 p-3 card bg-dark text-white-fixed small shadow-sm">
        <strong>💡 ملاحظة حول وقف الخسارة (SL):</strong>
        <p class="mb-0 mt-1">يتم تحديده تلقائياً بناءً على "قمة أو قاع" الشمعة السابقة لتوفير حماية منطقية للصفقة.</p>
    </div>
</div>

</body>
</html>