<?php

namespace Talk2Nextcloud\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class DefaultUser implements UserInterface
{
    /**
     * @inheritDoc
     */
    public function getRoles(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials(): void
    {
        // Nothing to erase
    }

    /**
     * @inheritDoc
     */
    public function getUserIdentifier(): string
    {
        return 'default-user';
    }
}
