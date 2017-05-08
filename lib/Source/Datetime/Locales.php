<?php

namespace Magium\Configuration\Source\Datetime;

use Magium\Configuration\Source\SourceInterface;

class Locales implements SourceInterface
{

    public function getSourceData()
    {
        $allLocales = [];
        $locales = \ResourceBundle::getLocales('');
        foreach ($locales as $locale) {
            $allLocales[$locale] = \Locale::getDisplayName($locale);
        }
        return $allLocales;
    }

}
