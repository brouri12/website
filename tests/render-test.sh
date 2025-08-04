#!/bin/bash

echo "Testing Render configuration..."

# 1. Validate YAML syntax
echo "Validating YAML syntax..."
yamllint ../render.yaml

# 2. Test build commands
echo "Testing build commands..."
composer install
npm install
npm run build

# 3. Test database connection
echo "Testing database connection..."
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:validate --env=test

# 4. Test Redis connection
echo "Testing Redis connection..."
redis-cli ping

# 5. Run Symfony tests
echo "Running Symfony tests..."
php bin/phpunit

echo "Tests completed!"
