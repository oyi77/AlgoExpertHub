#!/bin/bash

# Simple linting script for AlgoExpertHub

echo "Running PHP linting..."
find app -name "*.php" -exec php -l {} \; | grep -v "No syntax errors detected"

echo "Checking for strict types..."
grep -L "declare(strict_types=1);" $(find app -name "*.php") | head -n 20

echo "Checking for business logic in controllers (audit)..."
grep -r "DB::" app/Http/Controllers | head -n 20

echo "Linting complete."
