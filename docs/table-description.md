# 資料表說明文件

本文件列出系統主要資料表及欄位中文解釋。

---

## USER 使用者資料表
| 欄位名稱 | 型別     | 說明 |
|----------|----------|------|
| id (PK)  | int      | 使用者唯一識別碼 |
| u_idno   | nvarchar | 帳號（可為身分證字號或員工代號） |
| u_name   | nvarchar | 使用者姓名 |
| u_org    | nvarchar | 所屬組織或單位 |
| u_auth   | nvarchar | 使用者角色或權限代碼 |

---

## APPLICATION 申請主表
| 欄位名稱   | 型別     | 說明 |
|------------|----------|------|
| id (PK)    | int      | 申請單唯一識別碼 |
| agency     | nvarchar | 申請機關（例如縣市政府或分署名稱） |
| created_at | datetime | 建立日期時間 |

---

## APPLICATION_DETAIL 申請明細表
| 欄位名稱             | 型別     | 說明 |
|----------------------|----------|------|
| id (PK)              | int      | 申請明細唯一識別碼 |
| application_id (FK)  | int      | 對應 APPLICATION.id |
| location             | nvarchar | 災害或需求地點 |
| disaster_description | nvarchar | 災害/需求情況描述 |
| request_amount       | int      | 申請所需抽水機數量 |

---

## REVIEW 審核紀錄表
| 欄位名稱   | 型別     | 說明 |
|------------|----------|------|
| id (PK)    | int      | 審核紀錄唯一識別碼 |
| detail_id (FK) | int  | 對應 APPLICATION_DETAIL.id |
| reviewer   | nvarchar | 審核人員帳號或姓名 |
| status     | nvarchar | 審核狀態（通過/退回/審核中） |
| reviewed_at| datetime | 審核時間 |

---

## PUMP 抽水機資料表
| 欄位名稱     | 型別     | 說明 |
|--------------|----------|------|
| id (PK)      | int      | 抽水機唯一識別碼 |
| pd_idno      | nvarchar | 抽水機編號 |
| device_status| nvarchar | 抽水機當前狀態（例如待命、支援、故障） |
| location     | nvarchar | 抽水機所在位置 |

---

## PUMPDISPATCH 抽水機調度表
| 欄位名稱   | 型別     | 說明 |
|------------|----------|------|
| id (PK)    | int      | 調度紀錄唯一識別碼 |
| detail_id (FK) | int  | 對應 APPLICATION_DETAIL.id |
| pd_no      | nvarchar | 調度之抽水機編號 |
| dispatch_time | datetime | 調度執行時間 |

---

## ASSIGNED_BRANCH 分署派工表
| 欄位名稱   | 型別     | 說明 |
|------------|----------|------|
| id (PK)    | int      | 派工紀錄唯一識別碼 |
| detail_id (FK) | int  | 對應 APPLICATION_DETAIL.id |
| river_branch | nvarchar | 分署名稱（例：第九河川分署） |
| pump_count   | int      | 分派之抽水機數量 |
