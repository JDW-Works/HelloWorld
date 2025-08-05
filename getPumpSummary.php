<?php
header("Content-Type: application/json; charset=utf-8");
// ====== TOKEN 驗證區 ======
$allow_token = "M8fX!w72_LzR4b@S0vEkqT1aP5"; // 這裡請自行改成你自己的安全 token
$api_token = $_GET['api_token'] ?? $_POST['api_token'] ?? '';

if ($api_token !== $allow_token) {
    http_response_code(401); // Unauthorized
    echo json_encode(['result' => false, 'msg' => 'API Token 驗證失敗'], JSON_UNESCAPED_UNICODE);
    exit;
}
// ====== TOKEN 驗證結束 ======
require_once("lib/link.php");

// === 統計基本資料（與你 Word 版相同邏輯） ===

// 1. 查詢全臺抽水機等統計
$sql_stat = "SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN o_id NOT IN (1,2,3,4,5,6,7,8,9,10) THEN 1 ELSE 0 END) AS city,
    SUM(CASE WHEN pd_operationtype = 1 AND o_id NOT IN (1,2,3,4,5,6,7,8,9,10) THEN 1 ELSE 0 END) AS city_mobile,
    SUM(CASE WHEN pd_operationtype = 2 THEN 1 ELSE 0 END) AS fixed,
    SUM(CASE WHEN pd_operationtype = 3 THEN 1 ELSE 0 END) AS pre_deploy,
    SUM(CASE WHEN o_id IN (1,2,3,4,5,6,7,8,9,10) THEN 1 ELSE 0 END) AS wramobile
    FROM Pumpdata
    WHERE device_status <> 0 AND NOT (o_id = 14 AND pd_idno LIKE '%99-M%')";
$DB->query($sql_stat);
$pumpStat = $DB->fetchObject();

$total = $pumpStat->total;
$city = $pumpStat->city;
$fixed = $pumpStat->fixed;
$pre_deploy = $pumpStat->pre_deploy;
$city_mobile = $pumpStat->city_mobile;
$wramobile = $pumpStat->wramobile;

// 2. 支援彙總：以分署為單位
$sql = "SELECT
    pd.pd_sporg AS 分署,
    pd.pd_spzone AS 鄉鎮,
    pd.pd_spstatus AS 狀態,
    pd.pd_no AS 抽水機編號,
    pd.pd_sporg, pd.pd_spzone
FROM Pumpdispatch pd
JOIN Events e ON pd.ev_id = e.ev_id
WHERE e.ev_isend = 'N' AND (pd.pd_spstatus='支援' OR pd.pd_spstatus='待命')";
$DB->query($sql);

$分署彙總 = [];
$縣市彙總 = [];
while ($row = $DB->fetchObject()) {
    $分署 = trim($row->分署);
    $鄉鎮 = trim($row->鄉鎮);
    $狀態 = trim($row->狀態);

    // 分析縣市/鄉鎮
    preg_match('/^(.{2,3}縣|.{2,3}市)(.+)$/u', $鄉鎮, $matches);
    $縣市 = $matches[1] ?? '';
    $鄉鎮名稱 = $matches[2] ?? '';

    // 以分署彙總
    if (!isset($分署彙總[$分署])) $分署彙總[$分署] = ['支援' => [], '待命' => []];
    $分署彙總[$分署][$狀態][] = "{$縣市}{$鄉鎮名稱}";

    // 以縣市彙總
    if (!isset($縣市彙總[$縣市])) $縣市彙總[$縣市] = ['支援' => [], '待命' => []];
    $縣市彙總[$縣市][$狀態][$分署][] = $鄉鎮名稱;
}

// 3. 組出「以河川分署」的彙整文字
$out1 = "以河川分署進行分類\n";
$out1 .= "(一)完成全臺大型移動式抽水機{$total}台整備。[縣市政府(含調用)機組共{$city}台，其中固定機組{$fixed}台、預佈機組{$pre_deploy}台、機動機組{$city_mobile}台；本署機動機組{$wramobile}台]\n";

// 待命、支援數量
$wait_total = 0;
$support_total = 0;
foreach ($分署彙總 as $分署 => $arr) {
    $wait_total += count($arr['待命']);
    $support_total += count($arr['支援']);
}
$out1 .= "(二) 已待命 {$wait_total} 台\n";
$out1 .= "(三) 已支援 {$support_total} 台\n";

// 支援明細
$idx = 1;
foreach ($分署彙總 as $分署 => $arr) {
    if (count($arr['支援']) > 0) {
        $明細 = [];
        $stat = [];
        foreach (array_count_values($arr['支援']) as $地點 => $num) {
            $明細[] = "{$地點}{$num}台";
            // for later
            preg_match('/^(.{2,3}縣|.{2,3}市)(.+)$/u', $地點, $m);
            $city = $m[1] ?? '';
            $town = $m[2] ?? '';
            if ($city && $town) $stat[$city][$town] = $num;
        }
        $total = count($arr['支援']);
        $out1 .= "{$idx}. {$分署}支援" . implode("、", $明細) . "，共{$total}台\n";
        $idx++;
    }
}

// 4. 組出「以縣市」彙整文字
$out2 = "以縣市進行分類\n";
$out2 .= "(一) 已上車待命 {$wait_total} 台\n";
$out2 .= "(二) 已支援 {$support_total} 台\n";

$idx = 1;
foreach ($縣市彙總 as $縣市 => $arr) {
    if (count($arr['支援']) > 0) {
        $總台 = 0;
        $鄉鎮明細 = [];
        $分署明細 = [];
        foreach ($arr['支援'] as $分署 => $鄉鎮們) {
            $num = count($鄉鎮們);
            $分署明細[] = "{$分署}{$num}";
            $總台 += $num;
            foreach (array_count_values($鄉鎮們) as $鄉鎮 => $n) {
                $鄉鎮明細[] = "{$鄉鎮}{$n}";
            }
        }
        $out2 .= "{$idx}. {$縣市}{$總台}台(" . implode('、', $鄉鎮明細) . " : " . implode('、', $分署明細) . ")\n";
        $idx++;
    }
}

// 5. 組成最終回傳
$result = $out1 . $out2;

echo json_encode([
    'result' => true,
    'text' => $result
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
