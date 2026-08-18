<?php
/**
 * PHPUnit bootstrap for P-21 worktree.
 *
 * The vendor/ directory is a junction to the main project's vendor.
 * Normally Laravel infers its base path from the ClassLoader's registered
 * path, which resolves through the junction to the MAIN project.  We must
 * override that with APP_BASE_PATH before the application is created so that
 * routes, migrations, and config are all loaded from THIS worktree.
 *
 * We also patch the Composer ClassLoader's classmap so that model files
 * modified in this branch (HasFactory additions) and new factories (in the
 * Database\Factories\HR and Database\Factories\Leave sub-namespaces) are
 * resolved correctly.
 */

$worktree = dirname(__DIR__);

// Tell Laravel to use the worktree as its base path.
$_ENV['APP_BASE_PATH']    = $worktree;
$_SERVER['APP_BASE_PATH'] = $worktree;

// Ensure an APP_KEY is available for HTTP tests (encryption service provider).
if (empty($_ENV['APP_KEY'])) {
    $_ENV['APP_KEY']    = 'base64:Y56zbdawxWy4OP7M+d0nU1XS5zTnSxQaFRVyvURNc5s=';
    $_SERVER['APP_KEY'] = 'base64:Y56zbdawxWy4OP7M+d0nU1XS5zTnSxQaFRVyvURNc5s=';
}

// Load the (junctioned) main project vendor autoloader.
/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require $worktree . '/vendor/autoload.php';

// Patch the classmap: override models we've modified (HasFactory additions)
// and register the new factories that only exist in this worktree.
$overrides = [
    // Models with HasFactory added in this branch
    'App\\Models\\HR\\EmployeeProfile'    => $worktree . '/app/Models/HR/EmployeeProfile.php',
    'App\\Models\\Leave\\LeaveType'       => $worktree . '/app/Models/Leave/LeaveType.php',
    'App\\Models\\Leave\\LeaveBalance'    => $worktree . '/app/Models/Leave/LeaveBalance.php',
    'App\\Models\\HR\\ResignationRequest' => $worktree . '/app/Models/HR/ResignationRequest.php',
    // New factories (namespaced to match convention: Database\Factories\{ModelSubNs})
    'Database\\Factories\\HR\\EmployeeProfileFactory'    => $worktree . '/database/factories/HR/EmployeeProfileFactory.php',
    'Database\\Factories\\HR\\ResignationRequestFactory' => $worktree . '/database/factories/HR/ResignationRequestFactory.php',
    'Database\\Factories\\Leave\\LeaveTypeFactory'       => $worktree . '/database/factories/Leave/LeaveTypeFactory.php',
    'Database\\Factories\\Leave\\LeaveBalanceFactory'    => $worktree . '/database/factories/Leave/LeaveBalanceFactory.php',
    // Test files (override so worktree tests are found first)
    'Tests\\TestCase'                                    => $worktree . '/tests/TestCase.php',
];

$classMap = $loader->getClassMap();
foreach ($overrides as $class => $path) {
    $classMap[$class] = $path;
}
$loader->addClassMap($classMap);
