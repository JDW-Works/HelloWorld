<?php
/*********************************************************************
 * 檔案：board_history.php
 * 說明：歷史報表下載／調度單畫面
 * 作者：JDW
 * 最後更新：2025‑06‑20（加入 5 秒刷新支援／待命表格）
 ********************************************************************/
session_start();
require_once("lib/link.php");
require_once("common_page/head.php");
require_once("lib/allotpage.php");

$city_order = [
    "基隆市", "台北市", "新北市", "桃園市", "新竹市", "新竹縣",
    "宜蘭縣", "苗栗縣", "台中市", "彰化縣", "南投縣", "雲林縣",
    "嘉義市", "嘉義縣", "台南市", "高雄市", "屏東縣", "花蓮縣",
    "台東縣", "澎湖縣", "金門縣", "連江縣"
];
/*------------------------------------------------------------
|  參數與預設值
|------------------------------------------------------------*/
$dosearch = 0;     // 是否為搜尋模式
$ev_id    = 0;     // 目前進行中事件 ID
$ev_msg   = "";    // 事件提示文字
$search   = "";    // SQL 追加條件 (依事件)
$title    = "抽水機分配調度管理";   // <title> 文字
$pagename = "歷史報表下載";         // 頁面標題

/*------------------------------------------------------------
|  取得「事件」(Events) 資訊：取尚未結束(ev_isend='N') 最新一筆
|------------------------------------------------------------*/
$query = "SELECT * FROM Events WHERE ev_isend='N' ORDER BY ev_id DESC";
$DB->query($query);
if($row = $DB->fetchObject()){
    $ev_id   = $row->ev_id;
    $ev_msg  = "目前事件：".$row->ev_name;
    $search  = "AND a.ev_id ='".$ev_id."'";
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>

    <!-- 共用 head：含 Bootstrap 5、FontAwesome、Alertify、fancybox 等 -->
    <?php require("common/head_lib.php"); ?>

    <!-- jQuery UI Date / Time Picker -->
    <link rel="stylesheet" href="lib/jquery-ui/jquery-ui.css">

    <!-- 自定樣式 -->
    <style>
        .btn-word-blue{
            background-color:#2B579A;
            color:#FFF;
            border-color:#2B579A;
        }
        .btn-word-blue:hover{
            background-color:#1F4176;
            border-color:#1F4176;
        }
        /* 表格固定表頭（範例） */
        .tablefix thead th{
            position:sticky;
            top:0;
            background:#f7f7f7;
            z-index:10;
        }
        .sortable{
            cursor:pointer;
        }
    </style>
</head>
<body>
<div id="app">
    <!-------------- 側邊選單 / Sidebar -------------->
    <div id="sidebar" class="active">
        <div class="sidebar-wrapper active">
            <?php require_once("common_page/menu.php"); ?>
            <button class="sidebar-toggler btn x"><i data-feather="x"></i></button>
        </div>
    </div>

    <!-------------- 主要內容區 -------------->
    <div id="main">
        <?php require_once("common_page/header.php"); ?>

        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3><?php echo $pagename; ?></h3>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">首頁</a></li>
                                <li class="breadcrumb-item active" aria-current="page"><?php echo $pagename; ?></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <!-------------- Section：頁面功能 -------------->
            <section class="section">
                <div class="card">
                    <!------ 表頭搜尋區 ------>
                    <div class="card-header">
                        <div class="row">
                            <form id="searchform" name="searchform" class="form form-horizontal"
                                  method="post" action="common_lib/search_board_history.php"
                                  enctype="multipart/form-data">

                                <input type="hidden" id="searchpage" name="searchpage" value="board_history">

                                <!------ 區塊 A：事件選擇與功能鍵 ------>
                                <div class="board-search-area-a">

                                    <!---- 標題 ---->
                                    <div class="form-group search-sel board-search-area-title">
                                        歷史報表下載
                                    </div>

                                    <!---- 事件下拉 ---->
                                    <div class="form-group search-sel">
                                        <select class="choices form-select" id="evenhistory" name="evenhistory">
                                            <option value="">請選擇事件</option>
                                            <?php
                                            $query = "SELECT * FROM Events WHERE ev_isend!='-' ORDER BY ev_id DESC";
                                            $DB->query($query);
                                            while($row = $DB->fetchObject()){
                                                ?>
                                                <option value="<?php echo $row->ev_id; ?>"
                                                    <?php if($row->ev_id==$ev_id) echo "selected"; ?>>
                                                    【<?php echo $row->ev_bdate->format('Y-m-d'); ?>】 <?php echo $row->ev_name; ?>
                                                </option>
                                                <?php
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <!---- 搜尋按鈕 ---->
                                    <div class="form-group search-sel">
                                        <a id="searchbtn" class="btn btn-outline-primary" onclick="searchform.submit();">
                                            <span class="fa-fw select-all fas"></span>
                                        </a>
                                    </div>

                                    <!---- 右側功能鍵 ---->
                                    <div class="form-group search-sel float-right">
                                        <?php if($ev_id==0){ ?>
                                            <a class="btn btn-info"
                                            data-fancybox
                                            data-type="iframe"
                                            data-src="event_add.php"
                                            href="javascript:;">事件開始</a>

                                        <?php }else{ ?>
                                            <?php echo $ev_msg; ?>
                                            <a href="#" class="btn btn-danger" onclick="EventEnd(<?php echo $ev_id;?>)">事件結束</a>
                                            <?php if($_SESSION['nurauth']==1){ ?>
                                                <a class="btn btn-info" href="board_event_photo.php">打包圖片</a>
                                            <?php } ?>
                                            <!-- ★★★ Bootstrap 5 Modal 觸發按鈕 ★★★ -->
                                            <button type="button" class="btn btn-warning"
                                                    data-bs-toggle="modal" data-bs-target="#dispatchSheetModal">
                                                調度單
                                            </button>
                                        <?php } ?>
                                    </div>
                                </div><!-- /區塊 A -->

                                <!------ 區塊 B：Excel / Word 報表 ------>
                                <div class="board-search-area-b">
                                    <div class="form-group col-5 search-sel mt-30">
                                        <?php
                                        $strexcel   = "ev_id=".$ev_id;
                                        $args_excel = rawurlencode(CryptCode($strexcel,"E",CRYPT_KEY));
                                        ?>
                                        <a class="btn btn-success" data-fancybox data-type="iframe"
                                           href="board_excel_support.php?arg=<?php echo $args_excel;?>">
                                            <span class="fa-fw select-all fas"></span>支援現況表
                                        </a>
                                        <a class="btn btn-success" data-fancybox data-type="iframe"
                                           href="board_excel_standby.php?arg=<?php echo $args_excel;?>">
                                            <span class="fa-fw select-all fas"></span>待命現況表
                                        </a>
                                        <a id="dispatchStatusLink" class="btn btn-word-blue" href="#">
                                            <span class="fa-fw select-all fas"></span>分署設備調度狀況
                                        </a>
                                        <a id="downloadAllReportsBtn" class="btn btn-secondary ms-2" href="javascript:void(0);" disabled>
                                            <span class="fa-fw select-all fas"></span>打包下載
                                        </a>
                                    </div>

                                    <!---- 本署可調度總覽 ---->
                                    <div class="form-group col-6 search-sel">
<?php
/*--------- 抽水機可調度數量統計 ---------*/
$pumpnum_wra     = 0;   // WRA 抽水機數 (operationtype <99)
$pumpnum_wra_qr  = 0;   // WRA QR 抽水機

$query = "SELECT * FROM Pumpdata WHERE device_status>0 AND pd_operationtype<99";
$DB->query($query);
while($row=$DB->fetchObject()){
    if(OrgData($row->o_id,"o_type")==1) $pumpnum_wra++;
}
$query="SELECT * FROM Pumpdata WHERE device_status>0 AND pd_operationtype=99";
$DB->query($query);
while($row=$DB->fetchObject()){
    if(OrgData($row->o_id,"o_type")==1) $pumpnum_wra_qr++;
}

$pumpnum_wra_a = PumpBoardStatusNum($ev_id,"支援",0)+PumpBoardStatusNum($ev_id,"支援",1)
               + PumpBoardStatusNum($ev_id,"支援",2)+PumpBoardStatusNum($ev_id,"支援",3);
$pumpnum_wra_b = PumpBoardStatusNum($ev_id,"故障",0)+PumpBoardStatusNum($ev_id,"故障",1)
               + PumpBoardStatusNum($ev_id,"故障",2)+PumpBoardStatusNum($ev_id,"故障",3);
$pumpnum_wra_d = PumpBoardStatusNum($ev_id,"待命",0)+PumpBoardStatusNum($ev_id,"待命",1)
               + PumpBoardStatusNum($ev_id,"待命",2)+PumpBoardStatusNum($ev_id,"待命",3);
$pumpnum_wra_c = $pumpnum_wra - $pumpnum_wra_a - $pumpnum_wra_b - $pumpnum_wra_d;

$pumpnum_wra_a_qr = PumpBoardStatusNum($ev_id,"支援",99);
$pumpnum_wra_b_qr = PumpBoardStatusNum($ev_id,"故障",99);
$pumpnum_wra_d_qr = PumpBoardStatusNum($ev_id,"待命",99);
$pumpnum_wra_c_qr = $pumpnum_wra_qr - $pumpnum_wra_a_qr - $pumpnum_wra_b_qr - $pumpnum_wra_d_qr;
?>
                                        <table class="table table-striped mb-0" style="background:#FFF;">
                                            <tr>
                                                <td>本署可調度台數</td>
                                                <td colspan="5">
                                                    <?php
                                                        echo $pumpnum_wra;
                                                        if($pumpnum_wra_qr>0) echo "(+".$pumpnum_wra_qr.")";
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="20%">可支援</td>
                                                <td>
                                                    <?php
                                                    echo $pumpnum_wra_c;
                                                    if($pumpnum_wra_c_qr>0) echo "(+".$pumpnum_wra_c_qr.")";
                                                    ?>
                                                </td>
                                                <td width="10%">已支援</td>
                                                <td>
                                                    <?php
                                                    echo $pumpnum_wra_a;
                                                    if($pumpnum_wra_a_qr>0) echo "(+".$pumpnum_wra_a_qr.")";
                                                    ?>
                                                </td>
                                                <td width="10%">待命</td>
                                                <td>
                                                    <?php
                                                    echo $pumpnum_wra_d;
                                                    if($pumpnum_wra_d_qr>0) echo "(+".$pumpnum_wra_d_qr.")";
                                                    ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </div><!-- /本署可調度總覽 -->
                                </div><!-- /區塊 B -->
                            </form><!-- /form -->
                        </div>
                    </div><!-- /card-header -->

                    <!------ 卡片內容：左、右兩欄主要表 ------>
                    <div class="card-body">
                        <div class="row">
                            <!---------------- 左欄：機關 > 抽水機一覽 ---------------->
                            <div class="col-6">
                                <div id="orgTableContent">
                                <select id="OrgTypeSelect1" name="OrgTypeSelect1"
                                        class="choices form-select"
                                        onchange="change_table(this.value)">
                                    <option value="">請選擇機關</option>
                                    <?php
                                    $searchorgtype = isset($_SESSION["searchorgtype"]) && $dosearch==1
                                                   ? $_SESSION["searchorgtype"] : "1";
                                    foreach($org_type_array as $otkey=>$otvalue){
                                        ?>
                                        <option value="<?php echo $otkey; ?>"
                                            <?php if($otkey==$searchorgtype) echo "selected"; ?>>
                                            <?php echo $otvalue; ?>
                                        </option>
                                        <?php
                                    }
                                    ?>
                                </select>

                                <!-- 取得所有機關，準備存放狀態 -->
                                <?php
                                $pump_build   = $pump_fault = $pump_standby = $pump_support = [];

                                /*— 初始化每個機關陣列 —*/
                                $DB->query("SELECT * FROM Orgnization");
                                while($row=$DB->fetchObject()){
                                    $pump_build[$row->o_id]   = [];
                                    $pump_fault[$row->o_id]   = [];
                                    $pump_standby[$row->o_id] = [];
                                    $pump_support[$row->o_id] = [];
                                }

                                /*— 依 Pumpdata 歸類 —*/
                                $query="SELECT * FROM Pumpdata WHERE device_status>0";
                                $DB->query($query);
                                while($row=$DB->fetchObject()){

                                    /* 取該事件 (若有) 的 pd_spstatus */
                                    $pd_spstatus="";
                                    if($ev_id!=0){
                                        $query2="SELECT * FROM Pumpdispatch
                                                 WHERE pd_no='".$row->pd_idno."'
                                                   AND ev_id='".$ev_id."'
                                                 ORDER BY pd_id DESC";
                                        $DB2->query($query2);
                                        if($row2=$DB2->fetchObject()){
                                            $pd_spstatus=$row2->pd_spstatus;
                                        }
                                    }

                                    /* 依 pd_spstatus 歸類陣列 */
                                    if($pd_spstatus=="故障"){
                                        $pump_fault[$row->o_id][$row->pd_id]=$row->pd_name;
                                    }elseif($pd_spstatus=="待命"){
                                        $pump_standby[$row->o_id][$row->pd_id]=$row->pd_name;
                                    }elseif($pd_spstatus=="支援"){
                                        $pump_support[$row->o_id][$row->pd_id]=$row->pd_name;
                                    }else{
                                        $pump_build[$row->o_id][$row->pd_id]=$row->pd_name; // 歸建
                                    }
                                }
                                ?>

                                <!------ 表格（中央管） ------>
                                <div class="table-responsive">
                                    <table id="tablewra"
                                           class="table table-striped mb-0 table-hover tablefix">
                                        <thead>
                                        <tr>
                                            <th width="25%">組織</th>
                                            <th width="25%">歸建</th>
                                            <th width="25%">故障</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $DB->query("SELECT * FROM Orgnization WHERE o_type=1");
                                        while($row=$DB->fetchObject()){
                                            $o_id=$row->o_id;
                                            $buildCount=count($pump_build[$o_id]);
                                            $orgName=$row->o_name." (".$buildCount."台)";
                                            ?>
                                            <tr>
                                                <td><div class="orgtitle bgcolor<?php echo $o_id;?>"><?php echo $orgName;?></div></td>
                                                <td>
                                                    <?php
                                                    foreach($pump_build[$o_id] as $pid=>$pname){
                                                        $args_str = rawurlencode(CryptCode("ev_id=$ev_id&pump_id=$pid","E",CRYPT_KEY));
                                                        if($ev_id==0){
                                                            echo "<a class='btn btn-pump bgcolor$o_id' href='#'>$pname</a> ";
                                                        }else{
                                                            echo "<a data-fancybox data-type='iframe' class='btn btn-pump bgcolor$o_id'
                                                                   href='board_add.php?arg=$args_str'>$pname</a> ";
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    foreach($pump_fault[$o_id] as $pid=>$pname){
                                                        $args_str = rawurlencode(CryptCode("ev_id=$ev_id&pump_id=$pid","E",CRYPT_KEY));
                                                        if($ev_id==0){
                                                            echo "<a class='btn btn-pump bgcolor$o_id' href='#'>$pname</a> ";
                                                        }else{
                                                            echo "<a data-fancybox data-type='iframe' class='btn btn-pump bgcolor$o_id'
                                                                   href='board_add.php?arg=$args_str'>$pname</a> ";
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                        </tbody>
                                    </table>

                                    <!------ 表格（地方政府） ------>
                                    <table id="tablegov"
                                           class="table table-striped mb-0 table-hover tablefix"
                                           style="display:none">
                                        <thead>
                                        <tr>
                                            <th width="25%">組織</th>
                                            <th width="25%">歸建</th>
                                            <th width="25%">故障</th>
                                            <th width="25%">待命</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $DB->query("SELECT * FROM Orgnization WHERE o_type=2");
                                        while($row=$DB->fetchObject()){
                                            $o_id=$row->o_id;
                                            $buildCount=count($pump_build[$o_id]);
                                            $orgName=$row->o_name." (".$buildCount."台)";
                                            ?>
                                            <tr>
                                                <td><div class="orgtitle bgcolor<?php echo $o_id;?>"><?php echo $orgName;?></div></td>
                                                <td>
                                                    <?php
                                                    foreach($pump_build[$o_id] as $pid=>$pname){
                                                        $args_str = rawurlencode(CryptCode("ev_id=$ev_id&pump_id=$pid","E",CRYPT_KEY));
                                                        if($ev_id==0){
                                                            echo "<a class='btn btn-pump bgcolor$o_id' href='#'>$pname</a> ";
                                                        }else{
                                                            echo "<a data-fancybox data-type='iframe' class='btn btn-pump bgcolor$o_id'
                                                                   href='board_add.php?arg=$args_str'>$pname</a> ";
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    foreach($pump_fault[$o_id] as $pid=>$pname){
                                                        $args_str = rawurlencode(CryptCode("ev_id=$ev_id&pump_id=$pid","E",CRYPT_KEY));
                                                        if($ev_id==0){
                                                            echo "<a class='btn btn-pump bgcolor$o_id' href='#'>$pname</a> ";
                                                        }else{
                                                            echo "<a data-fancybox data-type='iframe' class='btn btn-pump bgcolor$o_id'
                                                                   href='board_add.php?arg=$args_str'>$pname</a> ";
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    foreach($pump_standby[$o_id] as $pid=>$pname){
                                                        $args_str = rawurlencode(CryptCode("ev_id=$ev_id&pump_id=$pid","E",CRYPT_KEY));
                                                        if($ev_id==0){
                                                            echo "<a class='btn btn-pump bgcolor$o_id' href='#'>$pname</a> ";
                                                        }else{
                                                            echo "<a data-fancybox data-type='iframe' class='btn btn-pump bgcolor$o_id'
                                                                   href='board_add.php?arg=$args_str'>$pname</a> ";
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                        </tbody>
                                    </table>
                                </div>
                                </div>
                            </div><!-- /col-6 左欄 -->

                            <!---------------- 右欄：支援＆待命清單 ---------------->
                            <div class="col-6">

                                <!------ 搜尋欄 ------>
                                <div class="table-responsive">
                                    <div class="form-group">
                                        <input type="text" id="searchInput" class="form-control"
                                               placeholder="搜尋縣市或鄉鎮...">
                                    </div>

                                    <!-- ====== ★★★ 5 秒刷新目標區塊 ★★★ ====== -->
                                    <div id="supportStandbyTables">

                                    <!--────────── 支援 ──────────-->
                                    <table id="supportTable" class="table table-striped mb-0 table-hover">
                                        <thead>
                                        <tr>
                                            <th width="15%" class="sortable sort-support">支援</th>
                                            <th width="10%">目前狀態</th>
                                            <th width="15%" class="sortable sort-city">支援縣市</th>
                                            <th width="15%">支援鄉鎮</th>
                                            <th width="15%">支援地點</th>
                                            <th width="10%">現在位置</th>
                                            <th width="10%">聯絡人</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        // 收集所有支援資料
                                        $all_support = [];
                                        foreach($pump_support as $o_id=>$supportArr){
                                            foreach($supportArr as $pid=>$pname){
                                                $pd_idno    = PumpData($pid,"pd_idno");
                                                $use_status = PumpData($pid,"use_status");
                                                $rd_lat     = RespondDataappData($pd_idno,"rd_lat");
                                                $rd_lon     = RespondDataappData($pd_idno,"rd_lon");
                                                $rd_opdate  = RespondDataappData($pd_idno,"rd_opdate");
                                                $qr_latitude   = QRpumpstatusData($pid,"latitude");
                                                $qr_longitude  = QRpumpstatusData($pid,"longitude");
                                                $qr_opdate     = QRpumpstatusData($pid,"create_datetime");
                                                $use_status_QR = PumpData($pid,"use_status_QRcode");
                                                $qr_status     = $use_status_QR!="" ? $pump_stage_array[$use_status_QR] : $pump_stage_array[0];
                                                $status = $qr_opdate >= $rd_opdate ? $qr_status : $pump_stage_array[$use_status];

                                                $pd_sporg    = PumpDispatchData($ev_id,$pid,"pd_sporg");
                                                $pd_spzone   = PumpDispatchData($ev_id,$pid,"pd_spzone");
                                                $pd_location = PumpDispatchData($ev_id,$pid,"pd_location");
                                                $args_str    = rawurlencode(CryptCode("ev_id=$ev_id&pump_id=$pid","E",CRYPT_KEY));
                                                $pd_latitude = $qr_opdate >= $rd_opdate ? $qr_latitude : $rd_lat;
                                                $pd_longitude = $qr_opdate >= $rd_opdate ? $qr_longitude : $rd_lon;
                                                $all_support[] = [
                                                    'o_id'        => $o_id,
                                                    'pid'         => $pid,
                                                    'pname'       => $pname,
                                                    'pd_idno'     => $pd_idno,
                                                    'status'      => $status,
                                                    'pd_sporg'    => $pd_sporg,
                                                    'pd_spzone'   => $pd_spzone,
                                                    'pd_location' => $pd_location,
                                                    'args_str'    => $args_str,
                                                    'pd_latitude' => $pd_latitude,
                                                    'pd_longitude'=> $pd_longitude
                                                ];
                                            }
                                        }
                                        // 依支援縣市排序
                                        usort($all_support, function($a, $b) use ($city_order){
                                            $a_idx = array_search($a['pd_sporg'], $city_order);
                                            $b_idx = array_search($b['pd_sporg'], $city_order);
                                            $a_idx = ($a_idx === false) ? 999 : $a_idx;
                                            $b_idx = ($b_idx === false) ? 999 : $b_idx;
                                            return $a_idx - $b_idx;
                                        });


                                        foreach($all_support as $row){
                                        ?>
                                            <tr>
                                                <td>
                                                    <a data-fancybox data-type="iframe"
                                                    class="btn btn-pump bgcolor<?php echo $row['o_id'];?>"
                                                    href="board_add.php?arg=<?php echo $row['args_str'];?>">
                                                        <?php echo $row['pd_idno'];?>
                                                    </a>
                                                </td>
                                                <td><?php echo $row['status']; ?></td>
                                                <td><?php echo $row['pd_sporg']; ?></td>
                                                <td><?php echo $row['pd_spzone']; ?></td>
                                                <td><?php echo $row['pd_location']; ?></td>
                                                <td>
                                                    <a class="btn btn-pump bgcolor<?php echo $row['o_id'];?>"
                                                    target="_blank"
                                                    href="nowposition.php?nowlat=<?php echo $row['pd_latitude'];?>&nowlng=<?php echo $row['pd_longitude'];?>&pd_idno=<?php echo $row['pd_idno'];?>">
                                                        現在位置
                                                    </a>
                                                </td>
                                                <td>
                                                    <a class="btn btn-pump bgcolor<?php echo $row['o_id'];?>"
                                                    href="#" onclick="alertcontact(<?php echo $row['pid'];?>)">聯絡人</a>
                                                </td>
                                            </tr>
                                        <?php
                                        }
                                        ?>
                                        </tbody>
                                    </table>


                                    <!--────────── 待命 ──────────-->
                                    <table id="standbyTable" class="table table-striped mb-0 table-hover">
                                        <thead>
                                        <tr>
                                            <th width="15%" class="sortable sort-support">借用待命</th>
                                            <th width="10%">目前狀態</th>
                                            <th width="15%" class="sortable sort-city">支援縣市</th>
                                            <th width="15%">支援鄉鎮</th>
                                            <th width="15%">支援地點</th>
                                            <th width="10%">現在位置</th>
                                            <th width="10%">聯絡人</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        // 收集所有待命資料
                                        $all_standby = [];
                                        $DB->query("SELECT * FROM Orgnization WHERE o_type=1");
                                        while($row=$DB->fetchObject()){
                                            $o_id=$row->o_id;
                                            foreach($pump_standby[$o_id] as $pid=>$pname){
                                                $pd_idno    = PumpData($pid,"pd_idno");
                                                $use_status = PumpData($pid,"use_status");
                                                $rd_lat     = RespondDataappData($pd_idno,"rd_lat");
                                                $rd_lon     = RespondDataappData($pd_idno,"rd_lon");
                                                $rd_opdate  = RespondDataappData($pd_idno,"rd_opdate");
                                                $qr_latitude   = QRpumpstatusData($pid,"latitude");
                                                $qr_longitude  = QRpumpstatusData($pid,"longitude");
                                                $qr_opdate     = QRpumpstatusData($pid,"create_datetime");
                                                $use_status_QR = PumpData($pid,"use_status_QRcode");
                                                $qr_status     = $use_status_QR!="" ? $pump_stage_array[$use_status_QR] : $pump_stage_array[0];
                                                $status = $qr_opdate >= $rd_opdate ? $qr_status : $pump_stage_array[$use_status];

                                                $pd_sporg    = PumpDispatchData($ev_id,$pid,"pd_sporg");
                                                $pd_spzone   = PumpDispatchData($ev_id,$pid,"pd_spzone");
                                                $pd_location = PumpDispatchData($ev_id,$pid,"pd_location");
                                                $args_str    = rawurlencode(CryptCode("ev_id=$ev_id&pump_id=$pid","E",CRYPT_KEY));
                                                $pd_latitude = $qr_opdate >= $rd_opdate ? $qr_latitude : $rd_lat;
                                                $pd_longitude = $qr_opdate >= $rd_opdate ? $qr_longitude : $rd_lon;
                                                $all_standby[] = [
                                                    'o_id'        => $o_id,
                                                    'pid'         => $pid,
                                                    'pname'       => $pname,
                                                    'pd_idno'     => $pd_idno,
                                                    'status'      => $status,
                                                    'pd_sporg'    => $pd_sporg,
                                                    'pd_spzone'   => $pd_spzone,
                                                    'pd_location' => $pd_location,
                                                    'args_str'    => $args_str,
                                                    'pd_latitude' => $pd_latitude,
                                                    'pd_longitude'=> $pd_longitude
                                                ];
                                            }
                                        }
                                        // 依支援縣市排序
                                        usort($all_standby, function($a, $b) use ($city_order){
                                            $a_idx = array_search($a['pd_sporg'], $city_order);
                                            $b_idx = array_search($b['pd_sporg'], $city_order);
                                            $a_idx = ($a_idx === false) ? 999 : $a_idx;
                                            $b_idx = ($b_idx === false) ? 999 : $b_idx;
                                            return $a_idx - $b_idx;
                                        });


                                        foreach($all_standby as $row){
                                        ?>
                                            <tr>
                                                <td>
                                                    <?php if($ev_id==0){ ?>
                                                        <a class="btn btn-pump bgcolor<?php echo $row['o_id'];?>" href="#"><?php echo $row['pname'];?></a>
                                                    <?php }else{ ?>
                                                        <a data-fancybox data-type="iframe"
                                                        class="btn btn-pump bgcolor<?php echo $row['o_id'];?>"
                                                        href="board_add.php?arg=<?php echo $row['args_str'];?>">
                                                            <?php echo $row['pname'];?>
                                                        </a>
                                                    <?php } ?>
                                                </td>
                                                <td><?php echo $row['status']; ?></td>
                                                <td><?php echo $row['pd_sporg']; ?></td>
                                                <td><?php echo $row['pd_spzone']; ?></td>
                                                <td><?php echo $row['pd_location']; ?></td>
                                                <td>
                                                    <a class="btn btn-pump bgcolor<?php echo $row['o_id'];?>"
                                                    target="_blank"
                                                    href="nowposition.php?nowlat=<?php echo $row['pd_latitude'];?>&nowlng=<?php echo $row['pd_longitude'];?>&pd_idno=<?php echo $row['pd_idno'];?>">
                                                    現在位置
                                                    </a>
                                                </td>
                                                <td>
                                                    <a class="btn btn-pump bgcolor<?php echo $row['o_id'];?>"
                                                    href="#" onclick="alertcontact(<?php echo $row['pid'];?>)">聯絡人</a>
                                                </td>
                                            </tr>
                                        <?php
                                        }
                                        ?>
                                        </tbody>
                                    </table>


                                    </div><!-- /#supportStandbyTables -->
                                </div><!-- /table-responsive -->
                            </div><!-- /col-6 右欄 -->
                        </div><!-- /row -->
                    </div><!-- /card-body -->
                </div><!-- /card -->
            </section><!-- /section -->
        </div><!-- /page-heading -->

        <?php require_once("common_page/footer.php"); ?>
    </div><!-- /#main -->
</div><!-- /#app -->

<!-- 共用 footer script：內含 Bootstrap 5 JS、Feather、Alertify、fancybox 等 -->
<?php require_once("common/footer_lib.php"); ?>

<!-- 其他前端外掛 -->
<script src="js/search_pump_choices.js"></script>
<script src="https://code.jquery.com/ui/1.11.0/jquery-ui.min.js"></script>
<script src="lib/jQuery-Timepicker-Addon-master/dist/jquery-ui-timepicker-addon.js"></script>
<script src="lib/jQuery-Timepicker-Addon-master/dist/i18n/jquery-ui-timepicker-addon-i18n.min.js"></script>
<script src="lib/jQuery-Timepicker-Addon-master/dist/i18n/jquery-ui-timepicker-addon-zh-TW.js"></script>
<script src="lib/jQuery-Timepicker-Addon-master/dist/jquery-ui-sliderAccess.js"></script>

<script>
/*------------------------------------------------------------
|  客製前端互動
|------------------------------------------------------------*/
const cityOrder = <?php echo json_encode($city_order, JSON_UNESCAPED_UNICODE); ?>;
let supportSort = {column: '', asc: true};
let standbySort = {column: '', asc: true};

function sortTableByText(tableId, colIndex, asc){
    const $table = $("#"+tableId);
    const $rows = $table.find("tbody > tr").get();
    $rows.sort(function(a,b){
        const keyA = $(a).children().eq(colIndex).text().trim();
        const keyB = $(b).children().eq(colIndex).text().trim();
        return asc ? keyA.localeCompare(keyB) : keyB.localeCompare(keyA);
    });
    $.each($rows, function(_, row){
        $table.children("tbody").append(row);
    });
}

function sortTableByCity(tableId, colIndex, asc){
    const $table = $("#"+tableId);
    const $rows = $table.find("tbody > tr").get();
    $rows.sort(function(a,b){
        const cityA = $(a).children().eq(colIndex).text().trim();
        const cityB = $(b).children().eq(colIndex).text().trim();
        let idxA = cityOrder.indexOf(cityA);
        let idxB = cityOrder.indexOf(cityB);
        idxA = idxA === -1 ? 999 : idxA;
        idxB = idxB === -1 ? 999 : idxB;
        return asc ? idxA - idxB : idxB - idxA;
    });
    $.each($rows, function(_, row){
        $table.children("tbody").append(row);
    });
}

function applySupportSort(){
    if(supportSort.column === 'support'){
        sortTableByText('supportTable',0,supportSort.asc);
    }else if(supportSort.column === 'city'){
        sortTableByCity('supportTable',2,supportSort.asc);
    }
}

function applyStandbySort(){
    if(standbySort.column === 'support'){
        sortTableByText('standbyTable',0,standbySort.asc);
    }else if(standbySort.column === 'city'){
        sortTableByCity('standbyTable',2,standbySort.asc);
    }
}

$(function (){

    /* — 搜尋鄉鎮/縣市 — */
    $("#searchInput").on("keyup", function (){
        const value = $(this).val().toLowerCase();
        $(".table-striped:not(#tablewra) tbody tr").filter(function (){
            $(this).toggle(
                $(this).find("td:nth-child(3)").text().toLowerCase().indexOf(value) > -1 ||
                $(this).find("td:nth-child(4)").text().toLowerCase().indexOf(value) > -1
            );
        });
    });

    // 點擊支援欄位排序
    $(document).on('click', '#supportTable th.sort-support', function(){
        supportSort.asc = supportSort.column === 'support' ? !supportSort.asc : true;
        supportSort.column = 'support';
        applySupportSort();
    });

    $(document).on('click', '#supportTable th.sort-city', function(){
        supportSort.asc = supportSort.column === 'city' ? !supportSort.asc : true;
        supportSort.column = 'city';
        applySupportSort();
    });

    $(document).on('click', '#standbyTable th.sort-support', function(){
        standbySort.asc = standbySort.column === 'support' ? !standbySort.asc : true;
        standbySort.column = 'support';
        applyStandbySort();
    });

    $(document).on('click', '#standbyTable th.sort-city', function(){
        standbySort.asc = standbySort.column === 'city' ? !standbySort.asc : true;
        standbySort.column = 'city';
        applyStandbySort();
    });

    /* — 下載分署調度狀況 (Word) — */
    $("#dispatchStatusLink").click(function(e){
        e.preventDefault();
        const evID = $("#evenhistory").val();
        if(evID===""){
            alert("請先選擇事件");
            return;
        }
        window.location.href = "board_dispatch_status.php?arg="+evID;
    });

setInterval(function(){
    // 左側：中央管表格
    var $tbody = $("#tablewra tbody");
    var $scrollBox = $("#orgTableContent .table-responsive");
    var scrollTop = $scrollBox.scrollTop();

    $tbody.load(location.href + " #tablewra tbody > *", function(){
        $scrollBox.scrollTop(scrollTop);
    });

    // 右側：支援＆待命清單
    var $rightBox = $("#supportStandbyTables");
    var rightScrollTop = $rightBox.scrollTop();

    $rightBox.load(location.href + " #supportStandbyTables > *", function(){
        $rightBox.scrollTop(rightScrollTop);
        $("#searchInput").trigger("keyup");
        applySupportSort();
        applyStandbySort();
    });
}, 5000);

});

/* — 事件結束：pd_spstatus => 歸建 — */
function EventEnd(evid){
    reset();
    alertify.confirm("確定事件已結束?", function(ok){
        if(ok){
            $.ajax({
                url:'common_lib/delete_SQL.php?evid='+evid+'&act=end_event&reset_status=1',
                type:"POST",
                success:function(){
                    alertify.success("已結束！抽水機狀態已改為『歸建』");
                    location.reload();
                }
            });
        }else{
            alertify.error("已取消！");
        }
    });
}

/* — 切換中央管／地方政府表格 — */
function change_table(val){
    if(val=="1"){ $("#tablegov").hide(); $("#tablewra").show(); }
    else if(val=="2"){ $("#tablewra").hide(); $("#tablegov").show(); }
}

/* — 聯絡人彈窗 — */
function alertcontact(pumpid){
    reset();
    $.post("common_lib/search_pumpdata.php?pumpid="+pumpid, $('#form').serialize(), function(txt){
        if(txt!==""){
            const a=txt.split(",");
            alertify.alert("聯絡人: "+a[0].trim()+"<br>姓名: "+a[1]+"<br>電話: "+a[2]+"<br>手機: "+a[3]);
        }
    });
}

/*------------------------------------------------------------
|  調度單 Modal 內容：開啟時載入 + 每 5 秒刷新
|------------------------------------------------------------*/
let dsTimer = null;

function loadDispatchSheet() {
    const evID = $("#evenhistory").val() || 0;
    $("#dispatchSheetContent")
      .load("dispatch_sheet.php?ev_id=" + evID, function (response, status, xhr) {
          if (status === "error") {
              $("#dispatchSheetContent").html(
                  "<div class='alert alert-danger'>載入失敗："
                  + xhr.status + " " + xhr.statusText + "</div>"
              );
              console.error("dispatch_sheet.php error:", xhr.responseText);
          }
      });
}


/* 開啟 Modal 前先顯示載入文字 */
$("#dispatchSheetModal").on("show.bs.modal", function () {
    $("#dispatchSheetContent").html("載入中…");
});

/* 開啟後：立即載入 + 啟動輪詢；關閉後停止輪詢 */
$("#dispatchSheetModal").on("shown.bs.modal", function () {
    loadDispatchSheet();
    dsTimer = setInterval(loadDispatchSheet, 5000);
}).on("hidden.bs.modal", function () {
    clearInterval(dsTimer);
    dsTimer = null;
});
</script>
<!-- ★ 建議放在 jquery.min.js、bootstrap.bundle.min.js 之後 -->
<script>
(function () {
    /*──────── 內部函式：載入調度單 ────────*/
    function loadDispatchSheet() {
        const evID = $("#evenhistory").val() || 0;
        $("#dispatchSheetContent").load(
            "dispatch_sheet.php?ev_id=" + evID,
            function (response, status, xhr) {
                if (status === "error") {
                    $("#dispatchSheetContent").html(
                        `<div class="alert alert-danger">
                          載入失敗 → ${xhr.status} ${xhr.statusText}
                        </div>`
                    );
                    console.error("dispatch_sheet.php error:", xhr.responseText);
                }
            }
        );
    }

    /*──────── 事件委派：確保一定綁得到 ────────*/
    let dsTimer = null;
    const $doc = $(document);

    // Modal 要顯示時先塞「載入中…」
    $doc.on("show.bs.modal", "#dispatchSheetModal", () => {
        $("#dispatchSheetContent").html("載入中…");
    });

    // Modal 完全顯示後：載一次並啟動 5 秒輪詢
    $doc.on("shown.bs.modal", "#dispatchSheetModal", () => {
        loadDispatchSheet();
        dsTimer = setInterval(loadDispatchSheet, 5000);
    });

    // 關閉 Modal：清除輪詢
    $doc.on("hidden.bs.modal", "#dispatchSheetModal", () => {
        clearInterval(dsTimer);
        dsTimer = null;
    });
})();
</script>
<script>
$("#downloadAllReportsBtn").on("click", function(){
    // 取得當前事件 id
    var ev_id = $("#evenhistory").val();
    if (!ev_id) {
        alert("請先選擇事件！");
        return;
    }
    // 直接開新分頁觸發下載
    window.open("download_support_and_standby_zip.php?ev_id=" + ev_id);
});
</script>

<!-- ────────────────────────────────────────────────
     Bootstrap 5 Modal：調度單（依來源類型分兩區塊）
──────────────────────────────────────────────── -->
<div class="modal fade" id="dispatchSheetModal" tabindex="-1" aria-labelledby="dispatchSheetLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="dispatchSheetLabel" class="modal-title">調度單</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="關閉"></button>
            </div>
<div class="modal-body" style="min-height:300px;">
    <div id="dispatchSheetContent" class="text-center">
        載入中…
    </div>
</div>

        </div>
    </div>
</div><!-- /#dispatchSheetModal -->

<?php
/*------------------------------------------------------------
|  （如有「pageaction」需自動開窗，可於此加入 BS Modal 觸發）
|------------------------------------------------------------*/
if(isset($_GET_ARGS["pageaction"]) && $_GET_ARGS["pageaction"]!=""){
    // 目前僅保留加密參數計算, 若需自動跳出可自行補 JS
}
?>
</body>
</html>
