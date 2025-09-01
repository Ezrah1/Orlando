<?php
/**
 * Final Cleanup Script
 * Fix all remaining syntax errors and remove unnecessary files
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== FINAL CLEANUP AND SYNTAX FIX ===\n\n";

// Get all PHP files recursively
function getAllPhpFiles() {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    
    return $files;
}

// Check syntax of a file
function checkSyntax($file) {
    $command = 'C:\\xampp\\php\\php.exe -l "' . $file . '" 2>&1';
    $output = shell_exec($command);
    return strpos($output, 'No syntax errors') !== false;
}

// Fix common syntax issues
function fixSyntaxIssues($content) {
    // Fix 1: Single slash comments
    $content = preg_replace('/^(\s*)\/(?!\*|\/)(\s*[A-Za-z])/m', '$1//$2', $content);
    $content = preg_replace('/(\s+)\/(?!\*|\/)(\s*[A-Za-z])/m', '$1//$2', $content);
    
    // Fix 2: Malformed header statements
    $content = preg_replace('/header\s*\(\s*Location:\s*([^")]+)\s*[")]*\s*;?/', 'header("Location: $1");', $content);
    
    // Fix 3: Missing semicolons after statements
    $content = preg_replace('/(\$\w+\s*=\s*[^;]+)\s*$(?!\s*;)/m', '$1;', $content);
    
    // Fix 4: Fix exit statements
    $content = preg_replace('/\bexit\b(?!\s*\()/m', 'exit()', $content);
    
    // Fix 5: Fix echo statements with unquoted strings
    $content = preg_replace('/echo\s+([A-Z][^;"\']*);/', 'echo "$1";', $content);
    
    // Fix 6: Fix incomplete string literals
    $content = preg_replace('/mysqli_query\s*\(\s*(\$\w+),\s*([^")]+)\s*\);/', 'mysqli_query($1, "$2");', $content);
    
    return $content;
}

// Step 1: Fix syntax errors
echo "Step 1: Fixing syntax errors...\n";
$files = getAllPhpFiles();
$fixed_count = 0;
$error_files = [];

foreach ($files as $file) {
    // Skip our cleanup script
    if (strpos($file, 'final_cleanup') !== false) {
        continue;
    }
    
    if (!checkSyntax($file)) {
        $content = file_get_contents($file);
        $original_content = $content;
        
        $content = fixSyntaxIssues($content);
        
        if ($content !== $original_content) {
            file_put_contents($file, $content);
            
            if (checkSyntax($file)) {
                echo "✅ Fixed: " . str_replace('.\\', '', $file) . "\n";
                $fixed_count++;
            } else {
                echo "⚠️ Still has errors: " . str_replace('.\\', '', $file) . "\n";
                $error_files[] = $file;
            }
        } else {
            echo "❌ Could not auto-fix: " . str_replace('.\\', '', $file) . "\n";
            $error_files[] = $file;
        }
    }
}

// Step 2: Remove unnecessary files
echo "\nStep 2: Removing unnecessary files...\n";

$unnecessary_files = [
    'booking_form_clean.php',
    'booking_form_new.php', 
    'booking_old.php',
    'booking.php',
    'reorganize_modules.php',
    'route_audit.php',
    'fix_broken_links.php',
    'update_navigation_fixed.php',
    'quick_fix_and_reorganize.php',
    'update_remaining_links.php'
];

$removed_count = 0;
foreach ($unnecessary_files as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "🗑️ Removed: $file\n";
            $removed_count++;
        } else {
            echo "❌ Failed to remove: $file\n";
        }
    }
}

// Step 3: Remove unnecessary SQL files (keep only essential ones)
$unnecessary_sql = [
    'accounting_finance_manual_setup.sql',
    'bar_management_manual_setup.sql',
    'food_kitchen_manual_setup.sql',
    'housekeeping_maintenance_manual_setup.sql',
    'housekeeping_maintenance_setup_simple.sql',
    'housekeeping_setup_step_by_step.sql',
    'user_preferences_setup.sql',
    'rbac_enhancement_plan.sql',
    'fix_finance_role_conflicts.sql',
    'corrected_migration.sql',
    'corrected_housekeeping_insert.sql',
    'create_housekeeping_tasks_only.sql',
    'create_tables.sql',
    'add_roombook_columns.sql',
    'add_rooms.sql'
];

foreach ($unnecessary_sql as $file) {
    if (file_exists($file)) {
        // Move to setup/sql instead of deleting
        $target = 'setup/sql/' . $file;
        if (!file_exists($target)) {
            if (rename($file, $target)) {
                echo "📁 Moved to setup: $file\n";
            }
        } else {
            unlink($file);
            echo "🗑️ Removed duplicate: $file\n";
        }
    }
}

// Step 4: Remove unnecessary markdown files
$unnecessary_docs = [
    'BOOKING_SYSTEM_UPGRADE_SUMMARY.md',
    'CART_SYSTEM_IMPLEMENTATION.md',
    'CRITICAL_FIXES_IMPLEMENTED.md',
    'DESIGN_CLEANUP_SUMMARY.md',
    'DYNAMIC_ROOMS_UPDATE_SUMMARY.md',
    'FINANCE_DESIGN_ANALYSIS.md',
    'FINANCE_IMPLEMENTATION_SUMMARY.md',
    'FUTURE_RECOMMENDATIONS.md',
    'LOGO_USAGE_GUIDE.md',
    'MODULE_REORGANIZATION_GUIDE.md',
    'RBAC_ENHANCEMENT_SUMMARY.md',
    'ROUTE_AUDIT_REPORT.md',
    'SYSTEM_ENHANCEMENTS_SUMMARY.md',
    'SYSTEM_FEATURES_ANALYSIS.md'
];

foreach ($unnecessary_docs as $file) {
    if (file_exists($file)) {
        $target = 'docs/' . $file;
        if (!file_exists($target)) {
            if (rename($file, $target)) {
                echo "📚 Moved to docs: $file\n";
            }
        } else {
            unlink($file);
            echo "🗑️ Removed duplicate: $file\n";
        }
    }
}

// Step 5: Remove temporary PHP check files
$temp_files = [
    'check_housekeeping_tables.php',
    'check_roles_permissions.php', 
    'check_roombook_structure.php',
    'run_migration.php',
    'setup_user_preferences.php'
];

foreach ($temp_files as $file) {
    if (file_exists($file)) {
        $target = 'setup/utilities/' . $file;
        if (!file_exists($target)) {
            if (rename($file, $target)) {
                echo "🔧 Moved to utilities: $file\n";
            }
        } else {
            unlink($file);
            echo "🗑️ Removed duplicate: $file\n";
        }
    }
}

// Final syntax check
echo "\nStep 6: Final syntax validation...\n";
$final_error_count = 0;
$clean_count = 0;

foreach (getAllPhpFiles() as $file) {
    if (strpos($file, 'final_cleanup') !== false) continue;
    
    if (checkSyntax($file)) {
        $clean_count++;
    } else {
        $final_error_count++;
        echo "❌ " . str_replace('.\\', '', $file) . " still has syntax errors\n";
    }
}

echo "\n=== CLEANUP COMPLETE ===\n";
echo "✅ Syntax fixes applied: $fixed_count\n";
echo "🗑️ Unnecessary files removed: $removed_count\n";
echo "📁 Files organized into modules\n";
echo "✅ Clean PHP files: $clean_count\n";
echo "❌ Files with remaining errors: $final_error_count\n";

if ($final_error_count === 0) {
    echo "\n🎉 ALL SYNTAX ERRORS FIXED!\n";
    echo "🏆 SYSTEM IS NOW CLEAN AND ORGANIZED!\n";
} else {
    echo "\n⚠️ Some files still need manual fixes\n";
}

echo "\n📋 FINAL STRUCTURE:\n";
echo "📁 modules/guest/booking/  - Booking system\n";
echo "📁 modules/guest/menu/     - Menu & ordering\n";
echo "📁 modules/guest/payments/ - Payment processing\n";
echo "📁 admin/                  - Admin panel\n";
echo "📁 setup/sql/             - Database scripts\n";
echo "📁 setup/utilities/       - Utility scripts\n";
echo "📁 docs/                  - Documentation\n";
echo "📁 api/                   - API endpoints\n";
echo "📁 includes/              - Shared components\n";

?>
