<?php
namespace Core;
use Codeception\Example;
use SilverStripe\Core\Convert;
use SilverStripe\View\Parsers\URLSegmentFilter;
use \UnitTester;

class ConvertCest
{

    /**
     * @var string
     */
    private $previousLocaleSetting;

    public function _before(UnitTester $I)
    {
        // clear the previous locale setting
        $this->previousLocaleSetting = null;
    }

    public function _after(UnitTester $I)
    {
        // If a test sets the locale, reset it on teardown
        if ($this->previousLocaleSetting) {
            setlocale(LC_CTYPE, $this->previousLocaleSetting);
        }
    }

    // tests
    /**
     * Tests {@link Convert::raw2att()}
     */
    public function testRaw2Att(UnitTester $I)
    {
        $val1 = '<input type="text">';
        $I->assertEquals(
            '&lt;input type=&quot;text&quot;&gt;',
            Convert::raw2att($val1),
            'Special characters are escaped'
        );

        $val2 = 'This is some normal text.';
        $I->assertEquals(
            'This is some normal text.',
            Convert::raw2att($val2),
            'Normal text is not escaped'
        );
    }

    /**
     * Tests {@link Convert::raw2htmlatt()}
     */
    public function testRaw2HtmlAtt(UnitTester $I)
    {
        $val1 = '<input type="text">';
        $I->assertEquals(
            '&lt;input type=&quot;text&quot;&gt;',
            Convert::raw2htmlatt($val1),
            'Special characters are escaped'
        );

        $val2 = 'This is some normal text.';
        $I->assertEquals(
            'This is some normal text.',
            Convert::raw2htmlatt($val2),
            'Normal text is not escaped'
        );
    }

    /**
     * Tests {@link Convert::html2raw()}
     */
    public function testHtml2raw(UnitTester $I)
    {
        $val1 = 'This has a <strong>strong tag</strong>.';
        $I->assertEquals(
            'This has a *strong tag*.',
            Convert::html2raw($val1),
            'Strong tags are replaced with asterisks'
        );

        $val1 = 'This has a <b class="test" style="font-weight: bold">b tag with attributes</b>.';
        $I->assertEquals(
            'This has a *b tag with attributes*.',
            Convert::html2raw($val1),
            'B tags with attributes are replaced with asterisks'
        );

        $val2 = 'This has a <strong class="test" style="font-weight: bold">strong tag with attributes</STRONG>.';
        $I->assertEquals(
            'This has a *strong tag with attributes*.',
            Convert::html2raw($val2),
            'Strong tags with attributes are replaced with asterisks'
        );

        $val3 = '<script type="application/javascript">Some really nasty javascript here</script>';
        $I->assertEquals(
            '',
            Convert::html2raw($val3),
            'Script tags are completely removed'
        );

        $val4 = '<style type="text/css">Some really nasty CSS here</style>';
        $I->assertEquals(
            '',
            Convert::html2raw($val4),
            'Style tags are completely removed'
        );

        $val5 = "<script type=\"application/javascript\">Some really nasty\nmultiline javascript here</script>";
        $I->assertEquals(
            '',
            Convert::html2raw($val5),
            'Multiline script tags are completely removed'
        );

        $val6 = "<style type=\"text/css\">Some really nasty\nmultiline CSS here</style>";
        $I->assertEquals(
            '',
            Convert::html2raw($val6),
            'Multiline style tags are completely removed'
        );

        $val7 = '<p>That&#39;s absolutely correct</p>';
        $I->assertEquals(
            "That's absolutely correct",
            Convert::html2raw($val7),
            "Single quotes are decoded correctly"
        );

        $val8 = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor '.
            'incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud '.
            'exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute '.
            'irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla '.
            'pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia '.
            'deserunt mollit anim id est laborum.';
        $I->assertEquals($val8, Convert::html2raw($val8), 'Test long text is unwrapped');
        $I->assertEquals(
            <<<PHP
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed
do eiusmod tempor incididunt ut labore et dolore magna
aliqua. Ut enim ad minim veniam, quis nostrud exercitation
ullamco laboris nisi ut aliquip ex ea commodo consequat.
Duis aute irure dolor in reprehenderit in voluptate velit
esse cillum dolore eu fugiat nulla pariatur. Excepteur sint
occaecat cupidatat non proident, sunt in culpa qui officia
deserunt mollit anim id est laborum.
PHP
            ,
            Convert::html2raw($val8, false, 60),
            'Test long text is wrapped'
        );
    }

