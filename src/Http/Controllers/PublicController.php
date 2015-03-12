<?php
namespace TypiCMS\Modules\Sitemap\Http\Controllers;

use App;
use App\Http\Controllers\Controller;
use Config;
use Route;
use URL;

class PublicController extends Controller
{
    private $modules = array();

    public function __construct()
    {
        $this->modules = Config::get('sitemap.modules');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function generate()
    {
        // create new sitemap object
        $sitemap = app('sitemap');

        // set cache (key (string), duration in minutes (Carbon|Datetime|int), turn on/off (boolean))
        // by default cache is disabled
        if (Config::get('app.cache')) {
            $sitemap->setCache('laravel.sitemap', 3600);
        }

        // check if there is cached sitemap and build new only if is not
        if (! $sitemap->isCached()) {

            foreach (Config::get('translatable.locales') as $locale) {

                App::setLocale($locale);

                foreach ($this->modules as $module) {

                    if (! class_exists($module)) {
                        continue;
                    }

                    $items = $module::all();

                    foreach ($items as $item) {
                        if ($module == 'Pages') {
                            $url = URL::to($item->uri);
                        } else {
                            if (Route::has($locale . '.' . $item->getTable() . '.categories.slug')) {
                                // Module with category
                                $url = route(
                                    $locale . '.' . $item->getTable() . '.categories.slug',
                                    [$item->category->slug, $item->slug]
                                );
                            } else {
                                 // Module without category
                                $url = route($locale . '.' . $item->getTable() . '.slug', $item->slug);
                            }
                        }
                        $sitemap->add($url, $item->updated_at);
                    }

                }

            }

        }

        // show your sitemap (options: 'xml' (default), 'html', 'txt', 'ror-rss', 'ror-rdf')
        return $sitemap->render('xml');

    }
}
