import { ApplicationConfig, isDevMode } from '@angular/core';
import { provideHttpClient } from '@angular/common/http';
import { provideServiceWorker } from '@angular/service-worker';

const isJoomlaStandaloneScope = window.location.pathname.startsWith('/media/com_balancirk/member-spa/');

export const appConfig: ApplicationConfig = {
  providers: [
    provideHttpClient(),
    provideServiceWorker('ngsw-worker.js', {
      enabled: !isDevMode() && isJoomlaStandaloneScope,
      registrationStrategy: 'registerWhenStable:30000'
    })
  ]
};
