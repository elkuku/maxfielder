# Proposal: Almacenar Plan Details Maxfield

## Intent

Store Maxfield Plan Results (portales, links, fields, AP, etc.) in DB and display in new "Plan Results" tab. Currently this data lives only in log.txt output.

## Scope

### In Scope
- Add `planResults` JSON field to Maxfield entity
- Parse "Maxfield Plan Results" section from log.txt during status polling
- Add new "Plan Results" tab in result.html.twig
- Create database migration

### Out of Scope
- Changes to Python script (maxfield logic)
- Changes to other entities
- Bulk operations

## Approach

1. **Entity**: Add `planResults` (Types::JSON, nullable) to `src/Entity/Maxfield.php` following jsonData/userData pattern
2. **Parsing**: In `maxfield_status` polling, when 'Total maxfield runtime' detected in log.txt, parse "Maxfield Plan Results" section and save to `planResults`
3. **Display**: Add "Plan Results" tab in `templates/maxfield/result.html.twig` showing the data

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `src/Entity/Maxfield.php` | Modified | Add `planResults` JSON field |
| `src/Controller/MaxFieldsController.php` | Modified | Parse log.txt in status polling |
| `templates/maxfield/result.html.twig` | Modified | Add Plan Results tab |
| Migration file | New | Auto-generated via Doctrine |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| log.txt format changes break parser | Medium | Robust regex, graceful handling |
| Async process edge cases | Low | Existing polling already handles lifecycle |

## Rollback Plan

1. Remove `planResults` via migration rollback
2. Revert twig changes (remove tab)
3. No data loss — field is additive and nullable

## Success Criteria

- [ ] `planResults` field exists in Maxfield entity, migration applied
- [ ] After export completes, `planResults` populated in DB
- [ ] New "Plan Results" tab shows data in show page
