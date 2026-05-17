# AGENTS.md

This file contains the most CRITICAL rules that ALL agents must follow.
You MUST read the MD files linked in this document which are relevant to the current task.

## CRITICAL RULES

### General
- Keep it simple, don't invent unnecessary checks.
- Before each new task, read `AGENTS.md` and review the relevant rules if task scope differs from previous.
- All documentation and code comments MUST be in English, even if the user communicates in Russian or another language.
- After completing a task, re-read the task description and verify every point.
- After finishing the task code, it is necessary to read the rules on this topic from the .md docs once again and double-check your changes.
- Before starting a task, you MUST read the relevant documents from the DOCUMENTATION TREE below.
- Documentation updates must be placed in the appropriate .md file (e.g., PHP rules in php.md).
- Any new knowledge about functionality must be added to separate sub-documents within `domain.md`.
- Documentation additions in `docs` must be concise, clear, and only about the core points.
- ALWAYS add newly created files to GIT immediately after creation.
- When the IDE is in 'Ask' (readonly) mode, it is STRICTLY FORBIDDEN to do anything except answering the user's question. No file modifications or tool calls that change state are allowed.
- ALWAYS use MCP tools (JetBrains IDE) when available for code search, file reading, navigation, and locating files, methods, and classes instead of Grep/Glob/Read/Bash.
- When checking file problems via IDE (`get_file_problems`), ALWAYS request ALL severity levels (`errorsOnly: false`). Never use `errorsOnly: true` or rely on the default — weak warnings (unused imports, unused parameters, etc.) must be caught too.
- Do not scan the whole project by file extension. Use targeted paths or direct file reads instead.
- Do not run naive recursive searches over the entire repository. Pick the specific directories from the documented project structure that match the task.

## DOCUMENTATION TREE

Read ONLY the documents relevant to your task.

### Core Documentation
- **[docs/domain.md](docs/domain.md)** - Project domain and entities

### Backend (PHP)
- **[docs/php.md](docs/php.md)** - PHP coding standards, DDD, SOLID, immutability, type safety

## COMMANDS

```bash
# PHP tests
composer test

# Static analysis
composer psalm
```