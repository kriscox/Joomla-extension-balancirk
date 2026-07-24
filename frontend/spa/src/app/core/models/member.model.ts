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
  username?: string;
}

export interface MemberProfileUpdate extends Omit<MemberProfile, 'id' | 'username'> {
  password?: string;
  password2?: string;
}

export interface MemberRegistration {
  firstname: string;
  name: string;
  username: string;
  email: string;
  phone?: string;
  street?: string;
  number?: string;
  bus?: string;
  postcode?: string;
  city?: string;
  password: string;
  password2: string;
}
