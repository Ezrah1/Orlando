# Git Workflow Guide

## Quick Start Commands

### Daily Development

```bash
# Check status
git status

# Add changes
git add .

# Commit with descriptive message
git commit -m "Description of what was changed"

# Push to remote (if remote is set up)
git push origin main
```

### Feature Development

```bash
# Create feature branch
git checkout -b feature/new-feature-name

# Make changes and commit
git add .
git commit -m "Add new feature: description"

# Switch back to main and merge
git checkout main
git merge feature/new-feature-name

# Delete feature branch (optional)
git branch -d feature/new-feature-name
```

### Bug Fixes

```bash
# Create hotfix branch
git checkout -b hotfix/bug-description

# Fix the bug and commit
git add .
git commit -m "Fix: description of the bug fix"

# Merge to main
git checkout main
git merge hotfix/bug-description
```

## Best Practices

### Commit Messages

- Use present tense: "Add feature" not "Added feature"
- Be descriptive but concise
- Start with a verb: Add, Fix, Update, Remove, Refactor
- Examples:
  - `Add user authentication system`
  - `Fix room booking validation bug`
  - `Update admin dashboard layout`
  - `Refactor database connection code`

### Branch Naming

- `feature/` - for new features
- `hotfix/` - for urgent bug fixes
- `bugfix/` - for regular bug fixes
- `refactor/` - for code refactoring
- `docs/` - for documentation updates

### File Management

- Don't commit sensitive files (database credentials, API keys)
- Don't commit temporary files or logs
- Don't commit large binary files unless necessary
- Keep `.gitignore` updated

## Common Scenarios

### Undo Last Commit

```bash
# Keep changes but undo commit
git reset --soft HEAD~1

# Undo commit and discard changes
git reset --hard HEAD~1
```

### View Changes

```bash
# See what files changed
git status

# See detailed changes
git diff

# See commit history
git log --oneline
```

### Stash Changes

```bash
# Save current work temporarily
git stash

# Apply stashed changes
git stash pop

# List stashes
git stash list
```

## Remote Repository Setup

### Add Remote (First time only)

```bash
git remote add origin <repository-url>
git branch -M main
git push -u origin main
```

### Clone Existing Repository

```bash
git clone <repository-url>
cd Hotel
```

## Troubleshooting

### Merge Conflicts

1. Git will show conflicted files
2. Edit files to resolve conflicts
3. Remove conflict markers (`<<<<<<<`, `=======`, `>>>>>>>`)
4. Add resolved files: `git add .`
5. Complete merge: `git commit`

### Reset to Remote

```bash
# If local changes conflict with remote
git fetch origin
git reset --hard origin/main
```

## Team Collaboration

### Before Starting Work

```bash
git pull origin main
```

### After Completing Work

```bash
git add .
git commit -m "Clear description of changes"
git push origin main
```

### Code Review Process

1. Create feature branch
2. Make changes and commit
3. Push feature branch
4. Create pull request
5. Get code review
6. Merge after approval

## Security Notes

- Never commit passwords, API keys, or database credentials
- Use environment variables for sensitive data
- Keep `.gitignore` updated to exclude sensitive files
- Review commits before pushing to ensure no sensitive data is included
