#!/bin/bash
# Comprehensive Frontend Route Testing Script

BASE_URL="https://aitradepulse.com"
PASS=0
FAIL=0

echo "=================================="
echo "Frontend Route Testing"
echo "=================================="
echo ""

# Function to test route
test_route() {
    local name="$1"
    local url="$2"
    local expected="$3"
    
    status=$(curl -s -o /dev/null -w '%{http_code}' "$url")
    
    if [ "$status" = "$expected" ]; then
        echo "✓ $name: $status"
        ((PASS++))
    else
        echo "✗ $name: $status (expected $expected)"
        ((FAIL++))
    fi
}

echo "=== PUBLIC ROUTES (Should be 200) ==="
test_route "Homepage" "$BASE_URL/" "200"
test_route "Login Page" "$BASE_URL/login" "200"
test_route "Register Page" "$BASE_URL/register" "200"
test_route "Health Check" "$BASE_URL/health" "200"
test_route "Swagger Docs" "$BASE_URL/docs.openapi" "200"

echo ""
echo "=== AUTHENTICATED ROUTES (Should redirect 302) ==="
test_route "User Dashboard" "$BASE_URL/dashboard" "302"
test_route "User Profile" "$BASE_URL/user/profile" "302"

echo ""
echo "=== ADMIN ROUTES (Should redirect 302) ==="
test_route "Admin Dashboard" "$BASE_URL/admin/dashboard" "302"
test_route "Admin Login" "$BASE_URL/admin/login" "200"

echo ""
echo "=================================="
echo "Results: $PASS passed, $FAIL failed"
echo "=================================="

exit $FAIL
