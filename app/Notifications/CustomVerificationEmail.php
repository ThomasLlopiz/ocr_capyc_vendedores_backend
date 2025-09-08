<?php
namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomVerificationEmail extends Notification
{
    public function toMail($notifiable)
    {
        // Generamos la URL de verificación en Laravel
        $url = url('/api/email/verify/' . $notifiable->getKey() . '/' . sha1($notifiable->getEmailForVerification()) .
            '?redirect=' . urlencode('http://127.0.0.1:8500/notificacion'));

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
