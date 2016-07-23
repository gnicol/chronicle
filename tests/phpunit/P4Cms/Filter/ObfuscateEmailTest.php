<?php
/**
 * Test the email obfuscation filter.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Filter_ObfuscateEmailTest extends TestCase
{
    /**
     * Test e-mail obfuscation.
     */
    public function testObfuscate()
    {
        $tests = array(
            array(
                "line"      => __LINE__,
                "text"      => 'joe@domain.com',
                "expected"  => '<script type="text/javascript">'
                            .  'document.write(function(d,u){'
                            .  'return u+"\x40"+d;}('
                            .  '"\x64\x6f\x6d\x61\x69\x6e\x2e\x63\x6f\x6d",'
                            .  '"\x6a\x6f\x65"));'
                            .  '</script>'
            ),
            array(
                "line"      => __LINE__,
                "text"      => '<a href="mailto:jdoe@domain.com">email</a>',
                "expected"  => '<a href="javascript:window.location.href=&quot;'
                            .  '\x6d\x61\x69\x6c\x74\x6f\x3a&quot;+'
                            .  'function(d,u){return u+&quot;\x40&quot;+d;}('
                            .  '&quot;\x64\x6f\x6d\x61\x69\x6e\x2e\x63\x6f\x6d&quot;,'
                            .  '&quot;\x6a\x64\x6f\x65&quot;);">email</a>'
            ),
            array(
                "line"      => __LINE__,
                "text"      => '<a href=\'mailto:j!#$%&\'*+/=?^_`{|}~-doe@domain.com\'>email</a>',
                "expected"  => '<a href=\'javascript:window.location.href=&quot;'
                            .  '\x6d\x61\x69\x6c\x74\x6f\x3a&quot;+'
                            .  'function(d,u){return u+&quot;\x40&quot;+d;}('
                            .  '&quot;\x64\x6f\x6d\x61\x69\x6e\x2e\x63\x6f\x6d&quot;,'
                            .  '&quot;\x6a\x21\x23\x24\x25\x26\x27\x2a\x2b\x2f\x3d'
                            .  '\x3f\x5e\x5f\x60\x7b\x7c\x7d\x7e\x2d\x64\x6f'
                            .  '\x65&quot;);\'>email</a>'
            ),
            array(
                "line"      => __LINE__,
                "text"      => '.joe@domain.com- earl+what@sub.foo.cc.',
                "expected"  => '.<script type="text/javascript">'
                            .  'document.write(function(d,u){'
                            .  'return u+"\x40"+d;}('
                            .  '"\x64\x6f\x6d\x61\x69\x6e\x2e\x63\x6f\x6d",'
                            .  '"\x6a\x6f\x65"));'
                            .  '</script>- '
                            .  '<script type="text/javascript">'
                            .  'document.write(function(d,u){'
                            .  'return u+"\x40"+d;}('
                            .  '"\x73\x75\x62\x2e\x66\x6f\x6f\x2e\x63\x63",'
                            .  '"\x65\x61\x72\x6c\x2b\x77\x68\x61\x74"));'
                            .  '</script>.'
            ),
        );

        $filter = new P4Cms_Filter_ObfuscateEmail;
        foreach ($tests as $test) {
            $filtered = $filter->filter($test['text']);
            $this->assertSame(
                $test['expected'],
                $filtered,
                "Expected obfuscated email (line: " . $test['line'] . ")"
            );
        }
    }
}
