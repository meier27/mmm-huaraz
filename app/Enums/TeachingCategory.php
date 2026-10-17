<?php

namespace App\Enums;

enum TeachingCategory: string
{
    case Sermon = 'sermon';
    case BibleStudy = 'bible_study';
    case Devotional = 'devotional';
    case Doctrine = 'doctrine';
    case Family = 'family';
    case Youth = 'youth';

    public function label(): string
    {
        return match ($this) {
            self::Sermon => 'Predicación',
            self::BibleStudy => 'Estudio bíblico',
            self::Devotional => 'Devocional',
            self::Doctrine => 'Doctrina',
            self::Family => 'Familia',
            self::Youth => 'Jóvenes',
        };
    }
}
