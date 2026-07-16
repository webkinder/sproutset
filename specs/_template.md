# <Feature name>

<One or two sentences: what this feature is and why it exists.>

## Behavior

<Prose description of the feature's intent, inputs, outputs, and edge cases. Enough for
a reader to understand what "correct" means without reading the code.>

## Scenarios

```gherkin
Scenario: <short name — matches a Pest test>
  Given <starting state>
  When <action>
  Then <expected outcome>

Scenario: <another named scenario>
  Given <...>
  When <...>
  Then <...>
```

## Acceptance criteria

Each scenario above maps 1:1 to a Pest test:

| Scenario | Pest test |
| --- | --- |
| `<scenario name>` | `tests/Feature/<File>Test.php` → `it('<test name>')` |
| `<scenario name>` | `tests/Feature/<File>Test.php` → `it('<test name>')` |
