<?php
    session_start();
    require_once('lib/link.php');
    require_once('common_page/head.php');
    require_once('lib/allotpage.php');

    // --- AJAX 更新 nextAgency ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_nextAgency') {
        header('Content-Type: application/json');
        $u_id = isset($_POST['u_id']) ? intval($_POST['u_id']) : 0;
        $ids = isset($_POST['nextAgency']) && is_array($_POST['nextAgency']) ? array_map('intval', $_POST['nextAgency']) : [];
        $nextAgencyStr = implode(',', $ids);
        $update_sql = "UPDATE UserData SET nextAgency='$nextAgencyStr', chUpdateDate=GETDATE() WHERE u_id=$u_id";
        $DB->query($update_sql);
        echo json_encode(['status' => 'ok']);
        exit;
    }

    $pagename = '使用者與上級機關一覽';
    $title = $pagename;

    // --- 篩選條件 ---
    $filter_options = [
        ''      => '全部',
        '9'     => '鄉鎮',
        '4'     => '縣市政府',
        '3'     => '河川分署',
        '1'     => '署內'
    ];
    $filter_auth = isset($_GET['filter_auth']) ? $_GET['filter_auth'] : '';
    $search_user = isset($_GET['search_user']) ? trim($_GET['search_user']) : '';

    // --- 分頁設定 ---
    $per_page = 10;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

    // --- where 條件組合 ---
    $where_arr = [];
    if ($filter_auth !== '' && in_array($filter_auth, ['1','3','4','9'])) {
        $where_arr[] = "u.u_auth = '{$filter_auth}'";
    }
    if ($search_user !== '') {
        // 這裡 LIKE 查詢預防 SQL injection，建議用參數化，下面範例為簡單版本
        $search_safe = str_replace("'", "''", $search_user);
        $where_arr[] = "u.u_name LIKE '%{$search_safe}%'";
    }
    $where = '';
    if (count($where_arr) > 0) {
        $where = "WHERE " . implode(' AND ', $where_arr);
    }

    // --- 取得總筆數 ---
    $count_sql = "SELECT COUNT(*) AS total FROM UserData u {$where}";
    $count_rs = $DB->query($count_sql);
    $total_rows = ($row = $DB->fetchObject($count_rs)) ? intval($row->total) : 0;
    $total_pages = ceil($total_rows / $per_page);
    $offset = ($page - 1) * $per_page;

    // 取得所有使用者供下拉選項使用
    $all_users = [];
    $all_users_sql = 'SELECT u_id, u_name FROM UserData ORDER BY u_name';
    $all_users_rs = $DB->query($all_users_sql);
    while($u = $DB->fetchObject($all_users_rs)) {
        $all_users[] = ['id' => $u->u_id, 'text' => $u->u_name];
    }
    $all_users_json = json_encode($all_users);
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $title;?></title>
<?php require("common/head_lib.php");?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
                <div class="page-title">
                    <div class="row">
                        <div class="col-12 col-md-6 order-md-1 order-last">
                            <h3><?php echo $pagename;?></h3>
                        </div>
                        <div class="col-12 col-md-6 order-md-2 order-first">
                            <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">首頁</a></li>
                                    <li class="breadcrumb-item active" aria-current="page"><?php echo $pagename;?></li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
                <section class="section">
                    <div class="card">                        
                        <div class="card-header">
                            <form class="row g-2" method="get" action="">
                                <div class="col-auto">
                                    <label for="filter_auth" class="form-label">篩選機關類型：</label>
                                </div>
                                <div class="col-auto">
                                    <select name="filter_auth" id="filter_auth" class="form-select">
                                        <?php foreach($filter_options as $val => $label): ?>
                                            <option value="<?php echo $val;?>" <?php echo ($filter_auth == $val) ? 'selected' : ''; ?>>
                                                <?php echo $label;?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <label for="search_user" class="form-label">使用者名稱：</label>
                                </div>
                                <div class="col-auto">
                                    <input type="text" name="search_user" id="search_user" class="form-control"
                                        placeholder="請輸入使用者名稱"
                                        value="<?php echo htmlspecialchars($search_user); ?>">
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-primary">查詢</button>
                                </div>
                            </form>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
<?php
$sql = "
SELECT 
    u.u_name           AS user_name,
    u.nextAgency       AS next_agency_ids,
    STRING_AGG(up.u_name, ',') AS parent_names,
    u.u_id
FROM UserData u
OUTER APPLY (
    SELECT 
        up.u_name
    FROM STRING_SPLIT(u.nextAgency, ',') s
    JOIN UserData up ON up.u_id = TRY_CAST(s.value AS int)
) up
{$where}
GROUP BY u.u_id, u.u_name, u.nextAgency
ORDER BY u.u_id ASC
OFFSET {$offset} ROWS FETCH NEXT {$per_page} ROWS ONLY;
";
$result = $DB->query($sql);

