#!/bin/bash

# Balancirk API Test Runner
# Runs both unit tests (PHPUnit) and API integration tests (Newman/Postman)

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== Balancirk API Test Suite ===${NC}"
echo ""

# Configuration
COMPOSER_BIN="./vendor/bin"
PHPUNIT_BIN="$COMPOSER_BIN/phpunit"
NEWMAN_BIN="newman"
REPORTS_DIR="tests/reports"
POSTMAN_COLLECTION="tests/postman/balancirk-api-tests.postman_collection.json"
POSTMAN_ENVIRONMENT="tests/postman/balancirk-environment.postman_environment.json"

# Create reports directory if it doesn't exist
mkdir -p $REPORTS_DIR

# Function to check if command exists
command_exists () {
    type "$1" &> /dev/null ;
}

# Check prerequisites
echo -e "${YELLOW}Checking prerequisites...${NC}"

if [ ! -f "composer.json" ]; then
    echo -e "${RED}Error: composer.json not found. Please run from project root.${NC}"
    exit 1
fi

if [ ! -f "$PHPUNIT_BIN" ]; then
    echo -e "${YELLOW}PHPUnit not found. Installing dependencies...${NC}"
    composer install --dev
fi

if ! command_exists newman; then
    echo -e "${YELLOW}Newman not found. Please install it globally: npm install -g newman${NC}"
    echo -e "${YELLOW}Skipping API integration tests...${NC}"
    NEWMAN_AVAILABLE=false
else
    NEWMAN_AVAILABLE=true
fi

echo ""

# Run PHPUnit tests
echo -e "${BLUE}Running PHPUnit tests...${NC}"
if [ -f "$PHPUNIT_BIN" ]; then
    $PHPUNIT_BIN --configuration phpunit.xml --coverage-text --colors=always
    PHPUNIT_EXIT_CODE=$?
    
    if [ $PHPUNIT_EXIT_CODE -eq 0 ]; then
        echo -e "${GREEN}✓ PHPUnit tests passed${NC}"
    else
        echo -e "${RED}✗ PHPUnit tests failed${NC}"
    fi
else
    echo -e "${RED}✗ PHPUnit not available${NC}"
    PHPUNIT_EXIT_CODE=1
fi

echo ""

# Run Newman/Postman API tests if available
if [ "$NEWMAN_AVAILABLE" = true ]; then
    echo -e "${BLUE}Running API integration tests...${NC}"
    
    if [ ! -f "$POSTMAN_COLLECTION" ]; then
        echo -e "${RED}Error: Postman collection not found at $POSTMAN_COLLECTION${NC}"
        NEWMAN_EXIT_CODE=1
    else
        # Check if environment file exists
        NEWMAN_ARGS=""
        if [ -f "$POSTMAN_ENVIRONMENT" ]; then
            NEWMAN_ARGS="-e $POSTMAN_ENVIRONMENT"
        fi
        
        # Run Newman with HTML and JUnit reporters
        newman run $POSTMAN_COLLECTION $NEWMAN_ARGS \
            --reporters cli,html,junit \
            --reporter-html-export "$REPORTS_DIR/newman-report.html" \
            --reporter-junit-export "$REPORTS_DIR/newman-junit.xml" \
            --color on \
            --disable-unicode
        
        NEWMAN_EXIT_CODE=$?
        
        if [ $NEWMAN_EXIT_CODE -eq 0 ]; then
            echo -e "${GREEN}✓ API integration tests passed${NC}"
        else
            echo -e "${RED}✗ API integration tests failed${NC}"
        fi
    fi
else
    echo -e "${YELLOW}Skipping API integration tests (Newman not available)${NC}"
    NEWMAN_EXIT_CODE=0
fi

echo ""

# Test summary
echo -e "${BLUE}=== Test Summary ===${NC}"
if [ $PHPUNIT_EXIT_CODE -eq 0 ]; then
    echo -e "Unit Tests: ${GREEN}PASSED${NC}"
else
    echo -e "Unit Tests: ${RED}FAILED${NC}"
fi

if [ "$NEWMAN_AVAILABLE" = true ]; then
    if [ $NEWMAN_EXIT_CODE -eq 0 ]; then
        echo -e "API Tests: ${GREEN}PASSED${NC}"
    else
        echo -e "API Tests: ${RED}FAILED${NC}"
    fi
else
    echo -e "API Tests: ${YELLOW}SKIPPED${NC}"
fi

echo ""
echo -e "Reports available in: ${BLUE}$REPORTS_DIR/${NC}"

# Exit with error if any test suite failed
if [ $PHPUNIT_EXIT_CODE -ne 0 ] || [ $NEWMAN_EXIT_CODE -ne 0 ]; then
    echo -e "${RED}Some tests failed!${NC}"
    exit 1
else
    echo -e "${GREEN}All tests passed!${NC}"
    exit 0
fi