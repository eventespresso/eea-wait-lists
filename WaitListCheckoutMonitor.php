<?php
namespace EventEspresso\WaitList;

use EE_Checkout;
use EE_Error;
use EEM_Registration;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class WaitListCheckoutMonitor
 * monitors for any registrations that were previously on a wait list
 * and modifies the checkout application flow accordingly
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class WaitListCheckoutMonitor
{

    /**
     * checks if
     *
     * @param EE_Checkout $checkout
     * @return EE_Checkout
     * @throws EE_Error
     */
    public function loadAndInstantiateRegSteps(EE_Checkout $checkout)
    {
        // revisits should function the same as they always do
        if ($checkout->revisit) {
            return $checkout;
        }
        // not a revisit but there's a reg_url_link ?
        $registration = EEM_Registration::instance()->get_registration_for_reg_url_link($checkout->reg_url_link);
        if (! $registration instanceof \EE_Registration) {
            return $checkout;
        }
        // ok, but were they ever on a wait list ?
        if ($registration->get_extra_meta(WaitList::REG_SIGNED_UP_META_KEY, true) !== null) {
            // reload reg steps
            add_filter('FHEE__Single_Page_Checkout__load_reg_steps__reload_reg_steps', '__return_true');
            // and do NOT bypass loading for any of them, the registrant needs to visit each step
            add_filter('FHEE__Single_Page_Checkout___load_and_instantiate_reg_step__bypass_reg_step', '__return_false');
        }
        return $checkout;
    }



    /**
     * ensures that the EE_Checkout has been initialized with ALL of the regular reg steps
     *
     * @param EE_Checkout $checkout
     * @return EE_Checkout
     */
    public function initializeTxnRegStepsArray(EE_Checkout $checkout)
    {
        $checkout->initialize_txn_reg_steps_array();
        return $checkout;
    }

}
// End of file WaitListCheckoutMonitor.php
// Location: EventEspresso/WaitList/WaitListCheckoutMonitor.php