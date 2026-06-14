<?php
require __DIR__ . '/vendor/autoload.php';
try {
    if (class_exists('Spatie\Permission\Models\Role')) {
        echo "Exists";
    } else {
        echo "Missing";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
