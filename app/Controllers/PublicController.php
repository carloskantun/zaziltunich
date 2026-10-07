<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{I18n, View};
use App\Domain\{Availability, BookingService, Catalog, Contact, Pricing, Selection, Settings};
use App\Payments\Gateways;

final class PublicController
{
    public function home(): void
    {
        View::render('public/home', ['title' => Settings::get('site_name', 'Zazil Tunich'), 'experiences' => Catalog::published()]);
    }

    public function list(): void
    {
        View::render('public/list', ['title' => t('list.title'), 'experiences' => Catalog::published()]);
    }

    public function experience(array $p): void
    {
        $exp = Catalog::bySlug($p['slug']);
        if (!$exp) {
            View::notFound();
        }
        $title = Catalog::text($exp, 'seo_title') ?: Catalog::text($exp, 'title');
        View::render('public/experience', [
            'title' => $title,
            'description' => Catalog::text($exp, 'seo_description') ?: Catalog::text($exp, 'subtitle'),
            'exp' => $exp,
            'scripts' => '<script src="' . e(asset('js/booking.js')) . '" defer></script>',
            'contact' => Contact::links(t('contact.msg', ['exp' => Catalog::text($exp, 'title'), 'date' => '____'])),
        ]);
    }

    /** Resumen y datos del cliente; todo se recalcula en el servidor. */
    public function checkout(): void
    {
        $exp = Catalog::load((int) ($_POST['exp'] ?? 0));
        if (!$exp || $exp['status'] !== 'published') {
            View::notFound();
        }
        $sel = Selection::fromInput($_POST);
        $q = Pricing::quote($exp, $sel);
        $err = $q['ok'] ? Availability::check($exp, $sel) : null;
        $errors = array_map(static fn ($e) => t($e['code'], $e['vars'] ?? []), $q['errors']);
        if ($err) {
            $errors[] = t($err);
        }
        View::render('public/checkout', [
            'title' => t('checkout.title'), 'exp' => $exp, 'sel' => $sel, 'q' => $q, 'errors' => $errors,
        ]);
    }

    public function confirm(): void
    {
        \App\Core\Auth::checkCsrf();
        $exp = Catalog::load((int) ($_POST['exp'] ?? 0));
        if (!$exp || $exp['status'] !== 'published') {
            View::notFound();
        }
        $sel = Selection::fromInput($_POST);
        $r = BookingService::create(
            $exp, $sel,
            ['name' => $_POST['name'] ?? '', 'email' => $_POST['email'] ?? '', 'phone' => $_POST['phone'] ?? ''],
            'web', I18n::lang(), trim((string) ($_POST['notes'] ?? '')) ?: null
        );
        if (!$r['ok']) {
            $errors = array_map(static fn ($e) => t($e['code'], $e['vars'] ?? []), $r['errors']);
            $q = Pricing::quote($exp, $sel);
            View::render('public/checkout', ['title' => t('checkout.title'), 'exp' => $exp, 'sel' => $sel, 'q' => $q, 'errors' => $errors]);
            return;
        }
        $booking = $r['booking'];
        redirect(Gateways::default()->start($booking, $booking['deposit_due']));
    }

    private function booking(string $code): array
    {
        $b = BookingService::findByCode($code);
        if (!$b) {
            View::notFound();
        }
        $exp = Catalog::load((int) $b['experience_id']);
        return [$b, $exp];
    }

    public function pay(array $p): void
    {
        [$b, $exp] = $this->booking($p['code']);
        View::render('public/pay', [
            'title' => t('pay.title'), 'b' => $b, 'exp' => $exp, 'extras' => BookingService::extras($b['id']),
            'instructions' => Settings::get('payment_instructions_' . I18n::lang()) ?: Settings::get('payment_instructions_es'),
            'contact' => Contact::links('Reserva ' . $b['code']),
        ]);
    }

    public function thanks(array $p): void
    {
        [$b, $exp] = $this->booking($p['code']);
        View::render('public/thanks', ['title' => t('thanks.title'), 'b' => $b, 'exp' => $exp]);
    }

    public function voucher(array $p): void
    {
        [$b, $exp] = $this->booking($p['code']);
        View::render('public/voucher', [
            'title' => $b['code'], 'b' => $b, 'exp' => $exp, 'extras' => BookingService::extras($b['id']),
        ], null);
    }
}
