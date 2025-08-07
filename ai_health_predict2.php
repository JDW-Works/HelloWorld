<?php
session_start();
require_once("lib/link.php");
require_once("common_page/head.php");
require_once("lib/allotpage.php");

// ======== 匯出 Excel（CSV）功能 ========
if (isset($_GET['export_excel']) && $_GET['export_excel'] == '1') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="pump_ai_export_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM

    // 欄位標題
    $fields = [
        "抽水機名稱", "AI預測結果", "AA", "AB", "AC", "AD", "AE", "AF", "AG", "AH", "AI", "AJ", "AK",
        "BA", "BB", "BC", "BD", "BF", "BG", "BH", "BI", "BJ", "ALL"
    ];
    fputcsv($fp = fopen('php://output', 'w'), $fields);

    // 查詢所有資料
    $result_cols = [
        "aa_result", "ab_result", "ac_result", "ad_result", "ae_result", "af_result",
        "ag_result", "ah_result", "ai_result", "aj_result", "ak_result",
        "ba_result", "bb_result", "bc_result", "bd_result",
        "bf_result", "bg_result", "bh_result", "bi_result", "bj_result",
        "all_result"
    ];

    // 查詢 o_id 1~10 的所有抽水機名稱
    $pumps = [];
    $sql = "SELECT pd_name FROM Pumpdata WHERE o_id BETWEEN 1 AND 10";
    $DB->query($sql);
    while ($row = $DB->fetchObject()) $pumps[] = $row->pd_name;

    foreach ($pumps as $idx => $pumpno) {
        $sql2 = "SELECT TOP 3 " . implode(",", $result_cols) . "
            FROM resume_general_maintenance_list
            WHERE pumpno = N'{$pumpno}'
            ORDER BY chCreateDate DESC";
        $DB->query($sql2);

        $rows = [];
        while ($row = $DB->fetchObject()) {
            $data = [$pumpno];
            // AI預測結果
            $ai_predict = '';
            if (count($rows) == 0) {
                $sample = [];
                foreach ($result_cols as $col) $sample[] = is_numeric($row->$col) ? floatval($row->$col) : 0;
                if (count($rows) == 2) {
                    $aiObj = getAiPredictResult([$sample]);
                    $ai_predict = isset($aiObj['result']) ? implode(",", $aiObj['result']) : ($aiObj['error'] ?? '');
                }
            }
            $data[] = $ai_predict;

            foreach ($result_cols as $col) $data[] = $row->$col;
            fputcsv($fp, $data);
            $rows[] = 1;
        }
    }
    fclose($fp);
    exit;
}

// ====== PHP 呼叫 Flask AI 預測函式（含花費時間）======
function getAiPredictResult($sample) {
    $url = "http://127.0.0.1:5001/predict";
    $start_time = microtime(true);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['sample' => $sample]));
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);

    $response = curl_exec($ch);
    $err      = curl_error($ch);
    $info     = curl_getinfo($ch);

    $end_time = microtime(true);
    curl_close($ch);

    $duration = round(($end_time - $start_time) * 1000); // 毫秒

    if ($err || $info['http_code'] != 200) return ['error'=>'AI API呼叫失敗', 'duration'=>$duration];
    $obj = json_decode($response, true);
    if (isset($obj['result'])) {
        $obj['duration'] = $duration;
        return $obj;
    }
    return ['error'=>'預測錯誤', 'duration'=>$duration];
}

// 1. 查詢 o_id 1~10 的所有抽水機名稱
$pumps = [];
$sql = "SELECT pd_name FROM Pumpdata WHERE o_id BETWEEN 1 AND 10";
$DB->query($sql);
while ($row = $DB->fetchObject()) $pumps[] = $row->pd_name;

// 2. 欄位
$result_cols = [
    "aa_result", "ab_result", "ac_result", "ad_result", "ae_result", "af_result",
    "ag_result", "ah_result", "ai_result", "aj_result", "ak_result",
    "ba_result", "bb_result", "bc_result", "bd_result",
    "bf_result", "bg_result", "bh_result", "bi_result", "bj_result",
    "all_result"
];

