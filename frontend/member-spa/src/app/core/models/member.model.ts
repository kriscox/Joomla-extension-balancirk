export interface MemberProfile {
  id: number;
  firstname: string;
  name: string;
  email: string;
  phone: string;
  street: string;
  number: string;
  bus: string;
  postcode: string;
  city: string;
}

export interface MemberProfileUpdate extends Omit<MemberProfile, 'id'> {
  password?: string;
  password2?: string;
}
