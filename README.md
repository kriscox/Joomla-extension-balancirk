# Joomla extension balancirk

## Push branch to GitHub (quick guide)

If you created a branch in Codex and want it on GitHub, run these commands in a terminal
that has access to your git checkout:

```bash
cd /workspace/Joomla-extension-balancirk
git remote set-url origin https://github.com/kriscox/joomla-extension-balancirk.git 2>/dev/null || git remote add origin https://github.com/kriscox/joomla-extension-balancirk.git
git push -u origin <branch-name>
```

Examples:

```bash
git push -u origin pwa-ready
# or
git push -u origin feat/package-and-pwa-ready
```

If the Codex chat view does not show a terminal, open the task in Code view and use the terminal panel,
or push from your local machine (Git Bash / VS Code terminal).
