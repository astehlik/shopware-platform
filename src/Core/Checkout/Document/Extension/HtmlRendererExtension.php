<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Document\Extension;

use Shopware\Core\Checkout\Document\Renderer\RenderedDocument;
use Shopware\Core\Framework\Extensions\Extension;
use Shopware\Core\Framework\Log\Package;

/**
 * @public this class is used as type-hint for all event listeners, so the class string is "public consumable" API
 *
 * @title Rendering of the HTML document
 *
 * @description This event allows manipulation of the input and output when rendering HTML documents.
 *
 * @codeCoverageIgnore
 *
 * @extends Extension<string>
 */
#[Package('checkout')]
final class HtmlRendererExtension extends Extension
{
    public const NAME = 'html-renderer';

    /**
     * @internal shopware owns the __constructor, but the properties are public API
     */
    public function __construct(public readonly RenderedDocument $document)
    {
    }
}
