# Design: Almacenar Plan Details Maxfield

## Technical Approach

Add `planResults` JSON field to Maxfield entity following existing `jsonData`/`userData` pattern. When `MaxfieldStatus::fromMaxfield()` detects 'Total maxfield runtime' in log.txt, parse the "Maxfield Plan Results" section and persist to DB. Add "Plan Results" tab in result.html.twig rendering the stored data.

## Architecture Decisions

| Option | Tradeoff | Decision |
|--------|----------|----------|
| JSON field vs 8 individual columns | JSON: flexible, single migration. Columns: queryable, typed | **JSON** — follows existing entity pattern (jsonData, userData) |
| Parse in polling vs lazy in show | Polling: parse once. Lazy: parse every view | **Polling** — data ready when user views, single source of truth in DB |
| Regex vs line-parser for log | Regex: fragile. Line-parser: robust against format changes | **Line-parser** — iterate lines, extract key-value pairs |

## Data Flow

```
[Python script finishes]
       ↓
[MaxfieldStatus::fromMaxfield() runs]
       ↓
[Check log.txt for 'Total maxfield runtime']
       ↓
[Parse "Maxfield Plan Results" section → extract 8 values]
       ↓
[Set planResults on Maxfield entity]
       ↓
[EntityManager->flush()]
       ↓
[User visits /maxfield/show/{id}]
       ↓
[Twig renders "Plan Results" tab with maxfield.planResults]
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `src/Entity/Maxfield.php` | Modify | Add `planResults` field (Types::JSON, nullable), getter, setter |
| `src/Type/MaxfieldStatus.php` | Modify | Add parsing logic in `fromMaxfield()` when status='finished', call new parse method |
| `src/Controller/MaxFieldsController.php` | Modify | Inject EntityManager in status route, flush after parsing (or handle in MaxfieldStatus) |
| `templates/maxfield/result.html.twig` | Modify | Add "Plan Results" tab with table showing portals, links, fields, keys, AP values |
| `src/Service/MaxFieldHelper.php` | Modify | Add `parsePlanResults(string $log): ?array` method |
| `Migrations/VersionXXXXXX.php` | Create | Auto-generated via `make:migration` after entity change |

## Interfaces / Contracts

```php
// Maxfield entity new field (follows jsonData pattern)
#[Column(type: Types::JSON, nullable: true)]
private ?array $planResults = null;

public function getPlanResults(): ?array
{
    return $this->planResults;
}

public function setPlanResults(?array $planResults): self
{
    $this->planResults = $planResults;
    return $this;
}

// Parser output format (MaxFieldHelper::parsePlanResults)
$planResults = [
    'portals' => 36,
    'links' => 94,
    'fields' => 84,
    'max_keys_needed' => 10,
    'ap_from_portals' => 63000,
    'ap_from_links' => 29422,
    'ap_from_fields' => 105000,
    'total_ap' => 197422,
];
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | Parser extracts correct values from log string | PHPUnit test with sample log.txt content |
| Integration | Status polling stores planResults on completion | Test `MaxfieldStatus::fromMaxfield()` with finished log |
| UI | Plan Results tab renders correctly | Twig template test or manual verification |

## Migration / Rollout

1. Add `planResults` field to Maxfield entity
2. Run `symfony console make:migration` to generate migration
3. Run `symfony console doctrine:migrations:migrate -n`
4. No feature flags needed — new nullable field, no breaking change

## Open Questions

- [ ] Should we also store the raw log.txt path reference? (Not needed, already in Maxfield::path)
- [ ] What happens if parsing fails? (Skip silently, field remains null, user sees "No results")
