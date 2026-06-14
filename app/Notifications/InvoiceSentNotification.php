<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class InvoiceSentNotification extends Notification
{
    use Queueable;

    protected $invoice;
    protected $pdfPath;

    public function __construct(Invoice $invoice, string $pdfPath)
    {
        $this->invoice = $invoice;
        $this->pdfPath = $pdfPath;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $tenant = $this->invoice->tenant;
        $settings = $tenant->settings['accounting'] ?? [];
        
        $fromEmail = $settings['send_invoice_from'] ?? config('mail.from.address');
        $fromName = $tenant->name;

        $portalUrl = route('portal.invoice.show', [
            'token' => $this->invoice->portal_token
        ]);

        return (new MailMessage)
            ->from($fromEmail, $fromName)
            ->subject("Invoice {$this->invoice->invoice_number} from {$fromName}")
            ->greeting("Hello {$this->invoice->contact->name},")
            ->line("Please find attached invoice {$this->invoice->invoice_number}.")
            ->line("**Amount Due:** {$this->invoice->currency} " . number_format($this->invoice->balance_due, 2))
            ->line("**Due Date:** " . $this->invoice->due_date->format('F j, Y'))
            ->action('View Invoice Online', $portalUrl)
            ->line($settings['invoice_terms'] ?? 'Thank you for your business!')
            ->attach(Storage::path($this->pdfPath), [
                'as' => "Invoice-{$this->invoice->invoice_number}.pdf",
                'mime' => 'application/pdf',
            ]);
    }
}
