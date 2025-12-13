# Quick Connection Test - DigitalOcean Server

**Server IP:** `134.199.174.78`

---

## 🚀 QUICK TESTS

### **1. Test SSH Connection:**

```bash
ssh root@134.199.174.78
```

**Expected:** Should connect and show server prompt

---

### **2. Test Ping:**

```bash
ping 134.199.174.78
```

**Expected:** Should receive ping responses

---

### **3. Test Port 22 (SSH):**

**Windows PowerShell:**
```powershell
Test-NetConnection -ComputerName 134.199.174.78 -Port 22
```

**Linux/Mac:**
```bash
nc -zv 134.199.174.78 22
```

**Expected:** Port 22 should be open

---

### **4. Test Port 80 (HTTP):**

**Windows PowerShell:**
```powershell
Test-NetConnection -ComputerName 134.199.174.78 -Port 80
```

**Linux/Mac:**
```bash
nc -zv 134.199.174.78 80
```

**Expected:** Port 80 should be open (if web server is running)

---

### **5. Test Port 443 (HTTPS):**

**Windows PowerShell:**
```powershell
Test-NetConnection -ComputerName 134.199.174.78 -Port 443
```

**Linux/Mac:**
```bash
nc -zv 134.199.174.78 443
```

**Expected:** Port 443 should be open (if SSL is configured)

---

### **6. Test Web Server:**

Open in browser:
```
http://134.199.174.78
```

**Expected:** Should show website or default Apache page

---

## ✅ ALL TESTS PASSED?

If all tests pass, your server is ready for:
- ✅ SSH connections
- ✅ Remote development in Cursor
- ✅ Automated deployment via GitHub Actions
- ✅ Web access

---

**Server IP:** `134.199.174.78` ✅

