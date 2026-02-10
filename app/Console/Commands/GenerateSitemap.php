<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

final class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate the sitemap (static and public routes).';

    public function handle(): int
    {
        $base = mb_rtrim(config('app.url'), '/');

        $sitemap = Sitemap::create()
            ->add(Url::create($base)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)->setPriority(1.0))
            ->add(Url::create($base.'/contact')->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)->setPriority(0.8))
            ->add(Url::create($base.'/login')->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)->setPriority(0.5))
            ->add(Url::create($base.'/register')->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)->setPriority(0.5));

        if (Route::has('legal.terms')) {
            $sitemap->add(Url::create($base.'/legal/terms')->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY)->setPriority(0.3));
        }
        if (Route::has('legal.privacy')) {
            $sitemap->add(Url::create($base.'/legal/privacy')->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY)->setPriority(0.3));
        }
        if (Route::has('blog.index')) {
            $sitemap->add(Url::create($base.'/blog')->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)->setPriority(0.7));
        }
        if (Route::has('changelog.index')) {
            $sitemap->add(Url::create($base.'/changelog')->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)->setPriority(0.6));
        }
        if (Route::has('help.index')) {
            $sitemap->add(Url::create($base.'/help')->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)->setPriority(0.7));
        }

        $path = public_path('sitemap.xml');
        $sitemap->writeToFile($path);

        $this->info('Sitemap written to '.$path);

        return self::SUCCESS;
    }
}
