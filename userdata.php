<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// SQL Server 連線資訊
$serverName = "127.0.0.1"; // 依你的實際 server 調整，預設本機
$uid = "iccldbuser";
$pwd = "JqewefqxSKHXisQ";
$database = "ICCLdb";
$connectionOptions = [
    "Database" => $database,
    "Uid" => $uid,
    "PWD" => $pwd,
    "CharacterSet" => "UTF-8"
];
$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'add') {
    $insertFileSQL = "INSERT INTO userdata (u_idno, u_name, u_passwd, u_org, u_auth, u_mail, c_name, c_tel, u_company, u_status, chCreateDate, chUpdateDate, nextAgency) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), GETDATE(), ?)";
    $fileParams = [
        $_POST['u_idno'],
        $_POST['u_name'],
        $_POST['u_passwd'],
        $_POST['u_org'],
        $_POST['u_auth'],
        $_POST['u_mail'],
        $_POST['c_name'],
        $_POST['c_tel'],
        $_POST['u_company'],
        $_POST['u_status'],
        $_POST['nextAgency']
    ];
    sqlsrv_query($conn, $insertFileSQL, $fileParams);
    header('Location: userdata.php');
    exit;
} elseif ($action === 'update') {
    $updateSQL = "UPDATE userdata SET u_idno=?, u_name=?, u_passwd=?, u_org=?, u_auth=?, u_mail=?, c_name=?, c_tel=?, u_company=?, u_status=?, chUpdateDate=GETDATE(), nextAgency=? WHERE u_id=?";
    $updateParams = [
        $_POST['u_idno'],
        $_POST['u_name'],
        $_POST['u_passwd'],
        $_POST['u_org'],
        $_POST['u_auth'],
        $_POST['u_mail'],
        $_POST['c_name'],
        $_POST['c_tel'],
        $_POST['u_company'],
        $_POST['u_status'],
        $_POST['nextAgency'],
        $_POST['u_id']
    ];
    sqlsrv_query($conn, $updateSQL, $updateParams);
    header('Location: userdata.php');
    exit;
} elseif ($action === 'delete') {
    $deleteSQL = "UPDATE userdata SET u_status='2' WHERE u_id=?";
    sqlsrv_query($conn, $deleteSQL, [$_POST['u_id']]);
    header('Location: userdata.php');
    exit;
}

// 查詢資料
$sql = "SELECT * FROM userdata";
$params = [];
if ($search !== '') {
    $sql .= " WHERE u_name LIKE ?";
    $params[] = "%$search%";
}
$stmt = sqlsrv_query($conn, $sql, $params);

