# Environment Configuration Setup

## Important: Protecting Sensitive Data

This project uses `.env` files to store sensitive API keys and configuration. These files are **already protected** from being committed to Git.

## What's Protected

- `ai_service/.env` - Contains your actual API keys (NEVER commit this)
- Any `.env` files in the project root or subdirectories

## Setup Instructions

### For New Developers

1. Copy the example environment file:
   ```bash
   copy ai_service\.env.example ai_service\.env
   ```

2. Edit `ai_service/.env` and add your actual API keys:
   - Get Gemini API key from: https://makersuite.google.com/app/apikey
   - Replace `your_gemini_api_key_here` with your actual key

3. Never commit the `.env` file - it's already in `.gitignore`

### Verification

To verify your `.env` file won't be committed:

```bash
git status
```

You should NOT see `ai_service/.env` in the list of files to be committed.

### If .env Was Already Committed

If you accidentally committed `.env` before, remove it from Git history:

```bash
git rm --cached ai_service/.env
git commit -m "Remove .env from version control"
```

## Files in Version Control

✅ **Safe to commit:**
- `.env.example` - Template file with placeholder values
- `.gitignore` - Protects sensitive files

❌ **Never commit:**
- `.env` - Contains actual API keys and secrets
- Any file with real credentials
