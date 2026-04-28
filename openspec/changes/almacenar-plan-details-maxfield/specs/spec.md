# Delta for entity/maxfield

## ADDED Requirements

### Requirement: Maxfield MUST have planResults field
The Maxfield entity MUST have a `planResults` field of type JSON (nullable) that stores the parsed Maxfield Plan Results as an associative array.

#### Scenario: Entity has planResults field
- GIVEN the Maxfield entity exists
- WHEN the entity is loaded
- THEN it MUST have a `planResults` field accessible via `getPlanResults()`

---

# Delta for controller/maxfield

## ADDED Requirements

### Requirement: Plan results MUST be parsed and stored after export
The system MUST parse the "Maxfield Plan Results" section from `log.txt` when the export process finishes and store it in `planResults`.

#### Scenario: Export completes successfully
- GIVEN the export process has finished (log.txt contains 'Total maxfield runtime')
- WHEN the status polling runs
- THEN the system MUST parse the plan results from log.txt
- AND save them to the `planResults` field of the Maxfield entity

#### Scenario: Export fails
- GIVEN the export process failed (log.txt contains 'Traceback')
- WHEN the status polling runs
- THEN the system MUST NOT attempt to parse plan results

---

# Delta for template/maxfield

## ADDED Requirements

### Requirement: Show page MUST display Plan Results tab
The system MUST display a new "Plan Results" tab in the maxfield show page (`/maxfield/show/{id}`).

#### Scenario: Show page with stored results
- GIVEN the Maxfield has `planResults` stored
- WHEN the user views `/maxfield/show/{id}`
- THEN the page MUST show a "Plan Results" tab
- AND display portals, links, fields, max keys needed, and all AP values

#### Scenario: Show page without results
- GIVEN the Maxfield has NO `planResults` stored
- WHEN the user views `/maxfield/show/{id}`
- THEN the page MUST still show the "Plan Results" tab
- AND display "No results stored yet"

## Data Format

The `planResults` field MUST store the following associative array:

| Key | Type | Description |
|-----|------|-------------|
| `portals` | int | Number of portals |
| `links` | int | Number of links |
| `fields` | int | Number of fields |
| `max_keys_needed` | int | Maximum keys needed |
| `ap_from_portals` | int | AP from portals |
| `ap_from_links` | int | AP from links |
| `ap_from_fields` | int | AP from fields |
| `total_ap` | int | Total AP |
