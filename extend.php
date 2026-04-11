<?php

/*
 * This file is part of dashzeveg/flarum-meta-tags.
 *
 * Copyright (c) 2026 Dashzeveg Galbadrakh.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Dashzeveg\Metatags;

use Flarum\Extend;
use Flarum\Frontend\Document;
use Flarum\Discussion\Discussion;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;

return [
    (new Extend\Frontend('forum'))
        // ->js(__DIR__.'/js/dist/forum.js')
        // ->css(__DIR__.'/less/forum.less')
        ->content(function (Document $document, ServerRequestInterface $request) {
            $settings = resolve(SettingsRepositoryInterface::class);
            $url = resolve(UrlGenerator::class);
            $baseUrl = $url->to('forum')->base();

            // Default fallback image
            $defaultImage = $settings->get('logo_url') ?: $baseUrl . '/assets/forex.mn.png';

            $ogImage = $defaultImage;
            $ogTitle = $settings->get('forum_title', 'Forum');
            $ogDescription = $settings->get('forum_description', '');
            $ogUrl = (string) $request->getUri();
            $ogType = 'website';

            // Get the current path
            $path = $request->getUri()->getPath();

            // Discussion pages
            if (preg_match('#/d/(\d+)#', $path, $matches)) {
                $discussionId = (int) $matches[1];

                try {
                    $discussion = Discussion::find($discussionId);

                    if ($discussion) {
                        $ogTitle = $discussion->title;
                        $ogType = 'article';

                        $firstPost = $discussion->firstPost;

                        if ($firstPost) {
                            $content = $firstPost->content;

                            // Strip HTML tags
                            $cleanContent = strip_tags($content);

                            // Remove Markdown images: ![alt](url)
                            $cleanContent = preg_replace('/!\[[^\]]*\]\([^)]+\)/', '', $cleanContent);

                            // Remove Markdown links: [text](url) → keep text
                            $cleanContent = preg_replace('/\[([^\]]*)\]\([^)]+\)/', '$1', $cleanContent);

                            // Remove bare URLs
                            $cleanContent = preg_replace('#https?://\S+#', '', $cleanContent);

                            // Collapse extra whitespace
                            $cleanContent = trim(preg_replace('/\s+/', ' ', $cleanContent));

                            // Remove Markdown bold: **text** → text
                            $cleanContent = preg_replace('/\*\*([^*]+)\*\*/', '$1', $cleanContent);

                            $ogDescription = Str::limit($cleanContent, 200);

                            // Extract first image from HTML
                            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $imgMatches)) {
                                $ogImage = $imgMatches[1];

                                if (!Str::startsWith($ogImage, ['http://', 'https://'])) {
                                    $ogImage = $baseUrl . '/' . ltrim($ogImage, '/');
                                }
                            }

                            // Fallback: try Markdown image syntax
                            if ($ogImage === $defaultImage && preg_match('/!\[[^\]]*\]\(([^)]+)\)/', $content, $mdMatches)) {
                                $ogImage = $mdMatches[1];

                                if (!Str::startsWith($ogImage, ['http://', 'https://'])) {
                                    $ogImage = $baseUrl . '/' . ltrim($ogImage, '/');
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Fall back to defaults silently
                }
            }

            // Add all OG tags
            $document->head[] = '<meta property="og:type" content="' . e($ogType) . '" />';
            $document->head[] = '<meta property="og:url" content="' . e(urldecode($ogUrl)) . '" />';
            $document->head[] = '<meta property="og:title" content="' . e($ogTitle) . '" />';
            $document->head[] = '<meta property="og:description" content="' . e($ogDescription) . '" />';
            $document->head[] = '<meta property="og:image" content="' . e($ogImage) . '" />';


            $document->head[] = '<meta property="twitter:card" content="summary_large_image" />';
            $document->head[] = '<meta property="twitter:site" content="@forexmn" />';
            $document->head[] = '<meta property="twitter:title" content="' . e($ogTitle) . '" />';
            $document->head[] = '<meta property="twitter:description" content="' . e($ogDescription) . '" />';
            $document->head[] = '<meta property="twitter:image" content="' . e($ogImage) . '" />';


            $document->meta['description'] = e($ogDescription);
        }, 100),
    // (new Extend\Frontend('admin'))
    //     ->js(__DIR__.'/js/dist/admin.js')
    //     ->css(__DIR__.'/less/admin.less'),
    // new Extend\Locales(__DIR__.'/locale'),
];
