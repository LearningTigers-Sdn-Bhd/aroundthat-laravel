<?php

namespace App\Enums;

/**
 * Something a member can do inside their business.
 */
enum Ability: string
{
    case Scan = 'scan';
    case ViewTodayActivity = 'view_today_activity';
    case ViewReports = 'view_reports';
    case ViewStatements = 'view_statements';
    case ManageOffers = 'manage_offers';
    case VoidVouchers = 'void_vouchers';
    case ManageBusiness = 'manage_business';
    case ManageOutlets = 'manage_outlets';
    case ManageStaff = 'manage_staff';
    case ManagePublicContent = 'manage_public_content';
}