    /**
     * Tests {@link Convert::raw2xml()}
     */
    public function testRaw2Xml(UnitTester $I)
    {
        $val1 = '<input type="text">';
        $I->assertEquals(
            '&lt;input type=&quot;text&quot;&gt;',
            Convert::raw2xml($val1),
            'Special characters are escaped'
        );

        $val2 = 'This is some normal text.';
        $I->assertEquals(
            'This is some normal text.',
            Convert::raw2xml($val2),
            'Normal text is not escaped'
        );

        $val3 = "This is test\nNow on a new line.";
        $I->assertEquals(
            "This is test\nNow on a new line.",
            Convert::raw2xml($val3),
            'Newlines are retained. They should not be replaced with <br /> as it is not XML valid'
        );
    }

    /**
     * Tests {@link Convert::raw2htmlid()}
     */
    public function testRaw2HtmlID(UnitTester $I)
    {
        $val1 = 'test test 123';
        $I->assertEquals('test_test_123', Convert::raw2htmlid($val1));

        $val2 = 'test[test][123]';
        $I->assertEquals('test_test_123', Convert::raw2htmlid($val2));

        $val3 = '[test[[test]][123]]';
        $I->assertEquals('test_test_123', Convert::raw2htmlid($val3));

        $val4 = 'A\\Namespaced\\Class';
        $I->assertEquals('A_Namespaced_Class', Convert::raw2htmlid($val4));
    }

    /**
     * Tests {@link Convert::xml2raw()}
     */
    public function testXml2Raw(UnitTester $I)
    {
        $val1 = '&lt;input type=&quot;text&quot;&gt;';
        $I->assertEquals('<input type="text">', Convert::xml2raw($val1), 'Special characters are escaped');

        $val2 = 'This is some normal text.';
        $I->assertEquals('This is some normal text.', Convert::xml2raw($val2), 'Normal text is not escaped');
    }

    /**
     * Tests {@link Convert::xml2raw()}
     */
    public function testArray2JSON(UnitTester $I)
    {
        $val = array(
            'Joe' => 'Bloggs',
            'Tom' => 'Jones',
            'My' => array(
                'Complicated' => 'Structure'
            )
        );
        $encoded = Convert::array2json($val);
        $I->assertEquals(
            '{"Joe":"Bloggs","Tom":"Jones","My":{"Complicated":"Structure"}}',
            $encoded,
            'Array is encoded in JSON'
        );
    }

    /**
     * Tests {@link Convert::json2array()}
     */
    public function testJSON2Array(UnitTester $I)
    {
        $val = '{"Joe":"Bloggs","Tom":"Jones","My":{"Complicated":"Structure"}}';
        $decoded = Convert::json2array($val);
        $I->assertEquals(3, count($decoded), '3 items in the decoded array');
        $I->assertContains('Bloggs', $decoded, 'Contains "Bloggs" value in decoded array');
        $I->assertContains('Jones', $decoded, 'Contains "Jones" value in decoded array');
        $I->assertContains('Structure', $decoded['My']['Complicated']);
    }

    /**
     * Tests {@link Convert::testJSON2Obj()}
     */
    public function testJSON2Obj(UnitTester $I)
    {
        $val = '{"Joe":"Bloggs","Tom":"Jones","My":{"Complicated":"Structure"}}';
        $obj = Convert::json2obj($val);
        $I->assertEquals('Bloggs', $obj->Joe);
        $I->assertEquals('Jones', $obj->Tom);
        $I->assertEquals('Structure', $obj->My->Complicated);
    }

    /**
     * Tests {@link Convert::testRaw2URL()}
     *
     * @todo test toASCII()
     */
    public function testRaw2URL(UnitTester $I)
    {
        URLSegmentFilter::config()->update('default_allow_multibyte', false);
        $I->assertEquals('foo', Convert::raw2url('foo'));
        $I->assertEquals('foo-and-bar', Convert::raw2url('foo & bar'));
        $I->assertEquals('foo-and-bar', Convert::raw2url('foo &amp; bar!'));
        $I->assertEquals('foos-bar-2', Convert::raw2url('foo\'s [bar] (2)'));
    }

    /**
     * Tests {@link Convert::nl2os()}
     */
    public function testNL2OS(UnitTester $I)
    {

        foreach (array("\r\n", "\r", "\n") as $nl) {
            // Base case: no action
            $I->assertEquals(
                "Base case",
                Convert::nl2os("Base case", $nl)
            );

            // Mixed formats
            $I->assertEquals(
                "Test{$nl}Text{$nl}Is{$nl}{$nl}Here{$nl}.",
                Convert::nl2os("Test\rText\r\nIs\n\rHere\r\n.", $nl)
            );

            // Test that multiple runs are non-destructive
            $expected = "Test{$nl}Text{$nl}Is{$nl}{$nl}Here{$nl}.";
            $I->assertEquals(
                $expected,
                Convert::nl2os($expected, $nl)
            );

            // Check repeated sequence behaves correctly
            $expected = "{$nl}{$nl}{$nl}{$nl}{$nl}{$nl}{$nl}{$nl}";
            $input = "\r\r\n\r\r\n\n\n\n\r";
            $I->assertEquals(
                $expected,
                Convert::nl2os($input, $nl)
            );
        }
    }

