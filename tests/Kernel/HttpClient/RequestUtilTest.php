<?php

namespace EasyWeChat\Tests\Kernel\HttpClient;

use EasyWeChat\Kernel\HttpClient\RequestUtil;
use EasyWeChat\Kernel\Support\UserAgent;
use EasyWeChat\Tests\TestCase;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;

class RequestUtilTest extends TestCase
{
    public function test_merge_default_retry_options()
    {
        $defaultRetryOptions = [
            'status_codes' => GenericRetryStrategy::DEFAULT_RETRY_STATUS_CODES,
            'delay' => 1000,
            'max_delay' => 0,
            'max_retries' => 3,
            'multiplier' => 2.0,
            'jitter' => 0.1,
        ];

        $this->assertSame($defaultRetryOptions, RequestUtil::mergeDefaultRetryOptions([]));
        $this->assertSame(
            \array_merge($defaultRetryOptions, ['max_retries' => 3, 'jitter' => 2]),
            RequestUtil::mergeDefaultRetryOptions(['max_retries' => 3, 'jitter' => 2])
        );
    }

    public function test_format_default_options()
    {
        $options = ['foo' => 'bar', 'headers' => ['User-Agent' => 'EasyWeChat']];

        $formatted = RequestUtil::formatDefaultOptions($options);

        $this->assertArrayNotHasKey('foo', $formatted);
        $this->assertArrayHasKey('User-Agent', $formatted['headers']);
        $this->assertSame('EasyWeChat', $formatted['headers']['User-Agent']);

        // test default User-Agent
        $options = ['foo' => 'bar', 'headers' => ['foo' => 'bar']];

        $formatted = RequestUtil::formatDefaultOptions($options);

        $this->assertArrayNotHasKey('foo', $formatted);
        $this->assertArrayHasKey('User-Agent', $formatted['headers']);
        $this->assertSame(UserAgent::create(), $formatted['headers']['User-Agent']);
    }

    public function test_format_xml_body()
    {
        // xml string
        $options = RequestUtil::formatBody(['xml' => '<xml><foo><![CDATA[bar]]></foo></xml>']);

        $this->assertArrayNotHasKey('xml', $options);
        $this->assertArrayHasKey('body', $options);
        $this->assertSame('<xml><foo><![CDATA[bar]]></foo></xml>', $options['body']);
        $this->assertSame(['Content-Type: text/xml'], $options['headers']['Content-Type']);

        // xml array
        $options = RequestUtil::formatBody(['xml' => ['foo' => 'bar']]);

        $this->assertArrayNotHasKey('xml', $options);
        $this->assertArrayHasKey('body', $options);
        $this->assertSame('<xml><foo><![CDATA[bar]]></foo></xml>', $options['body']);
        $this->assertSame(['Content-Type: text/xml'], $options['headers']['Content-Type']);

        // invalid xml
        $this->expectExceptionMessage('The type of `xml` must be string or array.');
        RequestUtil::formatBody(['xml' => true]);
    }

    public function test_format_json_body()
    {
        // json string
        $options = RequestUtil::formatBody(['json' => '{"foo":"bar"}']);

        $this->assertArrayNotHasKey('json', $options);
        $this->assertArrayHasKey('body', $options);
        $this->assertSame('{"foo":"bar"}', $options['body']);
        $this->assertSame(['Content-Type: application/json'], $options['headers']['Content-Type']);

        // json array
        $options = RequestUtil::formatBody(['json' => ['foo' => 'bar', 'chinese' => '中文']]);

        $this->assertArrayNotHasKey('json', $options);
        $this->assertArrayHasKey('body', $options);
        $this->assertSame('{"foo":"bar","chinese":"中文"}', $options['body']);

        // json empty array
        $options = RequestUtil::formatBody(['json' => []]);

        $this->assertArrayNotHasKey('json', $options);
        $this->assertArrayHasKey('body', $options);
        $this->assertSame('{}', $options['body']);

        // invalid json
        $this->expectExceptionMessage('The type of `json` must be string or array.');
        RequestUtil::formatBody(['json' => true]);
    }
}
