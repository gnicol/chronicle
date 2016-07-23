<?php
/**
 * Test the Notifications class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_NotificationsTest extends TestCase
{
    /**
     * Test usage scenarios.
     */
    public function testNotifications()
    {
        // make sure there are currently no notifications.
        $notifications = P4Cms_Notifications::fetch();
        $this->assertTrue(isset($notifications), 'Expect initial notifications to be set.');
        $this->assertTrue(is_array($notifications), 'Expect initial notifications to be an array.');
        $this->assertEquals(0, count($notifications), 'Expect no initial notifications');

        // test Notifications' facilities to measure the same things
        $this->assertFalse(P4Cms_Notifications::exist(), 'Expect initial notifications to not exist.');
        $this->assertEquals(0, P4Cms_Notifications::getCount(), 'Expect initial notifications total to be zero.');

        // now add a notification
        P4Cms_Notifications::add('Test message');
        $this->assertTrue(P4Cms_Notifications::exist(), 'Expect notifications to exist after add.');
        $this->assertEquals(1, P4Cms_Notifications::getCount(), 'Expect notifications total to be one after add.');

        // fetch without clearing
        $notifications = P4Cms_Notifications::fetch(null, false);
        $this->assertSame(
            array(
                'info'  => array(
                    'Test message',
                ),
            ),
            $notifications,
            'Expected notifications after add.'
        );

        // make sure notification still exist after fetch without clearing
        $this->assertTrue(P4Cms_Notifications::exist(), 'Expect notifications to exist after add.');
        $this->assertEquals(1, P4Cms_Notifications::getCount(), 'Expect notifications total to be one after add.');

        // fetch with clearing
        $notifications = P4Cms_Notifications::fetch();
        $this->assertSame(
            array('info' => array('Test message')),
            $notifications,
            'Expected notifications after add.'
        );

        // make sure notification still exists after fetch without clearing
        $this->assertFalse(
            P4Cms_Notifications::exist(),
            'Expect notifications to not exist after fetch with clearing.'
        );
        $this->assertEquals(
            0,
            P4Cms_Notifications::getCount(),
            'Expect notifications total to be zero after fetch with clearing.'
        );
    }

    /**
     * Test bulk addition of notifications.
     */
    public function testBulkNotifications()
    {
        // clear out existing notifications, if any.
        $notifications = P4Cms_Notifications::fetch();
        $this->assertFalse(
            P4Cms_Notifications::exist(),
            'Expect notifications to not exist after fetch with clearing.'
        );
        $this->assertEquals(
            0,
            P4Cms_Notifications::getCount(),
            'Expect notifications total to be zero after fetch with clearing.'
        );

        P4Cms_Notifications::add(array('one', 'two', 'three'));
        $this->assertTrue(
            P4Cms_Notifications::exist(),
            'Expect notifications to exist after bulk add.'
        );
        $this->assertEquals(
            3,
            P4Cms_Notifications::getCount(),
            'Expect notifications total to be three after bulk add.'
        );

        $notifications = P4Cms_Notifications::fetch();
        $this->assertSame(
            array('info' => array('one', 'two', 'three')),
            $notifications,
            'Expected notifications after bulk add.'
        );
    }

    /**
     * Test multiple notifications with differing severities.
     */
    public function testSeverities()
    {
        // clear out existing notifications, if any.
        $notifications = P4Cms_Notifications::fetch();
        $this->assertFalse(
            P4Cms_Notifications::exist(),
            'Expect notifications to not exist after fetch with clearing.'
        );
        $this->assertEquals(
            0,
            P4Cms_Notifications::getCount(),
            'Expect notifications total to be zero after fetch with clearing.'
        );

        // add notifications
        P4Cms_Notifications::add('a warning', 'warn');
        P4Cms_Notifications::add('an error', 'error');
        P4Cms_Notifications::add('information', 'info');
        P4Cms_Notifications::add('another warning', 'warn');
        P4Cms_Notifications::add('arbitrary notification', 'arbitrary');

        // overall existance/total
        $this->assertTrue(P4Cms_Notifications::exist(), 'Expect notifications to exist.');
        $this->assertEquals(5, P4Cms_Notifications::getCount(), 'Expect notifications total to be five.');

        $tests = array(
            'info'          => array('total' => 1, 'exist' => true),
            'warn'          => array('total' => 2, 'exist' => true),
            'error'         => array('total' => 1, 'exist' => true),
            'arbitrary'     => array('total' => 1, 'exist' => true),
            'nonexistant'   => array('total' => 0, 'exist' => false),
        );

        foreach ($tests as $severity => $values) {
            $not = $values['exist'] ? '' : ' not';
            $this->assertSame(
                $values['exist'],
                P4Cms_Notifications::exist($severity),
                "Expect $severity notifications to$not exist."
            );
            $this->assertEquals(
                $values['total'],
                P4Cms_Notifications::getCount($severity),
                "Expect $severity notifications total to be {$values['total']}."
            );
        }

        // fetch global notifications without clear
        $notifications = P4Cms_Notifications::fetch(null, false);
        $this->assertSame(
            array(
                'warn'      => array('a warning', 'another warning'),
                'error'     => array('an error'),
                'info'      => array('information'),
                'arbitrary' => array('arbitrary notification'),
            ),
            $notifications,
            'Expected global notifications.'
        );

        // fetch warn notifications with clear
        $notifications = P4Cms_Notifications::fetch('warn');
        $this->assertSame(
            array('a warning', 'another warning'),
            $notifications,
            'Expected warn notifications.'
        );

        // fetch global notifications without clear
        $notifications = P4Cms_Notifications::fetch(null, false);
        $this->assertSame(
            array(
                'warn'      => array(),
                'error'     => array('an error'),
                'info'      => array('information'),
                'arbitrary' => array('arbitrary notification'),
            ),
            $notifications,
            'Expected global notifications after removing warn notifications.'
        );

        // fetch arbitrary notifications without clear
        $notifications = P4Cms_Notifications::fetch('arbitrary', false);
        $this->assertSame(
            array('arbitrary notification'),
            $notifications,
            'Expected arbitrary notification.'
        );

        // fetch global notifications without clear
        $notifications = P4Cms_Notifications::fetch(null, false);
        $this->assertSame(
            array(
                'warn'      => array(),
                'error'     => array('an error'),
                'info'      => array('information'),
                'arbitrary' => array('arbitrary notification'),
            ),
            $notifications,
            'Expected global notifications after keeping arbitrary notifications.'
        );

    }
}
