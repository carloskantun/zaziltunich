<?php
declare(strict_types=1);

namespace App\Domain;

/** Enlaces de contacto directo: WhatsApp, llamada, correo y SMS con mensaje prellenado. */
final class Contact
{
    public static function links(string $message): array
    {
        $out = [];
        $wa = preg_replace('/\D+/', '', Settings::get('contact_whatsapp'));
        $phone = preg_replace('/[^\d+]/', '', Settings::get('contact_phone'));
        $sms = preg_replace('/[^\d+]/', '', Settings::get('contact_sms')) ?: $phone;
        $mail = trim(Settings::get('contact_email'));
        $enc = rawurlencode($message);
        if ($wa) {
            $out[] = ['key' => 'whatsapp', 'href' => 'https://wa.me/' . $wa . '?text=' . $enc, 'label' => t('contact.whatsapp')];
        }
        if ($phone) {
            $out[] = ['key' => 'call', 'href' => 'tel:' . $phone, 'label' => t('contact.call')];
        }
        if ($mail) {
            $out[] = ['key' => 'email', 'href' => 'mailto:' . $mail . '?subject=' . rawurlencode($message), 'label' => t('contact.email')];
        }
        if ($sms) {
            $out[] = ['key' => 'sms', 'href' => 'sms:' . $sms . '?&body=' . $enc, 'label' => t('contact.sms')];
        }
        return $out;
    }
}
