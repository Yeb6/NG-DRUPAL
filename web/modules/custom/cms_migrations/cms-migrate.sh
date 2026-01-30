#!/bin/bash

# CMS Migration Helper Script
# Usage: ./cms-migrate.sh [command] [project] [content_type] [options]

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DRUPAL_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"

cd "$DRUPAL_ROOT"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to show usage
show_usage() {
    echo "CMS Migration Helper Script"
    echo
    echo "Usage: $0 [command] [project] [content_type] [options]"
    echo
    echo "Commands:"
    echo "  list-projects              List available projects"
    echo "  list-migrations           List all CMS migrations"
    echo "  status [project]          Show migration status for a project"
    echo "  import [project] [type]   Import specific content type from project"
    echo "  import-all [project]      Import all content from project"
    echo "  rollback [project] [type] Rollback specific migration"
    echo "  update-topics [project]              Update supplementary topics entity references"
    echo
    echo "Projects:"
    echo "  WMRUSAPC                  WMR USA PC project"
    echo "  WMRUSAVan                 WMR USA Van project"
    echo
echo "Content Types:"
echo "  fs_article               FS Articles (templateName: pt_red_article)"
    echo
    echo "Options:"
    echo "  --limit=N                 Limit number of items to import"
    echo "  --update                  Update existing content"
    echo
    echo "Examples:"
    echo "  $0 list-projects"
    echo "  $0 import WMRUSAPC fs_article"
    echo "  $0 import WMRUSAVan fs_article --limit=10"
    echo "  $0 import-all WMRUSAPC"
    echo "  $0 status WMRUSAPC"
    echo "  $0 rollback WMRUSAPC fs_article"
    echo "  $0 update-topics WMRUSAPC"
}

# Function to list available projects
list_projects() {
    print_info "Available projects in cms-source directory:"
    echo

    source_dir="$DRUPAL_ROOT/modules/custom/cms_migrations/cms-source"

    if [ ! -d "$source_dir" ]; then
        print_error "Source directory not found: $source_dir"
        return 1
    fi

    for project_dir in "$source_dir"/*; do
        if [ -d "$project_dir" ]; then
            project_name=$(basename "$project_dir")
            json_count=$(find "$project_dir" -name "*.json" | wc -l)
            echo "  $project_name ($json_count JSON files)"
        fi
    done
}

# Function to list migrations
list_migrations() {
    print_info "Available CMS migrations:"
    echo
    vendor/bin/drush migrate:status --group=cms_migrations
}

# Function to show status for a project
show_status() {
    local project="$1"

    if [ -z "$project" ]; then
        print_error "Project name required"
        return 1
    fi

    print_info "Migration status for project: $project"
    echo

    local project_lower=$(echo "$project" | tr '[:upper:]' '[:lower:]')

    echo "=== FS Articles ==="
    vendor/bin/drush migrate:status "cms_articles_$project_lower"
}

# Function to import content
import_content() {
    local project="$1"
    local content_type="$2"
    shift 2
    local options="$@"

    if [ -z "$project" ] || [ -z "$content_type" ]; then
        print_error "Project and content type required"
        return 1
    fi

    local project_lower=$(echo "$project" | tr '[:upper:]' '[:lower:]')
    local migration_id="cms_${content_type}s_$project_lower"

    print_info "Importing $content_type content from project: $project"
    print_info "Migration ID: $migration_id"
    echo

    vendor/bin/drush migrate:import "$migration_id" $options

    if [ $? -eq 0 ]; then
        print_success "Successfully imported $content_type content from $project"
    else
        print_error "Failed to import $content_type content from $project"
    fi
}

# Function to import all content for a project
import_all() {
    local project="$1"
    shift
    local options="$@"

    if [ -z "$project" ]; then
        print_error "Project name required"
        return 1
    fi

    print_info "Importing all content from project: $project"
    echo

    echo "=== Importing FS Articles ==="
    import_content "$project" "fs_article" $options
    echo

    echo "=== Importing News ==="
    import_content "$project" "drupal_news" $options

    print_success "Completed importing all content from $project"
}

# Function to rollback migration
rollback_migration() {
    local project="$1"
    local content_type="$2"

    if [ -z "$project" ] || [ -z "$content_type" ]; then
        print_error "Project and content type required"
        return 1
    fi

    local project_lower=$(echo "$project" | tr '[:upper:]' '[:lower:]')
    local migration_id="cms_${content_type}s_$project_lower"

    print_warning "Rolling back $content_type migration from project: $project"
    print_info "Migration ID: $migration_id"
    echo

    vendor/bin/drush migrate:rollback "$migration_id"

    if [ $? -eq 0 ]; then
        print_success "Successfully rolled back $content_type migration from $project"
        
        
    else
        print_error "Failed to rollback $content_type migration from $project"
    fi
}

# Function to update supplementary topics entity references
update_supplementary_topics() {
    local project="$1"

    if [ -z "$project" ]; then
        print_error "Project name required"
        return 1
    fi

    print_info "Updating supplementary topics entity references for project: $project"
    echo

    vendor/bin/drush cms-migrations:update-supplementary-topics "$project"

    if [ $? -eq 0 ]; then
        print_success "Successfully updated supplementary topics for $project"
    else
        print_error "Failed to update supplementary topics for $project"
        return 1
    fi
}

# Main script logic
case "$1" in
    "list-projects")
        list_projects
        ;;
    "list-migrations")
        list_migrations
        ;;
    "status")
        show_status "$2"
        ;;
    "import")
        import_content "$2" "$3" "${@:4}"
        ;;
    "import-all")
        import_all "$2" "${@:3}"
        ;;
    "rollback")
        rollback_migration "$2" "$3"
        ;;
    "update-topics")
        update_supplementary_topics "$2"
        ;;
    "help"|"-h"|"--help"|"")
        show_usage
        ;;
    *)
        print_error "Unknown command: $1"
        echo
        show_usage
        exit 1
        ;;
esac