echo '<table class="table table-striped">';
echo '<thead>';
echo '<tr>';
echo '<th>使用者名稱</th>';
echo '<th>上級機關u_id</th>';
echo '<th>上級機關名稱</th>';
echo '<th>操作</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

while($row = $DB->fetchObject($result)) {
    echo '<tr data-id="' . htmlspecialchars($row->u_id) . '">';
    echo '<td>' . htmlspecialchars($row->user_name ?? '') . '</td>';
    echo '<td class="ids-cell">' . htmlspecialchars($row->next_agency_ids ?? '') . '</td>';
    echo '<td class="names-cell">' . htmlspecialchars($row->parent_names ?? '') . '</td>';
    echo '<td>';
    echo '<button type="button" class="btn btn-sm btn-primary edit-row">編輯</button>';
    echo '<button type="button" class="btn btn-sm btn-success save-row d-none">儲存</button>';
    echo '<button type="button" class="btn btn-sm btn-secondary cancel-row d-none">取消</button>';
    echo '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';

// 分頁按鈕
if ($total_pages > 1) {
    echo '<nav>';
    echo '<ul class="pagination">';
    // 分頁參數組合（保留篩選條件與查詢字串）
    $param_str = '';
    $params = [];
    if ($filter_auth !== '') {
        $params[] = 'filter_auth=' . urlencode($filter_auth);
    }
    if ($search_user !== '') {
        $params[] = 'search_user=' . urlencode($search_user);
    }
    $param_str = $params ? '&' . implode('&', $params) : '';
    if ($page > 1) {
        echo '<li class="page-item"><a class="page-link" href="?page=1' . $param_str . '">&laquo; 第一頁</a></li>';
        echo '<li class="page-item"><a class="page-link" href="?page=' . ($page-1) . $param_str . '">&lt;</a></li>';
    }
    // 只顯示附近幾頁
    $start_page = max(1, $page - 2);
    $end_page = min($total_pages, $page + 2);
    for($i=$start_page;$i<=$end_page;$i++) {
        $active = ($i == $page) ? 'active' : '';
        echo '<li class="page-item ' . $active . '"><a class="page-link" href="?page=' . $i . $param_str . '">' . $i . '</a></li>';
    }
    if ($page < $total_pages) {
        echo '<li class="page-item"><a class="page-link" href="?page=' . ($page+1) . $param_str . '">&gt;</a></li>';
        echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . $param_str . '">最後一頁 &raquo;</a></li>';
    }
    echo '</ul>';
    echo '</nav>';
}
?>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <?php require_once("common_page/footer.php");?>
        </div>
    </div>
    <?php require_once("common/footer_lib.php");?>
    <script src="js/search_pump_choices_firm.js"></script>
    <script type="text/javascript" src="https://code.jquery.com/ui/1.11.0/jquery-ui.min.js"></script>
    <script type="text/javascript" src="lib/jQuery-Timepicker-Addon-master/dist/jquery-ui-timepicker-addon.js"></script>
    <script type="text/javascript" src="lib/jQuery-Timepicker-Addon-master/dist/i18n/jquery-ui-timepicker-addon-i18n.min.js"></script>
    <script type="text/javascript" src="lib/jQuery-Timepicker-Addon-master/dist/i18n/jquery-ui-timepicker-addon-zh-TW.js"></script>
    <script type="text/javascript" src="lib/jQuery-Timepicker-Addon-master/dist/jquery-ui-sliderAccess.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    var allUsers = <?php echo $all_users_json; ?>;
    var editingRow = null;

    $(function(){
        $('.edit-row').on('click', function(){
            var tr = $(this).closest('tr');
            if (editingRow && editingRow.get(0) !== tr.get(0)) {
                alert('請先完成正在編輯的行');
                return;
            }
            if (tr.hasClass('editing')) return;
            editingRow = tr;
            tr.addClass('editing');
            var ids = tr.find('.ids-cell').text().trim();
            ids = ids ? ids.split(',') : [];
            var select = $('<select multiple class="form-select next-agency-select" style="min-width:200px"></select>');
            allUsers.forEach(function(u){ select.append(new Option(u.text, u.id)); });
            tr.find('.names-cell').html(select);
            select.val(ids).trigger('change');
            select.select2();
            tr.find('.edit-row').addClass('d-none');
            tr.find('.save-row, .cancel-row').removeClass('d-none');
        });

        $('.cancel-row').on('click', function(){
            location.reload();
        });

        $('.save-row').on('click', function(){
            var tr = $(this).closest('tr');
            var u_id = tr.data('id');
            var vals = tr.find('.next-agency-select').val() || [];
            $.post('user_next_agency_list.php', {action:'update_nextAgency', u_id:u_id, nextAgency:vals}, function(res){
                location.reload();
            }, 'json');
        });
    });
    </script>
</body>
</html>
