# Git Usage Guide for SMS2 Project

## 📋 Quick Start

Your project is already connected to GitHub:
- **Repository:** `https://github.com/hoseagilnig/PNGMC.git`
- **Branch:** `main`

## 🎯 Using Git in Cursor

### Method 1: Using Cursor's Source Control Panel (Recommended)

1. **Open Source Control:**
   - Click the Source Control icon in the left sidebar (looks like a branch symbol)
   - Or press `Ctrl+Shift+G` (Windows) / `Cmd+Shift+G` (Mac)

2. **View Changes:**
   - All modified files will appear in the Source Control panel
   - Green `U` = Untracked (new files)
   - Yellow `M` = Modified (changed files)
   - Red `D` = Deleted files

3. **Stage Changes:**
   - Click the `+` icon next to files to stage them
   - Or click "Stage All Changes" to stage everything

4. **Commit Changes:**
   - Type a commit message in the text box at the top
   - Click the checkmark (✓) or press `Ctrl+Enter`
   - Example messages:
     - "Add student profile photo upload feature"
     - "Fix SQL injection vulnerabilities"
     - "Update responsive dashboard headers"

5. **Push to GitHub:**
   - Click the "..." menu (three dots) in Source Control
   - Select "Push" or "Push to..."
   - Or use the sync icon if it appears

6. **Pull from GitHub:**
   - Click the "..." menu
   - Select "Pull" or "Pull, Rebase" or "Pull, Rebase and Push"

### Method 2: Using Terminal (If Git is Installed)

If you install Git for Windows, you can use these commands:

```bash
# Check status
git status

# Stage all changes
git add .

# Commit changes
git commit -m "Your commit message here"

# Push to GitHub
git push origin main

# Pull from GitHub
git pull origin main
```

## 📝 Common Git Workflows

### Daily Workflow

1. **Start of Day:**
   ```bash
   git pull origin main
   ```

2. **Make Changes:**
   - Edit files in Cursor
   - Files automatically show in Source Control panel

3. **Commit Changes:**
   - Stage files
   - Write commit message
   - Commit

4. **End of Day:**
   ```bash
   git push origin main
   ```

### Creating a New Feature Branch

1. In Source Control panel, click "..." menu
2. Select "Branch" → "Create Branch"
3. Name it (e.g., `feature/student-photos`)
4. Make changes and commit
5. Push branch: `git push origin feature/student-photos`
6. Create Pull Request on GitHub

## ⚠️ Important Notes

### Files NOT Tracked (in .gitignore)

These files are **NOT** committed to GitHub for security:
- `.env` files (contains API keys)
- `logs/` directory (error logs)
- `uploads/` directory (user-uploaded files)
- Database files (`.sql` dumps)
- Configuration files with sensitive data

### Files That ARE Tracked

- Source code (`.php` files)
- CSS and JavaScript files
- Documentation (`.md` files)
- Database schema files (`database/*.sql`)
- Template files (`.htaccess_production`)

## 🔒 Security Best Practices

1. **Never commit:**
   - Passwords
   - API keys
   - Database credentials
   - User-uploaded files
   - Log files

2. **Always review changes** before committing

3. **Use meaningful commit messages:**
   - ❌ Bad: "fix"
   - ✅ Good: "Fix SQL injection in applications.php"

4. **Commit frequently** (small, logical changes)

## 🐛 Troubleshooting

### "Git command not found"
- Install Git for Windows: https://git-scm.com/download/win
- Or use Cursor's built-in Git features (Source Control panel)

### "Authentication failed"
- You may need to set up GitHub credentials
- In Cursor: Settings → Git → Credential Helper
- Or use GitHub Personal Access Token

### "Merge conflicts"
- Cursor will highlight conflicts
- Choose "Accept Current", "Accept Incoming", or "Accept Both"
- Review carefully before committing

## 📚 Additional Resources

- [Git Documentation](https://git-scm.com/doc)
- [GitHub Guides](https://guides.github.com/)
- [Cursor Git Features](https://cursor.sh/docs)

---

**Current Repository:** `https://github.com/hoseagilnig/PNGMC.git`
**Default Branch:** `main`

