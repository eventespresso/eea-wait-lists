<?php
defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');



/**
 * @package    EventEspresso\Waitlists
 * @subpackage tests
 * @author     Brent Christensen
 * @since      1.0.0
 * @group      WaitList
 */
class EED_Wait_List_Test extends EE_UnitTestCase
{


    public function setUp()
    {
        parent::setUp();
        require_once EE_WAITLISTS_PLUGIN_DIR . 'domain/services/modules/EED_Wait_Lists.module.php';
    }




    public function test_wait_list_checkout_url()
    {
        /** @var EE_Registration $registration */
        $registration = $this->new_model_obj_with_dependencies('Registration');
        PHPUnit_Framework_TestCase::assertInstanceOf(
            'EE_Registration',
            $registration
        );
        $reg_page_url = EE_Registry::instance()->CFG->core->reg_page_url();
        echo "\n " . '$reg_page_url: ' . $reg_page_url;
        PHPUnit_Framework_TestCase::assertNotEmpty($reg_page_url);
        PHPUnit_Framework_TestCase::assertEquals(
            add_query_arg(
                array(
                    'e_reg_url_link' => $registration->reg_url_link(),
                    'revisit'        => 0,
                ),
                $reg_page_url
            ),
            EED_Wait_Lists::wait_list_checkout_url($registration)
        );
    }

}
// Location: /eea-wait-lists/tests/testcases/EED_Wait_List_Test.php
