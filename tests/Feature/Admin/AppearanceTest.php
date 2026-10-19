<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Livewire\Admin\AppearanceEditor;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppearanceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->role(Role::Admin)->create();
    }

    public function test_without_changes_no_theme_is_injected(): void
    {
        $this->get(route('home'))->assertOk()->assertDontSee('tema-del-sitio', false);
    }

    public function test_admin_changes_colors_and_fonts_and_the_site_uses_them(): void
    {
        Livewire::actingAs($this->admin())->test(AppearanceEditor::class)
            ->set('form.color_primary', '#1E3A5F')
            ->set('form.color_accent', '#f39c12')
            ->set('form.font_body', 'roboto')
            ->set('form.font_heading', 'playfair-display')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.appearance'));

        $site = SiteSetting::current();
        $this->assertSame('#1e3a5f', $site->color_primary);
        $this->assertNull($site->color_button); // vacío = diseño

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('--color-primary-deep:#1e3a5f', false)
            ->assertSee('--color-accent-light:#f39c12', false)
            ->assertSee("--font-sans:'Roboto Variable'", false)
            ->assertSee("--font-display:'Playfair Display Variable'", false)
            ->assertDontSee('--color-brand-strong', false);

        // También el panel usa el tema.
        $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertSee('--color-primary-deep:#1e3a5f', false);
    }

    public function test_gradient_end_color_is_editable_and_used_by_highlighted_cards(): void
    {
        Livewire::actingAs($this->admin())->test(AppearanceEditor::class)
            ->set('form.color_gradient_end', '#1E3A5F')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('#1e3a5f', SiteSetting::current()->color_gradient_end);
        $this->get(route('home'))->assertOk()->assertSee('--color-gradient-end:#1e3a5f', false);

        // Lleva texto blanco encima: un tono claro se rechaza.
        Livewire::actingAs($this->admin())->test(AppearanceEditor::class)
            ->set('form.color_gradient_end', '#f3ebde')
            ->call('save')
            ->assertHasErrors('form.color_gradient_end');
    }

    public function test_highlighted_cards_use_the_theme_gradient_instead_of_fixed_colors(): void
    {
        $views = collect(['pages/home', 'pages/partials/about-blocks', 'events/hub', 'events/modules/donations'])
            ->map(fn ($v) => file_get_contents(resource_path("views/{$v}.blade.php")))
            ->implode('');

        $this->assertStringNotContainsString('#5b3b26', $views);
        $this->assertStringContainsString('to-gradient-end', $views);
    }

    public function test_colors_too_light_for_white_text_are_rejected(): void
    {
        Livewire::actingAs($this->admin())->test(AppearanceEditor::class)
            ->set('form.color_primary', '#f5f5f5')
            ->set('form.color_button', '#f39c12')
            ->call('save')
            ->assertHasErrors(['form.color_primary', 'form.color_button']);

        $this->assertNull(SiteSetting::current()->color_primary);
    }

    public function test_invalid_values_cannot_inject_css_or_unknown_fonts(): void
    {
        Livewire::actingAs($this->admin())->test(AppearanceEditor::class)
            ->set('form.color_accent', 'red;}body{display:none')
            ->set('form.font_body', 'comic-sans')
            ->call('save')
            ->assertHasErrors(['form.color_accent', 'form.font_body']);

        // Aunque un valor raro llegara a la base de datos, no se emite.
        $site = new SiteSetting(['color_accent' => 'red;}body{x', 'font_body' => 'nope']);
        $this->assertSame('', Theme::css($site));
    }

    public function test_restore_defaults_clears_every_field(): void
    {
        $setting = SiteSetting::query()->first() ?? new SiteSetting(SiteSetting::defaults());
        $setting->fill(['color_primary' => '#1e3a5f', 'font_body' => 'roboto'])->save();

        Livewire::actingAs($this->admin())->test(AppearanceEditor::class)
            ->assertSet('form.color_primary', '#1e3a5f')
            ->call('restoreDefaults')
            ->call('save');

        $this->assertNull(SiteSetting::current()->color_primary);
        $this->assertNull(SiteSetting::current()->font_body);
        $this->get(route('home'))->assertDontSee('tema-del-sitio', false);
    }

    public function test_only_content_roles_can_edit_the_appearance(): void
    {
        $this->actingAs(User::factory()->role(Role::Cashier)->create())->get(route('admin.appearance'))->assertForbidden();
        $this->actingAs(User::factory()->role(Role::Editor)->create())->get(route('admin.appearance'))->assertOk()->assertSee('Vista previa');
    }

    public function test_contrast_is_computed_like_wcag(): void
    {
        $this->assertSame(21.0, Theme::contrastWithWhite('#000000'));
        $this->assertSame(1.0, Theme::contrastWithWhite('#ffffff'));
        $this->assertGreaterThanOrEqual(4.5, Theme::contrastWithWhite(Theme::DEFAULT_COLORS['color_button']));
        $this->assertGreaterThanOrEqual(4.5, Theme::contrastWithWhite(Theme::DEFAULT_COLORS['color_primary']));
    }
}
