<?php

namespace EventEspresso\WaitList\domain\services\registration;

use EE_Error;
use EE_Registration;
use EventEspresso\WaitList\domain\Domain;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class WaitListRegistrationMeta
 * class for interacting with wait list related Registration meta
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class WaitListRegistrationMeta
{

    /**
     * @param EE_Registration $registration
     * @return mixed
     * @throws EE_Error
     */
    public function getRegistrationSignedUp(EE_Registration $registration)
    {
        return $registration->get_extra_meta(Domain::META_KEY_WAIT_LIST_REG_SIGNED_UP, true);
    }



    /**
     * @param EE_Registration $registration
     * @return mixed
     * @throws EE_Error
     */
    public function addRegistrationSignedUp(EE_Registration $registration)
    {
        return $registration->add_extra_meta(
            Domain::META_KEY_WAIT_LIST_REG_SIGNED_UP,
            current_time('mysql', true),
            true
        );
    }



    /**
     * @param EE_Registration $registration
     * @return bool
     * @throws EE_Error
     */
    public function addRegistrationPromoted(EE_Registration $registration)
    {
        return $registration->add_extra_meta(
            Domain::META_KEY_WAIT_LIST_REG_PROMOTED,
            current_time('mysql', true)
        );
    }



    /**
     * @param EE_Registration $registration
     * @return bool
     * @throws EE_Error
     */
    public function addRegistrationDemoted(EE_Registration $registration)
    {
        return $registration->add_extra_meta(
            Domain::REG_DEMOTED_META_KEY,
            current_time('mysql', true)
        );
    }



    /**
     * @param EE_Registration $registration
     * @return bool
     * @throws EE_Error
     */
    public function addRegistrationRemoved(EE_Registration $registration)
    {
        return $registration->add_extra_meta(
            Domain::REG_REMOVED_META_KEY,
            current_time('mysql', true)
        );
    }



}
