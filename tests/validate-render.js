const fs = require('fs');
const yaml = require('js-yaml');
const path = require('path');

try {
    const renderConfig = yaml.load(
        fs.readFileSync(
            path.join(__dirname, '..', 'render.yaml'),
            'utf8'
        )
    );
    console.log('✅ YAML syntax is valid');
    console.log('Configuration loaded:', renderConfig);
} catch (e) {
    console.error('❌ YAML validation failed:', e);
    process.exit(1);
}
