<?php
namespace Core\Startup;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Startup\ParameterConfirmationToken;
use SilverStripe\Core\Tests\Startup\ParameterConfirmationTokenTest\ParameterConfirmationTokenTest_Token;
use SilverStripe\Core\Tests\Startup\ParameterConfirmationTokenTest\ParameterConfirmationTokenTest_ValidToken;
use \UnitTester;

class ParameterConfirmationTokenCest
{
    /**
     * @var HTTPRequest
     */
    protected $request = null;

    public function _before(UnitTester $I)
    {
        $get = [];
        $get['parameterconfirmationtokentest_notoken'] = 'value';
        $get['parameterconfirmationtokentest_empty'] = '';
        $get['parameterconfirmationtokentest_withtoken'] = '1';
        $get['parameterconfirmationtokentest_withtokentoken'] = 'dummy';
        $get['parameterconfirmationtokentest_nulltoken'] = '1';
        $get['parameterconfirmationtokentest_nulltokentoken'] = null;
        $get['parameterconfirmationtokentest_emptytoken'] = '1';
        $get['parameterconfirmationtokentest_emptytokentoken'] = '';
        $get['BackURL'] = 'page?parameterconfirmationtokentest_backtoken=1';
        $this->request = new HTTPRequest('GET', 'anotherpage', $get);
        $this->request->setSession(new Session([]));
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function testParameterDetectsParameters(UnitTester $I)
    {
        $withoutToken = new ParameterConfirmationTokenTest_Token('parameterconfirmationtokentest_notoken', $this->request);
        $emptyParameter = new ParameterConfirmationTokenTest_Token('parameterconfirmationtokentest_empty', $this->request);
        $withToken = new ParameterConfirmationTokenTest_ValidToken('parameterconfirmationtokentest_withtoken', $this->request);
        $withoutParameter = new ParameterConfirmationTokenTest_Token('parameterconfirmationtokentest_noparam', $this->request);
        $nullToken = new ParameterConfirmationTokenTest_Token('parameterconfirmationtokentest_nulltoken', $this->request);
        $emptyToken = new ParameterConfirmationTokenTest_Token('parameterconfirmationtokentest_emptytoken', $this->request);
        $backToken = new ParameterConfirmationTokenTest_Token('parameterconfirmationtokentest_backtoken', $this->request);

        // Check parameter
        $I->assertTrue($withoutToken->parameterProvided());
        $I->assertTrue($emptyParameter->parameterProvided());  // even if empty, it's still provided
        $I->assertTrue($withToken->parameterProvided());
        $I->assertFalse($withoutParameter->parameterProvided());
        $I->assertTrue($nullToken->parameterProvided());
        $I->assertTrue($emptyToken->parameterProvided());
        $I->assertFalse($backToken->parameterProvided());

        // Check backurl
        $I->assertFalse($withoutToken->existsInReferer());
        $I->assertFalse($emptyParameter->existsInReferer());  // even if empty, it's still provided
        $I->assertFalse($withToken->existsInReferer());
        $I->assertFalse($withoutParameter->existsInReferer());
        $I->assertFalse($nullToken->existsInReferer());
        $I->assertFalse($emptyToken->existsInReferer());
        $I->assertTrue($backToken->existsInReferer());

        // Check token
        $I->assertFalse($withoutToken->tokenProvided());
        $I->assertFalse($emptyParameter->tokenProvided());
        $I->assertTrue($withToken->tokenProvided()); // Actually forced to true for this test
        $I->assertFalse($withoutParameter->tokenProvided());
        $I->assertFalse($nullToken->tokenProvided());
        $I->assertFalse($emptyToken->tokenProvided());
        $I->assertFalse($backToken->tokenProvided());

        // Check if reload is required
        $I->assertTrue($withoutToken->reloadRequired());
        $I->assertTrue($emptyParameter->reloadRequired());
        $I->assertFalse($withToken->reloadRequired());
        $I->assertFalse($withoutParameter->reloadRequired());
        $I->assertTrue($nullToken->reloadRequired());
        $I->assertTrue($emptyToken->reloadRequired());
        $I->assertFalse($backToken->reloadRequired());

        // Check if a reload is required in case of error
        $I->assertTrue($withoutToken->reloadRequiredIfError());
        $I->assertTrue($emptyParameter->reloadRequiredIfError());
        $I->assertFalse($withToken->reloadRequiredIfError());
        $I->assertFalse($withoutParameter->reloadRequiredIfError());
        $I->assertTrue($nullToken->reloadRequiredIfError());
        $I->assertTrue($emptyToken->reloadRequiredIfError());
        $I->assertTrue($backToken->reloadRequiredIfError());

        // Check redirect url
        $home = (BASE_URL ?: '/') . '?';
        $current = Controller::join_links(BASE_URL, '/', 'anotherpage') . '?';
        $I->assertStringStartsWith($current, $withoutToken->redirectURL());
        $I->assertStringStartsWith($current, $emptyParameter->redirectURL());
        $I->assertStringStartsWith($current, $nullToken->redirectURL());
        $I->assertStringStartsWith($current, $emptyToken->redirectURL());
        $I->assertStringStartsWith($home, $backToken->redirectURL());

        // Check suppression
        $I->assertEquals('value', $this->request->getVar('parameterconfirmationtokentest_notoken'));
        $withoutToken->suppress();
        $I->assertNull($this->request->getVar('parameterconfirmationtokentest_notoken'));
    }

    public function testPrepareTokens(UnitTester $I)
    {
        // Test priority ordering
        $token = ParameterConfirmationToken::prepare_tokens(
            [
                'parameterconfirmationtokentest_notoken',
                'parameterconfirmationtokentest_empty',
                'parameterconfirmationtokentest_noparam'
            ],
            $this->request
        );
        // Test no invalid tokens
        $I->assertEquals('parameterconfirmationtokentest_empty', $token->getName());
        $token = ParameterConfirmationToken::prepare_tokens(
            [ 'parameterconfirmationtokentest_noparam' ],
            $this->request
        );
        $I->assertEmpty($token);

        // Test backurl token
        $token = ParameterConfirmationToken::prepare_tokens(
            [ 'parameterconfirmationtokentest_backtoken' ],
            $this->request
        );
        $I->assertEquals('parameterconfirmationtokentest_backtoken', $token->getName());
    }

    /**
     * @return array
     */
    protected function URLProvider()
    {
        return [
            [''],
            ['/'],
            ['bar'],
            ['bar/'],
            ['/bar'],
            ['/bar/'],
        ];
    }

    /**
     * currentAbsoluteURL needs to handle base or url being missing, or any combination of slashes.
     *
     * There should always be exactly one slash between each part in the result, and any trailing slash
     * should be preserved.
     *
     * @dataProvider URLProvider
     */
    public function testCurrentAbsoluteURLHandlesSlashes(UnitTester $I, \Codeception\Example $example)
    {
        $url = $example[0];
        $this->request->setUrl($url);

        $token = new ParameterConfirmationTokenTest_Token(
            'parameterconfirmationtokentest_parameter',
            $this->request
        );
        $expected = rtrim(Controller::join_links(BASE_URL, '/', $url), '/') ?: '/';
        $I->assertEquals($expected, $token->currentURL(), "Invalid redirect for request url $url");
    }
}
