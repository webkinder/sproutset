<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Managers;

final class TextDomainManager
{
    private const TEXT_DOMAIN = 'webkinder-sproutset';

    public function registerTextDomain(): void
    {
        add_action('init', $this->loadTextDomain(...));
    }

    private function loadTextDomain(): void
    {
        $locale = get_locale();
        $moFilePath = $this->buildMoFilePath($locale);

        if (! file_exists($moFilePath)) {
            return;
        }

        load_textdomain(self::TEXT_DOMAIN, $moFilePath);
    }

    private function buildMoFilePath(string $locale): string
    {
        $filename = sprintf('%s-%s.mo', self::TEXT_DOMAIN, $locale);

        return dirname(__DIR__, 2).'/languages/'.$filename;
    }
}
