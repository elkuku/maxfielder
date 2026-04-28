# Tasks: Almacenar Plan Details Maxfield

## Phase1: Entity & Migration

- [x] 1.1 Add `planResults` property to `src/Entity/Maxfield.php` with `#[Column(type: Types::JSON, nullable: true)]`
- [x] 1.2 Add `getPlanResults(): ?array` getter method
- [x] 1.3 Add `setPlanResults(?array): static` setter method
- [x] 1.4 Run `symfony console make:migration` to generate migration
- [x] 1.5 Run `symfony console doctrine:migrations:migrate -n`

## Phase2: Parsing Logic

- [x] 2.1 Create `parsePlanResults(string $logContent): ?array` method in `src/Service/MaxFieldHelper.php`
- [x] 2.2 Implement line-parser: find "Maxfield Plan Results:" header, extract 8 key-value pairs (portals, links, fields, max_keys_needed, ap_from_portals, ap_from_links, ap_from_fields, total_ap)
- [x] 2.3 In `src/Controller/MaxFieldsController.php::status()`, when finished status detected, call parser and persist planResults on entity
- [x] 2.4 EntityManager::flush() called after setting planResults in controller

## Phase3: Template Tab

- [ ] 3.1 Add "Plan Results" tab button in `templates/maxfield/result.html.twig` nav section alongside existing tabs
- [ ] 3.2 Add tab content div with Stimulus target and `data-id="plan-results"`
- [ ] 3.3 Render planResults as HTML table with keys: Portals, Links, Fields, Max Keys Needed, AP from Portals, AP from Links, AP from Fields, Total AP
- [ ] 3.4 Handle null/empty case: show "No results stored yet" message

## Phase4: Testing

- [x] 4.1 Write unit test for `MaxFieldHelper::parsePlanResults()` with sample log.txt content containing "Maxfield Plan Results:" section
- [~] 4.2 Write integration test: simulate finished export in controller test, verify planResults persisted to DB (SKIPPED - complex setup requires full controller/container mock)
- [ ] 4.3 Manual verification: generate export via UI, confirm "Plan Results" tab displays correct data

## Notes

### Task 4.1 Completion
- Added 4 test methods to `tests/Service/MaxFieldHelperTest.php`:
  - `testParsePlanResultsExtractsCorrectValues`: Verifies all 8 fields parse correctly
  - `testParsePlanResultsReturnsNullWhenSectionMissing`: Missing section returns null
  - `testParsePlanResultsReturnsPartialResultsWhenSectionIncomplete`: Partial results handled
  - `testParsePlanResultsReturnsNullWhenOnlyHeader`: Header only (no key-values) returns null
- PHPUnit has dependency issue (php-code-coverage trait missing) - needs `composer install` to fix

### Task 4.2 Skipped
- Integration test requires mocking the full Symfony container, EntityManager, and log file
- The controller test would need KernelTestCase with service mocking
- Recommended: Add later when test infrastructure is set up properly

### Task 4.3 Manual Verification Steps
1. Start Docker service: `bin/start` or ensure Docker is running
2. Generate a new maxfield export via the UI (/maxfield)
3. Wait for export to complete (status polling detects 'Total maxfield runtime')
4. Check database: `SELECT planResults FROM maxfield WHERE path = '...'` - should be non-null JSON
5. Visit `/maxfield/show/{path}` and verify the "Plan Results" tab shows:
   - Portals: N
   - Links: N
   - Fields: N
   - Max Keys Needed: N
   - AP from Portals: N
   - AP from Links: N
   - AP from Fields: N
   - Total AP: N
