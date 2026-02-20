# EndangeredMap - Database Structure

## Overview

The plugin stores endangered species conservation data across 66 European countries/regions. Data is sourced from an Excel file (`endangered-species.xlsx`) containing 2 sheets: species data with country statuses, and status code definitions.

## Tables

### `pensoft_endangeredmap_species`

Stores taxonomy information for each species. One row per species (~2,138 rows).

| Column | Type | Description |
|---|---|---|
| id | int (PK, auto-increment) | Primary key |
| internal_name | varchar(500), nullable | Full internal species name |
| family | varchar(255), nullable | Taxonomic family |
| subfamily | varchar(255), nullable | Taxonomic subfamily |
| tribe | varchar(255), nullable | Taxonomic tribe |
| genus | varchar(255), nullable | Genus |
| subgenus | varchar(255), nullable | Subgenus |
| species | varchar(255), nullable | Species epithet |
| taxonomic_authority | varchar(500), nullable | Naming authority |
| created_at | timestamp | |
| updated_at | timestamp | |

### `pensoft_endangeredmap_statuses`

Stores the conservation status of a species in a specific country. One row per species-country combination. Empty cells (no data for that country) are skipped during import.

| Column | Type | Description |
|---|---|---|
| id | int (PK, auto-increment) | Primary key |
| species_id | unsigned int (FK) | References `species.id` (cascade delete) |
| country | varchar(255) | Country or region name (from Excel column headers) |
| status | varchar(50) | Status acronym (e.g. CR, VU, EN). See acronyms table |
| created_at | timestamp | |
| updated_at | timestamp | |

**Indexes:** `species_id`, `country`, `status`, composite `[country, status]`

### `pensoft_endangeredmap_acronyms`

Lookup table for status code definitions (19 rows).

| Column | Type | Description |
|---|---|---|
| id | int (PK, auto-increment) | Primary key |
| acronym | varchar(50), unique | Status code (e.g. CR, VU, EN, LC) |
| meaning | varchar(255) | Full meaning (e.g. "Critically Endangered") |
| created_at | timestamp | |
| updated_at | timestamp | |

## Relationships

```
species (1) ──< statuses (many)
    └── species.id = statuses.species_id (cascade delete)

acronyms (standalone lookup)
    └── acronyms.acronym matches statuses.status
```

- Deleting a species automatically deletes all its status records.
- The `acronyms` table is not linked by foreign key; it serves as a reference for decoding `statuses.status` values.

## Common Queries

**All endangered species in a country:**
```sql
SELECT s.*, st.status
FROM pensoft_endangeredmap_species s
JOIN pensoft_endangeredmap_statuses st ON st.species_id = s.id
WHERE st.country = 'Germany';
```

**Status of a species across all countries:**
```sql
SELECT st.country, st.status, a.meaning
FROM pensoft_endangeredmap_statuses st
LEFT JOIN pensoft_endangeredmap_acronyms a ON a.acronym = st.status
WHERE st.species_id = 42;
```

**Count species per status in a country:**
```sql
SELECT st.status, a.meaning, COUNT(*) as total
FROM pensoft_endangeredmap_statuses st
LEFT JOIN pensoft_endangeredmap_acronyms a ON a.acronym = st.status
WHERE st.country = 'France'
GROUP BY st.status, a.meaning
ORDER BY total DESC;
```

## Data Import

```bash
php artisan endangeredmap:import /path/to/endangered-species.xlsx
```

- The file path argument is **required**.
- The command truncates all tables before importing (safe to re-run).
- Import runs inside a database transaction (all-or-nothing).
- Empty cells in the Excel are skipped (not stored as blank status rows).
- Statuses are batch-inserted 1,000 rows at a time for performance.

### Excel file structure

- **Sheet 1 ("Data"):** Columns A-H = species taxonomy, Columns I-BV = country statuses (header row = country names)
- **Sheet 2 ("Acronyms"):** Column A = acronym, Column B = meaning
