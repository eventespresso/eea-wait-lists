<?php

namespace EventEspresso\WaitList\tests\testcases;

use EE_Base_Class;
use EE_Error;
use EE_Event;
use EE_Registration;
use EE_UnitTestCase;
use EEM_Registration;
use EventEspresso\WaitList\domain\Domain;
use PHPUnit_Framework_Exception;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class WaitListUnitTestCase
 * Description
 *
 * @package EventEspresso\WaitList\tests\testcases
 * @author  Brent Christensen
 * @since   $VID:$
 */
class WaitListUnitTestCase extends EE_UnitTestCase
{

    /**
     * @param bool $allow_overflow
     * @param int  $reg_limit
     * @param int  $ticket_price
     * @param bool $add_extra_meta
     * @param int  $spaces
     * @param bool $auto_promote
     * @param int  $manual_spaces
     * @return EE_Event
     * @throws EE_Error
     */
    protected function setupEvent(
        $allow_overflow = true,
        $reg_limit = 2,
        $ticket_price = 10,
        $add_extra_meta = true,
        $spaces = 100,
        $auto_promote = true,
        $manual_spaces = 0
    ) {
        /** @var EE_Event $event */
        $event    = $this->new_model_obj_with_dependencies(
            'Event',
            array(
                'status'             => 'publish',
                'EVT_allow_overflow' => $allow_overflow,
            )
        );
        $datetime = $this->new_model_obj_with_dependencies(
            'Datetime',
            array(
                'EVT_ID'        => $event->ID(),
                'DTT_EVT_start' => time() + MONTH_IN_SECONDS,
                'DTT_EVT_end'   => time() + MONTH_IN_SECONDS + DAY_IN_SECONDS,
                'DTT_reg_limit' => $reg_limit,
                'DTT_sold'      => 0,
                'DTT_reserved'  => 0,
            )
        );
        $this->new_ticket(
            array(
                'TKT_price'        => $ticket_price,
                'TKT_min'          => 0,
                'TKT_max'          => EE_INF,
                'TKT_sold'         => 0,
                'TKT_reserved'     => 0,
                'datetime_objects' => array($datetime),
            )
        );
        if ($add_extra_meta) {
            // add wait list details to event
            $event->add_extra_meta(Domain::META_KEY_WAIT_LIST_SPACES, $spaces);
            $event->add_extra_meta(Domain::META_KEY_WAIT_LIST_AUTO_PROMOTE, $auto_promote);
            $event->add_extra_meta(Domain::META_KEY_WAIT_LIST_MANUALLY_CONTROLLED_SPACES, $manual_spaces);
            $event->add_extra_meta(Domain::META_KEY_WAIT_LIST_REG_COUNT, 0);
        }
        return $event;
    }


    /**
     * @param EE_Event $event
     * @param int      $qty
     * @param string   $reg_status
     * @return EE_Base_Class[]|EE_Registration[]
     * @throws EE_Error
     * @throws PHPUnit_Framework_Exception
     */
    protected function registerForWaitListEvent(
        EE_Event $event,
        $qty = 1,
        $reg_status = EEM_Registration::status_id_wait_list
    ) {
        $registrations = array();
        $tickets       = $event->tickets();
        $ticket        = reset($tickets);
        $transaction   = $this->new_typical_transaction(
            array(
                'tickets'   => array(1 => $ticket),
                'tkt_qty'   => $qty,
                'setup_reg' => false,
            )
        );
        for ($x = 0; $x < $qty; $x++) {
            $registrations[ $x ] = $this->new_model_obj_with_dependencies(
                'Registration',
                array(
                    'EVT_ID'          => $event->ID(),
                    'TKT_ID'          => $ticket->ID(),
                    'TXN_ID'          => $transaction->ID(),
                    'STS_ID'          => $reg_status,
                    'REG_count'       => 1,
                    'REG_group_size'  => 1,
                    'REG_final_price' => $ticket->price(),
                )
            );
            $registrations[ $x ]->save();
            // $ticket->increase_sold();
            if ($reg_status === EEM_Registration::status_id_wait_list) {
                $registrations[ $x ]->add_extra_meta(
                    Domain::META_KEY_WAIT_LIST_REG_SIGNED_UP,
                    current_time('mysql', true),
                    true
                );
            }
        }
        $this->assertEquals($x, $qty);
        if ($reg_status === EEM_Registration::status_id_wait_list) {
            $reg_count = $event->get_extra_meta(Domain::META_KEY_WAIT_LIST_REG_COUNT, true);
            $event->update_extra_meta(
                Domain::META_KEY_WAIT_LIST_REG_COUNT,
                $reg_count + $qty
            );
        }
        $ticket->save();
        return $registrations;
    }
}
