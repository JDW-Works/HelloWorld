# 系統架構

本專案由以下服務組成：

- **Laravel**：負責 Web 與商業邏輯。
- **Flask API**：提供基於 LSTM 的預測服務。

兩者可透過 `config/app.example.yaml` 進行跨服務設定，並透過 `.env` 檔案設定私密值。