    /**
     * Tests {@link Convert::raw2js()}
     */
    public function testRaw2JS(UnitTester $I)
    {
        // Test attempt to break out of string
        $I->assertEquals(
            '\\"; window.location=\\"http://www.google.com',
            Convert::raw2js('"; window.location="http://www.google.com')
        );
        $I->assertEquals(
            '\\\'; window.location=\\\'http://www.google.com',
            Convert::raw2js('\'; window.location=\'http://www.google.com')
        );
        // Test attempt to close script tag
        $I->assertEquals(
            '\\"; \\x3c/script\\x3e\\x3ch1\\x3eHa \\x26amp; Ha\\x3c/h1\\x3e\\x3cscript\\x3e',
            Convert::raw2js('"; </script><h1>Ha &amp; Ha</h1><script>')
        );
        // Test newlines are properly escaped
        $I->assertEquals(
            'New\\nLine\\rReturn',
            Convert::raw2js("New\nLine\rReturn")
        );
        // Check escape of slashes
        $I->assertEquals(
            '\\\\\\"\\x3eClick here',
            Convert::raw2js('\\">Click here')
        );
    }

    /**
     * Tests {@link Convert::raw2json()}
     */
    public function testRaw2JSON(UnitTester $I)
    {

        // Test object
        $input = new \stdClass();
        $input->Title = 'My Object';
        $input->Content = '<p>Data</p>';
        $I->assertEquals(
            '{"Title":"My Object","Content":"<p>Data<\/p>"}',
            Convert::raw2json($input)
        );

        // Array
        $array = array('One' => 'Apple', 'Two' => 'Banana');
        $I->assertEquals(
            '{"One":"Apple","Two":"Banana"}',
            Convert::raw2json($array)
        );

        // String value with already encoded data. Result should be quoted.
        $value = '{"Left": "Value"}';
        $I->assertEquals(
            '"{\\"Left\\": \\"Value\\"}"',
            Convert::raw2json($value)
        );
    }

    /**
     * Test that a context bitmask can be passed through to the json_encode method in {@link Convert::raw2json()}
     * and in {@link Convert::array2json()}
     */
    public function testRaw2JsonWithContext(UnitTester $I)
    {
        $data = array('foo' => 'b"ar');
        $expected = '{"foo":"b\u0022ar"}';
        $result = Convert::raw2json($data, JSON_HEX_QUOT);
        $I->assertSame($expected, $result);
        $wrapperResult = Convert::array2json($data, JSON_HEX_QUOT);
        $I->assertSame($expected, $wrapperResult);
    }

    /**
     * Tests {@link Convert::xml2array()}
     */
    public function testXML2Array(UnitTester $I)
    {
        $ex = null;
        // Ensure an XML file at risk of entity expansion can be avoided safely
        $inputXML = <<<XML
<?xml version="1.0"?>
<!DOCTYPE results [<!ENTITY long "SOME_SUPER_LONG_STRING">]>
<results>
    <result>Now include &long; lots of times to expand the in-memory size of this XML structure</result>
    <result>&long;&long;&long;</result>
</results>
XML
        ;
        try {
            Convert::xml2array($inputXML, true);
        } catch (\Exception $ex) {
        }
        $I->assertNotNull($ex);
        $I->assertInstanceOf(\InvalidArgumentException::class, $ex);
        $I->assertEquals('XML Doctype parsing disabled', $ex->getMessage());

        // Test without doctype validation
        $expected = array(
            'result' => array(
                "Now include SOME_SUPER_LONG_STRING lots of times to expand the in-memory size of this XML structure",
                array(
                    'long' => array(
                        array(
                            'long' => 'SOME_SUPER_LONG_STRING'
                        ),
                        array(
                            'long' => 'SOME_SUPER_LONG_STRING'
                        ),
                        array(
                            'long' => 'SOME_SUPER_LONG_STRING'
                        )
                    )
                )
            )
        );
        $result = Convert::xml2array($inputXML, false, true);
        $I->assertEquals(
            $expected,
            $result
        );
        $result = Convert::xml2array($inputXML, false, false);
        $I->assertEquals(
            $expected,
            $result
        );
    }

