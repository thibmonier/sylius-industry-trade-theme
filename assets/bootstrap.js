// Thème Industrie & Négoce - Bootstrap Stimulus
import { startStimulusApp } from '@symfony/stimulus-bridge';

// Enregistrer les controllers Stimulus du thème
export const app = startStimulusApp(require.context(
    './controllers',
    true,
    /\.(j|t)sx?$/
));
