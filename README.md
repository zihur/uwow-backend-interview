## 實作測驗

該專案方便啟用，請理解將 .env.example 包含了部分機敏資訊

### 前置需求
- Docker 與 Docker Compose（或 Docker Desktop）
> 如果是 windows 建議請安裝 wsl2，並在 wsl2 內部`非 /mnt/c/...` 相關位置進行專案開發

### 如何開始（開發環境）
1. **類 Unix 環境**

    專案已提供 `init.sh` 腳本，clone 下來之後，第一次可在本機直接執行一鍵完成常用部署步驟（啟動容器、安裝相依、產生 key、執行 migrate、產生 API 文件、啟動 queue worker）：

    ```bash
    chmod +x init.sh
    ./init.sh
    ```

2. **Windows 環境**

    專案已提供 `init.bat` 腳本，clone 下來之後，CMD 使用者，第一次可在本機直接執行一鍵完成常用部署步驟（啟動容器、安裝相依、產生 key、執行 migrate、產生 API 文件、啟動 queue worker）：

> 注意：兩者均會自動啟動容器並執行初始化步驟，**會自動啟動 queue worker**，若是想觀察 worker log 可參考下方輔助指令。

3. 查看 `http://localhost:8000/api` 是否正常運作，你應該可以看到 `"message": "API is working"`

---

### 其他輔助指令
- 產生 scribe 文檔：

    ```bash
    docker compose exec app php artisan scribe:generate --force
    ```
> 使用 --force 避免 .scribe 的 cache，也可省略


- docker 銷毀重置指令（!!警告，這會將所有連同 images 都砍掉，請確定知道自己在做什麼）：
    ```bash
    docker compose down --rmi all -v
    ```

- **Scribe 文檔位置** http://localhost:8000/docs/ 
---
