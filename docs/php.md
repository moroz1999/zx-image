# PHP

## Core Principles
- Use rules of SOLID principles.
- Use strict mode.
- ALWAYS use full variable names.
- ALWAYS use imports (`use`) for classes and namespaces. Fully qualified names in the code are prohibited.
- NEVER add unnecessary duplicate type casting.
- Use typed constants (e.g., `public const int MY_CONSTANT = 1;`).
- Place constants and variables at the beginning of the class, before any methods.

## Immutability and Type Safety
- DTOs must be 100% immutable. Use `readonly class` and constructor property promotion.
- Services and other stateless classes should be marked as `readonly class` if all their properties are immutable (e.g., dependencies injected via constructor). When a class is `readonly`, individual `readonly` modifiers on properties are redundant and should be omitted.
- Do NOT write PHPDoc `/** @var ... */` for `getService` calls if the class name is explicitly provided as the first argument (e.g. `getService(MyService::class)`). Modern IDEs and Psalm can infer the type from the class string.

## Coding Style
- Methods must follow SRP: each method does one thing. Extract private methods if a method handles multiple concerns or becomes hard to read at a glance.
- If nesting exceeds 2–3 levels, refactor: extract methods, use early returns, or split the logic.
- Avoid "comment ladders" (multiple sequential comments describing every line of code).
- Avoid inline method calls in conditions if they represent a state. Assign the result to a descriptive variable instead:
  ```php
  $isEditable = $element->isEditable();
  if ($isEditable) { ... }
  ```
  instead of `if ($element->isEditable()) { ... }`.
- Do NOT add a BOM header to any file.
- ALWAYS use strict comparisons (`===`, `!==`). Avoid "falsy" and "truthy" checks (e.g., use `if ($var === true)` instead of `if ($var)`).
- Do NOT scatter `(int)/(float)/(bool)/(string)` casts in DTO constructors, service mappers, or other consumer code when the source is a `structureElement` magic-property whose `@property` docblock disagrees with the runtime value (e.g. `@property int $downloads` on `zxReleaseElement` while the DB column is `text` and the magic-property returns a string). The docblock is not enforced — magic-property hands back whatever the DB stored. Type the source instead:
    - Always verify legacy magic-property PHPDoc against the element's `$moduleStructure` before relying on it. Fields declared as `'text'` in `$moduleStructure` must be treated as strings even when their names look numeric (e.g. `votesAmount`, `commentsAmount`, `plays`, `downloads`).
    - Add a typed accessor on the element class (e.g. `public function getDownloadsCount(): int { return (int)$this->downloads; }`) so the conversion lives in exactly one place.
    - Use the **`is` prefix** for boolean accessors (e.g. `isHtmlDescription(): bool`, `isVotingDenied(): bool`). Do NOT use `get` for booleans — `getHtmlDescription()` is wrong; the verb prefix is what makes the call site read like a predicate. Use `has` only when the meaning is presence/cardinality (`hasReleases()`), not for plain boolean flags. `get` stays for non-boolean accessors (`getDownloadsCount()`, `getVotes()`).
    - Mark the legacy `@property` line as `@deprecated use {@see self::xxx()}` with a short reason (e.g. "DB column is text — magic-property returns string"). This gives IDE/Psalm a single source of truth and surfaces remaining direct-property readers on hover.
    - Call the accessor from DTO builders and services.
      Existing direct-property usages elsewhere in legacy code can stay until they're touched — the deprecation tag flags them on read.
- Do NOT use magic numbers. Use class constants for single values or Enums for sets of related values.
- Do NOT use `const array` for a closed set of allowed string/int values (e.g., allowed sort columns, directions, statuses). Use a backed `enum` instead — it provides type safety, exhaustiveness checks, and eliminates `in_array` validation boilerplate.

## Post-Task Checklist
- After finishing work on any PHP files, request IDE diagnostics (errors, warnings, notices) for all modified files via the MCP IDE tool and fix all reported issues in added code before considering the task done.