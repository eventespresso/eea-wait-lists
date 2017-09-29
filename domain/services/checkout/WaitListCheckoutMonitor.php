<?php
namespace EventEspresso\WaitList\domain\services\checkout;

use DomainException;
use EE_Checkout;
use EE_Error;
use EE_Registration;
use EEM_Registration;
use EventEspresso\core\exceptions\UnexpectedEntityException;
use EventEspresso\WaitList\domain\services\registration\WaitListRegistrationMeta;

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
     * @var WaitListRegistrationMeta $event_meta
     */
    private $registration_meta;



    /**
     * WaitListCheckoutMonitor constructor.
     *
     * @param WaitListRegistrationMeta $registration_meta
     */
    public function __construct(WaitListRegistrationMeta $registration_meta)
    {
        $this->registration_meta = $registration_meta;
    }



    /**
     * checks if
     *
     * @param EE_Checkout $checkout
     * @throws EE_Error
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     * @throws \InvalidArgumentException
     */
    public function loadAndInstantiateRegSteps(EE_Checkout $checkout)
    {
        // revisits should function the same as they always do
        if ($checkout->revisit) {
            return;
        }
        // not a revisit but there's a reg_url_link ?
        $registration = EEM_Registration::instance()->get_registration_for_reg_url_link($checkout->reg_url_link);
        if (! $registration instanceof EE_Registration) {
            return;
        }
        // ok, but were they ever on a wait list ?
        if ($this->registration_meta->getRegistrationSignedUp($registration) !== null) {
            // reload reg steps
            add_filter('FHEE__Single_Page_Checkout__load_reg_steps__reload_reg_steps', '__return_true');
            // and do NOT bypass loading for any of them, the registrant needs to visit each step
            add_filter('FHEE__Single_Page_Checkout___load_and_instantiate_reg_step__bypass_reg_step', '__return_false');
            add_filter(
                'FHEE__EED_Single_Page_Checkout___final_verifications__checkout',
                array($this, 'initializeTxnRegStepsArray')
            );
            add_filter(
                'FHEE__EE_SPCO_Reg_Step__reg_step_hidden_inputs__default_form_action',
                array($this, 'defaultFormAction')
            );
        }
    }



    /**
     * ensures that the EE_Checkout has been initialized with ALL of the regular reg steps
     *
     * @param EE_Checkout $checkout
     * @return EE_Checkout
     * @throws EE_Error
     */
    public function initializeTxnRegStepsArray(EE_Checkout $checkout)
    {
        $reg_steps = $checkout->transaction->reg_steps();
        if (empty($reg_steps)) {
            $registrations = $checkout->transaction->registrations();
            $checkout->total_ticket_count = count($registrations);
            $checkout->transaction->set_reg_steps(
                $checkout->initialize_txn_reg_steps_array()
            );
            $checkout->transaction->save();
        }
        $checkout->revisit = false;
        $checkout->primary_revisit = false;
        return $checkout;
    }



    /**
     * @return string
     */
    public function defaultFormAction()
    {
        return 'process_reg_step';
    }



    /**
     * @param bool            $allow_payment
     * @param EE_Registration $registration
     * @return bool
     * @throws UnexpectedEntityException
     * @throws DomainException
     * @throws EE_Error
     */
    public function allowRegPayment($allow_payment, EE_Registration $registration)
    {
        $event = $registration->event_obj();
        if(! $event instanceof \EE_Event) {
            throw new DomainException();
        }
        $spaces_remaining = $event->spaces_remaining(array(), false);
        if((int)$spaces_remaining < 1) {
            return false;
        }
        if ($this->registration_meta->getRegistrationSignedUp($registration) !== null) {
            return true;
        }
        return $allow_payment;
    }

}
// End of file WaitListCheckoutMonitor.php
// Location: EventEspresso/Constants/WaitListCheckoutMonitor.php
