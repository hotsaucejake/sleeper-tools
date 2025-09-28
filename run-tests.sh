#!/bin/bash

# Test runner script for Sleeper Tools

echo "Running Sleeper Tools Test Suite"
echo "================================="

# Check if we're in a Docker environment with Sail
if [ -f "sail" ]; then
    echo "Using Laravel Sail..."
    ./sail test
else
    echo "Using local PHP installation..."

    # Try different PHP executables
    if command -v php >/dev/null 2>&1; then
        # Run with Pest
        if [ -f "vendor/bin/pest" ]; then
            echo "Running Pest tests..."
            php vendor/bin/pest
        # Fallback to PHPUnit
        elif [ -f "vendor/bin/phpunit" ]; then
            echo "Running PHPUnit tests..."
            php vendor/bin/phpunit
        else
            echo "Error: No test runner found. Run 'composer install' first."
            exit 1
        fi
    else
        echo "Error: PHP not found. Please install PHP or use Docker."
        exit 1
    fi
fi

echo ""
echo "Test run completed!"