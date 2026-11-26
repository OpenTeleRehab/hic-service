<?php

namespace App\Helpers;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Sanitizes rich text (TinyMCE) HTML so it can be stored and rendered safely.
 *
 * Removes scripts, event handler attributes (onclick, onerror, ...), javascript: URLs
 * and any element/attribute that is not on HTMLPurifier's safe allow-list, while keeping
 * the formatting TinyMCE produces (headings, lists, tables, images, links, inline styles).
 *
 * @package App\Helpers
 */
class HtmlSanitizer
{
    /**
     * Bump when the HTML definition below changes so the serialized cache is rebuilt.
     */
    private const DEFINITION_REV = 2;

    private static ?HTMLPurifier $purifier = null;

    /**
     * @param string|null $html
     *
     * @return string|null
     */
    public static function clean(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        return self::purifier()->purify($html);
    }

    /**
     * @return \HTMLPurifier
     */
    private static function purifier(): HTMLPurifier
    {
        if (self::$purifier) {
            return self::$purifier;
        }

        $cachePath = storage_path('framework/cache/htmlpurifier');
        if (!is_dir($cachePath)) {
            @mkdir($cachePath, 0755, true);
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', $cachePath);
        $config->set('Core.Encoding', 'UTF-8');
        // Layout-only CSS such as display/visibility/overflow, e.g. "display: block" that TinyMCE
        // uses to center images. These change layout but cannot run script.
        $config->set('CSS.AllowTricky', true);
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
            'tel' => true,
            // HTMLPurifier only accepts image/png, image/gif and image/jpeg data URIs.
            'data' => true,
        ]);
        // Keep id attributes so TinyMCE anchors (<a id="...">) still work as #fragment link targets.
        $config->set('Attr.EnableID', true);
        $config->set('Attr.AllowedFrameTargets', ['_blank', '_self']);
        $config->set('HTML.TargetNoopener', true);
        $config->set('HTML.TargetNoreferrer', true);
        // Allow embedded videos from trusted providers (TinyMCE media plugin).
        $config->set('HTML.SafeIframe', true);
        $config->set('URI.SafeIframeRegexp', '%^https://(www\.youtube(-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%');
        $config->set('HTML.DefinitionID', 'tinymce-content');
        $config->set('HTML.DefinitionRev', self::DEFINITION_REV);

        if ($def = $config->maybeGetRawHTMLDefinition()) {
            // HTML5 elements TinyMCE emits for captioned images.
            $def->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
            $def->addElement('figcaption', 'Inline', 'Flow', 'Common');
            $def->addAttribute('iframe', 'allowfullscreen', 'Bool');
        }

        return self::$purifier = new HTMLPurifier($config);
    }
}