$pump_regulars = [];
$js_chart_data = [];
$ai_results = [];
foreach ($pumps as $idx => $pumpno) {
    $sql2 = "SELECT TOP 3 " . implode(",", $result_cols) . "
            FROM resume_general_maintenance_list
            WHERE pumpno = N'{$pumpno}'
            ORDER BY chCreateDate DESC";
    $DB->query($sql2);
    $rows = [];
    $ai_data = [];
    $datasets = [];
    foreach ($result_cols as $col) $datasets[$col] = [];

    while ($row = $DB->fetchObject()) {
        $item = [];
        $item_ai = [];
        foreach ($result_cols as $col) {
            $val = is_numeric($row->$col) ? floatval($row->$col) : 0;
            $item[$col] = $row->$col;
            $item_ai[] = $val;
            $datasets[$col][] = $val;
        }
        $rows[] = $item;
        $ai_data[] = $item_ai;
    }
    $pump_regulars[$pumpno] = $rows;

    // 呼叫 AI 預測（只針對有3筆資料的 pump）
    if (count($ai_data) == 3) {
        $ai_results[$idx] = getAiPredictResult($ai_data);
    } else {
        $ai_results[$idx] = ['error' => '資料不足（需3筆）', 'duration' => 0];
    }

    foreach ($datasets as $col => $arr) {
        $datasets[$col] = array_reverse(array_pad($arr, 3, 0));
    }
    $chart_datasets = [];
    $colors = [
        "#19f0d7", "#14d7f6", "#ff4179", "#ffc300", "#45e397",
        "#6a8cff", "#ecb700", "#0073e6", "#7d5fff", "#ff7f50",
        "#ffb347", "#90ee90", "#c0392b", "#7fd5e3", "#fad400",
        "#ea8685", "#75b8fa", "#f38181", "#fe346e", "#6a89cc",
        "#4a69bd"
    ];
    $cidx = 0;
    foreach ($result_cols as $col) {
        $chart_datasets[] = [
            'label' => $col,
            'data'  => $datasets[$col],
            'fill'  => false,
            'borderColor' => $colors[$cidx % count($colors)],
            'backgroundColor' => $colors[$cidx % count($colors)],
            'tension' => 0.25,
            'pointRadius' => 3,
            'pointBackgroundColor' => $colors[$cidx % count($colors)],
            'pointBorderColor' => '#fff'
        ];
        $cidx++;
    }
    $js_chart_data[] = [
        'canvas_id' => "chart_pump$idx",
        'label'     => $pumpno,
        'datasets'  => $chart_datasets,
        'labels'    => ['第3筆', '第2筆', '最新']
    ];
}

