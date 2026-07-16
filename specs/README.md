# Specs

Feature behavior for Sproutset is described here **before** it is built. A spec is the
source of truth for what a feature does; the Pest suite is the executable proof that it
does it.

## Convention

- One Markdown file per feature, named after the feature (`kebab-case.md`).
- Each spec is **narrative + Gherkin**: a short prose description of intent, followed by
  `Scenario:` blocks written in Given/When/Then.
- Every `Scenario` maps **1:1** to a named Pest test, listed in the spec's
  **Acceptance criteria** section. Scenario and test are kept in sync — changing one
  without the other is a bug.

## Workflow

1. Copy [`_template.md`](_template.md) to `specs/<feature>.md`.
2. Fill in the narrative and scenarios. Get agreement on behavior *before* writing code.
3. Implement, writing one Pest test per scenario as you go.
4. Cross-link: each scenario names its test; the Acceptance criteria section lists them.

## Files

- [`_template.md`](_template.md) — skeleton for a new spec.
