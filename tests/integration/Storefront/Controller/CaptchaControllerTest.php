<?php declare(strict_types=1);

namespace Shopware\Tests\Integration\Storefront\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Framework\Captcha\BasicCaptcha;
use Shopware\Storefront\Test\Controller\StorefrontControllerTestBehaviour;

/**
 * @internal
 */
class CaptchaControllerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;
    use StorefrontControllerTestBehaviour;

    public function testLoadBasicCaptchaContent(): void
    {
        $browser = KernelLifecycleManager::createBrowser($this->getKernel());

        $browser->request('GET', $_SERVER['APP_URL'] . '/basic-captcha');

        $response = $browser->getResponse();

        static::assertSame(200, $response->getStatusCode(), (string) $response->getContent());
    }

    public function testValidateCaptcha(): void
    {
        $browser = KernelLifecycleManager::createBrowser($this->getKernel());
        $browser->setServerParameter('HTTP_X-Requested-With', 'XMLHttpRequest');

        $systemConfig = static::getContainer()->get(SystemConfigService::class);

        $systemConfig->set('core.basicInformation.activeCaptchasV2', [
            BasicCaptcha::CAPTCHA_NAME => [
                'name' => BasicCaptcha::CAPTCHA_NAME,
                'isActive' => true,
            ],
        ]);

        $formId = 'Kyln-test';
        $basicCaptchaSession = 'kylnsession';
        $this->getSession()->set($formId . 'basic_captcha_session', 'kylnsession');

        $payload = [
            'formId' => $formId,
            'shopware_basic_captcha_confirm' => $basicCaptchaSession,
        ];

        // Basic Captcha Valid
        $browser->request('POST', $_SERVER['APP_URL'] . '/basic-captcha-validate', $this->tokenize('frontend.captcha.basic-captcha.validate', $payload));

        $response = $browser->getResponse();
        static::assertSame(200, $response->getStatusCode());
        static::assertArrayHasKey('session', json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR));

        // BasicCaptcha Invalid
        $this->getSession()->set($formId . 'basic_captcha_session', 'invalid');
        $browser->request('POST', $_SERVER['APP_URL'] . '/basic-captcha-validate', $payload);

        $response = $browser->getResponse();
        static::assertSame(200, $response->getStatusCode());
        static::assertArrayHasKey('error', json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR)[0]);
    }
}
