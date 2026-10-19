<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPasswordNotification extends ResetPassword
{
    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // Gera a URL para a sua rota de reset no Livewire
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        // Pega o tempo de expiração do config/auth.php (padrão 60)
        $count = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        $corpoHtml = "
            <h2 style='color: #461a63; margin-bottom: 16px; font-size: 20px; font-weight: 800;'>Olá!</h2>
            <p>Você está recebendo este e-mail porque solicitou a redefinição de senha para a sua conta no Instituto Percorre.</p>
            
            <table width='100%' border='0' cellspacing='0' cellpadding='0' style='margin: 24px 0;'>
                <tr>
                    <td align='center'>
                        <a href='{$url}' style='background-color: #9333ea; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; font-size: 15px;'>
                            Redefinir Minha Senha
                        </a>
                    </td>
                </tr>
            </table>

            <p>Este link de redefinição de senha irá expirar em {$count} minutos.</p>
            <p>Se você não solicitou uma redefinição de senha, nenhuma ação é necessária e sua conta continua segura.</p>
            
            <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;'>
            
            <p style='font-size: 12px; color: #6b7280;'>
                Se estiver com problemas para clicar no botão \"Redefinir Minha Senha\", copie e cole a URL abaixo no seu navegador web:<br>
                <a href='{$url}' style='color: #7c3aed; word-break: break-all;'>{$url}</a>
            </p>
        ";

        return (new MailMessage)
            ->subject('Redefinição de Senha - Instituto Percorre')
            ->view('emails.layouts.padrao', [
                'assunto'        => 'Redefinição de Senha',
                'corpoHtml'      => $corpoHtml,
                'headerImageUrl' => file_exists(public_path('images/logo-email.png')) ? asset('images/logo-email.png') : null,
                'pixelUrl'       => null, // E-mail transacional de segurança não usa pixel
            ]);
    }
}