    /**
     * Tests {@link Convert::base64url_encode()} and {@link Convert::base64url_decode()}
     */
    public function testBase64url(UnitTester $I)
    {
        $data = 'Wëīrð characters ☺ such as ¤Ø¶÷╬';
        // This requires this test file to have UTF-8 character encoding
        $I->assertEquals(
            $data,
            Convert::base64url_decode(Convert::base64url_encode($data))
        );

        $data = 654.423;
        $I->assertEquals(
            $data,
            Convert::base64url_decode(Convert::base64url_encode($data))
        );

        $data = true;
        $I->assertEquals(
            $data,
            Convert::base64url_decode(Convert::base64url_encode($data))
        );

        $data = array('simple','array','¤Ø¶÷╬');
        $I->assertEquals(
            $data,
            Convert::base64url_decode(Convert::base64url_encode($data))
        );

        $data = array(
            'a'  => 'associative',
            4    => 'array',
            '☺' => '¤Ø¶÷╬'
        );
        $I->assertEquals(
            $data,
            Convert::base64url_decode(Convert::base64url_encode($data))
        );
    }

    public function testValidUtf8(UnitTester $I)
    {
        // Install a UTF-8 locale
        $this->previousLocaleSetting = setlocale(LC_CTYPE, 0);

        $locales = array('en_US.UTF-8', 'en_NZ.UTF-8', 'de_DE.UTF-8');
        $localeInstalled = false;
        foreach ($locales as $locale) {
            if ($localeInstalled = setlocale(LC_CTYPE, $locale)) {
                break;
            }
        }

        // If the system doesn't have any of the UTF-8 locales, exit early
        if ($localeInstalled === false) {
            $this->markTestIncomplete('Unable to run this test because of missing locale!');
            return;
        }

        $problematicText = html_entity_decode('<p>This is a&nbsp;Test with non-breaking&nbsp;space!</p>', ENT_COMPAT, 'UTF-8');

        $I->assertTrue(mb_check_encoding(Convert::html2raw($problematicText), 'UTF-8'));
    }

    public function testUpperCamelToLowerCamel(UnitTester $I)
    {
        $I->assertEquals(
            'd',
            Convert::upperCamelToLowerCamel('D'),
            'Single character'
        );
        $I->assertEquals(
            'id',
            Convert::upperCamelToLowerCamel('ID'),
            'Multi leading upper without trailing lower'
        );
        $I->assertEquals(
            'id',
            Convert::upperCamelToLowerCamel('Id'),
            'Single leading upper with trailing lower'
        );
        $I->assertEquals(
            'idField',
            Convert::upperCamelToLowerCamel('IdField'),
            'Single leading upper with trailing upper camel'
        );
        $I->assertEquals(
            'idField',
            Convert::upperCamelToLowerCamel('IDField'),
            'Multi leading upper with trailing upper camel'
        );
        $I->assertEquals(
            'iDField',
            Convert::upperCamelToLowerCamel('iDField'),
            'Single leading lower with trailing upper camel'
        );
        $I->assertEquals(
            '_IDField',
            Convert::upperCamelToLowerCamel('_IDField'),
            'Non-alpha leading  with trailing upper camel'
        );
    }

    /**
     * Test that memstring2bytes returns the number of bytes for a PHP ini style size declaration
     *
     * @param string $memString
     * @param int    $expected
     * @dataProvider memString2BytesProvider
     */
    public function testMemString2Bytes(UnitTester $I, Example $example)
    {
        list($memString, $expected) = $example;
        $I->assertSame($expected, Convert::memstring2bytes($memString));
    }

    /**
     * @return array
     */
    protected function memString2BytesProvider()
    {
        return [
            ['2048', (float)(2 * 1024)],
            ['2k', (float)(2 * 1024)],
            ['512M', (float)(512 * 1024 * 1024)],
            ['512MiB', (float)(512 * 1024 * 1024)],
            ['512 mbytes', (float)(512 * 1024 * 1024)],
            ['512 megabytes', (float)(512 * 1024 * 1024)],
            ['1024g', (float)(1024 * 1024 * 1024 * 1024)],
            ['1024G', (float)(1024 * 1024 * 1024 * 1024)]
        ];
    }

    /**
     * Test that bytes2memstring returns a binary prefixed string representing the number of bytes
     *
     * @dataProvider bytes2MemStringProvider
     */
    public function testBytes2MemString(UnitTester $I, Example $example)
    {
        list($bytes, $expected) = $example;
        $I->assertSame($expected, Convert::bytes2memstring($bytes));
    }

    /**
     * @return array
     */
    protected function bytes2MemStringProvider()
    {
        return [
            [200, '200B'],
            [(2 * 1024), '2K'],
            [(512 * 1024 * 1024), '512M'],
            [(512 * 1024 * 1024 * 1024), '512G'],
            [(512 * 1024 * 1024 * 1024 * 1024), '512T'],
            [(512 * 1024 * 1024 * 1024 * 1024 * 1024), '512P']
        ];
    }
}
