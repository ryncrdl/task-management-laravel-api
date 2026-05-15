<?php

namespace App\Mail;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Task $task,
        public readonly User $assignee,
        public readonly User $assigner,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Task Assigned: {$this->task->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlView: 'emails.task-assigned',
        );
    }
}
