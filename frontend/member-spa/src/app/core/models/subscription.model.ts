export interface SubscriptionSummary {
  id: number;
  lesson: string;
  year: string | number;
  subscribed: number;
  start?: string;
  end?: string;
}
