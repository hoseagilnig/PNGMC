# Fix SSH Authentication Issue

**Error:** `ssh: unable to authenticate, attempted methods [none publickey]`

**Possible Causes:**
1. SSH key format issue in GitHub secrets
2. Server doesn't have git repository
3. Key format incompatibility

---

## 🔧 SOLUTION 1: Verify Key Format in GitHub

The key in GitHub secrets must be **exactly** as shown, with no extra characters.

**Check:**
1. Go to: `https://github.com/hoseagilnig/PNGMC/settings/secrets/actions`
2. Click on `DIGITALOCEAN_SSH_KEY`
3. Make sure it looks like this (exact format):

```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
QyNTUxOQAAACDdlweyviYy0+XNZ75Ec4nF+mphe1bWhcp2/632i8KMxAAAAJj8sDFU/LAx
VAAAAAtzc2gtZWQyNTUxOQAAACDdlweyviYy0+XNZ75Ec4nF+mphe1bWhcp2/632i8KMxA
AAAEBRRX1z5+PEQSsTeOLcz6uWGxX4kH14y+xl3jhnl0AE8d2XB7K+JjLT5c1nvkRzicX6
amF7VtaFynb/rfaLwozEAAAAFWhvc2VhZ2lsbmlnQGdtYWlsLmNvbQ==
-----END OPENSSH PRIVATE KEY-----
```

**Common Issues:**
- ❌ Extra spaces at start/end
- ❌ Missing BEGIN/END lines
- ❌ Line breaks in wrong places
- ❌ Extra characters

---

## 🔧 SOLUTION 2: Set Up Git Repository on Server

The workflow tries to run `git pull`, but the server might not have git initialized.

**Check if git repo exists:**
```bash
ssh root@134.199.174.78 "cd /var/www/html && git status"
```

**If not a git repo, initialize it:**
```bash
ssh root@134.199.174.78 "cd /var/www/html && git init && git remote add origin https://github.com/hoseagilnig/PNGMC.git && git fetch origin && git checkout -b main && git branch --set-upstream-to=origin/main main"
```

---

## 🔧 SOLUTION 3: Test SSH Connection Manually

**Test if your key works:**
```bash
ssh -i C:\Users\ecdis\.ssh\id_ed25519 root@134.199.174.78 "echo 'SSH works!'"
```

If this works, the key is correct. The issue is in GitHub secrets format.

---

## 🔧 SOLUTION 4: Use RSA Key Instead (If Ed25519 Fails)

Some SSH actions have issues with Ed25519. Generate RSA key:

```bash
# Generate RSA key
ssh-keygen -t rsa -b 4096 -C "github-actions-deploy" -f C:\Users\ecdis\.ssh\github_deploy_rsa

# Copy public key to server
type C:\Users\ecdis\.ssh\github_deploy_rsa.pub | ssh root@134.199.174.78 "cat >> ~/.ssh/authorized_keys"

# Get private key
type C:\Users\ecdis\.ssh\github_deploy_rsa
```

Then use the RSA private key in GitHub secrets.

---

## 🔧 SOLUTION 5: Check Server SSH Configuration

**Verify SSH is working:**
```bash
ssh root@134.199.174.78 "cat /etc/ssh/sshd_config | grep -i 'PubkeyAuthentication\|AuthorizedKeysFile'"
```

Should show:
```
PubkeyAuthentication yes
AuthorizedKeysFile .ssh/authorized_keys
```

---

## ✅ QUICK FIX CHECKLIST

- [ ] SSH key in GitHub secrets has exact format (no extra spaces)
- [ ] Public key is on server: `~/.ssh/authorized_keys`
- [ ] Git repository is initialized on server
- [ ] Test SSH manually: `ssh root@134.199.174.78`
- [ ] Try RSA key if Ed25519 doesn't work

---

**Let me check the server setup first!**

