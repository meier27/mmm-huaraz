<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Enlace para crear una contraseña nueva (jugadores de los juegos). */
class PlayerResetPassword extends Notification
{
    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('player.password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);

        return (new MailMessage)
            ->subject('Crea una contraseña nueva · Juegos bíblicos')
            ->greeting('¡Hola, '.$notifiable->nickname.'!')
            ->line('Recibimos una solicitud para cambiar la contraseña de tu cuenta de Juegos bíblicos.')
            ->action('Crear contraseña nueva', $url)
            ->line('El enlace vence en '.config('auth.passwords.players.expire').' minutos.')
            ->line('Si no lo pediste, no hagas nada: tu contraseña sigue igual.')
            ->salutation('Movimiento Misionero Mundial – Huaraz');
    }
}
