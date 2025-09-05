<?php
namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class CustomVerificationEmail extends Notification
{
    public function toMail($notifiable)
    {
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addHours(48),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
        );

        // Reemplaza el puerto en la URL para redirigir a localhost:8500
        $url = str_replace('localhost:8000', 'localhost:8500', $url);

        return (new MailMessage)
            ->subject('Verifica tu dirección de correo electrónico')
            ->greeting('¡Hola!')
            ->line('Gracias por registrarte en OCR_Capyc_Vendedores. Por favor, haz clic en el botón de abajo para verificar tu dirección de correo electrónico.')
            ->action('Verificar Correo Electrónico', $url)
            ->line('Si no creaste una cuenta, no es necesario que hagas nada.')
            ->salutation('Saludos, Equipo OCR_Capyc_Vendedores');
    }

    public function via($notifiable)
    {
        return ['mail'];
    }
}
