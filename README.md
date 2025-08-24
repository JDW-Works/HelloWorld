# TestSample

TestSample 為一個整合 Laravel 與 Flask 的範例專案骨架，示範如何在同一個倉庫中管理 PHP 與 Python 服務。

## 專案結構

```
.
├── src/            Laravel 應用程式
├── api/            Flask + LSTM API
├── config/         跨服務設定樣板
├── docs/           技術文件
├── tests/          測試骨架
├── .env.example    環境變數範例
├── .gitignore
├── .gitattributes
├── phpunit.xml
└── pytest.ini
```

## 開發環境需求

- PHP 8.2+
- Composer 2+
- Python 3.10+
- Node.js (選用，用於前端資產)

## 安裝與啟動

### 1. 設定環境變數

複製範例檔案並依需求調整：

```
cp .env.example .env
```

### 2. 安裝 Laravel 依賴

```
cd src
composer install
php artisan key:generate
```

啟動開發伺服器：

- **Windows**
  ```
  php artisan serve
  ```
- **macOS / Linux**
  ```
  php artisan serve
  ```

### 3. 安裝 Flask API 依賴

```
cd ../api
python -m venv venv
# Windows
venv\\Scripts\\activate
pip install -r requirements.txt

# macOS / Linux
source venv/bin/activate
pip install -r requirements.txt
```

啟動 API 服務：

```
python app.py
```

### 4. 執行測試

- PHPUnit（於 `src` 目錄內）：
  ```
  ./vendor/bin/phpunit
  ```
- pytest（於根目錄或 `api` 目錄內）：
  ```
  pytest
  ```

## 授權

本專案採用 MIT 授權條款。
