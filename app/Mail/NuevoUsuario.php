<?php
namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NuevoUsuario extends Mailable
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $username,
        public readonly string $password,
        public readonly array  $apps = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Bienvenido al sistema — IES Pacífico');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credenciales',
            with: [
                'nombre'   => $this->nombre,
                'username' => $this->username,
                'password' => $this->password,
                'apps'     => $this->apps,
            ]
        );
    }
}
