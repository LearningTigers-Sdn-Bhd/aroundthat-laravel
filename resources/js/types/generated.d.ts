declare namespace App {
namespace Data {
export type CurrentWorkspaceData = {
business_id: string,
business_name: string,
onboarding_status: App.Enums.OnboardingStatus,
is_suspended: boolean,
role: App.Enums.MembershipRole,
abilities: App.Enums.Ability[],
};
export type WorkspaceOptionData = {
business_id: string,
business_name: string,
role: App.Enums.MembershipRole,
};
namespace Forms {
export type BusinessDetailsData = {
name: string,
contact_email: string,
timezone: string,
registered_name: string | null,
registration_number: string | null,
contact_phone: string | null,
address: string | null,
};
export type OnboardBusinessData = {
business: App.Data.Forms.BusinessDetailsData,
owner_method: App.Enums.OwnerMethod,
owner_email: string,
owner_name: string | null,
owner_password: string | null,
approve_immediately: boolean,
};
export type OutletDetailsData = {
name: string,
address_line_1: string,
city: string,
state: string,
postcode: string,
country_code: string,
timezone: string,
address_line_2: string | null,
contact_email: string | null,
contact_phone: string | null,
};
}
}
namespace Enums {
export type Ability = 'scan' | 'view_today_activity' | 'view_reports' | 'view_statements' | 'manage_offers' | 'void_vouchers' | 'manage_business' | 'manage_outlets' | 'manage_staff' | 'manage_public_content';
export type MembershipRole = 'owner' | 'manager' | 'cashier';
export type OnboardingStatus = 'draft' | 'pending' | 'approved' | 'rejected';
export type OwnerMethod = 'existing' | 'temporary_password';
}
}
declare namespace Illuminate {
export type CursorPaginator<TKey, TValue> = {
data: TKey extends string ? Record<TKey, TValue> : TValue[],
links: {
url: string | null,
label: string,
active: boolean,
}[],
meta: {
path: string,
per_page: number,
next_cursor: string | null,
next_page_url: string | null,
prev_cursor: string | null,
prev_page_url: string | null,
},
};
export type CursorPaginatorInterface<TKey, TValue> = Illuminate.CursorPaginator<TKey, TValue>;
export type LengthAwarePaginator<TKey, TValue> = {
data: TKey extends string ? Record<TKey, TValue> : TValue[],
links: {
url: string | null,
label: string,
active: boolean,
}[],
meta: {
total: number,
current_page: number,
first_page_url: string,
from: number | null,
last_page: number,
last_page_url: string,
next_page_url: string | null,
path: string,
per_page: number,
prev_page_url: string | null,
to: number | null,
},
};
export type LengthAwarePaginatorInterface<TKey, TValue> = Illuminate.LengthAwarePaginator<TKey, TValue>;
}
declare namespace Spatie {
namespace LaravelData {
export type CursorPaginatedDataCollection<TKey, TValue> = Illuminate.CursorPaginator<TKey, TValue>;
export type PaginatedDataCollection<TKey, TValue> = Illuminate.LengthAwarePaginator<TKey, TValue>;
}
}
