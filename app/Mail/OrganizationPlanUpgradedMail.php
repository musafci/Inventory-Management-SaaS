<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrganizationPlanUpgradedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public OrganizationSubscription $subscription,
        public ?User $owner,
        public ?string $previousPlanSlug,
        public ?string $previousStatus,
    ) {}

    public function envelope(): Envelope
    {
        $planName = $this->subscription->plan?->name ?? ucfirst($this->organization->plan);

        return new Envelope(
            subject: 'Organization plan upgraded: '.$this->organization->name.' → '.$planName,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.organization-plan-upgraded',
        );
    }
}
