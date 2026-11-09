<?php

namespace App\Traits;

use Throwable;

trait HasDeleteButton
{
    public bool $confirmingDeletion = false;

    abstract protected function authorizeDeletion(): void;

    /** @throws Throwable */
    abstract protected function performDeletion(): void;

    final public function confirmDelete(): void
    {
        $this->authorizeDeletion();
        $this->confirmingDeletion = true;
    }

    /** @throws Throwable */
    final public function delete(): void
    {
        $this->authorizeDeletion();
        $this->performDeletion();
        $this->confirmingDeletion = false;
    }
}
