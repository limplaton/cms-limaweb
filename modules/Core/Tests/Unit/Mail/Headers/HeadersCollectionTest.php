<?php
 

namespace Modules\Core\Tests\Unit\Mail\Headers;

use Modules\Core\App\Common\Mail\Headers\AddressHeader;
use Modules\Core\App\Common\Mail\Headers\DateHeader;
use Modules\Core\App\Common\Mail\Headers\Header;
use Modules\Core\App\Common\Mail\Headers\HeadersCollection;
use Modules\Core\App\Common\Mail\Headers\IdHeader;
use PHPUnit\Framework\TestCase;

class HeadersCollectionTest extends TestCase
{
    public function test_it_can_find_header_by_name()
    {
        $headers = new HeadersCollection([new Header('key', 'value')]);

        $this->assertInstanceOf(Header::class, $headers->find('key'));
        $this->assertInstanceOf(Header::class, $headers->find('Key'));
    }

    public function test_new_header_can_be_pushed()
    {
        $headers = new HeadersCollection([]);

        $headers->pushHeader('key', 'value');

        $this->assertCount(1, $headers->all());
    }

    public function test_id_headers_are_properly_mapped()
    {
        $headers = new HeadersCollection([]);

        $headers->pushHeader('message-id', '<unique-id1@example.com>')
            ->pushHeader('content-id', '<unique-id2@example.com>')
            ->pushHeader('in-reply-to', '<unique-id3@example.com>')
            ->pushHeader('references', '<unique-id4@example.com>');

        $this->assertInstanceOf(IdHeader::class, $headers->find('message-id'));
        $this->assertInstanceOf(IdHeader::class, $headers->find('content-id'));
        $this->assertInstanceOf(IdHeader::class, $headers->find('in-reply-to'));
        $this->assertInstanceOf(IdHeader::class, $headers->find('references'));
    }

    public function test_address_headers_are_properly_mapped()
    {
        $headers = new HeadersCollection([]);

        $headers->pushHeader('from', 'email-1@example.com')
            ->pushHeader('to', 'email-2@example.com')
            ->pushHeader('cc', 'email-3@example.com')
            ->pushHeader('bcc', 'email-4@example.com')
            ->pushHeader('reply-to', 'email-4@example.com')
            ->pushHeader('sender', 'email-4@example.com>');

        $this->assertInstanceOf(AddressHeader::class, $headers->find('from'));
        $this->assertInstanceOf(AddressHeader::class, $headers->find('to'));
        $this->assertInstanceOf(AddressHeader::class, $headers->find('cc'));
        $this->assertInstanceOf(AddressHeader::class, $headers->find('bcc'));
        $this->assertInstanceOf(AddressHeader::class, $headers->find('reply-to'));
        $this->assertInstanceOf(AddressHeader::class, $headers->find('sender'));
    }

    public function test_date_headers_are_properly_mapped()
    {
        $headers = new HeadersCollection([]);

        $headers->pushHeader('date', '2021-01-20 12:00:00')
            ->pushHeader('resentdate', '2021-01-20 12:00:00')
            ->pushHeader('deliverydate', '2021-01-20 12:00:00')
            ->pushHeader('expires', '2021-01-20 12:00:00')
            ->pushHeader('expirydate', '2021-01-20 12:00:00')
            ->pushHeader('replyby', '2021-01-20 12:00:00>');

        $this->assertInstanceOf(DateHeader::class, $headers->find('date'));
        $this->assertInstanceOf(DateHeader::class, $headers->find('resentdate'));
        $this->assertInstanceOf(DateHeader::class, $headers->find('deliverydate'));
        $this->assertInstanceOf(DateHeader::class, $headers->find('expires'));
        $this->assertInstanceOf(DateHeader::class, $headers->find('expirydate'));
        $this->assertInstanceOf(DateHeader::class, $headers->find('replyby'));
    }

    public function test_generic_header_is_used_if_no_map_exists()
    {
        $headers = new HeadersCollection([]);

        $headers->pushHeader('unknown-header', 'value');

        $this->assertInstanceOf(Header::class, $headers->find('unknown-header'));
    }
}
