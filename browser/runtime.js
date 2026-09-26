import Alpine from 'alpinejs';
import lyra from '@lyra-ds/alpine';

Alpine.plugin(lyra);
window.Alpine = Alpine;
if (!window.__lyraSkipStart) Alpine.start();
window.__lyraReady = true;
