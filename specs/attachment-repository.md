# Attachment repository

The single boundary between Sproutset and WordPress media retrieval. Component logic
resolves attachments through this contract so it can run against an in-memory fake with
no WordPress runtime, while the real WordPress-backed implementation stays isolated
behind the same interface.

## Behavior

`AttachmentRepository::find(int $id)` resolves a WordPress attachment ID to an
`Attachment` value object carrying the source's identity and intrinsic dimensions
(`id`, `url`, `width`, `height`). Size selection and srcset assembly are **not** the
repository's concern — they live in the component layer; the repository only fetches the
underlying source.

When no attachment exists for the given ID, `find` returns `null` (mirroring WordPress
media functions returning `false` for a missing attachment). Callers branch on `null`
rather than receiving an empty or partial value object.

Two implementations satisfy the contract and must behave identically:

- `FakeAttachmentRepository` — in-memory, seeded with `add()`; the fast Testbench lane.
- `WpAttachmentRepository` — calls WordPress media functions; the only code needing a
  live WordPress and the `wp-phpunit` integration lane. **Deferred** until the first
  WP-calling feature lands; a shared contract test will pin both implementations to the
  same scenarios.

## Scenarios

```gherkin
Scenario: resolves a seeded attachment by id
  Given an attachment with id 42, url, width and height is stored in the repository
  When find is called with id 42
  Then the matching Attachment value object is returned

Scenario: returns null for an unknown id
  Given an empty repository
  When find is called with an id that was never stored
  Then null is returned
```

## Acceptance criteria

Each scenario above maps 1:1 to a Pest test:

| Scenario | Pest test |
| --- | --- |
| `resolves a seeded attachment by id` | `tests/Feature/AttachmentRepositoryTest.php` → `it('resolves a seeded attachment by id')` |
| `returns null for an unknown id` | `tests/Feature/AttachmentRepositoryTest.php` → `it('returns null for an unknown id')` |
