<?php

namespace EventEspresso\WaitList\domain\services\registration;

use DomainException;
use EE_Error;
use EE_Registration;
use EE_Registry;
use EE_Request;
use EEH_HTML;
use EEM_Change_Log;
use EEM_Registration;
use EventEspresso\core\exceptions\ExceptionStackTraceDisplay;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\WaitList\domain\Domain;
use Exception;
use InvalidArgumentException;

/**
 * Class WaitListRegistrationConfirmation
 * Handles the confirmation of wait list registrations
 *
 * @package EventEspresso\WaitList
 * @author  Brent Christensen
 *
 */
class WaitListRegistrationConfirmation
{


    /**
     * @var WaitListRegistrationMeta $registration_meta
     */
    private $registration_meta;


    /**
     * @var EE_Request $request
     */
    private $request;


    /**
     * WaitListRegistrationConfirmation constructor.
     *
     * @param WaitListRegistrationMeta $registration_meta
     * @param EE_Request               $request
     */
    public function __construct(WaitListRegistrationMeta $registration_meta, EE_Request $request)
    {
        $this->registration_meta = $registration_meta;
        $this->request = $request;
    }

    /**
     * @param EE_Registration $registration
     * @return string
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     * @throws EE_Error
     */
    public function url(EE_Registration $registration)
    {
        $confirmation_url = apply_filters(
            'FHEE__EED_Wait_Lists__wait_list_confirmation_url',
            add_query_arg(
                array(
                    'ee-confirmation' => 'wait_list',
                    'e_reg_url_link'  => $registration->reg_url_link(),
                ),
                EE_Registry::instance()->CFG->core->reg_page_url()
            ),
            $registration
        );
        if (WP_DEBUG) {
            EEM_Change_Log::instance()->log(
                Domain::LOG_TYPE_WAIT_LIST,
                "Wait List Confirmation URL: {$confirmation_url}",
                $registration
            );
        }
        return $confirmation_url;
    }


    /**
     * Displays a notice to the visitor that their spot on the wait list has been confirmed
     *
     * @return void
     * @throws Exception
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     */
    public function routeHandler()
    {
        try {
            add_filter('FHEE__EED_Single_Page_Checkout__run', '__return_false');
            $registration = EEM_Registration::instance()->get_registration_for_reg_url_link(
                $this->request->get('e_reg_url_link')
            );
            if (! $registration instanceof EE_Registration) {
                throw new InvalidArgumentException(
                    esc_html__(
                        'A valid registration could not be found for the supplied URL link.',
                        'event_espresso'
                    )
                );
            }
            if (! $this->registration_meta->confirmWaitListSpace($registration)) {
                throw new DomainException(
                    esc_html__(
                        'We\'re sorry, but your wait list registration could not be confirmed. Please contact support or refresh the page and try again.',
                        'event_espresso'
                    )
                );
            }
            $wait_list_confirmation = apply_filters(
                'FHEE__WaitListRegistrationConfirmation__routeHandler__success_notice',
                sprintf(
                    esc_html__(
                        '%2$sCongratulations!%3$s%4$sYour spot on the wait list for %1$s has been confirmed. If spaces become available, we\'ll contact you with information about how to proceed with the registration process.%5$s',
                        'event_espresso'
                    ),
                    $registration->event_name(),
                    '<h3>',
                    '</h3>',
                    '<p>',
                    '</p>'
                )
            );
            EE_Registry::instance()->REQ->add_output(
                EEH_HTML::div($wait_list_confirmation, 'ee-wait-list-confirmation', 'ee-attention')
            );
        } catch (Exception $exception) {
            EE_Error::add_error($exception->getMessage(), __FILE__, __FUNCTION__, __LINE__);
            if (WP_DEBUG) {
                new ExceptionStackTraceDisplay($exception);
            }
        }
    }
}
