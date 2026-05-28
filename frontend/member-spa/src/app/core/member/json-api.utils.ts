/** Minimal JSON:API response envelope. */
export interface JsonApiItem<T> {
  id?: string | number;
  attributes?: Partial<T>;
}

export interface JsonApiResponse<T> {
  data?: JsonApiItem<T> | Array<JsonApiItem<T>>;
}

/** Joomla plain JsonResponse envelope. */
export interface JoomlaResponse<T> {
  success?: boolean;
  data?: T;
  message?: string;
}

/** Flatten a single JSON:API item into a typed object. */
export function extractItem<T>(item: JsonApiItem<T>): T & { id: number } {
  const attrs = (item.attributes ?? {}) as Record<string, unknown>;
  return { id: Number(item.id ?? attrs['id'] ?? 0), ...(attrs as object) } as T & { id: number };
}

/** Flatten a JSON:API list response. */
export function extractList<T>(response: JsonApiResponse<T>): Array<T & { id: number }> {
  const items = Array.isArray(response.data) ? response.data : [];
  return items.map(extractItem);
}

/** Detect whether a response is a Joomla JsonResponse (not JSON:API). */
export function isJoomlaResponse<T>(value: unknown): value is JoomlaResponse<T> {
  return !!value && typeof value === 'object' && ('success' in value || 'message' in value);
}
