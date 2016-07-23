<?php
/**
 * Test methods for the P4Cms Validate RobotsTxt class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Validate_RobotsTxtTest extends TestCase
{
    /**
     * Test instantiation.
     */
    public function testInstantiation()
    {
        $validator = new P4Cms_Validate_RobotsTxt;
        $this->assertTrue($validator instanceof P4Cms_Validate_RobotsTxt, 'Expected class');
    }

    /**
     * Test isValid.
     */
    public function testIsValid()
    {
        $tests = array(
            array(
                'label'   => __LINE__ .': null',
                'value'   => null,
                'valid'   => true,
                'error'   => array()
            ),
            array(
                'label'   => __LINE__ .': empty string',
                'value'   => '',
                'valid'   => true
            ),
            array(
                'label'   => __LINE__ .': valid simple',
                'value'   => <<<EOV
# an example
User-agent: *
Crawl-delay: 30
Disallow:
Allow:
Sitemap: abc
EOV
                ,
                'valid'   => true,
                'error'   => array(),
            ),
            array(
                'label'   => __LINE__ .': unknown directive',
                'value'   => <<<EOV
# an example
User-agent: *
Bogus-directive:
Disallow:
EOV
                ,
                'valid'   => true,
                'error'   => array(),
            ),
            array(
                'label'   => __LINE__ .': just numeric',
                'value'   => 1,
                'valid'   => false,
                'error'   => array(
                    'directiveBeforeUserAgent'
                        => 'The User-agent directive must precede any other per-record directives.'
                ),
            ),
            array(
                'label'   => __LINE__ .': disallow before user agent',
                'value'   => <<<EOV
Disallow:
User-agent: *
EOV
                ,
                'valid'   => false,
                'error'   => array(
                    'directiveBeforeUserAgent'
                        => 'The User-agent directive must precede any other per-record directives.'
                ),
            ),
            array(
                'label'   => __LINE__ .': disallow before user agent in record 2',
                'value'   => <<<EOV
User-agent: google
Disallow:

Disallow:
User-agent: *
EOV
                ,
                'valid'   => false,
                'error'   => array(
                    'directiveBeforeUserAgent'
                        => 'The User-agent directive must precede any other per-record directives.'
                ),
            ),
            array(
                'label'   => __LINE__ .': allow before user agent',
                'value'   => <<<EOV
Allow:
User-agent: *
EOV
                ,
                'valid'   => false,
                'error'   => array(
                    'directiveBeforeUserAgent'
                        => 'The User-agent directive must precede any other per-record directives.'
                ),
            ),
            array(
                'label'   => __LINE__ .': allow before user agent in record 2',
                'value'   => <<<EOV
User-agent: google
Allow:

Allow:
User-agent: *
EOV
                ,
                'valid'   => false,
                'error'   => array(
                    'directiveBeforeUserAgent'
                        => 'The User-agent directive must precede any other per-record directives.'
                ),
            ),
            array(
                'label'   => __LINE__ .': empty record',
                'value'   => <<<EOV
User-agent: *
EOV
                ,
                'valid'   => true,
                'error'   => array(),
            ),
            array(
                'label'   => __LINE__ .': empty record in the middle',
                'value'   => <<<EOV
User-agent: google
Disallow:

User-agent: *

User-agent: yahoo
Disallow:
EOV
                ,
                'valid'   => true,
                'error'   => array(),
            ),
            array(
                'label'   => __LINE__ .': just a sitemap',
                'value'   => "Sitemap: abc",
                'valid'   => true,
                'error'   => array(),
            ),
            array(
                'label'   => __LINE__ .': sitemap followed by whitespace',
                'value'   => "Sitemap: ",
                'valid'   => false,
                'error'   => array('sitemapIncomplete' => 'A Sitemap directive is missing a sitemap URL.'),
            ),
            array(
                'label'   => __LINE__ .': sitemap with no URL',
                'value'   => "Sitemap:",
                'valid'   => false,
                'error'   => array('sitemapIncomplete' => 'A Sitemap directive is missing a sitemap URL.'),
            ),
            array(
                'label'   => __LINE__ .': user agent with just whitespace',
                'value'   => "User-agent: ",
                'valid'   => false,
                'error'   => array(
                    'userAgentIncomplete' => 'A User-agent directive is missing a user agent identifier.'
                ),
            ),
            array(
                'label'   => __LINE__ .': user agent with no identifier',
                'value'   => "User-agent: ",
                'valid'   => false,
                'error'   => array(
                    'userAgentIncomplete' => 'A User-agent directive is missing a user agent identifier.'
                ),
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $validator = new P4Cms_Validate_RobotsTxt;

            $this->assertSame(
                $test['valid'],
                $validator->isValid($test['value']),
                "$label - Expected validation result.". join("\n", $validator->getMessages())
            );

            if (isset($test['error'])) {
                $this->assertSame(
                    $test['error'],
                    $validator->getMessages(),
                    "$label - Expected error message(s)"
                );
            }
        }
    }
}