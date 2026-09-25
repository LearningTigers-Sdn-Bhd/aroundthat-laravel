declare namespace App {
namespace Data {
export type BusinessData = {
id: string,
name: string,
registered_name: string | null,
registration_number: string | null,
contact_email: string,
contact_phone: string | null,
address: string | null,
timezone: string,
onboarding_status: App.Enums.OnboardingStatus,
submitted_at: string | null,
rejection_reason: string | null,
is_suspended: boolean,
is_writable: boolean,
};
export type BusinessPlaceData = {
slug: string,
summary: string | null,
description: string | null,
logo: App.Data.ImageData | null,
};
export type CategoryOptionData = {
id: string,
name: string,
};
export type CurrentWorkspaceData = {
business_id: string,
business_name: string,
onboarding_status: App.Enums.OnboardingStatus,
is_suspended: boolean,
role: App.Enums.MembershipRole,
abilities: App.Enums.Ability[],
};
export type DateExceptionData = {
date: string,
is_closed: boolean,
periods: App.Data.OpeningPeriodData[],
note: string | null,
};
export type ImageData = {
id: string,
kind: App.Enums.ImageKind,
url: string,
alt_text: string,
width: number,
height: number,
position: number,
};
export type InvitationData = {
id: string,
email: string,
role: App.Enums.MembershipRole,
status: App.Enums.InvitationStatus,
outlets: App.Data.OutletOptionData[],
invited_by_name: string | null,
sent_at: string | null,
expires_at: string,
created_at: string | null,
};
export type InvitationPreviewData = {
business_name: string,
inviter_name: string | null,
email: string,
role: App.Enums.MembershipRole,
status: App.Enums.InvitationStatus,
expires_at: string,
outlet_names: string[],
};
export type LocationOptionsData = {
timezones: string[],
country_codes: string[],
malaysian_states: string[],
};
export type MemberData = {
id: string,
user_id: string,
name: string,
email: string,
role: App.Enums.MembershipRole,
outlets: App.Data.OutletOptionData[],
suspended_at: string | null,
suspension_reason: string | null,
joined_at: string | null,
};
export type OpeningPeriodData = {
opens: string,
closes: string,
};
export type OutletData = {
id: string,
name: string,
contact_email: string | null,
contact_phone: string | null,
address_line_1: string,
address_line_2: string | null,
city: string,
state: string,
postcode: string,
country_code: string,
timezone: string,
host_outlet: App.Data.OutletOptionData | null,
onboarding_status: App.Enums.OnboardingStatus,
submitted_at: string | null,
rejection_reason: string | null,
is_suspended: boolean,
archived_at: string | null,
is_operational: boolean,
is_writable: boolean,
is_public: boolean,
hidden_reason: string | null,
};
export type OutletHoursData = {
regular_hours: Record<string, App.Data.OpeningPeriodData[]>,
date_exceptions: App.Data.DateExceptionData[],
timezone: string,
};
export type OutletOptionData = {
id: string,
name: string,
};
export type PlacePreviewData = {
name: string,
address: string,
contact_phone: string | null,
contact_email: string | null,
place: App.Data.PlaceProfileData,
hours: App.Data.OutletHoursData,
images: App.Data.ImageData[],
business_name: string,
business: App.Data.BusinessPlaceData,
is_open_now: boolean,
closes_at: string | null,
next_opens_at: string | null,
};
export type PlaceProfileData = {
slug: string,
summary: string | null,
description: string | null,
category: App.Data.CategoryOptionData | null,
latitude: number | null,
longitude: number | null,
google_maps_url: string | null,
website: string | null,
whatsapp: string | null,
facebook: string | null,
instagram: string | null,
tags: App.Data.TagOptionData[],
is_listed: boolean,
is_public: boolean,
hidden_reason: string | null,
missing_for_listing: string[],
};
export type RevertNoticeData = {
event: string,
reason: string | null,
reverted_at: string | null,
};
export type TagOptionData = {
id: string,
name: string,
};
export type WorkspaceOptionData = {
business_id: string,
business_name: string,
role: App.Enums.MembershipRole,
};
namespace Admin {
export type ActivityChangeData = {
field: string,
old: any,
new: any,
};
export type ActivityData = {
id: number,
log_name: string | null,
event: string,
subject_type: string | null,
subject_id: string | null,
causer_name: string | null,
reason: string | null,
ip_address: string | null,
changes: App.Data.Admin.ActivityChangeData[],
properties: Record<string, any>,
reviewed_at: string | null,
reverted_at: string | null,
created_at: string | null,
};
export type ApiKeyData = {
id: number,
name: string,
last_used_at: string | null,
expires_at: string | null,
is_expired: boolean,
created_at: string | null,
};
export type BusinessData = {
id: string,
name: string,
registered_name: string | null,
registration_number: string | null,
contact_email: string,
contact_phone: string | null,
address: string | null,
timezone: string,
onboarding_status: App.Enums.OnboardingStatus,
submitted_at: string | null,
approved_at: string | null,
approved_by_name: string | null,
rejection_reason: string | null,
suspended_at: string | null,
suspended_by_name: string | null,
suspension_reason: string | null,
created_at: string | null,
};
export type CategoryData = {
id: string,
slug: string,
name: string,
position: number,
is_active: boolean,
outlets_count: number,
};
export type ChangeData = {
activity: App.Data.Admin.ActivityData,
subject: App.Data.Admin.ChangeSubjectData | null,
reviewed_by_name: string | null,
reverted_at: string | null,
conflicts: string[],
};
export type ChangeSubjectData = {
type: string,
id: string,
name: string,
business_name: string | null,
};
export type HostOutletOptionData = {
id: string,
name: string,
business_name: string,
};
export type IntegrationData = {
id: string,
name: string,
type: App.Enums.IntegrationType,
capabilities: string[],
starts_at: string | null,
expires_at: string | null,
is_usable: boolean,
keys_count: number,
suspended_at: string | null,
suspended_by_name: string | null,
suspension_reason: string | null,
created_at: string | null,
};
export type MembershipData = {
id: string,
business_id: string,
business_name: string,
role: App.Enums.MembershipRole,
outlets: App.Data.OutletOptionData[],
suspended_at: string | null,
suspension_reason: string | null,
joined_at: string | null,
};
export type OutletData = {
id: string,
business_id: string,
business_name: string,
name: string,
contact_email: string | null,
contact_phone: string | null,
address_line_1: string,
address_line_2: string | null,
city: string,
state: string,
postcode: string,
country_code: string,
timezone: string,
host_outlet: App.Data.OutletOptionData | null,
onboarding_status: App.Enums.OnboardingStatus,
submitted_at: string | null,
approved_at: string | null,
approved_by_name: string | null,
rejection_reason: string | null,
suspended_at: string | null,
suspended_by_name: string | null,
suspension_reason: string | null,
archived_at: string | null,
hidden_at: string | null,
hidden_reason: string | null,
is_operational: boolean,
created_at: string | null,
};
export type TagData = {
id: string,
slug: string,
name: string,
status: App.Enums.TagStatus,
is_active: boolean,
created_by_business_id: string | null,
created_by_business_name: string | null,
merged_into_name: string | null,
outlets_count: number,
created_at: string | null,
};
export type UserData = {
id: string,
name: string,
email: string,
is_admin: boolean,
is_email_verified: boolean,
must_change_password: boolean,
has_two_factor: boolean,
last_login_at: string | null,
suspended_at: string | null,
suspended_by_name: string | null,
suspension_reason: string | null,
created_at: string | null,
};
}
namespace Forms {
export type ApiKeyData = {
name: string,
expires_on: string | null,
};
export type BusinessDetailsData = {
name: string,
contact_email: string,
timezone: string,
registered_name: string | null,
registration_number: string | null,
contact_phone: string | null,
address: string | null,
};
export type BusinessPublicProfileData = {
summary: string | null,
description: string | null,
};
export type CategoryData = {
name: string,
is_active: boolean,
};
export type ImageUploadData = {
file: undefined,
kind: App.Enums.ImageKind,
alt_text: string,
};
export type IntegrationData = {
name: string,
type: App.Enums.IntegrationType,
capabilities: string[],
starts_on: string | null,
ends_on: string | null,
};
export type InviteStaffData = {
email: string,
role: App.Enums.MembershipRole,
outlet_ids: string[],
};
export type MediaSettingsData = {
media_disk: App.Enums.MediaDisk,
};
export type MemberAccessData = {
role: App.Enums.MembershipRole,
outlet_ids: string[],
};
export type NewAccountData = {
name: string,
password: string,
};
export type OnboardBusinessData = {
business: App.Data.Forms.BusinessDetailsData,
owner_method: App.Enums.OwnerMethod,
owner_email: string,
owner_name: string | null,
owner_password: string | null,
approve_immediately: boolean,
};
export type OpeningHoursData = {
regular_hours: {
opens: string,
closes: string,
}[][] | null,
date_exceptions: {
date: string,
is_closed: boolean,
periods?: {
opens: string,
closes: string,
}[],
note?: string | null,
}[] | null,
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
export type OutletPublicProfileData = {
summary: string | null,
description: string | null,
category_id: string | null,
google_maps_url: string | null,
latitude: number | null,
longitude: number | null,
website: string | null,
whatsapp: string | null,
facebook: string | null,
instagram: string | null,
tags: string[] | null,
is_listed: boolean,
};
export type ReasonData = {
reason: string,
};
export type TagData = {
name: string,
is_active: boolean,
};
}
}
namespace Enums {
export type Ability = 'scan' | 'view_today_activity' | 'view_reports' | 'view_statements' | 'manage_offers' | 'void_vouchers' | 'manage_business' | 'manage_outlets' | 'manage_staff' | 'manage_public_content';
export type ImageKind = 'cover' | 'gallery' | 'logo';
export type IntegrationCapability = 'places:read' | 'engagement:write';
export type IntegrationType = 'pms' | 'travel_agency' | 'internal';
export type InvitationStatus = 'pending' | 'accepted' | 'declined' | 'cancelled' | 'expired';
export type MediaDisk = 'local' | 'r2';
export type MembershipRole = 'owner' | 'manager' | 'cashier';
export type OnboardingStatus = 'draft' | 'pending' | 'approved' | 'rejected';
export type OwnerMethod = 'existing' | 'temporary_password' | 'invite';
export type TagStatus = 'pending' | 'approved' | 'rejected';
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
