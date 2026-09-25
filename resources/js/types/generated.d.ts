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
export type DashboardAttentionData = {
title: string,
description: string,
url: string,
};
export type DashboardOfferData = {
id: string,
name: string,
state: 'active' | 'paused' | 'scheduled',
issued_count: number,
voucher_limit: number | null,
ends_at: string,
ends_soon: boolean,
};
export type DashboardRedemptionData = {
id: string,
offer_name: string,
outlet_name: string,
bill_amount: string,
discount_amount: string,
currency: string,
redeemed_at: string,
is_cancelled: boolean,
};
export type DashboardTileData = {
label: string,
value: string | number | null,
format: App.Enums.ReportValueFormat,
hint: string | null,
report: string,
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
export type OfferData = {
id: string,
name: string,
description: string | null,
discount_type: App.Enums.DiscountType,
discount_value: string | null,
max_discount_amount: string | null,
min_spend_amount: string | null,
free_item: string | null,
currency: string,
starts_at: string,
ends_at: string,
starts_at_local: string,
ends_at_local: string,
voucher_valid_days: number | null,
voucher_limit: number | null,
issued_count: number,
uses_per_voucher: number,
status: App.Enums.OfferStatus,
state: 'draft' | 'active' | 'paused' | 'scheduled' | 'ended' | 'hidden',
is_locked: boolean,
hidden_reason: string | null,
outlets: App.Data.OfferOutletData[],
};
export type OfferOutletData = {
id: string,
name: string,
is_sponsored: boolean,
business_name: string | null,
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
export type RedemptionData = {
id: string,
offer_name: string,
code_prefix: string,
bill_amount: string,
discount_amount: string,
net_amount: string,
currency: string,
redeemed_at: string,
cashier_name: string | null,
cancelled_at: string | null,
cancelled_by_name: string | null,
cancel_reason: string | null,
can_cancel: boolean,
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
export type VoucherData = {
id: string,
code_prefix: string,
status: 'active' | 'used' | 'void' | 'expired',
redemption_count: number,
uses_per_voucher: number,
expires_at: string,
created_at: string | null,
claimed_by_name: string | null,
voided_at: string | null,
void_reason: string | null,
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
export type OfferData = {
offer: App.Data.OfferData,
business_id: string,
business_name: string,
is_sponsored: boolean,
hidden_at: string | null,
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
export type CounterCheckData = {
outlet_id: string,
code: string,
bill_amount: string | null,
free_item_value: string | null,
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
export type OfferFormData = {
name: string,
discount_type: App.Enums.DiscountType,
starts_at: string,
ends_at: string,
description: string | null,
discount_value: string | null,
max_discount_amount: string | null,
min_spend_amount: string | null,
free_item: string | null,
voucher_valid_days: number | null,
voucher_limit: number | null,
uses_per_voucher: number,
};
export type OfferLimitsData = {
ends_at: string,
description: string | null,
voucher_limit: number | null,
};
export type OfferOutletsData = {
outlet_ids: string[],
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
export type OutletLinksData = {
website: string | null,
whatsapp: string | null,
facebook: string | null,
instagram: string | null,
};
export type OutletListingData = {
is_listed: boolean,
};
export type OutletLocationData = {
google_maps_url: string | null,
latitude: number | null,
longitude: number | null,
};
export type OutletPublicProfileData = {
summary: string | null,
description: string | null,
category_id: string | null,
tags: string[] | null,
};
export type ReasonData = {
reason: string,
};
export type RedeemVoucherData = {
outlet_id: string,
code: string,
bill_amount: string,
idempotency_key: string,
free_item_value: string | null,
};
export type ReportFiltersData = {
period: App.Enums.ReportPeriodPreset,
from: string | null,
to: string | null,
group: App.Enums.ReportGrouping | null,
offer: string | null,
outlet: string | null,
};
export type TagData = {
name: string,
is_active: boolean,
};
}
namespace Reports {
export type ReportOptionData = {
value: string,
label: string,
};
export type ReportPageData = {
key: string,
title: string,
question: string,
period: App.Enums.ReportPeriodPreset,
from: string,
to: string,
period_label: string,
period_options: App.Data.Reports.ReportOptionData[],
grouping: App.Enums.ReportGrouping,
grouping_options: App.Data.Reports.ReportOptionData[],
offer: string | null,
offer_options: App.Data.Reports.ReportOptionData[] | null,
outlet: string | null,
outlet_options: App.Data.Reports.ReportOptionData[] | null,
currency: string,
tiles: App.Support.Reports.ReportTile[],
columns: App.Support.Reports.ReportColumn[],
chart_series: string[],
rows: Record<string, any>[],
};
}
}
namespace Enums {
export type Ability = 'scan' | 'view_today_activity' | 'view_reports' | 'view_statements' | 'manage_offers' | 'void_vouchers' | 'manage_business' | 'manage_outlets' | 'manage_staff' | 'manage_public_content';
export type DiscountType = 'percentage' | 'amount' | 'free_item';
export type EngagementEventType = 'place_impression' | 'place_view' | 'outbound_click';
export type ImageKind = 'cover' | 'gallery' | 'logo';
export type IntegrationCapability = 'places:read' | 'engagement:write' | 'vouchers:read' | 'vouchers:claim';
export type IntegrationType = 'pms' | 'travel_agency' | 'internal';
export type InvitationStatus = 'pending' | 'accepted' | 'declined' | 'cancelled' | 'expired';
export type MediaDisk = 'local' | 'r2';
export type MembershipRole = 'owner' | 'manager' | 'cashier';
export type OfferStatus = 'draft' | 'active' | 'paused';
export type OnboardingStatus = 'draft' | 'pending' | 'approved' | 'rejected';
export type OutboundDestination = 'map' | 'phone' | 'website' | 'whatsapp' | 'facebook' | 'instagram';
export type OwnerMethod = 'existing' | 'temporary_password' | 'invite';
export type ReportFilter = 'offer' | 'outlet';
export type ReportGrouping = 'day' | 'week' | 'month' | 'outlet' | 'offer';
export type ReportPeriodPreset = 'today' | 'last_7_days' | 'last_30_days' | 'this_month' | 'last_month' | 'custom';
export type ReportValueFormat = 'text' | 'count' | 'money' | 'percent';
export type TagStatus = 'pending' | 'approved' | 'rejected';
export type VoidReason = 'guest_cancelled' | 'duplicate_claim' | 'issued_in_error' | 'suspected_abuse';
export type VoucherStatus = 'active' | 'used' | 'void';
}
namespace Support {
namespace Reports {
export type ReportColumn = {
key: string,
label: string,
format: App.Enums.ReportValueFormat,
};
export type ReportTile = {
label: string,
value: string | number | null,
format: App.Enums.ReportValueFormat,
hint: string | null,
};
}
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
