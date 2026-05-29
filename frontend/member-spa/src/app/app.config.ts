import { ApplicationConfig, isDevMode } from '@angular/core';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { provideRouter, withHashLocation } from '@angular/router';
import { provideServiceWorker } from '@angular/service-worker';
import { routes } from './app.routes';
import { apiInterceptor } from './core/http/api.interceptor';

const isJoomlaStandaloneScope = window.location.pathname.startsWith('/media/com_balancirk/member-spa/');

export const appConfig: ApplicationConfig = {
  providers: [
    provideRouter(routes, withHashLocation()),
    provideHttpClient(withInterceptors([apiInterceptor])),
    provideServiceWorker('ngsw-worker.js', {
      enabled: !isDevMode() && isJoomlaStandaloneScope,
      registrationStrategy: 'registerWhenStable:30000',
    }),
  ],
};
