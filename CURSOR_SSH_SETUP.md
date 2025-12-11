# Add DigitalOcean Server to Cursor - SSH Connection Guide

**Purpose:** Connect Cursor IDE to your DigitalOcean server for remote development

---

## 📋 PREREQUISITES

Before starting, you need:
- ✅ DigitalOcean droplet IP address
- ✅ SSH username (usually `root` or custom user)
- ✅ SSH private key (or password)
- ✅ Cursor IDE installed

---

## 🔧 METHOD 1: Using Cursor's Remote SSH Extension

### **Step 1: Install Remote SSH Extension**

1. Open Cursor
2. Click **Extensions** icon (or press `Ctrl+Shift+X`)
3. Search for: **"Remote - SSH"**
4. Install the extension by Microsoft (or the Cursor equivalent)

### **Step 2: Configure SSH Config File**

1. Press `Ctrl+Shift+P` (or `Cmd+Shift+P` on Mac) to open command palette
2. Type: **"Remote-SSH: Open SSH Configuration File"**
3. Select: **"C:\Users\YourUsername\.ssh\config"** (Windows) or **"~/.ssh/config"** (Mac/Linux)

### **Step 3: Add DigitalOcean Server Configuration**

Add this configuration to your SSH config file:

```
Host digitalocean
    HostName YOUR_DROPLET_IP
    User root
    IdentityFile ~/.ssh/id_rsa
    Port 22
    ServerAliveInterval 60
    ServerAliveCountMax 3
```

**Replace:**
- `YOUR_DROPLET_IP` - Your actual DigitalOcean droplet IP address
- `root` - Your SSH username (if different)
- `~/.ssh/id_rsa` - Path to your SSH private key

**Example:**
```
Host digitalocean
    HostName 123.45.67.89
    User root
    IdentityFile C:\Users\YourName\.ssh\id_rsa
    Port 22
    ServerAliveInterval 60
    ServerAliveCountMax 3
```

### **Step 4: Connect to Server**

1. Press `Ctrl+Shift+P` (or `Cmd+Shift+P`)
2. Type: **"Remote-SSH: Connect to Host"**
3. Select: **"digitalocean"** (the host you just configured)
4. Cursor will open a new window connected to your server
5. Enter your SSH password if prompted (or it will use your SSH key)

### **Step 5: Open Remote Folder**

1. Once connected, click **File → Open Folder**
2. Navigate to: `/var/www/html`
3. Click **OK**

You're now working directly on your DigitalOcean server!

---

## 🔑 METHOD 2: Generate SSH Key (If You Don't Have One)

### **On Windows (PowerShell):**

```powershell
# Generate SSH key
ssh-keygen -t rsa -b 4096 -C "your_email@example.com"

# Save to: C:\Users\YourName\.ssh\id_rsa
# Press Enter to accept default location
# Enter passphrase (optional, press Enter for no passphrase)
```

### **Copy Public Key to DigitalOcean:**

```powershell
# Display your public key
cat C:\Users\YourName\.ssh\id_rsa.pub

# Copy the output, then SSH into your server:
ssh root@YOUR_DROPLET_IP

# On the server, add your public key:
mkdir -p ~/.ssh
echo "YOUR_PUBLIC_KEY_HERE" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
chmod 700 ~/.ssh
```

---

## 🎯 METHOD 3: Using Password Authentication

If you prefer password authentication:

```
Host digitalocean
    HostName YOUR_DROPLET_IP
    User root
    Port 22
    PreferredAuthentications password
    ServerAliveInterval 60
    ServerAliveCountMax 3
```

**Note:** You'll be prompted for password each time you connect.

---

## ✅ VERIFICATION

### **Test SSH Connection:**

```bash
# Test connection from terminal
ssh root@YOUR_DROPLET_IP

# Or using the host alias
ssh digitalocean
```

### **Verify in Cursor:**

1. Connect via Remote SSH
2. Open terminal in Cursor (`Ctrl+~`)
3. Run: `pwd` - Should show `/root` or your home directory
4. Run: `ls -la /var/www/html` - Should show your project files

---

## 🔧 TROUBLESHOOTING

### **Issue: "Permission denied (publickey)"**

**Solution:**
1. Verify SSH key is correct
2. Check public key is on server: `cat ~/.ssh/authorized_keys`
3. Verify file permissions on server:
   ```bash
   chmod 700 ~/.ssh
   chmod 600 ~/.ssh/authorized_keys
   ```

### **Issue: "Connection refused"**

**Solution:**
1. Check droplet is running
2. Verify IP address is correct
3. Check firewall allows SSH (port 22)
4. Test connection: `ping YOUR_DROPLET_IP`

### **Issue: "Host key verification failed"**

**Solution:**
```bash
# Remove old host key
ssh-keygen -R YOUR_DROPLET_IP

# Or add to known hosts
ssh-keyscan -H YOUR_DROPLET_IP >> ~/.ssh/known_hosts
```

### **Issue: "Could not establish connection"**

**Solution:**
1. Check SSH config file syntax
2. Verify SSH service is running on server: `systemctl status ssh`
3. Check server logs: `tail -f /var/log/auth.log`

---

## 📝 WORKING WITH REMOTE FILES

### **Once Connected:**

1. **Open Project:**
   - File → Open Folder → `/var/www/html`

2. **Edit Files:**
   - Files open directly in Cursor
   - Changes are saved to server immediately

3. **Terminal:**
   - Press `Ctrl+~` to open terminal
   - Terminal runs commands on remote server

4. **Git Operations:**
   - Can run `git` commands on remote server
   - Can commit/push from remote connection

---

## 🚀 BEST PRACTICES

### **1. Use SSH Keys (Not Passwords)**
- More secure
- No password prompts
- Better for automation

### **2. Use Host Aliases**
- Easier to remember: `ssh digitalocean` vs `ssh root@123.45.67.89`
- Can configure multiple servers

### **3. Keep SSH Keys Secure**
- Don't share private keys
- Use strong passphrases
- Rotate keys periodically

### **4. Configure Timeout Settings**
```
ServerAliveInterval 60    # Send keepalive every 60 seconds
ServerAliveCountMax 3     # Max 3 failed attempts before disconnect
```

---

## 📋 QUICK REFERENCE

### **SSH Config File Location:**
- **Windows:** `C:\Users\YourName\.ssh\config`
- **Mac/Linux:** `~/.ssh/config`

### **SSH Key Location:**
- **Windows:** `C:\Users\YourName\.ssh\id_rsa`
- **Mac/Linux:** `~/.ssh/id_rsa`

### **Connect Command:**
- `Ctrl+Shift+P` → "Remote-SSH: Connect to Host" → Select "digitalocean"

### **Project Directory on Server:**
- `/var/www/html`

---

## ✅ CHECKLIST

- [ ] SSH key generated (or password ready)
- [ ] Public key added to DigitalOcean server
- [ ] SSH config file created/updated
- [ ] Remote SSH extension installed in Cursor
- [ ] Connection tested successfully
- [ ] Project folder opened (`/var/www/html`)
- [ ] Terminal working on remote server

---

## 🎯 NEXT STEPS

Once connected:

1. **Open your project:** `/var/www/html`
2. **Edit files directly** on the server
3. **Use terminal** for Git, database, etc.
4. **Deploy changes** instantly (no file transfer needed)

**You're now ready to develop directly on your DigitalOcean server!** 🚀

