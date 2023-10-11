<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Dashboard\Contracts;

/**
 * Interface DeepLinks
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Dashboard\Contracts
 */
interface DeepLinks
{
    const CLEVERREACH_NEW_MAILING_URL = '/admin/mailing_create_new.php';
    const CLEVERREACH_REPORTS_URL = '/admin/report_list.php';
    const CLEVERREACH_FORMS_URL = '/admin/forms_list.php';
    const CLEVERREACH_AUTOMATION_URL = '/admin/automation_list.php';
    const CLEVERREACH_MAILINGS_URL = '/admin/mailing_list.php';
    const CLEVERREACH_PRICE_PLANS_URL = '/admin/account_plan.php';
    const CLEVERREACH_EDIT_FORM_URL = '/admin/forms_layout_create.php';
    const CLEVERREACH_EDIT_AUTOMATION_URL='/admin/automation_edit.php?id=';
}
