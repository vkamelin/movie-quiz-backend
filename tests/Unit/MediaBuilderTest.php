<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\MediaBuilder;
use PHPUnit\Framework\TestCase;

final class MediaBuilderTest extends TestCase
{
    public function testParseModeNullNotAdded(): void
    {
        $media = MediaBuilder::buildInputMedia('photo', 'https://example.com/a.jpg', [
            'caption' => 'One',
            'parse_mode' => null,
        ]);

        $this->assertArrayHasKey('caption', $media);
        $this->assertArrayNotHasKey('parse_mode', $media);
    }

    public function testPrepareMediaDataFiltersNull(): void
    {
        $data = MediaBuilder::prepareMediaData(123, 'video', 'https://example.com/b.mp4', 'Video', [
            'width' => 640,
            'height' => null,
            'supports_streaming' => null,
        ]);

        $this->assertArrayHasKey('width', $data);
        $this->assertArrayNotHasKey('height', $data);
        $this->assertArrayNotHasKey('supports_streaming', $data);
    }

    public function testInputMediaPhotoHandlesOptions(): void
    {
        $media = MediaBuilder::inputMediaPhoto(
            'https://example.com/p.jpg',
            'Hi',
            'Markdown',
            true,
            true
        );

        $this->assertSame('photo', $media['type']);
        $this->assertSame('https://example.com/p.jpg', $media['media']);
        $this->assertSame('Hi', $media['caption']);
        $this->assertSame('Markdown', $media['parse_mode']);
        $this->assertTrue($media['show_caption_above_media']);
        $this->assertTrue($media['has_spoiler']);
    }

    public function testInputMediaPhotoSkipsEmptyValues(): void
    {
        $media = MediaBuilder::inputMediaPhoto('https://example.com/p.jpg', '');

        $this->assertArrayNotHasKey('caption', $media);
        $this->assertArrayNotHasKey('parse_mode', $media);
        $this->assertArrayNotHasKey('show_caption_above_media', $media);
        $this->assertArrayNotHasKey('has_spoiler', $media);
    }

    public function testInputMediaPhotoThrowsOnLongCaption(): void
    {
        $this->expectException(\RuntimeException::class);

        MediaBuilder::inputMediaPhoto('https://example.com/p.jpg', str_repeat('a', 1025));
    }
}
