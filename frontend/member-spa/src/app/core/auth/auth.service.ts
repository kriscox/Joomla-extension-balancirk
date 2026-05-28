import { Injectable, signal } from '@angular/core';

interface JoomlaOptions {
  token?: string;
  apiBase?: string;
  [key: string]: unknown;
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly _options = this.readJoomlaOptions();

  /** Bearer token: localStorage override → Joomla.getOptions token. */
  readonly token: string | null =
    localStorage.getItem('balancirk_api_token') ?? this._options.token ?? null;

  /** Base URL for the Joomla REST API. */
  readonly apiBase: string = (this._options.apiBase ?? '/api/index.php/v1').replace(/\/+$/, '');

  /** Read a string option injected by spa.php via Joomla.addScriptOptions. */
  getOption(key: string, fallback = ''): string {
    const v = this._options[key];
    return typeof v === 'string' && v.trim() !== '' ? v : fallback;
  }

  /** Read a boolean option injected by spa.php. */
  getBoolOption(key: string, fallback = false): boolean {
    const v = this._options[key];
    return typeof v === 'boolean' ? v : fallback;
  }

  /** Called by the auth guard when a 401 is received. */
  readonly isUnauthorized = signal(false);

  handleUnauthorized(): void {
    this.isUnauthorized.set(true);
  }

  private readJoomlaOptions(): JoomlaOptions {
    const joomla = (globalThis as { Joomla?: { getOptions?: (name: string) => unknown } }).Joomla;
    const opts = joomla?.getOptions?.('balancirk-member-spa');
    return (opts && typeof opts === 'object' ? opts : {}) as JoomlaOptions;
  }
}