$rows = [];
if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $rows[] = $row;
    }
} else {
    // 查詢錯誤
    die(print_r(sqlsrv_errors(), true));
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>使用者資料</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
<div class="container mx-auto">
    <h1 class="text-3xl font-bold mb-4">使用者資料管理</h1>
    <form method="get" class="mb-4 flex">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="搜尋名稱" class="flex-grow border border-gray-300 p-2 rounded-l">
        <button type="submit" class="bg-blue-500 text-white px-4 rounded-r">查詢</button>
    </form>
    <table class="min-w-full bg-white border border-gray-200 mb-4">
        <thead>
            <tr>
                <th class="px-2 py-1 border">ID</th>
                <th class="px-2 py-1 border">帳號</th>
                <th class="px-2 py-1 border">名稱</th>
                <th class="px-2 py-1 border">密碼</th>
                <th class="px-2 py-1 border">組織</th>
                <th class="px-2 py-1 border">權限id</th>
                <th class="px-2 py-1 border">E-mail</th>
                <th class="px-2 py-1 border">聯絡人姓名</th>
                <th class="px-2 py-1 border">聯絡人電話</th>
                <th class="px-2 py-1 border">公司名稱</th>
                <th class="px-2 py-1 border">狀態</th>
                <th class="px-2 py-1 border">創建日期</th>
                <th class="px-2 py-1 border">更新日期</th>
                <th class="px-2 py-1 border">上級機關</th>
                <th class="px-2 py-1 border">操作</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr class="text-center">
                <td class="border px-2 py-1"><?php echo htmlspecialchars($r['u_id'] ?? ''); ?></td>
                <td class="border px-2 py-1"><?php echo htmlspecialchars($r['u_idno'] ?? ''); ?></td>
                <td class="border px-2 py-1"><?php echo htmlspecialchars($r['u_name'] ?? ''); ?></td>
                <td class="border px-2 py-1"><?php echo htmlspecialchars($r['u_passwd'] ?? ''); ?></td>
                <td class="border px-2 py-1"><?php echo htmlspecialchars($r['u_org'] ?? ''); ?></td>
                <td class="border px-2 py-1"><?php echo htmlspecialchars($r['u_auth'] ?? ''); ?></td>
                <td class="border px-2 py-1"><?php echo htmlspecialchars($r['u_mail'] ?? ''); ?></td>
                <td class="border px-2 py-1"><?php echo htmlspecialchars($r['c_name'] ?? ''); ?></td>
                <td class="border px-2 py-1"><?php echo htmlspecialchars($r['c_tel'] ?? ''); ?></td>
                <td class="border px-2 py-1"><?php echo htmlspecialchars($r['u_company'] ?? ''); ?></td>
                <td class="border px-2 py-1"><?php echo htmlspecialchars($r['u_status'] ?? ''); ?></td>
                <td class="border px-2 py-1"><?php echo htmlspecialchars(isset($r['chCreateDate']) && is_object($r['chCreateDate']) ? $r['chCreateDate']->format('Y-m-d H:i:s') : ($r['chCreateDate'] ?? '')); ?></td>
                <td class="border px-2 py-1"><?php echo htmlspecialchars(isset($r['chUpdateDate']) && is_object($r['chUpdateDate']) ? $r['chUpdateDate']->format('Y-m-d H:i:s') : ($r['chUpdateDate'] ?? '')); ?></td>
                <td class="border px-2 py-1"><?php echo htmlspecialchars($r['nextAgency'] ?? ''); ?></td>
                <td class="border px-2 py-1">
                    <form method="post" class="inline">
                        <input type="hidden" name="u_id" value="<?php echo $r['u_id']; ?>">
                        <button type="submit" name="action" value="delete" class="text-red-600">刪除</button>
                    </form>
                </td>
            </tr>

        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="bg-white p-4 rounded shadow">
        <h2 class="text-xl font-bold mb-2">新增 / 修改 使用者</h2>
        <form method="post" class="grid grid-cols-2 gap-4">
            <input type="hidden" name="u_id" value="">
            <div>
                <label class="block">帳號</label>
                <input type="text" name="u_idno" class="w-full border p-2" required>
            </div>
            <div>
                <label class="block">名稱</label>
                <input type="text" name="u_name" class="w-full border p-2" required>
            </div>
            <div>
                <label class="block">密碼</label>
                <input type="password" name="u_passwd" class="w-full border p-2" required>
            </div>
            <div>
                <label class="block">組織</label>
                <input type="text" name="u_org" class="w-full border p-2">
            </div>
            <div>
                <label class="block">權限id</label>
                <input type="text" name="u_auth" class="w-full border p-2">
            </div>
            <div>
                <label class="block">E-mail</label>
                <input type="email" name="u_mail" class="w-full border p-2">
            </div>
            <div>
                <label class="block">聯絡人姓名</label>
                <input type="text" name="c_name" class="w-full border p-2">
            </div>
            <div>
                <label class="block">聯絡人電話</label>
                <input type="text" name="c_tel" class="w-full border p-2">
            </div>
            <div class="col-span-2">
                <label class="block">公司名稱</label>
                <input type="text" name="u_company" class="w-full border p-2">
            </div>
            <div>
                <label class="block">狀態</label>
                <select name="u_status" class="w-full border p-2">
                    <option value="1">啟用</option>
                    <option value="0">停用</option>
                    <option value="2">刪除</option>
                </select>
            </div>
            <div>
                <label class="block">上級機關</label>
                <input type="text" name="nextAgency" class="w-full border p-2">
            </div>
            <div class="col-span-2 flex justify-end space-x-2">
                <button type="submit" name="action" value="add" class="bg-blue-500 text-white px-4 py-2 rounded">新增</button>
                <button type="submit" name="action" value="update" class="bg-green-500 text-white px-4 py-2 rounded">修改</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
