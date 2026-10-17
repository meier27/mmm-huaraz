<?php

namespace App\Livewire\Forms;

class SocialLinksForm extends ContentForm
{
    /**
     * Redes que se muestran en la cabecera, el menú y el pie (en este orden),
     * con el dominio que debe tener cada enlace.
     */
    public const NETWORKS = [
        'facebook_url' => ['label' => 'Facebook', 'icon' => 'facebook', 'domains' => 'facebook\.com|fb\.com|fb\.me', 'example' => 'https://www.facebook.com/tu-pagina'],
        'instagram_url' => ['label' => 'Instagram', 'icon' => 'instagram', 'domains' => 'instagram\.com', 'example' => 'https://www.instagram.com/tu-cuenta'],
        'youtube_url' => ['label' => 'YouTube', 'icon' => 'youtube', 'domains' => 'youtube\.com|youtu\.be', 'example' => 'https://www.youtube.com/@tu-canal'],
        'tiktok_url' => ['label' => 'TikTok', 'icon' => 'tiktok', 'domains' => 'tiktok\.com', 'example' => 'https://www.tiktok.com/@tu-cuenta'],
    ];

    public ?string $facebook_url = '';

    public ?string $instagram_url = '';

    public ?string $youtube_url = '';

    public ?string $tiktok_url = '';

    public ?string $contact_whatsapp = '';

    protected function rules(): array
    {
        $rules = [
            'contact_whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\s()-]+$/'],
        ];

        foreach (self::NETWORKS as $field => $network) {
            // https y del dominio de la red (evita pegar por error el enlace de otra).
            $rules[$field] = ['nullable', 'url:https', 'max:500', 'regex:#^https://([a-z0-9-]+\.)*('.$network['domains'].')(/|$)#i'];
        }

        return $rules;
    }

    protected function messages(): array
    {
        $messages = ['contact_whatsapp.regex' => 'Escribe solo el número, con el código de país (p. ej. +51 987 654 321).'];

        foreach (self::NETWORKS as $field => $network) {
            $messages["{$field}.regex"] = "Debe ser un enlace de {$network['label']}, p. ej. {$network['example']}.";
            $messages["{$field}.url"] = "Debe ser un enlace completo que empiece por https://, p. ej. {$network['example']}.";
        }

        return $messages;
    }
}
