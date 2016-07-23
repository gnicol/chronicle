<?php
/**
 * Stub to test module integration.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Navigable_Module extends P4Cms_Module_Integration
{

    /**
     * Subscribe to the relevant topics.
     */
    public static function load()
    {
       // contribute a handler which will provide dynamic menu items.
        P4Cms_PubSub::subscribe('p4cms.navigation.dynamicHandlers',
            function()
            {
                $handler = new P4Cms_Navigation_DynamicHandler;
                $handler->setId('test/test')
                        ->setLabel('Test')
                        ->setExpansionCallback(
                            function($item, $options)
                            {
                                $date = date('Y-M-d');
                                $entries = array();
                                for ($i = 0; $i < 5; $i++) {
                                    $page = array(
                                        'label'         => "Test #$i - $date",
                                        'uri'           => "http://test$i.$date.test/",
                                        'expansionId'   => "test$i",
                                        'pages'         => array(
                                            array(
                                                'label'         => "Test #$i.1 - $date",
                                                'uri'           => "http://test$i.1.$date.test/",
                                                'expansionId'   => "test{$i}_1",
                                                'pages'         => array(
                                                    array(
                                                        'label'         => "Test #$i.1.1 - $date",
                                                        'uri'           => "http://test$i.1.1.$date.test/",
                                                        'expansionId'   => "test{$i}_1_1",
                                                        'pages'         => array(
                                                            array(
                                                                'label'         => "Test #$i.1.1.last - $date",
                                                                'uri'           => "http://test$i.1.1.last.$date.test/",
                                                                'expansionId'   => "test{$i}_1_1_last",
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                    );
                                    $entries[] = $page;
                                }

                                return $entries;
                            }
                        );

                return array($handler);
            }
        );
    }
}
