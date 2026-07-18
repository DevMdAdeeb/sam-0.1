<?php
/**
 * منصة رادار الكريبتو الاحترافية - النسخة الشاملة
 * البحث اليدوي + حالة BTC + رادار السيولة (Top 10)
 */

error_reporting(0);
ini_set('max_execution_time', 60); // زيادة وقت التنفيذ لجلب بيانات متعددة

// إعدادات
$intervals = ["15M" => "15m", "30M" => "30m", "1H" => "1h", "4H" => "4h"];
$MIN_PROFIT_PERCENT = 0.5;

// --- دالة جلب البيانات من MEXC ---
function get_mexc_json($endpoint, $params = []) {
    $url = "https://api.mexc.com/api/v3/" . $endpoint . "?" . http_build_query($params);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: Mozilla/5.0"]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// --- دالة حساب PSAR ---
function analyze_psar_logic($klines) {
    if (!$klines || count($klines) < 20) return null;
    $ohlc = [];
    foreach ($klines as $k) {
        $ohlc[] = ['h' => (float)$k[2], 'l' => (float)$k[3], 'c' => (float)$k[4]];
    }

    // خوارزمية PSAR
    $is_long = $ohlc[1]['c'] > $ohlc[0]['c'];
    $sar = $is_long ? $ohlc[0]['l'] : $ohlc[0]['h'];
    $ep = $is_long ? $ohlc[1]['h'] : $ohlc[1]['l'];
    $af = 0.02; $af_max = 0.20;

    for ($i = 2; $i < count($ohlc); $i++) {
        $prev_sar = $sar;
        $sar = $prev_sar + $af * ($ep - $prev_sar);
        if ($is_long) {
            $sar = min($sar, $ohlc[$i-1]['l'], $ohlc[$i-2]['l']);
            if ($ohlc[$i]['l'] < $sar) {
                $is_long = false; $sar = $ep; $ep = $ohlc[$i]['l']; $af = 0.02;
            } else {
                if ($ohlc[$i]['h'] > $ep) { $ep = $ohlc[$i]['h']; $af = min($af + 0.02, $af_max); }
            }
        } else {
            $sar = max($sar, $ohlc[$i-1]['h'], $ohlc[$i-2]['h']);
            if ($ohlc[$i]['h'] > $sar) {
                $is_long = true; $sar = $ep; $ep = $ohlc[$i]['h']; $af = 0.02;
            } else {
                if ($ohlc[$i]['l'] < $ep) { $ep = $ohlc[$i]['l']; $af = min($af + 0.02, $af_max); }
            }
        }
    }
    return ['sar' => $sar, 'is_long' => $is_long];
}

// --- 1. جلب حالة البيتكوين ---
$btc_ticker = get_mexc_json("ticker/24hr", ["symbol" => "BTCUSDT"]);

// --- 2. جلب أعلى 10 عملات سيولة وتحليلها ---
$all_tickers = get_mexc_json("ticker/24hr");
usort($all_tickers, function($a, $b) { return $b['quoteVolume'] <=> $a['quoteVolume']; });

$top_list = [];
$count = 0;
foreach ($all_tickers as $t) {
    if (strpos($t['symbol'], 'USDT') !== false && !strpos($t['symbol'], 'BTC') && $count < 10) {
        // تحليل كل عملة على فريم الساعة
        $klines = get_mexc_json("klines", ["symbol" => $t['symbol'], "interval" => "1h", "limit" => "50"]);
        if ($klines) {
            $psar = analyze_psar_logic(array_slice($klines, 0, -1));
            $current = (float)end($klines)[4];
            if ($psar) {
                $profit = abs($psar['sar'] - $current) / $current * 100;
                $signal = "محايد"; $color = "text-white";
                if (!$psar['is_long'] && $current < $psar['sar']) { $signal = "🟢 شراء"; $color = "text-success"; }
                elseif ($psar['is_long'] && $current > $psar['sar']) { $signal = "🔴 بيع"; $color = "text-danger"; }

                $top_list[] = [
                    'symbol' => str_replace('USDT', '', $t['symbol']),
                    'price' => $current,
                    'profit' => number_format($profit, 2) . "%",
                    'signal' => $signal,
                    'color' => $color
                ];
                $count++;
            }
        }
    }
}

// --- 3. تحليل العملة المبحوث عنها يدوياً ---
$results = [];
$symbol = isset($_POST['symbol']) ? strtoupper(trim($_POST['symbol'])) : '';
if ($symbol) {
    $clean_sym = str_replace(['/', '-', ' '], '', $symbol);
    if (!str_ends_with($clean_sym, 'USDT')) $clean_sym .= 'USDT';
    foreach ($intervals as $label => $code) {
        $data = get_mexc_json("klines", ["symbol" => $clean_sym, "interval" => $code, "limit" => 50]);
        if ($data && !isset($data['msg'])) {
            $psar_res = analyze_psar_logic(array_slice($data, 0, -1));
            if ($psar_res) {
                $curr = (float)end($data)[4];
                $prof = abs($psar_res['sar'] - $curr) / $curr * 100;
                if ($prof >= $MIN_PROFIT_PERCENT) {
                    $sig = "انتظار"; $cl = "text-white";
                    if (!$psar_res['is_long'] && $curr < $psar_res['sar']) { $sig = "🟢 شراء"; $cl = "text-success fw-bold"; }
                    elseif ($psar_res['is_long'] && $curr > $psar_res['sar']) { $sig = "🔴 بيع"; $cl = "text-danger fw-bold"; }

                    $results[] = [
                        'tf' => $label, 'entry' => $curr, 'tp' => round($psar_res['sar'], 6),
                        'sl' => round(($sig=="🟢 شراء" ? $data[count($data)-2][3] : $data[count($data)-2][2]), 6),
                        'profit' => number_format($prof, 2) . "%", 'signal' => $sig, 'color' => $cl
                    ];
                }
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
    <title>رادار الكريبتو الاحترافي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #0b0e11; color: #ffffff; font-family: 'Cairo', sans-serif; }
        .card { background-color: #1e2329; border: none; border-radius: 12px; }
        .text-white-fixed { color: #ffffff !important; }
        .table { color: #ffffff; border-color: #333; table-layout: fixed; font-size: 0.85rem; }
        .table th, .table td { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding: 10px 5px; }
        .btc-bar { background: linear-gradient(90deg, #1e2329 0%, #2b3139 100%); border-left: 4px solid #f0b90b; }
        @media (max-width: 600px) { .table { font-size: 0.7rem; } }
    </style>
</head>
<body class="p-2 p-md-4">

<div class="container" style="max-width: 1000px;">

    <!-- قسم البحث -->
    <div class="card p-4 mb-3 shadow">
        <h4 class="text-center text-warning mb-3">🔍 رادار التحليل الذكي</h4>
        <form method="POST" class="row g-2">
            <div class="col-8 col-md-9">
                <input type="text" name="symbol" class="form-control form-control-lg bg-dark text-white border-secondary"
                       placeholder="مثال: SOL, XRP, ETH..." value="<?= htmlspecialchars($symbol) ?>">
            </div>
            <div class="col-4 col-md-3">
                <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold">تحليل</button>
            </div>
        </form>
    </div>

    <!-- شريط البيتكوين -->
    <div class="card p-3 mb-3 btc-bar shadow-sm">
        <div class="row align-items-center text-center text-md-start">
            <div class="col-md-3">
                <h6 class="mb-0 text-white-fixed">🪙 BITCOIN (BTC)</h6>
            </div>
            <div class="col-md-3">
                <span class="fs-5 fw-bold text-warning"><?= number_format($btc_ticker['lastPrice'], 2) ?> $</span>
            </div>
            <div class="col-md-3">
                <span class="<?= $btc_ticker['priceChangePercent'] >= 0 ? 'text-success' : 'text-danger' ?> fw-bold">
                    <?= $btc_ticker['priceChangePercent'] ?>% (24h)
                </span>
            </div>
            <div class="col-md-3 d-none d-md-block text-muted small">
                H: <?= round($btc_ticker['highPrice'], 2) ?> | L: <?= round($btc_ticker['lowPrice'], 2) ?>
            </div>
        </div>
    </div>

    <!-- نتائج البحث اليدوي -->
    <?php if ($symbol && !empty($results)): ?>
    <div class="card p-3 mb-3 border-warning border-1 shadow">
        <h6 class="text-white-fixed mb-3">📊 الفرص المتاحة لعملة: <span class="text-warning"><?= $symbol ?></span></h6>
        <table class="table text-center align-middle">
            <thead class="table-dark">
                <tr>
                    <th>الفريم</th>
                    <th>الدخول</th>
                    <th>الهدف</th>
                    <th>الوقف</th>
                    <th>الربح</th>
                    <th>الإشارة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $res): ?>
                <tr>
                    <td><?= $res['tf'] ?></td>
                    <td><?= $res['entry'] ?></td>
                    <td class="text-warning"><?= $res['tp'] ?></td>
                    <td class="text-danger small"><?= $res['sl'] ?></td>
                    <td class="text-info"><?= $res['profit'] ?></td>
                    <td class="<?= $res['color'] ?>"><?= $res['signal'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- رادار السيولة (Top 10) -->
    <div class="card p-3 shadow-lg">
        <h6 class="text-white-fixed mb-3">🔥 رادار أعلى 10 عملات سيولة (فريم 1H)</h6>
        <table class="table text-center align-middle">
            <thead class="table-dark">
                <tr>
                    <th>العملة</th>
                    <th>السعر</th>
                    <th>المسافة للهدف</th>
                    <th>الحالة الحالية</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_list as $coin): ?>
                <tr>
                    <td class="fw-bold"><?= $coin['symbol'] ?></td>
                    <td><?= $coin['price'] ?></td>
                    <td class="text-info"><?= $coin['profit'] ?></td>
                    <td class="<?= $coin['color'] ?> fw-bold"><?= $coin['signal'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="mt-2 small text-white-fixed opacity-75 text-center">
            💡 ملاحظة حول وقف الخسارة (SL): يتم تحديده تلقائياً بناءً على "قمة أو قاع" الشمعة السابقة لتوفير حماية منطقية للصفقة.
        </div>
    </div>

</div>

</body>
</html>