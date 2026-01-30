# CMS Migrations Module

Drupal 10 custom module for migrating content from JSON files exported from FirstSpirit CMS to Drupal content types.

## Overview

This module provides migration functionality for importing FS Article and News content from JSON files into Drupal. It includes custom process plugins, Drush commands, and post-migration utilities for managing supplementary topics entity references.

## Features

- **JSON File Source Plugin**: Reads and processes JSON data from FirstSpirit exports
- **Custom Process Plugins**: Handles complex field mappings including:
  - Project taxonomy lookup and creation
  - Hierarchy taxonomy management
  - Paragraph creation for content sections
  - Attachments processing
  - Supplementary topics extraction
  - Language code mapping
  - Article type determination
- **Post-Migration Commands**: Update supplementary topics entity references after migration
- **Multi-Project Support**: Handles multiple projects (WMRUSAPC, WMRUSAVan, etc.)

## Installation

1. Place the module in `web/modules/custom/cms_migrations`
2. Import configuration:
   ```bash
   drush config:import -y
   drush entity:update -y
   ```
3. Enable the module:
```bash
   drush pm:enable cms_migrations -y
   ```

## JSON Data Structure

Place JSON files in the `cms-source` directory organized by project:

```
cms-source/
├── WMRUSAPC/
│   ├── WMRUSAPC1.json
│   └── WMRUSAPC2.json
└── WMRUSAVan/
    ├── WMRUSAVan1.json
    └── WMRUSAVan2.json
```

### Expected JSON Format

```json
{
  "_embedded": {
    "rh:doc": [
      {
        "_id": "article_unique_id_en_us",
        "fs_id": "article_unique_id",
        "headline": "Article Title",
        "fs_project_name": "ProjectName",
        "templateName": "pt_red_article",
        "articles": [...],
        ...
      }
    ]
  }
}
```

## Field Mappings

### FS Article Content Type

| JSON Field | Drupal Field | Description |
|------------|--------------|-------------|
| `_id` | `field_id` | Unique identifier with language suffix |
| `fs_id` | `field_source_id` | FS ID without language suffix |
| `headline` | `title` | Article title |
| `fs_project_name` | `field_project_name` | Project taxonomy reference |
| `parentIentifier` | `field_hierarchy` | Hierarchy taxonomy reference |
| `articles` | `field_create_section` | Content sections (paragraphs) |
| `articles` | `field_attachments` | File attachments |
| `articles` | `field_supplementary_topics` | Related articles references |
| `LastReleaseDatetime` | `field_release_date` | Release date |
| `last_release_article` | `field_last_update` | Last update checkbox |

## Usage

### Running Migrations

Use the provided shell script:

```bash
# Import articles from a project
./cms-migrate.sh import WMRUSAPC article

# Import all content from a project
./cms-migrate.sh import-all WMRUSAPC

# Rollback a migration
./cms-migrate.sh rollback WMRUSAPC article

# Check migration status
./cms-migrate.sh status WMRUSAPC
```

Or use Drush directly:

```bash
# Import articles
drush migrate:import cms_articles_wmrusavan

# Rollback migration
drush migrate:rollback cms_articles_wmrusavan

# Reset migration status
drush migrate:reset cms_articles_wmrusavan

# Check status
drush migrate:status cms_articles_wmrusavan
```

### Updating Supplementary Topics

After migration, run the supplementary topics update command to resolve entity references:

```bash
# Interactive - prompts for project selection
drush cms-migrations:update-supplementary-topics

# Or use the alias
drush cms-update-topics
```

**How it works:**
1. Reads `field_id` from each node (contains `_id` from JSON)
2. Searches JSON files to find matching article
3. Extracts supplementary topics from the `articles` array
4. Finds referenced nodes by their `field_source_id` (contains `fs_id`)
5. Updates `field_supplementary_topics` with proper entity references

## Drush Commands

### Migration Commands (via shell script)

- `./cms-migrate.sh import <PROJECT> <TYPE>` - Import content
- `./cms-migrate.sh rollback <PROJECT> <TYPE>` - Rollback migration
- `./cms-migrate.sh status <PROJECT>` - Show migration status
- `./cms-migrate.sh reset <PROJECT> <TYPE>` - Reset migration
- `./cms-migrate.sh import-all <PROJECT>` - Import all content types
- `./cms-migrate.sh list-projects` - List available projects

### Supplementary Topics Commands

- `drush cms-migrations:update-supplementary-topics` - Update supplementary topics (interactive)
- `drush cms-migrations:test-topics` - Test command functionality

## Adding New Projects

1. Create project folder in `cms-source/`:
   ```bash
   mkdir web/modules/custom/cms_migrations/cms-source/NEWPROJECT
   ```

2. Add JSON files to the folder

3. Create migration configuration files in `config/install/`:
   - `migrate_plus.migration.cms_articles_newproject.yml`
   - `migrate_plus.migration.cms_news_newproject.yml`

4. Update the project name in the migration YAML files

5. Reinstall or import configuration:
   ```bash
   drush config:import -y
   ```

## Troubleshooting

### Migration fails with field errors

**Solution**: Import configuration to ensure all fields exist:
```bash
drush config:import -y
drush entity:update -y
```

### Supplementary topics not updating

**Solution**: Ensure migration has run first and `field_id` and `field_source_id` are populated:
```bash
# Check if fields have data
drush sql-query "SELECT nid, field_id_value, field_source_id_value FROM node__field_id JOIN node__field_source_id USING(entity_id) LIMIT 5"
```

### JSON files not found

**Solution**: Verify file paths and permissions:
```bash
ls -la web/modules/custom/cms_migrations/cms-source/
```

## Module Structure

```
cms_migrations/
├── src/
│   ├── Drush/
│   │   └── Commands/
│   │       └── SupplementaryTopicsUpdateCommands.php
│   └── Plugin/
│       ├── migrate/
│       │   ├── process/
│       │   │   ├── JsonFieldMapper.php
│       │   │   ├── ProjectTaxonomyLookup.php
│       │   │   ├── LanguageMapper.php
│       │   │   ├── ArticleTypeMapper.php
│       │   │   ├── ParagraphCreator.php
│       │   │   ├── AttachmentsProcessor.php
│       │   │   └── SupplementaryTopicsProcessor.php
│       │   └── source/
│       │       └── JsonFileSource.php
├── config/
│   └── install/
│       ├── migrate_plus.migration.cms_articles_wmrusapc.yml
│       ├── migrate_plus.migration.cms_articles_wmrusavan.yml
│       └── migrate_plus.migration_group.cms_migrations.yml
├── cms-source/
│   ├── WMRUSAPC/
│   └── WMRUSAVan/
├── cms-migrate.sh
├── cms_migrations.info.yml
├── cms_migrations.services.yml
└── README.md
```

## Requirements

- Drupal 10.x
- PHP 8.1+
- Drush 12.x
- Required Drupal modules:
  - migrate
  - migrate_plus
  - migrate_tools
  - paragraphs

## Author

Custom module developed for FirstSpirit to Drupal content migration.

## Support

For issues or questions, refer to the migration logs:
```bash
drush watchdog:show --type=cms_migrations
```

