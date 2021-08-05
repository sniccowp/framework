<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Mail;

    use Snicco\Mail\Mailable;
    use Snicco\Support\WP;
    use WP_User;

    class MagicLinkLoginMail extends Mailable
    {

        public WP_User $user;
        public string  $site_name;
        public string $magic_link;
        // public int $expires;
        public int $expiration;

        public function __construct(WP_User $user, string $magic_link, int $expiration)
        {
            $this->magic_link = $magic_link;
            $this->expiration = $expiration;
            $this->user = $user;
            $this->site_name = WP::siteName();
        }

        public function unique() : bool
        {

            return true;
        }

        public function build() : MagicLinkLoginMail
        {

            return $this
                ->subject(sprintf(__('[%s] Login Link'), WP::siteName()))
                ->view('magic-link-login-email');

        }

    }