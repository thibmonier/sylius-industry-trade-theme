const Encore = Webpack Encore;
const path = require('path');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/bundles/tmoniersyliusindustrytradetheme/build')
    .setManifestKeyPrefix('build')

    // Entrée du thème
    .addEntry('theme', './assets/app.js')

    // Style Loader pour SCSS
    .enableSassLoader()

    // Source maps en dev
    .enableSourceMaps(!Encore.isProduction())

    // Versioning des assets en production
    .enableVersioning(Encore.isProduction())

    // Cache busting
    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    })

    // Optimisation en production
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSingleRuntimeChunk()

    // Stimulus bridge pour les controllers
    .enableStimulusBridge('./assets/controllers.json')
;

module.exports = Encore.getWebpackConfig();
