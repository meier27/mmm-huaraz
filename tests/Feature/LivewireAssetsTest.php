<?php

namespace Tests\Feature;

use App\Support\LivewireAssets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class LivewireAssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_paths_are_unchanged_at_the_domain_root(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('src="/livewire/livewire.js', $html);
        $this->assertStringContainsString('data-update-uri="/livewire/update"', $html);
    }

    public function test_paths_include_the_base_when_installed_in_a_subdirectory(): void
    {
        // Como XAMPP: http://localhost/mmm-huaraz/public/
        $request = Request::create('http://localhost/mmm-huaraz/public/', 'GET', server: [
            'SCRIPT_NAME' => '/mmm-huaraz/public/index.php',
            'SCRIPT_FILENAME' => public_path('index.php'),
        ]);
        $this->app->instance('request', $request);

        $html = (string) LivewireAssets::scripts();

        $this->assertStringContainsString('src="/mmm-huaraz/public/livewire/livewire.js', $html);
        $this->assertStringContainsString('data-update-uri="/mmm-huaraz/public/livewire/update"', $html);
    }

    public function test_built_css_references_fonts_relatively(): void
    {
        $css = collect(glob(public_path('build/assets/*.css')))->map(fn ($f) => file_get_contents($f))->join('');

        if ($css === '') {
            $this->markTestSkipped('Sin assets compilados.');
        }

        $this->assertStringNotContainsString('url(/build/', $css);
        // Una compilación fallida dejó una vez rutas del disco ("../C:/xampp/...node_modules").
        $this->assertDoesNotMatchRegularExpression('#url\([^)]*([A-Za-z]:/|node_modules)#', $css, 'El CSS apunta a archivos del disco: vuelve a compilar.');
    }
}