// ====== 計算全部 AI 預測總花費 ======
$ai_total_cost = 0;
foreach ($ai_results as $result) {
    if (!empty($result['duration'])) $ai_total_cost += $result['duration'];
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI 抽水機健康預測總覽</title>
<link href="https://fonts.googleapis.com/css?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php require("common/head_lib.php");?>
<style>
body {
  background: linear-gradient(120deg, #121a26 0%, #232b3e 100%);
  color: #dbeafe;
  font-family: 'Orbitron', '微軟正黑體', Arial, sans-serif;
  letter-spacing: 1px;
  min-height: 100vh;
}
.page-heading {
  padding: 36px 24px 18px 24px;
}
.page-title h3 {
  font-size: 2.1em;
  font-weight: 700;
  letter-spacing: 2px;
  color: #27e3ff;
  text-shadow: 0 0 12px #1e90ff99, 0 0 1px #000;
}
#accordion {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 28px;
}
/* --- 核心修改：展開時該卡片佔滿整列 --- */
.accordion-card.expand-row {
  grid-column: 1/-1 !important;
  z-index: 2;
  box-shadow: 0 18px 64px #27e3ff66, 0 2px 12px #0009;
  transition: grid-column .2s, box-shadow .2s, transform .22s;
}
.accordion-card:not(.expand-row) {
  z-index: 1;
}
.accordion-card {
  background: rgba(28,36,60,0.93);
  border: 2px solid #22d3ee33;
  border-radius: 18px;
  box-shadow: 0 6px 40px #27e3ff11, 0 2px 8px #0004;
  transition: transform .22s cubic-bezier(.24,1.56,.71,.49), box-shadow .22s;
  padding: 0;
  margin-bottom: 0;
}
.accordion-card:hover {
  transform: translateY(-6px) scale(1.03);
  box-shadow: 0 12px 60px #27e3ff44, 0 2px 12px #0007;
}
.accordion-header {
  padding: 26px 30px 14px 30px;
  font-size: 1.26em;
  color: #38f3ff;
  background: linear-gradient(90deg, #0b2536 85%, #114761 100%);
  font-weight: bold;
  border-radius: 18px 18px 0 0;
  cursor: pointer;
  display: flex;
  align-items: center;
  transition: background 0.16s;
  letter-spacing: 1.5px;
  box-shadow: 0 2px 12px #16c2ff08 inset;
  border-bottom: 1.5px solid #222a;
}
.accordion-header.active {
  background: linear-gradient(90deg, #14d7f6 40%, #111928 100%);
  color: #fff;
}
.ai-box {
  margin-left: auto;
  font-weight: bold;
  font-size: 1.15em;
  color: #19f0d7;
  letter-spacing: 2px;
  text-shadow: 0 0 8px #27e3ff88;
  padding-left: 14px;
  filter: brightness(1.2);
}
.accordion-body {
  padding: 18px 28px 30px 28px;
  background: rgba(12,24,32,0.95);
  border-radius: 0 0 18px 18px;
  border-top: 1.5px solid #22d3ee66;
  box-shadow: 0 2px 16px #27e3ff09;
  margin-top: -2px;
  display: none;
}
.table-responsive { margin-top: 9px; }
table {
  width: 100%;
  border-collapse: collapse;
  background: rgba(20,30,44,0.95);
  font-size: 0.98em;
}
th, td {
  border: 1px solid #334155;
  padding: 7px 10px;
  text-align: center;
}
th {
  background: #16213e;
  color: #19f0d7;
  font-weight: 600;
  border-bottom: 2.5px solid #19f0d7cc;
}
tr:nth-child(even) { background: #15202b77; }
tr:nth-child(odd) { background: #15202bcc; }
.btn-success {
  background: linear-gradient(90deg, #14d7f6 20%, #7fffd4 100%);
  color: #0b263d !important;
  font-weight: bold;
  border-radius: 8px;
  box-shadow: 0 2px 20px #19f0d799;
  letter-spacing: 1.1px;
  border: none;
  transition: background 0.18s;
}
.btn-success:hover {
  background: linear-gradient(90deg, #7fffd4 0%, #14d7f6 100%);
  color: #1b263b !important;
}
::-webkit-scrollbar { width: 10px; background: #0a192f;}
::-webkit-scrollbar-thumb { background: #0be7e9; border-radius: 8px;}
.ai-gauge {
  display: inline-block;
  min-width: 60px;
  border-radius: 24px;
  padding: 5px 15px;
  background: linear-gradient(90deg,#09fbd3,#0be7e9 80%,#01c4fd);
  box-shadow: 0 0 12px #27e3ff66;
  color: #0a254e;
  font-size: 1.11em;
  margin-left: 5px;
  animation: blink 2s infinite;
}
@keyframes blink {
  0%, 100% { filter: brightness(1.3);}
  50% { filter: brightness(2);}
}
.ai-gauge.danger {
  background: linear-gradient(90deg,#ff4179 30%,#ff8e53 100%);
  color: #fff;
  box-shadow: 0 0 16px #ff417966;
  animation: dangerblink 1.4s infinite;
}
@keyframes dangerblink {
  0%,100{filter:brightness(1);}
  60%{filter:brightness(2);}
}
/* 讓展開時曲線圖滿版、移除左右 padding */
.accordion-card.expand-row .chart-fullwidth {
  padding-left: 0 !important;
  padding-right: 0 !important;
}
.accordion-card.expand-row .chart-fullwidth canvas {
  width: 100% !important;
  max-width: 100vw !important;
  min-width: 0 !important;
  display: block;
}

/* 標題區3D人頭樣式 */
.title-head {
  display: flex;
  align-items: center;
}
@media (max-width: 900px) {
  .title-head { flex-direction: column; align-items: flex-start; }
  #head3d-container { margin-left:0!important; margin-top:16px!important; }
}
#head3d-container {
  width:320px; height:320px; min-width:220px; min-height:220px;
  margin-left:20px;
  background:transparent !important;
  border-radius:0 !important;
  box-shadow:none !important;
  overflow:visible !important;
  position:relative;
}
</style>
</head>
<body>
<div id="app">
    <div id="sidebar" class="active">
        <div class="sidebar-wrapper active">
            <?php require_once("common_page/menu.php");?>
            <button class="sidebar-toggler btn x"><i data-feather="x"></i></button>
        </div>
    </div>
    <div id="main">
        <?php require_once("common_page/header.php");?>
        <div class="page-heading">
            <!-- 🟦 標題＋3D人頭 區塊 -->
            <div class="page-title">
              <div class="row align-items-center">
                <div class="col-12 col-md-8 order-md-1 order-last">
                  <div class="title-head">
                    <h3>
                      抽水機 一般維護狀態 <span style="color:#19f0d7;text-shadow:0 0 10px #27e3ffbb;">AI 分析總覽</span>
                    </h3>
                    <div id="head3d-container"></div>
                  </div>
                </div>
                <div class="col-12 col-md-4 order-md-2 order-first">
                  <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                      <li class="breadcrumb-item"><a href="index.php">首頁</a></li>
                      <li class="breadcrumb-item active" aria-current="page">AI預測總覽</li>
                    </ol>
                  </nav>
                </div>
              </div>
            </div>
            <div style="margin-bottom:18px;">
              <a href="?export_excel=1" class="btn btn-success" style="font-weight:bold;">匯出 Excel</a>
              <span style="margin-left:22px; color:#93f9b9; font-weight:bold; font-size:1.11em;">
                AI預測總花費：<?php echo round($ai_total_cost / 1000, 2); ?> 秒
              </span>
            </div>
            <!-- 🟦 標題＋3D人頭 區塊結束 -->

            <section class="section">
                <div id="accordion">
                    <?php foreach ($pumps as $idx => $pumpno): ?>
                        <div class="accordion-card">
                            <div class="accordion-header">
                                <span><strong><?php echo htmlspecialchars($pumpno); ?></strong></span>
                                <div class="ai-box" id="ai_result_<?php echo $idx; ?>">
                                <?php
                                $aiObj = $ai_results[$idx];
                                if (isset($aiObj['result'])) {
                                    echo '<span style="color:#14d7f6;font-weight:600;">AI預測：</span>';
                                    foreach ($aiObj['result'] as $r) {
                                        if (mb_strpos($r, '正常') !== false) {
                                            echo '<span class="ai-gauge">'.htmlspecialchars($r).'</span>';
                                        } else {
                                            echo '<span class="ai-gauge danger">'.htmlspecialchars($r).'</span>';
                                        }
                                    }
                                } else {
                                    echo '<span style="color:#ff4179;font-weight:600;">'.htmlspecialchars($aiObj['error'] ?? '預測錯誤').'</span>';
                                }
                                ?>
                                </div>
                            </div>
                            <div class="accordion-body">
                                <?php
                                    $data = $pump_regulars[$pumpno];
                                    if (!$data || count($data) < 3) {
                                        echo '<div style="color:gray;">資料不足（需3筆）</div>';
                                    } else {
                                        echo '<div class="table-responsive">';
                                        echo '<table class="table table-bordered table-sm"><thead><tr>';
                                        foreach ($data[0] as $col => $v) echo "<th>$col</th>";
                                        echo '</tr></thead><tbody>';
                                        foreach ($data as $row) {
                                            echo '<tr>';
                                            foreach ($row as $v) echo "<td>$v</td>";
                                            echo '</tr>';
                                        }
                                        echo '</tbody></table></div>';

                                        $canvas_id = "chart_pump$idx";
                                        echo '<div class="chart-fullwidth" style="padding:18px 8px 4px 8px;"><canvas id="'.$canvas_id.'" height="180"></canvas></div>';
                                    }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
        <?php require_once("common_page/footer.php");?>
    </div>
</div>
<?php require_once("common/footer_lib.php");?>
<script>
const chartData = <?php echo json_encode($js_chart_data); ?>;
window.chartJSObjects = {};
$('#accordion').on('click', '.accordion-header', function(){
    var $card = $(this).closest('.accordion-card');
    var $body = $card.find('.accordion-body');
    var idx = $card.index();

    if ($body.is(':visible')) {
        $body.slideUp();
        $(this).removeClass('active');
        $card.removeClass('expand-row');
    } else {
        $('.accordion-body').slideUp();
        $('.accordion-header').removeClass('active');
        $('.accordion-card').removeClass('expand-row');

        $body.slideDown(function(){
            var chartItem = chartData[idx];
            var canvasId = chartItem.canvas_id;
            var ctx = document.getElementById(canvasId);

            if (window.chartJSObjects[canvasId]) {
                window.chartJSObjects[canvasId].destroy();
                window.chartJSObjects[canvasId] = null;
            }
            ctx.width = ctx.parentElement.offsetWidth;

            window.chartJSObjects[canvasId] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartItem.labels,
                    datasets: chartItem.datasets
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: true }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, grid: { color: '#33415533' } }
                    }
                }
            });
        });
        $(this).addClass('active');
        $card.addClass('expand-row');
    }
});
</script>

<!-- Three.js + 3D人頭載入 -->
<script src="https://unpkg.com/three@0.146.0/build/three.min.js"></script>
<script src="https://unpkg.com/three@0.146.0/examples/js/loaders/GLTFLoader.js"></script>
<script>
const scene = new THREE.Scene();
scene.background = null;
const camera = new THREE.PerspectiveCamera(45, 1, 0.1, 100);
camera.position.set(0, 1.5, 3);

const renderer = new THREE.WebGLRenderer({antialias: true, alpha:true});
renderer.setSize(320,320);
document.getElementById("head3d-container").appendChild(renderer.domElement);

scene.add(new THREE.AmbientLight(0xffffff, 0.7));
const light = new THREE.DirectionalLight(0xffffff, 1.2);
light.position.set(5,10,10);
scene.add(light);

let head;
const loader = new THREE.GLTFLoader();
const MODEL_URL = 'https://raw.githubusercontent.com/mrdoob/three.js/dev/examples/models/gltf/LeePerrySmith/LeePerrySmith.glb';

loader.load(MODEL_URL, function(gltf) {
  head = gltf.scene;
  head.scale.set(0.2, 0.2, 0.2);
  head.position.y = 0.6;
  scene.add(head);
}, undefined, function (error) {
  alert("模型載入失敗：" + error.message);
});

function animate() {
  requestAnimationFrame(animate);
  renderer.render(scene, camera);
}
animate();

window.addEventListener('resize', () => {
  const size = Math.min(320, document.getElementById('head3d-container').offsetWidth, document.getElementById('head3d-container').offsetHeight);
  camera.aspect = 1;
  camera.updateProjectionMatrix();
  renderer.setSize(size, size);
});
</script>
</body>
</html>
