<?php
declare(strict_types=1);

namespace App\Services\Design;

/** Servis za slike je odbio sadržaj (npr. zaštićen lik), a ne zbog limita, ključa ili mreže. */
final class ContentRefusedException extends \RuntimeException
{
}
